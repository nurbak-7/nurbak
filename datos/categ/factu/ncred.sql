
/* ============================================================
   Ruta: datos/categ/factu/ncred.sql
   Módulo: Facturación
   Base: factu
============================================================ */

CREATE TABLE IF NOT EXISTS ncred (
    id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    oriti  ENUM('factura','boleta') NOT NULL,
    oride  INT UNSIGNED NOT NULL,
    clien  INT UNSIGNED NULL,

    tipod  VARCHAR(10) NULL,
    docum  VARCHAR(20) NULL,
    razon  VARCHAR(150) NOT NULL,

    serie  VARCHAR(4) NOT NULL,
    numer  INT UNSIGNED NOT NULL,
    fecha  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    codmo  VARCHAR(10) NULL,
    motvo  VARCHAR(255) NOT NULL,

    subto  DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    igv    DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    total  DECIMAL(14,2) NOT NULL DEFAULT 0.00,

    estad  ENUM('borrador','emitida','anulada')
           NOT NULL DEFAULT 'borrador',

    UNIQUE KEY uq011 (serie, numer),
    INDEX ix054 (oriti, oride),
    INDEX ix055 (estad)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS detnc (
    id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ncred  INT UNSIGNED NOT NULL,
    produ  INT UNSIGNED NULL,

    descr  VARCHAR(150) NOT NULL,
    canti  DECIMAL(14,3) NOT NULL DEFAULT 0.000,
    impor  DECIMAL(14,2) NOT NULL DEFAULT 0.00,

    INDEX ix056 (ncred),

    CONSTRAINT fk018
        FOREIGN KEY (ncred)
        REFERENCES ncred(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
