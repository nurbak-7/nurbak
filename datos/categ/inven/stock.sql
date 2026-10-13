/* ============================================================
   Ruta: datos/categ/inven/stock.sql
   Módulo: Inventario / Stock

   FUNCIÓN:
   Mantener la cantidad física de cada producto por almacén.

   REGLAS:
   - canti = stock físico real y nunca puede ser negativo.
   - salss = demanda realizada con stock físico en 0; tampoco puede
     ser negativa y no convierte el stock físico en negativo.
   - stmin y stmax no pueden ser negativos.
   - Un producto solo tiene una fila por almacén.
   - produ pertenece conceptualmente a CATAL; no se crea FK física
     si CATAL se mantiene separado de INVEN.
============================================================ */

CREATE TABLE IF NOT EXISTS stock (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    produ INT UNSIGNED NOT NULL,
    almac INT UNSIGNED NOT NULL,
    canti BIGINT NOT NULL DEFAULT 0,
    salss BIGINT NOT NULL DEFAULT 0,
    stmin BIGINT NOT NULL DEFAULT 0,
    stmax BIGINT NOT NULL DEFAULT 0,
    ultim DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq007 (produ, almac),
    INDEX ix034 (produ),
    INDEX ix035 (almac),

    CONSTRAINT ck005 CHECK (canti >= 0),
    CONSTRAINT ck006 CHECK (salss >= 0),
    CONSTRAINT ck007 CHECK (stmin >= 0),
    CONSTRAINT ck008 CHECK (stmax >= 0),

    CONSTRAINT fk014 FOREIGN KEY (almac) REFERENCES almac(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;