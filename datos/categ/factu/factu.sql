
/* ============================================================
   Ruta: datos/categ/factu/factu.sql
   Módulo: Facturación
   Base: factu
============================================================ */

CREATE TABLE IF NOT EXISTS factu (
    id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    opera  INT UNSIGNED NULL,
    notav  INT UNSIGNED NULL,
    clien  INT UNSIGNED NULL,

    tipod  VARCHAR(10) NOT NULL,
    docum  VARCHAR(20) NULL,
    razon  VARCHAR(150) NOT NULL,
    direc  VARCHAR(180) NULL,

    serie  VARCHAR(4) NOT NULL,
    numer  INT UNSIGNED NOT NULL,
    fecha  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    moned  CHAR(3) NOT NULL DEFAULT 'PEN',
    formp  VARCHAR(30) NULL,

    subto  DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    igv    DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    total  DECIMAL(14,2) NOT NULL DEFAULT 0.00,

    estad  ENUM('borrador','emitida','anulada')
           NOT NULL DEFAULT 'borrador',
    obser  VARCHAR(255) NULL,

    UNIQUE KEY uq010 (serie, numer),
    INDEX ix047 (opera),
    INDEX ix048 (notav),
    INDEX ix049 (clien),
    INDEX ix050 (estad),
    INDEX ix051 (fecha)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS detfa (
    id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    factu  INT UNSIGNED NOT NULL,
    produ  INT UNSIGNED NULL,

    descr  VARCHAR(150) NOT NULL,
    canti  DECIMAL(14,3) NOT NULL,

    preun  DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    subto  DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    igv    DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    total  DECIMAL(14,2) NOT NULL DEFAULT 0.00,

    INDEX ix052 (factu),
    INDEX ix053 (produ),

    CONSTRAINT fk017
        FOREIGN KEY (factu)
        REFERENCES factu(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
