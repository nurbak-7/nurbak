/* ============================================================
   Ruta: datos/categ/catal/produ.sql
   Módulo: Catálogo
   Submódulo: Productos

   FUNCIÓN:
   Definir la información propia del artículo de CATAL y
   administrar los identificadores mediante una secuencia
   transaccional independiente.

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
   - MOTOR y MODEL se almacenan en vehic y se relacionan
     mediante la tabla equiv.
   - MARCA pertenece al intercambio IMEXT; no se almacena aquí.
   - QTY pertenece a INVENTARIO y se consulta mediante stock.
   - SUPER es textual; admite valores como ST, STD, HG, 6UNI,
     12UNI, 24UNI, 0.00, 0.25, 0.50, 0.75 y 1.00.
   - PCOMP corresponde al circuito de compras.
   - PVENT corresponde al circuito de ventas.
   - SEPRO controla exclusivamente la secuencia de IDs de produ.

   CONTROL DE IDENTIFICADORES:
   - produ.id no utiliza AUTO_INCREMENT.
   - Cada ID se reserva mediante la tabla sepro.
   - La reserva y la inserción del producto deben ejecutarse
     dentro de la misma transacción.
   - Si la operación falla, el ROLLBACK debe revertir ambas.
   - Todas las rutas de alta deben utilizar el mismo mecanismo.

   CREACIÓN DESDE CERO:
   Este archivo define las tablas sepro y produ.
   No migra datos históricos ni modifica o elimina otras tablas.

   IMPORTANTE:
   La aplicación debe implementar la reserva transaccional
   de IDs antes de utilizar este esquema.
============================================================ */


/* ============================================================
   NIVEL 1
   SECUENCIA EXCLUSIVA DE PRODUCTOS
============================================================ */

CREATE TABLE sepro (
    id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
    valor INT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


/* CONTADOR INICIAL PARA UNA INSTALACIÓN NUEVA */

INSERT INTO sepro (id, valor)
VALUES (1, 0);


/* ============================================================
   NIVEL 2
   PRODUCTOS
============================================================ */

CREATE TABLE produ (
    id INT UNSIGNED NOT NULL,
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
    INDEX ix063 (equiv),
    INDEX ix064 (descr, codig, origi, medid, super, equiv)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;