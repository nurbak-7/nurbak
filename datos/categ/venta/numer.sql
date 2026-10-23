/* ============================================================
   Ruta: datos/categ/venta/numer.sql
   Módulo: Ventas / Numeración
   Base: VENTA

   RESPONSABILIDAD:
   Controlar exclusivamente la numeración de documentos propios
   de VENTAS.

   DOCUMENTOS:
   - notav = Nota de Venta
   - cotiz = Cotización

   Los documentos tributarios pertenecen a FACTU y utilizan
   factu.numer.
============================================================ */

CREATE TABLE IF NOT EXISTS numer (
    id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tipo   VARCHAR(20) NOT NULL,
    serie  VARCHAR(4) NOT NULL,
    ultim  INT UNSIGNED NOT NULL DEFAULT 0,

    UNIQUE KEY uq003 (tipo, serie)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


/* ============================================================
   NUMERACIÓN INICIAL
============================================================ */

INSERT IGNORE INTO numer (tipo, serie, ultim)
VALUES
    ('notav', 'N001', 0),
    ('cotiz', 'C001', 99);