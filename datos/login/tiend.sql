
/* ============================================================
   Ruta: datos/login/tiend.sql
   Módulo: Administración / Tiendas

   RESPONSABILIDAD:
   - Administrar las tiendas.
   - Relacionar usuarios con una o varias tiendas.
   - Identificar un responsable principal por tienda.
   - Mantener la numeración propia de las tiendas.

   REGLAS:
   - Una tienda puede tener varios usuarios.
   - Un usuario puede pertenecer a una o varias tiendas.
   - respo = 1 identifica al responsable principal.
   - Una tienda operativa debe tener responsable y almacén principal
     activo; la validación de esa combinación corresponde al servicio.
   - Una tienda nueva inicia inactiva.
   - activ y respo solo admiten 0 o 1.
   - Una tienda puede tener como máximo un responsable.

   NOTA:
   El almacén pertenece a INVEN y guarda tiend como referencia lógica.
   No se duplica información de almacén en LOGIN.
============================================================ */

USE login;

/* ============================================================
   NIVEL 1 — TIENDA
============================================================ */

CREATE TABLE tiend (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codig VARCHAR(20) NOT NULL UNIQUE,
    nombr VARCHAR(100) NOT NULL,
    direc VARCHAR(200) NULL,
    telef VARCHAR(30) NULL,
    activ TINYINT(1) NOT NULL DEFAULT 0,
    cread TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT ck003 CHECK (activ IN (0,1))
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

/* ============================================================
   NIVEL 2 — SECUENCIA DE TIENDAS
============================================================ */

CREATE TABLE tisec (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ultim INT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

INSERT INTO tisec (ultim) VALUES (0);

/* ============================================================
   NIVEL 3 — USUARIO / TIENDA
============================================================ */

CREATE TABLE ustie (
    usuar INT UNSIGNED NOT NULL,
    tiend INT UNSIGNED NOT NULL,
    respo TINYINT(1) NOT NULL DEFAULT 0,

    resti INT UNSIGNED
        GENERATED ALWAYS AS (
            CASE WHEN respo = 1 THEN tiend ELSE NULL END
        ) STORED,

    PRIMARY KEY (usuar, tiend),
    UNIQUE KEY uq014 (resti),

    CONSTRAINT ck004 CHECK (respo IN (0,1)),

    CONSTRAINT fk004 FOREIGN KEY (usuar)
        REFERENCES usuar(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk005 FOREIGN KEY (tiend)
        REFERENCES tiend(id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

/* ============================================================
   FIN — TIEND
============================================================ */
