/* ============================================================
   Ruta: datos/categ/catal/equiv.sql
   Módulo: Catálogo
   Submódulo: Equiv

   FUNCIÓN:
   Relacionar un producto con un vehículo para indicar que esa pieza
   se aplica a ese vehículo.

   TABLA:
   equiv → relación Producto ↔ Vehículo.

   IMPORTANTE:
   Esta tabla NO corresponde al campo "equiv" de la tabla produ.
   - produ.equiv = código alternativo/equivalente de la misma pieza.
   - equiv = relación entre una pieza y un vehículo.

   CICLO QUE CUMPLE:
   1. Se selecciona un producto existente de produ.
   2. Se selecciona un vehículo existente de vehic.
   3. Se registra la relación mediante produ y vehic.
   4. No se duplican los datos completos del producto ni del vehículo.
   5. Al consultar un producto se pueden obtener sus vehículos.
   6. Al consultar un vehículo se pueden obtener sus productos.

   CONEXIONES CON OTROS ARCHIVOS:
   - datos/categ/catal/produ.sql → equiv.produ referencia produ.id.
   - datos/categ/catal/vehic.sql → equiv.vehic referencia vehic.id.

   EJEMPLO:
   produ = 8 + vehic = 3
   significa que el producto 8 está registrado para el vehículo 3.

   ORDEN:
   Importar después de datos/categ/catal/produ.sql y datos/categ/catal/vehic.sql.
============================================================ */

CREATE TABLE IF NOT EXISTS equiv (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    produ INT UNSIGNED NOT NULL,
    vehic INT UNSIGNED NOT NULL,
    UNIQUE KEY uq013 (produ, vehic),
    INDEX ix069 (produ),
    INDEX ix070 (vehic),
    CONSTRAINT fk021
        FOREIGN KEY (produ) REFERENCES produ(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk022
        FOREIGN KEY (vehic) REFERENCES vehic(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
