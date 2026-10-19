/* ============================================================
   Ruta: datos/categ/inven/reser.sql
   Módulo: Inventario / Reservas

   REGLAS:
   - La reserva no descuenta stock.canti.
   - Una reserva pertenece a operación, producto y almacén.
   - canti siempre es positiva.
   - No se crea FK hacia la operación de VENTAS para mantener la
     independencia física entre módulos/bases.
============================================================ */

CREATE TABLE IF NOT EXISTS reser (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    opera INT UNSIGNED NOT NULL,
    produ INT UNSIGNED NOT NULL,
    almac INT UNSIGNED NOT NULL,
    canti DECIMAL(14,3) NOT NULL DEFAULT 0.000,
    estad ENUM('reservada','liberada','consumida') NOT NULL DEFAULT 'reservada',
    cread DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actua DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq006 (opera,produ,almac),
    INDEX ix031 (produ,almac),
    INDEX ix032 (opera),
    INDEX ix033 (estad),
    CONSTRAINT ck010 CHECK (canti > 0),
    CONSTRAINT fk015 FOREIGN KEY (almac) REFERENCES almac(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;