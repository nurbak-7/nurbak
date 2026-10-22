/* ============================================================
   Ruta: datos/categ/catal/asinc.sql
   Módulo: Catálogo / Procesos asíncronos

   Función:
   Crear la estructura utilizada por JOB, CRON, WORKER e IMEXT
   para los procesos de importación y exportación.

   TABLAS:
   - asinc   → JOB / proceso asíncrono.
   - lotes   → WORKER / bloques de procesamiento.
   - error   → WORKER / errores del proceso.

   FLUJO:
   IMEXT → JOB → CRON → WORKER
   ============================================================ */


/* ============================================================
   NIVEL 2
   ÁREA: JOB
   PROCESOS ASÍNCRONOS
   ============================================================ */

CREATE TABLE asinc (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    tipo VARCHAR(30) NOT NULL,
    modo VARCHAR(20) NOT NULL DEFAULT 'AMBOS',
    estado VARCHAR(20) NOT NULL DEFAULT 'PENDIENTE',

    tiend INT UNSIGNED DEFAULT NULL,

    archivo VARCHAR(500) DEFAULT NULL,

    total BIGINT UNSIGNED NOT NULL DEFAULT 0,
    proce BIGINT UNSIGNED NOT NULL DEFAULT 0,
    corre BIGINT UNSIGNED NOT NULL DEFAULT 0,
    erro BIGINT UNSIGNED NOT NULL DEFAULT 0,

    progr DECIMAL(5,2) NOT NULL DEFAULT 0.00,

    inten TINYINT UNSIGNED NOT NULL DEFAULT 0,

    fechc DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fechi DATETIME DEFAULT NULL,
    fechf DATETIME DEFAULT NULL,

    mens VARCHAR(500) DEFAULT NULL,

    INDEX idx_estado (estado),
    INDEX idx_tipo (tipo),
    INDEX idx_tiend (tiend),
    INDEX idx_fechc (fechc)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  AUTO_ID_CACHE=1;


/* ============================================================
   NIVEL 3
   ÁREA: CRON
   ============================================================ */

/*
   CRON no necesita una tabla propia.

   Detecta procesos PENDIENTE en `asinc` y solicita su ejecución
   al WORKER.
*/


/* ============================================================
   NIVEL 4
   ÁREA: WORKER
   LOTES DE PROCESAMIENTO
   ============================================================ */

CREATE TABLE lotes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    asinc_id INT UNSIGNED NOT NULL,

    numero INT UNSIGNED NOT NULL,

    inicio BIGINT UNSIGNED NOT NULL DEFAULT 0,
    cantidad INT UNSIGNED NOT NULL DEFAULT 0,

    estado VARCHAR(20) NOT NULL DEFAULT 'PENDIENTE',

    fechc DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fechi DATETIME DEFAULT NULL,
    fechf DATETIME DEFAULT NULL,

    INDEX idx_asinc (asinc_id),
    INDEX idx_estado (estado),

    CONSTRAINT fk071
        FOREIGN KEY (asinc_id)
        REFERENCES asinc(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


/* ============================================================
   NIVEL 5
   ÁREA: ERROR
   REGISTRO DE ERRORES
   ============================================================ */

CREATE TABLE error (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    asinc_id INT UNSIGNED NOT NULL,
    lote_id BIGINT UNSIGNED DEFAULT NULL,

    fila BIGINT UNSIGNED DEFAULT NULL,

    codigo VARCHAR(50) DEFAULT NULL,
    mens VARCHAR(500) NOT NULL,

    fechc DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_asinc (asinc_id),
    INDEX idx_lote (lote_id),
    INDEX idx_fila (fila),

    CONSTRAINT fk072
        FOREIGN KEY (asinc_id)
        REFERENCES asinc(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk073
        FOREIGN KEY (lote_id)
        REFERENCES lotes(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
