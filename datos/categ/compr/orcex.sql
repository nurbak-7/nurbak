/* ============================================================
   Ruta: datos/categ/compr/orcex.sql
   Módulo: Compras / Orden Compra Ext

   FUNCIÓN:
   Registrar solicitudes de compra a proveedores externos.

   REGLA:
   ORCEX no actualiza stock, no crea productos y no genera
   recepción. Es un documento independiente de COMEX.
============================================================ */

CREATE TABLE IF NOT EXISTS orcex (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nume INT UNSIGNED NULL,
    prove INT UNSIGNED NULL,
    razon VARCHAR(180) NULL,
    ruc VARCHAR(20) NULL,
    direc VARCHAR(200) NULL,
    emiso VARCHAR(50) NOT NULL,
    estad ENUM('borrador','emitida','atendida','anulada') NOT NULL DEFAULT 'borrador',
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    total DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    UNIQUE KEY uq101 (nume),
    INDEX ix102 (prove),
    INDEX ix103 (estad),
    INDEX ix104 (fecha),
    CONSTRAINT fk031 FOREIGN KEY (prove) REFERENCES prove(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/* AJUSTE DE ESTRUCTURA: proveedor y datos documentales pueden quedar vacíos. */
SET @p01 := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE()
      AND TABLE_NAME='orcex'
      AND COLUMN_NAME='prove'
);
SET @s01 := IF(
    @p01=1,
    'ALTER TABLE orcex MODIFY prove INT UNSIGNED NULL, MODIFY razon VARCHAR(180) NULL',
    'SELECT 1'
);
PREPARE x01 FROM @s01; EXECUTE x01; DEALLOCATE PREPARE x01;



/* ============================================================
   NUMERACIÓN PROPIA DE ORDEN COMPRA EXT
   Igual al patrón funcional de Cotización:
   - muestra el siguiente número al abrir la MINI;
   - el número se reserva/incrementa al guardar dentro de una
     transacción con bloqueo de la fila de numeración.
============================================================ */
CREATE TABLE IF NOT EXISTS numer (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tipo VARCHAR(20) NOT NULL,
    ultim INT UNSIGNED NOT NULL DEFAULT 0,
    UNIQUE KEY uq105 (tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO numer (tipo,ultim) VALUES ('orcex',0);

CREATE TABLE IF NOT EXISTS detox (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    orcex INT UNSIGNED NOT NULL,
    produ INT UNSIGNED NULL,
    descr VARCHAR(300) NOT NULL,
    canti DECIMAL(14,3) NOT NULL,
    preun DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    subto DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    INDEX ix105 (orcex),
    INDEX ix106 (produ),
    CONSTRAINT fk032 FOREIGN KEY (orcex) REFERENCES orcex(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
