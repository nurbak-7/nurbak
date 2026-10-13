/* ============================================================
   Ruta: datos/categ/venta/notav.sql

   Módulo: Ventas / Nota de Venta

   Base: VENTA

   RESPONSABILIDAD:
   Registrar Nota de Venta independiente de Operaciones.

   SEPARACIÓN:
   opera se conserva como columna nullable solo para referencias históricas.
   No existe clave foránea ni dependencia obligatoria hacia la tabla opera.
   Las nuevas notas independientes se guardan con opera = NULL.

   CLIENTE:
   clien es opcional y sirve como referencia al maestro de clientes.

   La Nota conserva los datos del comprador utilizados:
   tipod, docum, razon y direc.

   NUMERACIÓN:
   La serie y numeración pertenecen a VENTA.numer → notav.
============================================================ */


/* ============================================================
   NIVEL 1 — NOTA DE VENTA
============================================================ */

CREATE TABLE IF NOT EXISTS notav (

    /* ========================================================
       NIVEL 2 — IDENTIFICACIÓN
    ======================================================== */

    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    opera INT UNSIGNED NULL,

    clien INT UNSIGNED NULL,

    vende INT UNSIGNED NULL,

    almac INT UNSIGNED NULL,

    placa VARCHAR(20) NULL,


    /* ========================================================
       NIVEL 2 — COMPRADOR
    ======================================================== */

    tipod VARCHAR(10) NOT NULL DEFAULT 'DNI',

    docum VARCHAR(20) NULL,

    razon VARCHAR(150) NOT NULL,

    direc VARCHAR(180) NULL,


    /* ========================================================
       NIVEL 2 — DOCUMENTO
    ======================================================== */

    serie VARCHAR(4) NOT NULL,

    numer INT UNSIGNED NOT NULL,

    estad ENUM('emitida','anulada')
        NOT NULL DEFAULT 'emitida',

    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,


    /* ========================================================
       NIVEL 2 — TOTALES
    ======================================================== */

    subto DECIMAL(14,2) NOT NULL DEFAULT 0.00,

    igv DECIMAL(14,2) NOT NULL DEFAULT 0.00,

    total DECIMAL(14,2) NOT NULL DEFAULT 0.00,

    formp VARCHAR(20) NOT NULL DEFAULT 'CONTADO',

    medi VARCHAR(30) NULL,

    obser VARCHAR(255) NULL,


    /* ========================================================
       NIVEL 2 — ÍNDICES
    ======================================================== */

    UNIQUE KEY uq005 (serie, numer),

    INDEX ix021 (opera),

    INDEX ix022 (clien),

    INDEX ix023 (estad),

    INDEX ix024 (fecha),


    /* ========================================================
       NIVEL 2 — RELACIONES
    ======================================================== */

    CONSTRAINT fk_notav_clien

        FOREIGN KEY (clien) REFERENCES clien(id)

        ON UPDATE CASCADE

        ON DELETE RESTRICT

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;



/* ============================================================
   NIVEL 1 — DETALLE DE NOTA DE VENTA
============================================================ */

CREATE TABLE IF NOT EXISTS detnv (

    /* ========================================================
       NIVEL 2 — IDENTIFICACIÓN
    ======================================================== */

    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    notav INT UNSIGNED NOT NULL,

    produ INT UNSIGNED NOT NULL,


    /* ========================================================
       NIVEL 2 — CANTIDAD Y PRECIO
    ======================================================== */

    canti DECIMAL(14,3) NOT NULL,

    preun DECIMAL(14,2) NOT NULL DEFAULT 0.00,

    subto DECIMAL(14,2) NOT NULL DEFAULT 0.00,


    /* ========================================================
       NIVEL 2 — ÍNDICES
    ======================================================== */

    INDEX ix025 (notav),

    INDEX ix026 (produ),


    /* ========================================================
       NIVEL 2 — RELACIÓN
    ======================================================== */

    CONSTRAINT fk012

        FOREIGN KEY (notav) REFERENCES notav(id)

        ON UPDATE CASCADE

        ON DELETE CASCADE

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

/* ============================================================
   MIGRACIÓN ADITIVA — CAMPOS PROPIOS DE LA NOTA INDEPENDIENTE
   Permite actualizar una tabla notav ya creada sin reemplazar datos.
============================================================ */

SET @nv_col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='notav' AND COLUMN_NAME='vende');
SET @nv_sql := IF(@nv_col=0,'ALTER TABLE notav ADD COLUMN vende INT UNSIGNED NULL AFTER clien','SELECT 1');
PREPARE nv_mig FROM @nv_sql;
EXECUTE nv_mig;
DEALLOCATE PREPARE nv_mig;

SET @nv_col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='notav' AND COLUMN_NAME='almac');
SET @nv_sql := IF(@nv_col=0,'ALTER TABLE notav ADD COLUMN almac INT UNSIGNED NULL AFTER vende','SELECT 1');
PREPARE nv_mig FROM @nv_sql;
EXECUTE nv_mig;
DEALLOCATE PREPARE nv_mig;

SET @nv_col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='notav' AND COLUMN_NAME='placa');
SET @nv_sql := IF(@nv_col=0,'ALTER TABLE notav ADD COLUMN placa VARCHAR(20) NULL AFTER direc','SELECT 1');
PREPARE nv_mig FROM @nv_sql;
EXECUTE nv_mig;
DEALLOCATE PREPARE nv_mig;

SET @nv_col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='notav' AND COLUMN_NAME='igv');
SET @nv_sql := IF(@nv_col=0,'ALTER TABLE notav ADD COLUMN igv DECIMAL(14,2) NOT NULL DEFAULT 0.00 AFTER subto','SELECT 1');
PREPARE nv_mig FROM @nv_sql;
EXECUTE nv_mig;
DEALLOCATE PREPARE nv_mig;

SET @nv_col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='notav' AND COLUMN_NAME='formp');
SET @nv_sql := IF(@nv_col=0,'ALTER TABLE notav ADD COLUMN formp VARCHAR(20) NOT NULL DEFAULT ''CONTADO'' AFTER total','SELECT 1');
PREPARE nv_mig FROM @nv_sql;
EXECUTE nv_mig;
DEALLOCATE PREPARE nv_mig;

SET @nv_col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='notav' AND COLUMN_NAME='medi');
SET @nv_sql := IF(@nv_col=0,'ALTER TABLE notav ADD COLUMN medi VARCHAR(30) NULL AFTER formp','SELECT 1');
PREPARE nv_mig FROM @nv_sql;
EXECUTE nv_mig;
DEALLOCATE PREPARE nv_mig;


/* ============================================================
   MIGRACIÓN — SEPARAR NOTA DE VENTA DE OPERACIONES

   Se elimina únicamente la restricción que obliga a que notav.opera
   corresponda a opera(id). Se conserva la columna nullable para no
   borrar referencias históricas ni romper el backend actual.

   Nuevas notas independientes: opera = NULL.
============================================================ */
SET @nv_fk := (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'notav'
      AND CONSTRAINT_NAME = 'fk_notav_opera'
      AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);
SET @nv_sql := IF(
    @nv_fk > 0,
    'ALTER TABLE notav DROP FOREIGN KEY fk_notav_opera',
    'SELECT 1'
);
PREPARE nv_mig FROM @nv_sql;
EXECUTE nv_mig;
DEALLOCATE PREPARE nv_mig;

/* Se asegura que la referencia histórica sea opcional. */
ALTER TABLE notav MODIFY COLUMN opera INT UNSIGNED NULL;
