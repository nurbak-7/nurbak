<?php

/* ============================================================
   Ruta: modul/categ/catal/asinc/imext/impor/repos.php
   Módulo: Catálogo / ASINC / IMEXT / IMPORT
   Función: Persistir una fila integral del catálogo.

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