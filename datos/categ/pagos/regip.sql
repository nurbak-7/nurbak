/* ============================================================
   Ruta: datos/categ/pagos/regip.sql
   Módulo: Pagos / Registro de pagos
   Base: pago

   RESPONSABILIDAD:
   Registrar el dinero recibido por una venta/documento.

   RELACIONES LÓGICAS:
   - clien   → VENTA.clien.id
   - opera → VENTA.opera.id
   - docum → factu.factu.id o factu.bolet.id, según documento.
   - usuar   → logi.usuarios.id
   - caja      → pago.caja.id

   No se crean FK entre bases independientes.
============================================================ */

CREATE TABLE IF NOT EXISTS regis (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    opera INT UNSIGNED NULL,
    tipod VARCHAR(20) NULL,
    docid INT UNSIGNED NULL,
    clien INT UNSIGNED NULL,
    usuar INT UNSIGNED NULL,
    caja INT UNSIGNED NULL,
    monto DECIMAL(14,2) NOT NULL,
    medpa VARCHAR(30) NOT NULL,
    refer VARCHAR(100) NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    estad ENUM('registrado','anulado') NOT NULL DEFAULT 'registrado',
    obser VARCHAR(255) NULL,
    INDEX ix096 (opera),
    INDEX ix097 (tipod, docid),
    INDEX ix098 (clien),
    INDEX ix099 (usuar),
    INDEX ix100 (caja),
    INDEX ix101 (fecha),
    INDEX ix102 (estad)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* ============================================================
   MIGRACIÓN — RELACIÓN PAGO / CAJA
============================================================ */

SET @hay_caja := (
    SELECT COUNT(*) FROM information_schema.TABLES
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='caja'
);
SET @frc := (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='regis'
      AND CONSTRAINT_NAME='fk_regis_caja'
);
SET @src := IF(@hay_caja=0,'SELECT 1',IF(@frc=0,
    'ALTER TABLE regis ADD CONSTRAINT fk_regis_caja FOREIGN KEY (caja) REFERENCES caja(id) ON UPDATE CASCADE ON DELETE RESTRICT',
    'SELECT 1'));
PREPARE xrc FROM @src; EXECUTE xrc; DEALLOCATE PREPARE xrc;
