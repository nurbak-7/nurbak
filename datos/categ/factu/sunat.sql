/* ============================================================
   Ruta: datos/categ/factu/sunat.sql
   Módulo: Facturación
   Base: factu
============================================================ */

CREATE TABLE IF NOT EXISTS sunat (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tipod VARCHAR(20) NOT NULL, docid INT UNSIGNED NOT NULL,
 estad ENUM('pendiente','enviado','aceptado','rechazado','observado') NOT NULL DEFAULT 'pendiente', codre VARCHAR(50) NULL, mensj TEXT NULL, ticke VARCHAR(100) NULL,
 xmlen LONGTEXT NULL, cdrre LONGTEXT NULL, fenvi DATETIME NULL, fresp DATETIME NULL,
 INDEX ix038 (tipod,docid), INDEX ix039 (estad), INDEX ix040 (ticke)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
