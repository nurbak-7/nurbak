/* ============================================================
   Ruta: datos/categ/compr/comex.sql
   Módulo: Compras / Compra externa

   FUNCIÓN:
   Registrar compras realizadas a proveedores externos.

   PCOMP:
   El costo recibido pertenece al circuito de compra.
============================================================ */

CREATE TABLE IF NOT EXISTS comex (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    prove INT UNSIGNED NOT NULL,
    estad ENUM('borrador','emitida','parcial','recibida','anulada') NOT NULL DEFAULT 'borrador',
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    obser VARCHAR(300) NULL,
    total DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    INDEX ix097 (prove),
    INDEX ix098 (estad),
    INDEX ix099 (fecha),
    CONSTRAINT fk029 FOREIGN KEY (prove) REFERENCES prove(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS detex (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    comex INT UNSIGNED NOT NULL,
    produ INT UNSIGNED NOT NULL,
    canti DECIMAL(14,3) NOT NULL,
    preun DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    subto DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    INDEX ix100 (comex),
    INDEX ix101 (produ),
    CONSTRAINT fk030 FOREIGN KEY (comex) REFERENCES comex(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
