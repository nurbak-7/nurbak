/* ============================================================
   Ruta: datos/categ/venta/clien.sql
   Módulo: Ventas / Clientes

   FUNCIÓN:
   Mantener los clientes que participan en las operaciones de
   VENTA. El cliente NO se duplica en Facturación ni Pagos.

   RELACIONES LÓGICAS:
   - VENTA.opera.clien → clien.id
   - VENTA.notav.clien → clien.id
   - VENTA.devol.clien → clien.id

   La tabla solamente define la estructura. No inserta datos.
============================================================ */

CREATE TABLE IF NOT EXISTS clien (
    id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tipod  VARCHAR(10) NOT NULL DEFAULT 'DNI',
    docum  VARCHAR(20) NULL,
    razon  VARCHAR(150) NOT NULL,
    telef  VARCHAR(30) NULL,
    email  VARCHAR(120) NULL,
    direc  VARCHAR(180) NULL,
    estad  TINYINT(1) NOT NULL DEFAULT 1,
    fecre  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uq004 (docum),
    INDEX ix013 (razon),
    INDEX ix014 (estad)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;