/* ============================================================
   Ruta: datos/categ/pagos/credi.sql
   Módulo: Pagos / Créditos
   Base: pago

   RESPONSABILIDAD:
   Controlar ventas/documentos a crédito, saldo pendiente y
   cancelación mediante pagos posteriores.

   CONEXIONES LÓGICAS:
   - clien   → VENTA.clien.id
   - opera → VENTA.opera.id
   - docum → factu.factu.id / factu.bolet.id
   - pago.regis aplica pagos posteriores al crédito.
============================================================ */

CREATE TABLE IF NOT EXISTS credi (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    opera INT UNSIGNED NULL,
    tipod VARCHAR(20) NULL,
    docid INT UNSIGNED NULL,
    clien INT UNSIGNED NOT NULL,
    monti DECIMAL(14,2) NOT NULL,
    saldo DECIMAL(14,2) NOT NULL,
    finic DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fvenc DATE NULL,
    estad ENUM('pendiente','pagado','anulado') NOT NULL DEFAULT 'pendiente',
    obser VARCHAR(255) NULL,
    INDEX ix091 (opera),
    INDEX ix092 (tipod, docid),
    INDEX ix093 (clien),
    INDEX ix094 (estad),
    INDEX ix095 (fvenc)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
