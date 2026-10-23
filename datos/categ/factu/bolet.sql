
/* ============================================================
   Ruta: datos/categ/factu/bolet.sql
   Módulo: Facturación
   Base: factu
============================================================ */

CREATE TABLE IF NOT EXISTS bolet (
    id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    opera  INT UNSIGNED NULL,
    notav  INT UNSIGNED NULL,
    clien  INT UNSIGNED NULL,

    tipod  VARCHAR(10) NOT NULL DEFAULT 'DNI',
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

    UNIQUE KEY uq012 (serie, numer),
    INDEX ix057 (estad),
    INDEX ix058 (fecha),
    INDEX ix059 (clien)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS detbo (
    id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    bolet  INT UNSIGNED NOT NULL,
    produ  INT UNSIGNED NULL,

    descr  VARCHAR(150) NOT NULL,
    canti  DECIMAL(14,3) NOT NULL,

    preun  DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    subto  DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    igv    DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    total  DECIMAL(14,2) NOT NULL DEFAULT 0.00,

    INDEX ix060 (bolet),

    CONSTRAINT fk019
        FOREIGN KEY (bolet)
        REFERENCES bolet(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
