/* ============================================================
   Ruta: datos/categ/pagos/caja.sql
   Módulo: Pagos / Caja
   Base: pago

   RESPONSABILIDAD:
   Controlar la apertura/cierre de caja y sus movimientos
   monetarios.

   CONEXIONES LÓGICAS:
   - usape / usaci → logi.usuarios.id
   - pago          → pago.regis.id
   - opera         → VENTA.opera.id
============================================================ */

CREATE TABLE IF NOT EXISTS caja (
    id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usape  INT UNSIGNED NOT NULL,
    faper  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    saldi  DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    usaci  INT UNSIGNED NULL,
    fecie  DATETIME NULL,
    saldc  DECIMAL(14,2) NULL,

    estad  ENUM('abierta', 'cerrada')
           NOT NULL DEFAULT 'abierta',

    obser  VARCHAR(255) NULL,

    INDEX ix103 (faper),
    INDEX ix104 (estad),
    INDEX ix105 (usape)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS cajmo (
    id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    caja   INT UNSIGNED NOT NULL,
    pago   INT UNSIGNED NULL,
    opera  INT UNSIGNED NULL,
    usuar  INT UNSIGNED NULL,

    tipo   ENUM('ingreso', 'salida') NOT NULL,
    conce  VARCHAR(150) NOT NULL,
    monto  DECIMAL(14,2) NOT NULL,
    fecha  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    estad  ENUM('registrado', 'anulado')
           NOT NULL DEFAULT 'registrado',

    INDEX ix106 (caja),
    INDEX ix107 (pago),
    INDEX ix108 (opera),
    INDEX ix109 (fecha),

    CONSTRAINT fk028
        FOREIGN KEY (caja)
        REFERENCES caja(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_cajmo_pago
        FOREIGN KEY (pago)
        REFERENCES regis(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;