/* ============================================================
   Ruta: datos/categ/inven/movim.sql
   Módulo: Inventario / Movimientos

   REGLAS:
   - entrada, salida y ajuste modifican stock según la operación.
   - salss registra demanda con stock físico en 0 y no reduce canti.
   - canti del movimiento siempre es positiva.
============================================================ */

CREATE TABLE IF NOT EXISTS movim (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    produ INT UNSIGNED NOT NULL,
    almac INT UNSIGNED NOT NULL,
    tipo ENUM('entrada','salida','ajuste','salss') NOT NULL,
    canti DECIMAL(14,3) NOT NULL,
    motvo VARCHAR(200) NULL,
    orige VARCHAR(5) NULL,
    docid BIGINT UNSIGNED NULL,
    refid BIGINT UNSIGNED NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX ix027 (produ),
    INDEX ix028 (almac),
    INDEX ix029 (tipo),
    INDEX ix030 (fecha),
    INDEX ix031 (orige,docid),
    INDEX ix032 (refid),
    CONSTRAINT ck009 CHECK (canti > 0),
    CONSTRAINT fk013 FOREIGN KEY (almac) REFERENCES almac(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE movim MODIFY COLUMN tipo ENUM('entrada','salida','ajuste','salss') NOT NULL;

SET @c9 := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='movim' AND CONSTRAINT_NAME='ck009');
SET @s9 := IF(@c9=0,'ALTER TABLE movim ADD CONSTRAINT ck009 CHECK (canti > 0)','SELECT 1'); PREPARE x9 FROM @s9; EXECUTE x9; DEALLOCATE PREPARE x9;


/* ============================================================
   MIGRACIÓN ADITIVA — TRAZABILIDAD DOCUMENTAL
   orige/docid identifican el documento que originó un movimiento.
   refid identifica el movimiento original compensado.
   Los campos son opcionales para no romper los módulos existentes.
============================================================ */
SET @mv_col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='movim' AND COLUMN_NAME='orige');
SET @mv_sql := IF(@mv_col=0,'ALTER TABLE movim ADD COLUMN orige VARCHAR(5) NULL AFTER motvo','SELECT 1');
PREPARE mv_mig FROM @mv_sql; EXECUTE mv_mig; DEALLOCATE PREPARE mv_mig;

SET @mv_col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='movim' AND COLUMN_NAME='docid');
SET @mv_sql := IF(@mv_col=0,'ALTER TABLE movim ADD COLUMN docid BIGINT UNSIGNED NULL AFTER orige','SELECT 1');
PREPARE mv_mig FROM @mv_sql; EXECUTE mv_mig; DEALLOCATE PREPARE mv_mig;

SET @mv_col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='movim' AND COLUMN_NAME='refid');
SET @mv_sql := IF(@mv_col=0,'ALTER TABLE movim ADD COLUMN refid BIGINT UNSIGNED NULL AFTER docid','SELECT 1');
PREPARE mv_mig FROM @mv_sql; EXECUTE mv_mig; DEALLOCATE PREPARE mv_mig;

SET @mv_ix := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='movim' AND INDEX_NAME='ix031');
SET @mv_sql := IF(@mv_ix=0,'CREATE INDEX ix031 ON movim(orige,docid)','SELECT 1');
PREPARE mv_mig FROM @mv_sql; EXECUTE mv_mig; DEALLOCATE PREPARE mv_mig;

SET @mv_ix := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='movim' AND INDEX_NAME='ix032');
SET @mv_sql := IF(@mv_ix=0,'CREATE INDEX ix032 ON movim(refid)','SELECT 1');
PREPARE mv_mig FROM @mv_sql; EXECUTE mv_mig; DEALLOCATE PREPARE mv_mig;
