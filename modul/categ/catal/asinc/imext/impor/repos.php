<?php

/* ============================================================
   Ruta: modul/categ/catal/asinc/imext/impor/repos.php
   Módulo: Catálogo / ASINC / IMEXT / IMPORT
   Función: Persistir importaciones integrales por lote y conservar
   la operación individual cuando el archivo mezcla IDs explícitos y vacíos.

   IDENTIDAD DE LA FILA EXCEL:
   - MARCA + MOTOR + MODEL + DESCR + CODIG + ORIGI
   - MEDID + SUPER + EQUIV + ANIO

   La identidad de PRODU se resuelve mediante PRODU + VEHIC.
   MOTOR, MODEL y ANIO permanecen en VEHIC; no se duplican en PRODU.

   DISTRIBUCIÓN:
   - VEHIC  → marca, motor, model, anio
   - PRODU  → descr, codig, origi, medid, super, equiv
   - PRODU  → pcomp, pvent, fecha
   - INVENTARIO → qty, en el almacén principal activo de la tienda
   - EQUIV  → relación PRODU ↔ VEHIC
============================================================ */

declare(strict_types=1);

final class ImpoRepo
{
    /* ========================================================
       NIVEL 1: Dependencias
       Recibe las conexiones de CATAL e INVENTARIO.
    ======================================================== */

    public function __construct(
        private PDO $db,
        private PDO $inve
    ) {
    }

    /* ========================================================
       NIVEL 1: Guardar
       Persiste una fila completa del proceso de importación.
    ======================================================== */


    /* ========================================================
       NIVEL 1: GUARDAR LOTE
       El trabajo de lectura se mantiene por lotes, pero la
       persistencia se ejecuta mediante operaciones agrupadas.
       El camino individual se conserva como recuperación ante
       un error SQL, para mantener el aislamiento de errores por fila.
    ======================================================== */

