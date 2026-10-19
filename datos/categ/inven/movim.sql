/* ============================================================
   Ruta: datos/categ/inven/movim.sql
   Módulo: Inventario / Movimientos

   REGLAS:
   - entrada, salida y ajuste modifican stock según la operación.
   - salss registra demanda con stock físico en 0 y no reduce canti.
   - canti del movimiento siempre es positiva.
============================================================ */

USE inven;

CREATE TABLE IF NOT EXISTS movim (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    produ INT UNSIGNED NOT NULL,
    almac INT UNSIGNED NOT NULL,
    tipo ENUM('entrada','salida','ajuste','salss') NOT NULL,
    canti DECIMAL(14,3) NOT NULL,
    motvo VARCHAR(200) NULL,
    orige VARCHAR(5) NULL,
    docid BIGINT UNSIGNED NULL,
    refid BIGINT UNSIGNED NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX ix027 (produ),
    INDEX ix028 (almac),
    INDEX ix029 (tipo),
    INDEX ix030 (fecha),
    INDEX ix031 (orige,docid),
    INDEX ix032 (refid),

    CONSTRAINT ck009 CHECK (canti > 0),

    CONSTRAINT fk013 FOREIGN KEY (almac)
        REFERENCES almac(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;