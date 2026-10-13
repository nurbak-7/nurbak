/* ============================================================
   Ruta: datos/categ/venta/opera.sql
   Módulo: Ventas / Operaciones
   Base: VENTA

   RESPONSABILIDAD:
   Guardar la operación comercial y sus productos para el flujo
   de emisión de Boleta y Factura del área FACTU.

   REGLA:
   opera no es un documento numerado.
   La operación conserva los datos del comprador y los productos
   que se utilizarán en el proceso de emisión correspondiente.

   CLIENTE:
   clien es opcional y sirve como referencia al maestro de clientes.
   La operación conserva los datos del comprador utilizados:
   tipod, docum, razon y direc.

   NUMERACIÓN:
   La numeración de los documentos tributarios se administra
   desde FACTU.numer: bolet / factu / ncred / ndebi / gremi.
============================================================ */

CREATE TABLE IF NOT EXISTS opera (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    clien INT UNSIGNED NULL,
    tipod VARCHAR(10) NULL,
    docum VARCHAR(20) NULL,
    razon VARCHAR(150) NULL,
    direc VARCHAR(180) NULL,
    vende INT UNSIGNED NULL,
    almac INT UNSIGNED NOT NULL,
    placa VARCHAR(20) NULL,
    estad ENUM('borrador','confirmada','anulada') NOT NULL DEFAULT 'borrador',
    fecha DATE NOT NULL,
    emisi DATETIME NULL,
    subto DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    igv DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    total DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    obser VARCHAR(255) NULL,
    INDEX ix004 (clien),
    INDEX ix005 (vende),
    INDEX ix006 (placa),
    INDEX ix007 (almac),
    INDEX ix009 (estad),
    INDEX ix010 (fecha),
    CONSTRAINT fk_opera_clien
        FOREIGN KEY (clien) REFERENCES clien(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS detop (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    opera INT UNSIGNED NOT NULL,
    produ INT UNSIGNED NOT NULL,
    canti DECIMAL(14,3) NOT NULL,
    preun DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    subto DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    INDEX ix011 (opera),
    INDEX ix012 (produ),
    CONSTRAINT fk010
        FOREIGN KEY (opera) REFERENCES opera(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;