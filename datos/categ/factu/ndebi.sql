/* ============================================================
   Ruta: datos/categ/factu/ndebi.sql
   Módulo: Facturación
   Base: factu
============================================================ */

CREATE TABLE IF NOT EXISTS ndebi (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, oriti ENUM('factura','boleta') NOT NULL, oride INT UNSIGNED NOT NULL, clien INT UNSIGNED NULL,
 tipod VARCHAR(10) NULL, docum VARCHAR(20) NULL, razon VARCHAR(150) NOT NULL, serie VARCHAR(4) NOT NULL, numer INT UNSIGNED NOT NULL,
 fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, codmo VARCHAR(10) NULL, motvo VARCHAR(255) NOT NULL,
 subto DECIMAL(14,2) NOT NULL DEFAULT 0.00, igv DECIMAL(14,2) NOT NULL DEFAULT 0.00, total DECIMAL(14,2) NOT NULL DEFAULT 0.00,
 estad ENUM('borrador','emitida','anulada') NOT NULL DEFAULT 'borrador', UNIQUE KEY uq008 (serie,numer), INDEX ix041 (oriti,oride), INDEX ix042 (estad)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS detnd (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, ndebi INT UNSIGNED NOT NULL, produ INT UNSIGNED NULL, descr VARCHAR(150) NOT NULL, canti DECIMAL(14,3) NOT NULL DEFAULT 0.000,
 impor DECIMAL(14,2) NOT NULL DEFAULT 0.00, INDEX ix043 (ndebi), CONSTRAINT fk015 FOREIGN KEY (ndebi) REFERENCES ndebi(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* ============================================================
   MIGRACIÓN — SERIE CONTROLADA POR FACTU.NUMER
============================================================ */

ALTER TABLE ndebi MODIFY COLUMN serie VARCHAR(4) NOT NULL;
