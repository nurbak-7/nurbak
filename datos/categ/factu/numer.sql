
/* ============================================================
   Ruta: datos/categ/factu/numer.sql
   Módulo: Facturación / Numeración
   Base: factu

   RESPONSABILIDAD:
   Controlar exclusivamente la numeración de los documentos
   pertenecientes a FACTU.

   DOCUMENTOS:
   - bolet = Boleta
   - factu = Factura
   - ncred = Nota de Crédito
   - ndebi = Nota de Débito
   - gremi = Guía de Remisión
============================================================ */

CREATE TABLE IF NOT EXISTS numer (
    id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tipo   VARCHAR(20) NOT NULL,
    serie  VARCHAR(4) NOT NULL,
    ultim  INT UNSIGNED NOT NULL DEFAULT 0,

    UNIQUE KEY uq001 (tipo, serie)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


INSERT IGNORE INTO numer (tipo, serie, ultim)
VALUES
    ('boleta',  'B001', 0),
    ('factura', 'F001', 0),
    ('ncred',   'FC01', 0),
    ('ndebi',   'FD01', 0),
    ('gremi',   'T001', 0);
