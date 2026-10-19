
/* ============================================================
   Ruta: datos/categ/inven/almac.sql
   Módulo: Inventario / Almacenes

   FUNCIÓN:
   Registrar los almacenes de cada tienda.

   MODELO:
   TIEND 1:N ALMAC 1:N STOCK

   REGLAS:
   - tiend identifica la tienda propietaria del almacén.
   - prin = 1 identifica el almacén principal de la tienda.
   - Una tienda puede tener varios almacenes.
   - Una tienda puede tener como máximo un almacén principal.
   - estad = 1 significa almacén activo.
   - No existe FK física hacia LOGIN.TIEND porque LOGIN e INVENTARIO
     mantienen bases independientes; tiend es una referencia lógica.
============================================================ */

USE inven;

CREATE TABLE IF NOT EXISTS almac (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tiend INT UNSIGNED NOT NULL,
    nombr VARCHAR(100) NOT NULL,
    descr VARCHAR(200) NULL,
    direc VARCHAR(200) NULL,
    estad TINYINT(1) NOT NULL DEFAULT 1,
    prin TINYINT(1) NOT NULL DEFAULT 0,
    prti INT UNSIGNED
        GENERATED ALWAYS AS (
            CASE WHEN prin = 1 THEN tiend ELSE NULL END
        ) STORED,
    fecre DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uq008 (prti),
    INDEX ix036 (tiend),
    INDEX ix037 (nombr),
    INDEX ix038 (estad),
    INDEX ix039 (prin),

    CONSTRAINT ck001 CHECK (estad IN (0,1)),
    CONSTRAINT ck002 CHECK (prin IN (0,1))
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

/* ============================================================
   FIN — ALMAC
============================================================ */
