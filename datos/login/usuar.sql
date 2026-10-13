/* ============================================================
   Ruta: datos/login/usuar.sql
   Módulo: Seguridad / Usuarios

   USUA — CUENTAS Y OPERADORES

   RESPONSABILIDAD:
   - Crear la tabla de usuarios.
   - Relacionar usuarios con permisos individuales.
   - Mantener secuencias de códigos de cuenta.
   - Mantener historial de códigos de operador.
   - Crear las cuentas iniciales.

   DEPENDENCIAS:
   - Requiere que login.sql haya creado la base `login`.
   - Requiere las tablas `rol` y `permi`.

   SIGUIENTE:
   - recup.sql → recuperación de acceso.
   - tien.sql → tiendas y relación usuario / tienda.
============================================================ */

USE login;

/* ============================================================
   NIVEL 1 — USUARIO
============================================================ */

CREATE TABLE usuar (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empre INT UNSIGNED NULL,
    rol INT UNSIGNED NOT NULL,
    admic VARCHAR(20) NULL UNIQUE,
    operc VARCHAR(20) NULL UNIQUE,
    logi VARCHAR(60) NOT NULL UNIQUE,
    nombr VARCHAR(120) NOT NULL,
    apell VARCHAR(120) NOT NULL,
    dni VARCHAR(8) NULL,
    celul VARCHAR(15) NULL,
    clave VARCHAR(255) NOT NULL,
    corre VARCHAR(150) NULL UNIQUE,
    activ TINYINT(1) NOT NULL DEFAULT 1,
    fallo TINYINT UNSIGNED NOT NULL DEFAULT 0,
    bloqu DATETIME NULL,
    ultim DATETIME NULL,
    cread TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk006
        FOREIGN KEY (rol)
        REFERENCES rol(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    UNIQUE KEY uq001 (dni)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

/* ============================================================
   NIVEL 2 — USUARIO / PERMISO
============================================================ */

CREATE TABLE usape (
    usuar INT UNSIGNED NOT NULL,
    permi INT UNSIGNED NOT NULL,

    PRIMARY KEY (usuar, permi),

    CONSTRAINT fk007
        FOREIGN KEY (usuar)
        REFERENCES usuar(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk008
        FOREIGN KEY (permi)
        REFERENCES permi(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

/* ============================================================
   NIVEL 3 — SECUENCIAS DE CUENTAS
============================================================ */

CREATE TABLE seque (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tipo VARCHAR(20) NOT NULL UNIQUE,
    ultim INT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

INSERT INTO seque (tipo, ultim)
VALUES
    ('ADMINISTRADOR', 1),
    ('OPERADOR', 1);

/* ============================================================
   NIVEL 4 — HISTORIAL DE CÓDIGOS DE OPERADOR
============================================================ */

CREATE TABLE ophis (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    operc VARCHAR(20) NOT NULL,
    usuar INT UNSIGNED NULL,
    fecha TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk009
        FOREIGN KEY (usuar)
        REFERENCES usuar(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,

    INDEX ix003 (usuar)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

/* ============================================================
   NIVEL 5 — ROLES INICIALES
============================================================ */

INSERT INTO rol (nombr, descr, totac, activ)
VALUES
    ('ADMINISTRADOR', 'Control total del sistema', 1, 1),
    ('USUARIO', 'Usuario con permisos asignados', 0, 1);

/* ============================================================
   NIVEL 6 — CUENTA ADMINISTRADOR
============================================================ */

INSERT INTO usuar (
    empre, rol, admic, operc,
    logi, nombr, apell, dni, celul, clave, corre, activ
)
SELECT
    NULL,
    r.id,
    'A001',
    NULL,
    'lukaz',
    'Lukaz',
    'Contreras',
    NULL,
    NULL,
    '$2y$10$O4cZiAl/ZRPSSvTG0odoKuKT185LT/2KJqkHDMyksaYGT0j4ku.fa',
    NULL,
    1
FROM rol r
WHERE r.nombr = 'ADMINISTRADOR';

/* ============================================================
   NIVEL 7 — CUENTA USUARIO INICIAL
============================================================ */

INSERT INTO usuar (
    empre, rol, operc,
    logi, nombr, apell, dni, celul, clave, activ
)
SELECT
    NULL,
    r.id,
    '001',
    'nurbak',
    'Nurbak',
    'Automotive',
    NULL,
    NULL,
    '$2y$10$EcyKaLlpkFmpk.aeDGA76O6VDs.J7/PcQslXvDEJc1WMM4H3TecRW',
    1
FROM rol r
WHERE r.nombr = 'USUARIO';

INSERT INTO ophis (operc, usuar)
SELECT operc, id
FROM usuar
WHERE operc IS NOT NULL;

/* ============================================================
   FIN — USUA
============================================================ */