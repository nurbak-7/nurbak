/* ============================================================
   Ruta: datos/categ/venta/notav.sql
   Módulo: Ventas / Nota de Venta
   Base: VENTA

   RESPONSABILIDAD:
   Registrar Nota de Venta independiente de Operaciones.

   SEPARACIÓN:
   - opera es nullable y conserva referencias históricas.
   - No existe clave foránea hacia opera.
   - Las nuevas notas independientes usan opera = NULL.

   CLIENTE:
   - clien es opcional.
   - Se conservan los datos del comprador: tipod, docum,
     razon y direc.

   NUMERACIÓN:
   - La serie y numeración pertenecen a VENTA.numer → notav.
============================================================ */


/* ============================================================
   NIVEL 1 — NOTA DE VENTA
============================================================ */

CREATE TABLE IF NOT EXISTS notav (
    id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    opera  INT UNSIGNED NULL,
    clien  INT UNSIGNED NULL,
    vende  INT UNSIGNED NULL,
    almac  INT UNSIGNED NULL,
    placa  VARCHAR(20) NULL,

    tipod  VARCHAR(10) NOT NULL DEFAULT 'DNI',
    docum  VARCHAR(20) NULL,
    razon  VARCHAR(150) NOT NULL,
    direc  VARCHAR(180) NULL,

    serie  VARCHAR(4) NOT NULL,
    numer  INT UNSIGNED NOT NULL,

    estad  ENUM('emitida', 'anulada')
           NOT NULL DEFAULT 'emitida',

    fecha  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    subto  DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    igv    DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    total  DECIMAL(14,2) NOT NULL DEFAULT 0.00,

    formp  VARCHAR(20) NOT NULL DEFAULT 'CONTADO',
    medi   VARCHAR(30) NULL,
    obser  VARCHAR(255) NULL,

    UNIQUE KEY uq005 (serie, numer),

    INDEX ix021 (opera),
    INDEX ix022 (clien),
    INDEX ix023 (estad),
    INDEX ix024 (fecha),

    CONSTRAINT fk_notav_clien
        FOREIGN KEY (clien)
        REFERENCES clien(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


/* ============================================================
   NIVEL 1 — DETALLE DE NOTA DE VENTA
============================================================ */

CREATE TABLE IF NOT EXISTS detnv (
    id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    notav  INT UNSIGNED NOT NULL,
    produ  INT UNSIGNED NOT NULL,

    canti  DECIMAL(14,3) NOT NULL,
    preun  DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    subto  DECIMAL(14,2) NOT NULL DEFAULT 0.00,

    INDEX ix025 (notav),
    INDEX ix026 (produ),

    CONSTRAINT fk012
        FOREIGN KEY (notav)
        REFERENCES notav(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;