    public function guardarLote(
        string $modo,
        array $items,
        int $almac,
        int $tiend
    ): array {
        if ($items === []) {
            return ['errores' => []];
        }

        $tienenId = 0;
        $sinId = 0;

        foreach ($items as $registro) {
            $id = (int) ($registro['datos']['id'] ?? 0);
            if ($id > 0) {
                $tienenId++;
            } else {
                $sinId++;
            }
        }

        /*
         * Los archivos con ID mixtos pueden actualizar una identidad
         * antes de que otra fila la busque. Se conservan en el camino
         * secuencial existente para no cambiar esa semántica de orden.
         */
        if ($tienenId > 0 && $sinId > 0) {
            return $this->guardarLoteIndividual($modo, $items, $almac);
        }

        $inveTx = false;
        $catTx = false;

        try {
            $this->db->beginTransaction();
            $catTx = true;
            $this->inve->beginTransaction();
            $inveTx = true;

            $datos = [];
            $vehDefs = [];
            $ids = [];

            foreach ($items as $idx => $registro) {
                $f = $registro['datos'];
                $veh = $this->vehInfoLote($f);
                $vk = $this->vehKeyLote($veh);

                $datos[$idx] = [
                    'f' => $f,
                    'veh' => $veh,
                    'vk' => $vk,
                    'pk' => $this->prodKeyLote($vk, $f),
                ];

                $vehDefs[$vk] = $veh;

                $id = (int) ($f['id'] ?? 0);
                if ($id > 0) {
                    $ids[$id] = $id;
                }
            }

            /* Buscar aplicaciones vehiculares con una consulta agrupada. */
            $vehMap = $this->consultarVehiculosLote($vehDefs);

            /* Comprobar los IDs explícitos en grupos, no fila por fila. */
            $idExiste = $this->consultarProductosIdLote(array_values($ids));

            /* Buscar producto + vehículo para filas sin ID. */
            $busca = [];
            if ($sinId > 0) {
                foreach ($datos as $d) {
                    if ((int) ($d['f']['id'] ?? 0) > 0) {
                        continue;
                    }
                    $vid = (int) ($vehMap[$d['vk']] ?? 0);
                    if ($vid < 1) {
                        continue;
                    }
                    $busca[$d['pk']] = [
                        'vehic' => $vid,
                        'f' => $d['f'],
                    ];
                }
            }
            $prodMap = $this->consultarProductosLote($busca);

            $errores = [];
            $planes = [];
            $nuevos = [];
            $vistos = [];

            foreach ($datos as $idx => $d) {
                $f = $d['f'];
                $vk = $d['vk'];
                $pk = $d['pk'];
                $id = (int) ($f['id'] ?? 0);

                if ($id > 0) {
                    if (!isset($idExiste[$id])) {
                        $errores[$idx] = "El producto {$id} no existe.";
                        continue;
                    }

                    $planes[$idx] = [
                        'vk' => $vk,
                        'id' => $id,
                        'nuevo' => null,
                        'actualizar' => true,
                    ];
                    continue;
                }

                $existente = (int) ($prodMap[$pk] ?? 0);
                if ($existente > 0) {
                    if ($modo === 'NUEVO') {
                        $errores[$idx] = 'El producto para esta aplicación ya existe.';
                        continue;
                    }

                    $planes[$idx] = [
                        'vk' => $vk,
                        'id' => $existente,
                        'nuevo' => null,
                        'actualizar' => true,
                    ];
                    continue;
                }

                if ($modo === 'ACTUAL') {
                    $errores[$idx] = 'El producto para esta aplicación no existe.';
                    continue;
                }

                if (isset($vistos[$pk])) {
                    if ($modo === 'NUEVO') {
                        $errores[$idx] = 'El producto para esta aplicación ya existe.';
                        continue;
                    }

                    $nuevos[$pk]['ultimo'] = $f;
                    $planes[$idx] = [
                        'vk' => $vk,
                        'id' => null,
                        'nuevo' => $pk,
                        'actualizar' => false,
                    ];
                    continue;
                }

                $vistos[$pk] = true;
                $nuevos[$pk] = ['ultimo' => $f];
                $planes[$idx] = [
                    'vk' => $vk,
                    'id' => null,
                    'nuevo' => $pk,
                    'actualizar' => false,
                ];
            }

            if ($planes === []) {
                $this->inve->rollBack();
                $inveTx = false;
                $this->db->rollBack();
                $catTx = false;
                return ['errores' => $errores];
            }

            /* Solo se crean/actualizan vehículos usados por filas válidas. */
            $vehUsados = [];
            foreach ($planes as $idx => $plan) {
                $vehUsados[$plan['vk']] = $datos[$idx]['veh'];
            }

            $this->guardarVehiculosLote($vehUsados, $vehMap);
            $vehMap = array_replace(
                $vehMap,
                $this->consultarVehiculosLote($vehUsados)
            );

            /* Insertar productos nuevos en grupos multi-fila. */
            $nuevoId = $this->insertarProductosLote($nuevos);

            /* Agrupar las actualizaciones: el último dato de cada ID prevalece. */
            $actualizar = [];
            foreach ($planes as $idx => $plan) {
                if ($plan['nuevo'] !== null) {
                    $planes[$idx]['id'] = (int) ($nuevoId[$plan['nuevo']] ?? 0);
                    continue;
                }
                if ($plan['actualizar']) {
                    $actualizar[(int) $plan['id']] = $datos[$idx]['f'];
                }
            }

            $this->actualizarProductosLote($actualizar);

            /* Agrupar relaciones y stock; para stock prevalece la última fila. */
            $pares = [];
            $stock = [];
            foreach ($planes as $idx => $plan) {
                $pid = (int) ($plan['id'] ?? 0);
                $vid = (int) ($vehMap[$plan['vk']] ?? 0);

                if ($pid < 1 || $vid < 1) {
                    throw new RuntimeException(
                        'No se pudo resolver el ID de producto o vehículo del lote.'
                    );
                }

                $pares[$pid . ':' . $vid] = [
                    'produ' => $pid,
                    'vehic' => $vid,
                ];
                $stock[$pid] = (float) $datos[$idx]['f']['qty'];
            }

            $this->guardarEquivLote(array_values($pares));
            $this->validarAlmacenLote($almac, $tiend);
            $this->guardarStockLote($stock, $almac);

        } catch (Throwable $e) {
            if ($inveTx && $this->inve->inTransaction()) {
                try { $this->inve->rollBack(); } catch (Throwable $ignorar) {}
            }
            if ($catTx && $this->db->inTransaction()) {
                try { $this->db->rollBack(); } catch (Throwable $ignorar) {}
            }

            error_log('[IMPO_LOTE_SQL] ' . $e->getMessage());
            throw new RuntimeException(
                'No se pudo guardar el lote de importación: ' . $e->getMessage(),
                0,
                $e
            );
        }

        /* Los dos almacenes SQL usan conexiones PDO distintas. */
        try {
            if ($inveTx && $this->inve->inTransaction()) {
                $this->inve->commit();
                $inveTx = false;
            }
            if ($catTx && $this->db->inTransaction()) {
                $this->db->commit();
                $catTx = false;
            }
        } catch (Throwable $e) {
            if ($inveTx && $this->inve->inTransaction()) {
                try { $this->inve->rollBack(); } catch (Throwable $ignorar) {}
            }
            if ($catTx && $this->db->inTransaction()) {
                try { $this->db->rollBack(); } catch (Throwable $ignorar) {}
            }
            throw new RuntimeException(
                'No se pudo confirmar la transacción del lote: ' . $e->getMessage(),
                0,
                $e
            );
        }

        return ['errores' => $errores];
    }

