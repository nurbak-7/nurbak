<?php

/* ============================================================
   Ruta: modul/categ/catal/asinc/imext/impor/repos.php
   Módulo: Catálogo / ASINC / IMEXT / IMPORT
   Función: Resolver identidades y persistir cada lote de importación
   mediante consultas y escrituras agrupadas.

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
       NIVEL 1: GUARDAR LOTE
       Recibe filas con una sola modalidad de ID, en el orden
       original del Excel, y persiste sus cambios en operaciones
       agrupadas sobre CATAL e INVENTARIO.
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

        $sinId = 0;
        $modalidadId = null;

        foreach ($items as $registro) {
            $tieneId = (int) ($registro['datos']['id'] ?? 0) > 0;

            if ($modalidadId !== null && $tieneId !== $modalidadId) {
                throw new LogicException(
                    'ImpoRepo::guardarLote requiere agrupar las filas por modalidad de ID y conservar el orden del Excel.'
                );
            }

            $modalidadId = $tieneId;
            if (!$tieneId) {
                $sinId++;
            }
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