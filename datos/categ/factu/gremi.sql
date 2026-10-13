/* ============================================================
   Ruta: datos/categ/factu/gremi.sql
   Módulo: Facturación
   Base: factu
============================================================ */

CREATE TABLE IF NOT EXISTS gremi (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, opera INT UNSIGNED NULL, refti VARCHAR(20) NULL, refid INT UNSIGNED NULL, mottr VARCHAR(100) NULL,
 orige VARCHAR(180) NOT NULL, desti VARCHAR(180) NOT NULL, serie VARCHAR(4) NOT NULL, numer INT UNSIGNED NOT NULL, fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 estad ENUM('borrador','emitida','anulada') NOT NULL DEFAULT 'borrador', obser VARCHAR(255) NULL, UNIQUE KEY uq009 (serie,numer), INDEX ix044 (estad), INDEX ix045 (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS detgr (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, gremi INT UNSIGNED NOT NULL, produ INT UNSIGNED NULL, descr VARCHAR(150) NOT NULL, canti DECIMAL(14,3) NOT NULL DEFAULT 0.000,
 INDEX ix046 (gremi), CONSTRAINT fk016 FOREIGN KEY (gremi) REFERENCES gremi(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* ============================================================
   MIGRACIÓN — SERIE CONTROLADA POR FACTU.NUMER
============================================================ */

ALTER TABLE gremi MODIFY COLUMN serie VARCHAR(4) NOT NULL;