    private function guardarLoteIndividual(
        string $modo,
        array $items,
        int $almac
    ): array {
        $errores = [];
        foreach ($items as $idx => $registro) {
            try {
                $this->guardar($modo, $registro['datos'], $almac);
            } catch (Throwable $e) {
                $errores[$idx] = $e->getMessage();
            }
        }
        return ['errores' => $errores];
    }

    private function claveTextoLote(mixed $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $texto = trim((string) $valor);
        if (class_exists('Normalizer')) {
            $normal = \Normalizer::normalize($texto, \Normalizer::FORM_D);
            if (is_string($normal)) {
                $texto = preg_replace('/\p{Mn}+/u', '', $normal) ?? $normal;
            }
        }

        return function_exists('mb_strtolower')
            ? mb_strtolower($texto, 'UTF-8')
            : strtolower($texto);
    }

    private function vehInfoLote(array $f): array
    {
        [$anio, $gdesd, $ghast] = $this->rango(
            (string) ($f['_anio_txt'] ?? ''),
            (int) $f['anio']
        );

        return [
            'marca' => trim((string) $f['marca']),
            'motor' => trim((string) $f['motor']),
            'model' => trim((string) $f['model']),
            'anio' => $anio,
            'gdesd' => $gdesd,
            'ghast' => $ghast,
        ];
    }

    private function vehKeyLote(array $v): string
    {
        return serialize([
            $this->claveTextoLote($v['marca']),
            $this->claveTextoLote($v['motor']),
            $this->claveTextoLote($v['model']),
            (int) $v['anio'],
            (int) $v['gdesd'],
            (int) $v['ghast'],
        ]);
    }

    private function prodKeyLote(string $vk, array $f): string
    {
        return serialize([
            $vk,
            $this->claveTextoLote($f['descr'] ?? null),
            $this->claveTextoLote($f['codig'] ?? null),
            $this->claveTextoLote($f['origi'] ?? null),
            $this->claveTextoLote($f['medid'] ?? null),
            $this->claveTextoLote($f['super'] ?? null),
            $this->claveTextoLote($f['equiv'] ?? null),
        ]);
    }

    private function consultarVehiculosLote(array $defs): array
    {
        $map = [];
        if ($defs === []) {
            return $map;
        }

        foreach (array_chunk($defs, 200, true) as $grupo) {
            $partes = [];
            $params = [];
            $claves = array_keys($grupo);
            $i = 0;

            foreach ($grupo as $v) {
                $partes[] = "SELECT {$i} AS pos,:m{$i} AS marca,:mo{$i} AS motor,"
                    . ":md{$i} AS model,:a{$i} AS anio,:gd{$i} AS gdesd,:gh{$i} AS ghast";
                $params[":m{$i}"] = $v['marca'];
                $params[":mo{$i}"] = $v['motor'];
                $params[":md{$i}"] = $v['model'];
                $params[":a{$i}"] = $v['anio'];
                $params[":gd{$i}"] = $v['gdesd'];
                $params[":gh{$i}"] = $v['ghast'];
                $i++;
            }

            $sql = 'SELECT d.pos, MIN(v.id) AS id FROM ('
                . implode(' UNION ALL ', $partes)
                . ') d LEFT JOIN vehic v'
                . ' ON v.marca=d.marca AND v.motor=d.motor AND v.model=d.model'
                . ' AND v.anio=d.anio AND v.gdesd=d.gdesd AND v.ghast=d.ghast'
                . ' GROUP BY d.pos';

            $st = $this->db->prepare($sql);
            $st->execute($params);

            foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $clave = $claves[(int) $r['pos']] ?? null;
                if ($clave !== null && $r['id'] !== null) {
                    $map[$clave] = (int) $r['id'];
                }
            }
        }

