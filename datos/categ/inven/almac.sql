/* ============================================================
   Ruta: datos/categ/inven/almac.sql
   Módulo: Inventario / Almacenes

   FUNCIÓN:
   Registrar los almacenes de cada tienda.

   MODELO:
   TIEND 1:N ALMAC 1:N STOCK

   REGLAS:
   - tiend identifica la tienda propietaria del almacén.
   - prin = 1 identifica el almacén principal de la tienda.
   - Una tienda puede tener varios almacenes.
   - Una tienda puede tener como máximo un almacén principal.
   - estad = 1 significa almacén activo.
   - No existe FK física hacia LOGIN.TIEND porque LOGIN e INVENTARIO
     mantienen bases independientes; tiend es una referencia lógica.
============================================================ */

CREATE TABLE IF NOT EXISTS almac (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tiend INT UNSIGNED NOT NULL,
    nombr VARCHAR(100) NOT NULL,
    descr VARCHAR(200) NULL,
    direc VARCHAR(200) NULL,
    estad TINYINT(1) NOT NULL DEFAULT 1,
    prin TINYINT(1) NOT NULL DEFAULT 0,
    prti INT UNSIGNED
        GENERATED ALWAYS AS (
            CASE WHEN prin = 1 THEN tiend ELSE NULL END
        ) STORED,
    fecre DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uq008 (prti),
    INDEX ix036 (tiend),
    INDEX ix037 (nombr),
    INDEX ix038 (estad),
    INDEX ix039 (prin),

    CONSTRAINT ck001 CHECK (estad IN (0,1)),
    CONSTRAINT ck002 CHECK (prin IN (0,1))
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

/* ============================================================
   MIGRACIÓN — instalaciones existentes
   Se agregan las columnas nuevas solo si todavía no existen.
   Si existen datos con más de un principal por tienda, primero
   deben normalizarse antes de crear uq008.
============================================================ */

SET @hay_tiend := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='almac' AND COLUMN_NAME='tiend'
);
SET @sql_tiend := IF(@hay_tiend=0,
    'ALTER TABLE almac ADD COLUMN tiend INT UNSIGNED NOT NULL AFTER id',
    'SELECT 1');
PREPARE stmt_tiend FROM @sql_tiend; EXECUTE stmt_tiend; DEALLOCATE PREPARE stmt_tiend;

SET @hay_prin := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='almac' AND COLUMN_NAME='prin'
);
SET @sql_prin := IF(@hay_prin=0,
    'ALTER TABLE almac ADD COLUMN prin TINYINT(1) NOT NULL DEFAULT 0 AFTER estad',
    'SELECT 1');
PREPARE stmt_prin FROM @sql_prin; EXECUTE stmt_prin; DEALLOCATE PREPARE stmt_prin;

SET @hay_prti := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='almac' AND COLUMN_NAME='prti'
);
SET @sql_prti := IF(@hay_prti=0,
    'ALTER TABLE almac ADD COLUMN prti INT UNSIGNED GENERATED ALWAYS AS (CASE WHEN prin = 1 THEN tiend ELSE NULL END) STORED AFTER prin',
    'SELECT 1');
PREPARE stmt_prti FROM @sql_prti; EXECUTE stmt_prti; DEALLOCATE PREPARE stmt_prti;

SET @hay_ix_prti := (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='almac' AND INDEX_NAME='uq008'
);
SET @sql_ix_prti := IF(@hay_ix_prti=0,
    'ALTER TABLE almac ADD UNIQUE KEY uq008 (prti)',
    'SELECT 1');
PREPARE stmt_ix_prti FROM @sql_ix_prti; EXECUTE stmt_ix_prti; DEALLOCATE PREPARE stmt_ix_prti;

SET @hay_ix_tiend := (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='almac' AND INDEX_NAME='ix036'
);
SET @sql_ix_tiend := IF(@hay_ix_tiend=0,
    'ALTER TABLE almac ADD INDEX ix036 (tiend)',
    'SELECT 1');
PREPARE stmt_ix_tiend FROM @sql_ix_tiend; EXECUTE stmt_ix_tiend; DEALLOCATE PREPARE stmt_ix_tiend;

/* ============================================================
   FIN — ALMAC
============================================================ */
