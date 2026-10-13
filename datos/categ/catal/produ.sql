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
   - equiv : Equivalente.
   - pcomp : Precio de compra.
   - pvent : Precio de venta.
   - fecha : Fecha de actualización de precios/datos.

   IMPORTANTE:
   PCOMP y PVENT pertenecen al mismo artículo pero mantienen
   responsabilidades diferentes:
   - PCOMP → circuito de compras.
   - PVENT → circuito de ventas.

   MOTOR y MODEL no se duplican en produ.
   Se almacenan en vehic y se obtienen mediante equiv al construir
   la vista interna de CATAL.

   MARCA pertenece al intercambio IMEXT. No forma parte de la vista
   interna de Artículos.

   QTY NO se almacena aquí:
   QTY pertenece a INVENTARIO y se consulta mediante stock.

   SUPER:
   Se almacena como texto porque puede contener:
   ST, STD, HG, 6UNI, 12UNI, 24UNI, 0.00, 0.25, 0.50, 0.75, 1.00.

   CICLO:
   CATAL → produ
   INVENTARIO → stock
   VENTAS → pvent
   COMPRAS → pcomp
============================================================ */

CREATE TABLE IF NOT EXISTS produ (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    descr VARCHAR(150) NOT NULL,
    codig VARCHAR(80) NULL,
    origi VARCHAR(80) NULL,
    medid VARCHAR(100) NULL,
    super VARCHAR(20) NULL,
    equiv VARCHAR(80) NULL,
    pcomp DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    pvent DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX ix061 (codig),
    INDEX ix062 (origi),
    INDEX ix063 (equiv)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


/* ============================================================
   NORMALIZACIÓN ACTUAL
   CODIG es el nombre definitivo del código del artículo.
   El nombre definitivo del código es codig.
============================================================ */

/* SUPER: mantenerlo explícitamente como texto. */
ALTER TABLE produ
    MODIFY COLUMN super VARCHAR(20) NULL;


/* ============================================================
   COLUMNAS DE PRECIO Y FECHA
   Se agregan antes de leer la tabla histórica `preci`.
============================================================ */

SET @hay_pcomp := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE()
      AND TABLE_NAME='produ'
      AND COLUMN_NAME='pcomp'
);

SET @sql_pcomp := IF(
    @hay_pcomp=0,
    'ALTER TABLE produ ADD COLUMN pcomp DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER equiv',
    'SELECT 1'
);

PREPARE stmt_pcomp FROM @sql_pcomp;
EXECUTE stmt_pcomp;
DEALLOCATE PREPARE stmt_pcomp;

SET @hay_pvent := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE()
      AND TABLE_NAME='produ'
      AND COLUMN_NAME='pvent'
);

SET @sql_pvent := IF(
    @hay_pvent=0,
    'ALTER TABLE produ ADD COLUMN pvent DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER pcomp',
    'SELECT 1'
);

PREPARE stmt_pvent FROM @sql_pvent;
EXECUTE stmt_pvent;
DEALLOCATE PREPARE stmt_pvent;

SET @hay_fecha := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE()
      AND TABLE_NAME='produ'
      AND COLUMN_NAME='fecha'
);

SET @sql_fecha := IF(
    @hay_fecha=0,
    'ALTER TABLE produ ADD COLUMN fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER pvent',
    'SELECT 1'
);

PREPARE stmt_fecha FROM @sql_fecha;
EXECUTE stmt_fecha;
DEALLOCATE PREPARE stmt_fecha;


/* ============================================================
   PRECI → PRODU
   Si existe la tabla histórica `preci`, toma el último precio
   activo y lo lleva a produ.
============================================================ */

SET @hay_preci := (
    SELECT COUNT(*)
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'preci'
);

SET @sql_preci := IF(
    @hay_preci > 0,
    'UPDATE produ p
     INNER JOIN (
         SELECT x.produ,
                x.pcomp,
                x.pvent,
                x.fecre
         FROM preci x
         INNER JOIN (
             SELECT produ, MAX(id) id
             FROM preci
             WHERE activ=1
             GROUP BY produ
         ) u ON u.id=x.id
     ) pr ON pr.produ=p.id
     SET p.pcomp=pr.pcomp,
         p.pvent=pr.pvent,
         p.fecha=pr.fecre',
    'SELECT 1'
);

PREPARE stmt_preci FROM @sql_preci;
EXECUTE stmt_preci;
DEALLOCATE PREPARE stmt_preci;


/* ============================================================
   ELIMINAR TABLA HISTÓRICA PRECI
   Solo después de haber migrado sus valores.
============================================================ */

SET @sql_drop_fk := IF(
    @hay_preci > 0,
    IF(
        EXISTS (
            SELECT 1
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA=DATABASE()
              AND TABLE_NAME='preci'
              AND CONSTRAINT_NAME='fk020'
              AND CONSTRAINT_TYPE='FOREIGN KEY'
        ),
        'ALTER TABLE preci DROP FOREIGN KEY fk020',
        'SELECT 1'
    ),
    'SELECT 1'
);

PREPARE stmt_drop_fk FROM @sql_drop_fk;
EXECUTE stmt_drop_fk;
DEALLOCATE PREPARE stmt_drop_fk;

SET @sql_drop_preci := IF(
    @hay_preci > 0,
    'DROP TABLE preci',
    'SELECT 1'
);

PREPARE stmt_drop_preci FROM @sql_drop_preci;
EXECUTE stmt_drop_preci;
DEALLOCATE PREPARE stmt_drop_preci;
