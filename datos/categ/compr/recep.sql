/* ============================================================
   Ruta: datos/categ/compr/recep.sql
   Módulo: Compras / Recepción

   FUNCIÓN:
   Registrar la mercadería recibida de una compra externa y
   aplicarla a INVENTARIO.
============================================================ */

CREATE TABLE IF NOT EXISTS recep (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    comex INT UNSIGNED NULL,
    prove INT UNSIGNED NOT NULL,
    almac INT UNSIGNED NOT NULL,
    estad ENUM('pendiente','aplicada','anulada') NOT NULL DEFAULT 'pendiente',
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    obser VARCHAR(300) NULL,
    INDEX ix081 (comex),
    INDEX ix082 (prove),
    INDEX ix083 (almac),
    INDEX ix084 (estad),
    INDEX ix085 (fecha),
    CONSTRAINT fk025 FOREIGN KEY (comex) REFERENCES comex(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk026 FOREIGN KEY (prove) REFERENCES prove(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS detre (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    recep INT UNSIGNED NOT NULL,
    produ INT UNSIGNED NOT NULL,
    canti DECIMAL(14,3) NOT NULL,
    costu DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    INDEX ix086 (recep),
    INDEX ix087 (produ),
    CONSTRAINT fk027 FOREIGN KEY (recep) REFERENCES recep(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* MIGRACIÓN: instalaciones anteriores pueden tener orden; comex es la referencia nueva. */
ALTER TABLE recep ADD COLUMN IF NOT EXISTS comex INT UNSIGNED NULL AFTER id;
