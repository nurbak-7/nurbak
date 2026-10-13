/* ============================================================
   Ruta: datos/categ/inven/ordco.sql
   Módulo: Inventario / Orden de traslado interna

   FUNCIÓN:
   Solicitar traslado de artículos entre almacenes propios.

   REGLAS:
   - No es una compra a proveedor externo.
   - El origen y destino son almacenes existentes.
   - El origen debe ser distinto del destino.
   - Las cantidades del detalle siempre son positivas.
============================================================ */

CREATE TABLE IF NOT EXISTS ordco (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    orige INT UNSIGNED NOT NULL,
    desti INT UNSIGNED NOT NULL,
    estad ENUM('borrador','emitida','parcial','recibida','anulada') NOT NULL DEFAULT 'borrador',
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    obser VARCHAR(300) NULL,
    INDEX ix091 (orige),
    INDEX ix092 (desti),
    INDEX ix093 (estad),
    INDEX ix094 (fecha),
    CONSTRAINT ck011 CHECK (orige <> desti),
    CONSTRAINT fk031 FOREIGN KEY (orige) REFERENCES almac(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk032 FOREIGN KEY (desti) REFERENCES almac(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS detor (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ordco INT UNSIGNED NOT NULL,
    produ INT UNSIGNED NOT NULL,
    canti DECIMAL(14,3) NOT NULL,
    INDEX ix095 (ordco),
    INDEX ix096 (produ),
    CONSTRAINT ck012 CHECK (canti > 0),
    CONSTRAINT fk028 FOREIGN KEY (ordco) REFERENCES ordco(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @c11 := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='ordco' AND CONSTRAINT_NAME='ck011');
SET @s11 := IF(@c11=0,'ALTER TABLE ordco ADD CONSTRAINT ck011 CHECK (orige <> desti)','SELECT 1'); PREPARE x11 FROM @s11; EXECUTE x11; DEALLOCATE PREPARE x11;
SET @c12 := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='detor' AND CONSTRAINT_NAME='ck012');
SET @s12 := IF(@c12=0,'ALTER TABLE detor ADD CONSTRAINT ck012 CHECK (canti > 0)','SELECT 1'); PREPARE x12 FROM @s12; EXECUTE x12; DEALLOCATE PREPARE x12;
