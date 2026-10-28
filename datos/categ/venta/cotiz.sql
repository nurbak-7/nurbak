
/* ============================================================
   Ruta: datos/categ/venta/cotiz.sql
   Módulo: Ventas / Cotización
   Base: VENTA

   RESPONSABILIDAD:
   Crear las tablas exclusivas de Cotización.

   REGLAS:
   - Cotización no reserva stock.
   - Cotización no modifica stock.
   - Cotización no genera saldos.
   - Guardar registra la cotización.
   - Cancelar cambia únicamente su estado.
   - Serie C001 se administra mediante venta.numer.

   NOMBRES INTERNOS:
   - cotiz = cabecera.
   - dtall = detalle.
============================================================ */

CREATE TABLE IF NOT EXISTS cotiz (
    id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    clien  INT UNSIGNED NOT NULL,
    vende  INT UNSIGNED NOT NULL,
    placa  VARCHAR(7) NULL,
    serie  VARCHAR(4) NOT NULL,
    numco  INT UNSIGNED NOT NULL,
    fecha  DATE NOT NULL,
    vigia  DATE NOT NULL,

    estad  ENUM('activa', 'cancelada')
           NOT NULL DEFAULT 'activa',

    obser  VARCHAR(255) NULL,
    subto  DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    igv    DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    total  DECIMAL(14,2) NOT NULL DEFAULT 0.00,

    cread  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actua  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
           ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uqc01 (serie, numco),

    INDEX icli1 (clien),
    INDEX iven1 (vende),
    INDEX iest1 (estad),
    INDEX ifec1 (fecha),
    INDEX ivig1 (vigia),

    CONSTRAINT fk_cotiz_clien
        FOREIGN KEY (clien)
        REFERENCES clien(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS dtall (
    id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cotiz  INT UNSIGNED NOT NULL,
    produ  INT UNSIGNED NOT NULL,
    codig  VARCHAR(80) NULL,
    descr  VARCHAR(255) NULL,
    canti  DECIMAL(14,3) NOT NULL,
    preun  DECIMAL(22,10) NOT NULL DEFAULT 0.0000000000,
    total  DECIMAL(14,2) NOT NULL DEFAULT 0.00,

    INDEX icot1 (cotiz),
    INDEX ipro1 (produ),

    CONSTRAINT fdc01
        FOREIGN KEY (cotiz)
        REFERENCES cotiz(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
