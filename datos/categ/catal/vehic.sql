/* ============================================================
   Ruta: datos/categ/catal/vehic.sql
   Módulo: Catálogo
   Submódulo: Vehículos

   FUNCIÓN:
   Definir los vehículos utilizados por el catálogo para identificar
   la aplicación de cada producto.

   TABLA:
   vehic → vehículos del catálogo.

   CAMPOS PRINCIPALES:
   - marca : Marca de intercambio IMEXT. No se muestra en la vista interna de Artículos.
   - motor : Motor.
   - model : Modelo.
   - anio  : Año.
   - gdesd  : Generación desde.
   - ghast  : Generación hasta.

   EJEMPLO:
   13-2005-18 representa:
   - anio_modelo = 2013
   - generacion_desde = 2005
   - generacion_hasta = 2018

   CICLO QUE CUMPLE:
   1. Se registra un vehículo una sola vez.
   2. El vehículo recibe un id único.
   3. datos/categ/catal/equiv.sql utiliza ese id para asociarlo con productos.
   4. Las consultas pueden mostrar motor, model y año sin repetir
      esos datos en cada producto.

   CONEXIONES CON OTROS ARCHIVOS:
   - datos/categ/catal/equiv.sql → equiv.vehic_id referencia vehic.id.
   - datos/categ/catal/produ.sql → contiene los productos que se asociarán.
   - IMEXT utiliza marca para agrupar hojas XLSX por marca.
   - Inventario, compras, ventas y facturación podrán consultar la
     aplicación del producto mediante la relación del catálogo.

   ORDEN:
   Debe existir antes de importar datos/categ/catal/equiv.sql.
============================================================ */

CREATE TABLE IF NOT EXISTS vehic (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    marca CHAR(2) NULL DEFAULT NULL,
    motor VARCHAR(80) NOT NULL,
    model VARCHAR(100) NOT NULL,
    anio SMALLINT UNSIGNED NOT NULL,
    gdesd SMALLINT UNSIGNED NOT NULL,
    ghast SMALLINT UNSIGNED NOT NULL,
    INDEX ix065 (marca),
    INDEX ix066 (motor),
    INDEX ix067 (model),
    INDEX ix068 (anio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
