/* ============================================================
   Ruta: datos/categ/catal/produ.sql
   Módulo: Catálogo
   Submódulo: Productos

   FUNCIÓN:
   Definir la información propia del artículo de CATAL.

   CAMPOS:
   - descr : Descripción.
   - codig : Código.
   - origi : Original.
   - medid : Medida.
   - super : Característica textual.
   - equiv : Código equivalente del artículo.
   - pcomp : Precio de compra.
   - pvent : Precio de venta.
   - fecha : Fecha de actualización de precios/datos.

   RESPONSABILIDADES:
   - MOTOR y MODEL se almacenan en vehic y se relacionan mediante
     la tabla equiv.
   - MARCA pertenece al intercambio IMEXT; no se almacena aquí.
   - QTY pertenece a INVENTARIO y se consulta mediante stock.
   - SUPER es textual; admite valores como ST, STD, HG, 6UNI,
     12UNI, 24UNI, 0.00, 0.25, 0.50, 0.75 y 1.00.
   - PCOMP corresponde al circuito de compras.
   - PVENT corresponde al circuito de ventas.

   CREACIÓN DESDE CERO:
   Este archivo define únicamente la tabla actual produ.
   No migra datos históricos ni modifica o elimina otras tablas.
============================================================ */

USE catal;

CREATE TABLE produ (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    descr VARCHAR(150) NOT NULL,
    codig VARCHAR(80) NULL,
    origi VARCHAR(80) NULL,
    medid VARCHAR(100) NULL,
    super VARCHAR(20) NULL,
    equiv VARCHAR(80) NULL,
    pcomp DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    pvent DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX ix061 (codig),
    INDEX ix062 (origi),
    INDEX ix063 (equiv)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