        return $map;
    }

    private function consultarProductosIdLote(array $ids): array
    {
        $map = [];
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        foreach (array_chunk($ids, 500) as $grupo) {
            if ($grupo === []) {
                continue;
            }
            $sql = 'SELECT id FROM produ WHERE id IN ('
                . implode(',', array_fill(0, count($grupo), '?'))
                . ')';
            $st = $this->db->prepare($sql);
            $st->execute($grupo);
            foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $id) {
                $map[(int) $id] = true;
            }
        }
        return $map;
    }

    private function consultarProductosLote(array $defs): array
    {
        $map = [];
        if ($defs === []) {
            return $map;
        }

        foreach (array_chunk($defs, 200, true) as $grupo) {
            $partes = [];
            $params = [];
            $claves = array_keys($grupo);
            $i = 0;

            foreach ($grupo as $d) {
                $f = $d['f'];
                $partes[] = "SELECT {$i} AS pos,:v{$i} AS vehic,:d{$i} AS descr,"
                    . ":c{$i} AS codig,:o{$i} AS origi,:m{$i} AS medid,"
                    . ":s{$i} AS super,:e{$i} AS equiv";
                $params[":v{$i}"] = (int) $d['vehic'];
                $params[":d{$i}"] = $f['descr'];
                $params[":c{$i}"] = $f['codig'];
                $params[":o{$i}"] = $f['origi'];
                $params[":m{$i}"] = $f['medid'];
                $params[":s{$i}"] = $f['super'];
                $params[":e{$i}"] = $f['equiv'];
                $i++;
            }

            /*
             * La identidad del artículo se busca con el índice compuesto
             * de PRODU y luego se exige su relación concreta en EQUIV.
             * Una sola consulta resuelve las identidades de todo el grupo.
             */
            $sql = 'SELECT d.pos, MIN(CASE WHEN c.produ IS NOT NULL THEN p.id ELSE NULL END) AS id '
                . 'FROM (' . implode(' UNION ALL ', $partes) . ') d '
                . 'LEFT JOIN produ p ON p.descr <=> d.descr '
                . 'AND p.codig <=> d.codig AND p.origi <=> d.origi '
                . 'AND p.medid <=> d.medid AND p.super <=> d.super '
                . 'AND p.equiv <=> d.equiv '
                . 'LEFT JOIN equiv c ON c.produ=p.id AND c.vehic=d.vehic '
                . 'GROUP BY d.pos';

            $st = $this->db->prepare($sql);
            $st->execute($params);

            foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $clave = $claves[(int) $r['pos']] ?? null;
                if ($clave !== null && $r['id'] !== null) {
                    $map[$clave] = (int) $r['id'];
                }
            }
        }

        return $map;
    }

    private function guardarVehiculosLote(array $defs, array $existentes): void
    {
        if ($defs === []) {
            return;
        }

        $actualizar = [];
        $insertar = [];
        foreach ($defs as $clave => $v) {
            $id = (int) ($existentes[$clave] ?? 0);
            if ($id > 0) {
                $actualizar[$id] = $v;
            } else {
                $insertar[$clave] = $v;
            }
        }

        /* Actualiza los vehículos existentes en grupos, sin hacer UPDATE por fila. */
        foreach (array_chunk($actualizar, 100, true) as $grupo) {
            $partes = [];
            $params = [];
            $i = 0;
            foreach ($grupo as $id => $v) {
                $partes[] = "SELECT :id{$i} AS id,:m{$i} AS marca,:mo{$i} AS motor,"
                    . ":md{$i} AS model,:a{$i} AS anio,:gd{$i} AS gdesd,:gh{$i} AS ghast";
                $params[":id{$i}"] = (int) $id;
                $params[":m{$i}"] = $v['marca'];
                $params[":mo{$i}"] = $v['motor'];
                $params[":md{$i}"] = $v['model'];
                $params[":a{$i}"] = $v['anio'];
                $params[":gd{$i}"] = $v['gdesd'];
                $params[":gh{$i}"] = $v['ghast'];
                $i++;
            }

            $sql = 'UPDATE vehic v INNER JOIN (' . implode(' UNION ALL ', $partes) . ') d ON d.id=v.id '
                . 'SET v.marca=d.marca,v.motor=d.motor,v.model=d.model,'
                . 'v.anio=d.anio,v.gdesd=d.gdesd,v.ghast=d.ghast';
            $st = $this->db->prepare($sql);
            $st->execute($params);
        }

        /* Los vehículos nuevos se insertan en bloque. No presupone una clave única inexistente. */
        foreach (array_chunk($insertar, 200, true) as $grupo) {
            if ($grupo === []) {
                continue;
            }
            $valores = [];
            $params = [];
            $i = 0;
            foreach ($grupo as $v) {
                $valores[] = "(:m{$i},:mo{$i},:md{$i},:a{$i},:gd{$i},:gh{$i})";
                $params[":m{$i}"] = $v['marca'];
                $params[":mo{$i}"] = $v['motor'];
                $params[":md{$i}"] = $v['model'];
                $params[":a{$i}"] = $v['anio'];
                $params[":gd{$i}"] = $v['gdesd'];
                $params[":gh{$i}"] = $v['ghast'];
                $i++;
            }
            $sql = 'INSERT INTO vehic (marca,motor,model,anio,gdesd,ghast) VALUES '
                . implode(',', $valores);
            $st = $this->db->prepare($sql);
            $st->execute($params);
        }
    }

    private function insertarProductosLote(array $grupos): array
    {
        $ids = [];
        if ($grupos === []) {
            return $ids;
        }

        foreach (array_chunk($grupos, 100, true) as $grupo) {
            $valores = [];
            $params = [];
            $marcadores = [];
            $i = 0;

            foreach ($grupo as $clave => $d) {
                $f = $d['ultimo'];
                $marca = '__IMPO_' . bin2hex(random_bytes(12)) . '_' . $i;
                $marcadores[$marca] = [
                    'clave' => $clave,
                    'descr' => $f['descr'],
                ];

                /* El marcador existe solo dentro de la transacción sin confirmar. */
                $valores[] = "(:d{$i},:c{$i},:o{$i},:m{$i},:s{$i},:e{$i},:pc{$i},:pv{$i},COALESCE(:fe{$i},NOW()))";
                $params[":d{$i}"] = $marca;
                $params[":c{$i}"] = $f['codig'];
                $params[":o{$i}"] = $f['origi'];
                $params[":m{$i}"] = $f['medid'];
                $params[":s{$i}"] = $f['super'];
                $params[":e{$i}"] = $f['equiv'];
                $params[":pc{$i}"] = $f['pcomp'];
                $params[":pv{$i}"] = $f['pvent'];
                $params[":fe{$i}"] = $f['fecha'];
                $i++;
            }

            $sql = 'INSERT INTO produ (descr,codig,origi,medid,super,equiv,pcomp,pvent,fecha) VALUES '
                . implode(',', $valores);
            $st = $this->db->prepare($sql);
            $st->execute($params);

            /* Recupera cada ID por su marcador real; no asume IDs consecutivos en TiDB. */
            $marcas = array_keys($marcadores);
            $consulta = 'SELECT id, descr FROM produ WHERE descr IN ('
                . implode(',', array_fill(0, count($marcas), '?')) . ')';
            $st = $this->db->prepare($consulta);
            $st->execute($marcas);
            $porMarca = [];
            foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $fila) {
                $porMarca[(string) $fila['descr']] = (int) $fila['id'];
            }

            $restaurar = [];
            foreach ($marcadores as $marca => $dato) {
                $id = (int) ($porMarca[$marca] ?? 0);
                if ($id < 1) {
                    throw new RuntimeException(
                        'No se pudo recuperar el ID generado para un producto del lote.'
                    );
                }
                $ids[$dato['clave']] = $id;
                $restaurar[$id] = $dato['descr'];
            }

            /* Restituye la descripción original antes de confirmar la transacción. */
            foreach (array_chunk($restaurar, 100, true) as $grupoRestaurar) {
                $partes = [];
                $params = [];
                $n = 0;
                foreach ($grupoRestaurar as $id => $descr) {
                    $partes[] = "SELECT :id{$n} AS id,:d{$n} AS descr";
                    $params[":id{$n}"] = (int) $id;
                    $params[":d{$n}"] = $descr;
                    $n++;
                }
                $sql = 'UPDATE produ p INNER JOIN (' . implode(' UNION ALL ', $partes) . ') d ON d.id=p.id '
                    . 'SET p.descr=d.descr';
                $st = $this->db->prepare($sql);
                $st->execute($params);
            }
        }

        return $ids;
    }

    private function actualizarProductosLote(array $actualizar): void
    {
        if ($actualizar === []) {
            return;
        }

        foreach (array_chunk($actualizar, 200, true) as $grupo) {
            $partes = [];
            $params = [];
            $i = 0;
            foreach ($grupo as $id => $f) {
                $partes[] = "SELECT :id{$i} AS id,:d{$i} AS descr,:c{$i} AS codig,"
                    . ":o{$i} AS origi,:m{$i} AS medid,:s{$i} AS super,:e{$i} AS equiv,"
                    . ":pc{$i} AS pcomp,:pv{$i} AS pvent,:fe{$i} AS fecha";
                $params[":id{$i}"] = (int) $id;
                $params[":d{$i}"] = $f['descr'];
                $params[":c{$i}"] = $f['codig'];
                $params[":o{$i}"] = $f['origi'];
                $params[":m{$i}"] = $f['medid'];
                $params[":s{$i}"] = $f['super'];
                $params[":e{$i}"] = $f['equiv'];
                $params[":pc{$i}"] = $f['pcomp'];
                $params[":pv{$i}"] = $f['pvent'];
                $params[":fe{$i}"] = $f['fecha'];
                $i++;
            }

            $sql = 'UPDATE produ p INNER JOIN (' . implode(' UNION ALL ', $partes) . ') v ON v.id=p.id '
                . 'SET p.descr=v.descr,p.codig=v.codig,p.origi=v.origi,p.medid=v.medid, '
                . 'p.super=v.super,p.equiv=v.equiv,p.pcomp=v.pcomp,p.pvent=v.pvent, '
                . 'p.fecha=COALESCE(v.fecha,NOW())';
            $st = $this->db->prepare($sql);
            $st->execute($params);
        }
    }

    private function guardarEquivLote(array $pares): void
    {
        foreach (array_chunk($pares, 400) as $grupo) {
            if ($grupo === []) {
                continue;
            }
            $valores = [];
            $params = [];
            foreach ($grupo as $i => $par) {
                $valores[] = "(:p{$i},:v{$i})";
                $params[":p{$i}"] = (int) $par['produ'];
                $params[":v{$i}"] = (int) $par['vehic'];
            }
            $st = $this->db->prepare(
                'INSERT IGNORE INTO equiv (produ,vehic) VALUES ' . implode(',', $valores)
            );
            $st->execute($params);
        }
    }

    private function validarAlmacenLote(int $almac, int $tiend): void
    {
        if ($almac < 1 || $tiend < 1) {
            throw new RuntimeException(
                'No existe un almacén principal válido para la tienda.'
            );
        }

        $st = $this->inve->prepare(
            'SELECT id
             FROM almac
             WHERE id = :id
               AND tiend = :tiend
               AND estad = 1
               AND prin = 1
             LIMIT 1
             FOR UPDATE'
        );
        $st->execute([
            ':id' => $almac,
            ':tiend' => $tiend,
        ]);

        if (!$st->fetchColumn()) {
            throw new RuntimeException(
                'El almacén principal activo de la tienda ya no está disponible.'
            );
        }
    }

    private function guardarStockLote(array $stock, int $almac): void
    {
        if ($almac < 1) {
            throw new RuntimeException('No existe un almacén principal válido para la tienda.');
        }

        foreach (array_chunk($stock, 400, true) as $grupo) {
            if ($grupo === []) {
                continue;
            }
            $valores = [];
            $params = [];
            $i = 0;
            foreach ($grupo as $produ => $qty) {
                $valores[] = "(:p{$i},:a{$i},:q{$i},0,0,0)";
                $params[":p{$i}"] = (int) $produ;
                $params[":a{$i}"] = $almac;
                $params[":q{$i}"] = $qty;
                $i++;
            }
            $sql = 'INSERT INTO stock (produ,almac,canti,salss,stmin,stmax) VALUES '
                . implode(',', $valores)
                . ' ON DUPLICATE KEY UPDATE canti=VALUES(canti)';
            $st = $this->inve->prepare($sql);
            $st->execute($params);
        }
    }


    public function guardar(
        string $modo,
        array $f,
        int $almac
    ): void {
        $this->db->beginTransaction();

        try {
            /* =================================================
               NIVEL 2: Vehículo
               Obtiene o crea la aplicación vehicular.
            ================================================= */

            $vehic = $this->vehic($f);

            /* =================================================
               NIVEL 2: Producto
               Obtiene o crea el producto asociado al vehículo.
            ================================================= */

            $produ = $this->produ($modo, $f, $vehic);

            /* =================================================
               NIVEL 2: Precio
               Actualiza precios y fecha del producto.
            ================================================= */

            $this->precio(
                $produ,
                (float) $f['pcomp'],
                (float) $f['pvent'],
                $f['fecha']
            );

            /* =================================================
               NIVEL 2: Equiv
               Relaciona producto y vehículo.
            ================================================= */

            $this->equiv($produ, $vehic);

            /* =================================================
               NIVEL 2: Stock
               Registra la cantidad en el almacén principal.
            ================================================= */

            $this->stock(
                $produ,
                (float) $f['qty'],
                $almac
            );

            $this->db->commit();

        } catch (Throwable $e) {

            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    /* ========================================================
       NIVEL 1: Vehículo
       Obtiene o crea un VEHIC manteniendo correctamente
       anio, gdesd y ghast.
    ======================================================== */

    private function vehic(array $f): int
    {
        /* =====================================================
           NIVEL 2: Normalizar rango
           Se calcula una sola representación lógica del ANIO.
        ===================================================== */

        $anioTxt = trim((string) ($f['_anio_txt'] ?? ''));

        [$anio, $gdesd, $ghast] = $this->rango(
            $anioTxt,
            (int) $f['anio']
        );

        if ($anio < 1) {
            throw new RuntimeException(
                'AÑO no válido para el vehículo.'
            );
        }

        /* =====================================================
           NIVEL 2: Buscar vehículo
           Se busca usando exactamente el rango normalizado.
        ===================================================== */

        $id = $this->buscarVehic($f);

        /* =====================================================
           NIVEL 2: Actualizar existente
           IMPORTANTE:
           Se conserva el rango real del Excel.
           No se reemplaza por anio-anio-anio.
        ===================================================== */

        if ($id > 0) {
            $st = $this->db->prepare(
                'UPDATE vehic
                 SET marca=:marca,
                     motor=:motor,
                     model=:model,
                     anio=:anio,
                     gdesd=:gdesd,
                     ghast=:ghast
                 WHERE id=:id'
            );

            $st->execute([
                ':id' => $id,
                ':marca' => $f['marca'],
                ':motor' => $f['motor'],
                ':model' => $f['model'],
                ':anio' => $anio,
                ':gdesd' => $gdesd,
                ':ghast' => $ghast
            ]);

            return $id;
        }

        /* =====================================================
           NIVEL 2: Crear vehículo
           Se guarda la misma representación normalizada.
        ===================================================== */

        $st = $this->db->prepare(
            'INSERT INTO vehic
                (marca,motor,model,anio,gdesd,ghast)
             VALUES
                (:marca,:motor,:model,:anio,:gdesd,:ghast)'
        );

        $st->execute([
            ':marca' => $f['marca'],
            ':motor' => $f['motor'],
            ':model' => $f['model'],
            ':anio' => $anio,
            ':gdesd' => $gdesd,
            ':ghast' => $ghast
        ]);

        return (int) $this->db->lastInsertId();
    }

    /* ========================================================
       NIVEL 1: Buscar vehículo
       Identifica el VEHIC por marca, motor, model y rango.
    ======================================================== */

    private function buscarVehic(array $f): int
    {
        /* =====================================================
           NIVEL 2: Normalizar año
        ===================================================== */

        [$anio, $gdesd, $ghast] = $this->rango(
            (string) ($f['_anio_txt'] ?? ''),
            (int) $f['anio']
        );

        /* =====================================================
           NIVEL 2: Consulta por identidad del vehículo.
           MARCA es obligatoria en el esquema actual.
        ===================================================== */

        $st = $this->db->prepare(
            'SELECT id
             FROM vehic
             WHERE marca = :marca
               AND motor = :motor
               AND model = :model
               AND anio = :anio
               AND gdesd = :gdesd
               AND ghast = :ghast
             LIMIT 1'
        );

        $st->execute([
            ':marca' => $f['marca'],
            ':motor' => $f['motor'],
            ':model' => $f['model'],
            ':anio' => $anio,
            ':gdesd' => $gdesd,
            ':ghast' => $ghast
        ]);

        return (int) ($st->fetchColumn() ?: 0);
    }

    /* ========================================================
       NIVEL 1: Producto
       Obtiene o crea PRODU para la aplicación VEHIC.
    ======================================================== */

    private function produ(
        string $modo,
        array $f,
        int $vehic
    ): int {
        /* =====================================================
           NIVEL 2: ID explícito
           Se utiliza cuando Excel trae ID.
        ===================================================== */

        $id = $f['id'] ? (int) $f['id'] : 0;

        if ($id > 0) {
            $st = $this->db->prepare(
                'SELECT id
                 FROM produ
                 WHERE id=:id
                 LIMIT 1'
            );

            $st->execute([
                ':id' => $id
            ]);

            $exis = (int) ($st->fetchColumn() ?: 0);

            if (!$exis && $modo === 'ACTUAL') {
                throw new RuntimeException(
                    "El producto {$id} no existe."
                );
            }
        }

        /* =====================================================
           NIVEL 2: Actualizar por ID
        ===================================================== */

        if ($id > 0) {
            $st = $this->db->prepare(
                'UPDATE produ
                 SET descr=:descr,
                     codig=:codig,
                     origi=:origi,
                     medid=:medid,
                     super=:super,
                     equiv=:equiv
                 WHERE id=:id'
            );

            $st->execute([
                ':id' => $id,
                ':descr' => $f['descr'],
                ':codig' => $f['codig'],
                ':origi' => $f['origi'],
                ':medid' => $f['medid'],
                ':super' => $f['super'],
                ':equiv' => $f['equiv']
            ]);

            return $id;
        }

        /* =====================================================
           NIVEL 2: Buscar producto por aplicación
        ===================================================== */

        $id = $this->buscarProdu($f, $vehic);

        /* =====================================================
           NIVEL 2: Control NUEVO
        ===================================================== */

        if ($id > 0 && $modo === 'NUEVO') {
            throw new RuntimeException(
                'El producto para esta aplicación ya existe.'
            );
        }

        /* =====================================================
           NIVEL 2: Actualizar encontrado
        ===================================================== */

        if ($id > 0) {
            $st = $this->db->prepare(
                'UPDATE produ
                 SET descr=:descr,
                     codig=:codig,
                     origi=:origi,
                     medid=:medid,
                     super=:super,
                     equiv=:equiv
                 WHERE id=:id'
            );

            $st->execute([
                ':id' => $id,
                ':descr' => $f['descr'],
                ':codig' => $f['codig'],
                ':origi' => $f['origi'],
                ':medid' => $f['medid'],
                ':super' => $f['super'],
                ':equiv' => $f['equiv']
            ]);

            return $id;
        }

        /* =====================================================
           NIVEL 2: Control ACTUAL
        ===================================================== */

        if ($modo === 'ACTUAL') {
            throw new RuntimeException(
                'El producto para esta aplicación no existe.'
            );
        }

        /* =====================================================
           NIVEL 2: Crear producto
        ===================================================== */

        $st = $this->db->prepare(
            'INSERT INTO produ
                (descr,codig,origi,medid,super,equiv)
             VALUES
                (:descr,:codig,:origi,:medid,:super,:equiv)'
        );

        $st->execute([
            ':descr' => $f['descr'],
            ':codig' => $f['codig'],
            ':origi' => $f['origi'],
            ':medid' => $f['medid'],
            ':super' => $f['super'],
            ':equiv' => $f['equiv']
        ]);

        return (int) $this->db->lastInsertId();
    }

    /* ========================================================
       NIVEL 1: Buscar producto
       La aplicación está determinada por PRODU + VEHIC.
    ======================================================== */

    private function buscarProdu(
        array $f,
        int $vehic
    ): int {
        /* =====================================================
           NIVEL 2: Validar vehículo
        ===================================================== */

        if ($vehic <= 0) {
            throw new RuntimeException(
                'No existe un vehículo válido para identificar la aplicación.'
            );
        }

        /* =====================================================
           NIVEL 2: Consulta
           Busca el producto únicamente dentro del VEHIC actual.
        ===================================================== */

        $st = $this->db->prepare(
            'SELECT p.id
             FROM produ p
             INNER JOIN equiv c
                ON c.produ = p.id
               AND c.vehic = :vehic
             WHERE p.descr <=> :descr
               AND p.codig <=> :codig
               AND p.origi <=> :origi
               AND p.medid <=> :medid
               AND p.super <=> :super
               AND p.equiv <=> :equiv
             ORDER BY p.id
             LIMIT 1'
        );

        $st->execute([
            ':vehic' => $vehic,
            ':descr' => $f['descr'],
            ':codig' => $f['codig'],
            ':origi' => $f['origi'],
            ':medid' => $f['medid'],
            ':super' => $f['super'],
            ':equiv' => $f['equiv']
        ]);

        return (int) ($st->fetchColumn() ?: 0);
    }

    /* ========================================================
       NIVEL 1: Precio
       Actualiza precio de compra, venta y fecha.
    ======================================================== */

    private function precio(
        int $produ,
        float $pcomp,
        float $pvent,
        ?string $fecha
    ): void {
        $st = $this->db->prepare(
            'UPDATE produ
             SET pcomp=:pcomp,
                 pvent=:pvent,
                 fecha=COALESCE(:fecha,NOW())
             WHERE id=:id'
        );

        $st->execute([
            ':id' => $produ,
            ':pcomp' => $pcomp,
            ':pvent' => $pvent,
            ':fecha' => $fecha
        ]);
    }

    /* ========================================================
       NIVEL 1: Equiv
       Relaciona PRODU con VEHIC.
    ======================================================== */

    private function equiv(
        int $produ,
        int $vehic
    ): void {
        $st = $this->db->prepare(
            'INSERT IGNORE INTO equiv (produ,vehic)
             VALUES (:produ,:vehic)'
        );

        $st->execute([
            ':produ' => $produ,
            ':vehic' => $vehic
        ]);
    }

    /* ========================================================
       NIVEL 1: Stock
       Actualiza la cantidad del producto en el almacén
       principal activo.
    ======================================================== */

    private function stock(
        int $produ,
        float $qty,
        int $almac
    ): void {
        /* =====================================================
           NIVEL 2: Validar almacén
        ===================================================== */

        if ($almac <= 0) {
            throw new RuntimeException(
                'No existe un almacén principal válido para la tienda.'
            );
        }

        /* =====================================================
           NIVEL 2: Verificar almacén principal activo
        ===================================================== */

        $st = $this->inve->prepare(
            'SELECT id
             FROM almac
             WHERE id = :id
               AND estad = 1
               AND prin = 1
             LIMIT 1'
        );

        $st->execute([
            ':id' => $almac
        ]);

        if (!$st->fetchColumn()) {
            throw new RuntimeException(
                'El almacén principal de la tienda no está disponible.'
            );
        }

        /* =====================================================
           NIVEL 2: Guardar stock
        ===================================================== */

        $st = $this->inve->prepare(
            'INSERT INTO stock
                (produ,almac,canti,salss,stmin,stmax)
             VALUES
                (:produ,:almac,:canti,0,0,0)
             ON DUPLICATE KEY UPDATE
                canti=VALUES(canti)'
        );

        $st->execute([
            ':produ' => $produ,
            ':almac' => $almac,
            ':canti' => $qty
        ]);
    }

    /* ========================================================
       NIVEL 1: Rango
       Convierte ANIO del Excel en:
       - anio  = año modelo
       - gdesd = inicio de generación
       - ghast = fin de generación
    ======================================================== */

    private function rango(
        string $texto,
        int $anio
    ): array {
        /* =====================================================
           NIVEL 2: Rango textual
           Ejemplo:
           10-2004-10 → 2010 / 2004 / 2010
        ===================================================== */

        if (preg_match(
            '/^(\d{2})-(\d{4})-(\d{2})$/',
            $texto,
            $m
        )) {
            return [
                2000 + (int) $m[1],
                (int) $m[2],
                2000 + (int) $m[3]
            ];
        }

        /* =====================================================
           NIVEL 2: Año simple
           Si no existe rango, el mismo año representa
           modelo, inicio y fin.
        ===================================================== */

        return [
            $anio,
            $anio,
            $anio
        ];
    }
}