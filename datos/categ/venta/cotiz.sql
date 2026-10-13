/* ============================================================
   Ruta: datos/categ/venta/cotiz.sql
   Módulo: Ventas / Cotización
   Base: VENTA

   RESPONSABILIDAD:
   Crear las tablas exclusivas de Cotización.

   REGLAS:
   - Cotización no reserva stock.
   - Cotización no modifica stock.
   - Cotización no genera salss.
   - Guardar registra la cotización.
   - Cancelar cambia únicamente su estado.
   - Serie C001 se administra mediante venta.numer.

   NOMBRES INTERNOS:
   - cotiz = cabecera.
   - dtall = detalle.
============================================================ */

CREATE TABLE IF NOT EXISTS cotiz (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    clien INT UNSIGNED NOT NULL,
    vende INT UNSIGNED NOT NULL,
    placa VARCHAR(7) NULL,
    serie VARCHAR(4) NOT NULL,
    numco INT UNSIGNED NOT NULL,
    fecha DATE NOT NULL,
    vigia DATE NOT NULL,
    estad ENUM('activa','cancelada') NOT NULL DEFAULT 'activa',
    obser VARCHAR(255) NULL,
    subto DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    igv DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    total DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    cread DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actua DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uqc01 (serie,numco),
    INDEX icli1 (clien),
    INDEX iven1 (vende),
    INDEX iest1 (estad),
    INDEX ifec1 (fecha),
    INDEX ivig1 (vigia),
    CONSTRAINT fk_cotiz_clien FOREIGN KEY (clien) REFERENCES clien(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dtall (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cotiz INT UNSIGNED NOT NULL,
    produ INT UNSIGNED NOT NULL,
    canti DECIMAL(14,3) NOT NULL,
    preun DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    total DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    INDEX icot1 (cotiz),
    INDEX ipro1 (produ),
    CONSTRAINT fdc01 FOREIGN KEY (cotiz) REFERENCES cotiz(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/* ============================================================
   MIGRACIÓN SEGURA — PLACA
============================================================ */
SET @colum := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='cotiz' AND column_name='placa');
SET @tampl := (SELECT CHARACTER_MAXIMUM_LENGTH FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='cotiz' AND column_name='placa');
SET @sqlx1 := IF(@colum=0,'ALTER TABLE cotiz ADD COLUMN placa VARCHAR(7) NULL AFTER vende',IF(@tampl<>7,'ALTER TABLE cotiz MODIFY COLUMN placa VARCHAR(7) NULL AFTER vende','SELECT 1'));
PREPARE stpl1 FROM @sqlx1; EXECUTE stpl1; DEALLOCATE PREPARE stpl1;

/* ============================================================
   MIGRACIÓN — RELACIÓN COTIZACIÓN / CLIENTE
============================================================ */

SET @fco := (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='cotiz'
      AND CONSTRAINT_NAME='fk_cotiz_clien'
);
SET @sco := IF(@fco=0,
    'ALTER TABLE cotiz ADD CONSTRAINT fk_cotiz_clien FOREIGN KEY (clien) REFERENCES clien(id) ON UPDATE CASCADE ON DELETE RESTRICT',
    'SELECT 1');
PREPARE xco FROM @sco; EXECUTE xco; DEALLOCATE PREPARE xco;
