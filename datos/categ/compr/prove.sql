/* ============================================================
   Ruta: datos/categ/compr/prove.sql
   Módulo: Compras / Proveedores

   FUNCIÓN:
   Guardar los proveedores a los que se realizan compras.

   BASE DE DATOS:
   compr

   RELACIÓN:
   orden.prove y recep.prove apuntarán al proveedor.
   orden a subir a tidb
   prove.sql
   comex.sql
   orcex.sql
   recep.sql
============================================================ */

CREATE TABLE IF NOT EXISTS prove (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    razon VARCHAR(180) NOT NULL,
    ruc VARCHAR(20) NULL,
    conta VARCHAR(120) NULL,
    telef VARCHAR(50) NULL,
    email VARCHAR(150) NULL,
    direc VARCHAR(200) NULL,
    estad TINYINT(1) NOT NULL DEFAULT 1,
    fecre DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX ix088 (razon),
    INDEX ix089 (ruc),
    INDEX ix090 (estad)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
