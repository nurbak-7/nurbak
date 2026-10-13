/* ============================================================
   Ruta: datos/categ/repor/audit.sql
   Módulo: Reportes / Auditoría
   Base: repor

   RESPONSABILIDAD:
   Conservar el historial de acciones relevantes del sistema.

   CONEXIONES LÓGICAS:
   - usuar → login.usuar.id
   - módulo / acción → módulo que originó el evento.
   - referencia → identificador del registro afectado.

   IMPORTANTE:
   Auditoría no reemplaza las tablas operativas.
============================================================ */

USE repor;

CREATE TABLE IF NOT EXISTS audit (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuar INT UNSIGNED NULL,
    modul VARCHAR(40) NOT NULL,
    accio VARCHAR(60) NOT NULL,
    refer VARCHAR(80) NULL,
    detal TEXT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX ix071 (usuar),
    INDEX ix072 (modul),
    INDEX ix073 (accio),
    INDEX ix074 (refer),
    INDEX ix075 (fecha)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;