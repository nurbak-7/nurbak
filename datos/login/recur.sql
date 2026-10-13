/* ============================================================
   RECUPERACIÓN

   La recuperación referencia USUA por usuar.id.
   No duplica DNI ni celular.
   ============================================================ */

/* ============================================================
   Ruta: datos/login/recur.sql
   Módulo: Seguridad / Recuperación

   RECU — RECUPERACIÓN DE ACCESO

   RESPONSABILIDAD:
   - Crear la tabla de tokens temporales de recuperación.

   DEPENDENCIA:
   - Requiere `usuar` creada por usuar.sql.
   - La configuración de correo/OAuth permanece en confi/recup/.
============================================================ */

USE login;

/* ============================================================
   NIVEL 1 — RECUPERACIÓN
============================================================ */

CREATE TABLE recup (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuar INT UNSIGNED NOT NULL,
    hash CHAR(64) NOT NULL UNIQUE,
    expir DATETIME NOT NULL,
    usado TINYINT(1) NOT NULL DEFAULT 0,
    cread TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk001
        FOREIGN KEY (usuar)
        REFERENCES usuar(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    INDEX ix001 (usuar),
    INDEX ix002 (expir)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

/* ============================================================
   FIN — RECU
============================================================ */