/* ============================================================
   IDENTIDAD USUARIO

   Nombres, apellidos, DNI y celular se administran en usuar.sql.
   ============================================================ */

/* ============================================================
   Ruta: datos/login/login.sql
   Módulo: Seguridad / Autorización

   LOGIN — NÚCLEO DE SEGURIDAD

   RESPONSABILIDAD:
   - Crear la base de datos LOGIN.
   - Definir roles.
   - Definir permisos.
   - Definir la relación rol / permiso.
   - Cargar los permisos base del sistema.

   NO CONTIENE:
   - Usuarios → usuar.sql
   - Recuperación → recup.sql
   - Tiendas → tien.sql

   ORDEN DE INSTALACIÓN:
   1. login.sql
   2. usuar.sql
   3. recup.sql
   4. tien.sql
============================================================ */

/* ============================================================
   NIVEL 1 — BASE DE DATOS
============================================================ */

DROP DATABASE IF EXISTS login;

CREATE DATABASE login
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE login;

/* ============================================================
   NIVEL 2 — ROL
============================================================ */

CREATE TABLE rol (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombr VARCHAR(50) NOT NULL UNIQUE,
    descr VARCHAR(150) NULL,
    totac TINYINT(1) NOT NULL DEFAULT 0,
    activ TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

/* ============================================================
   NIVEL 3 — PERMISO
============================================================ */

CREATE TABLE permi (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codig VARCHAR(80) NOT NULL UNIQUE,
    nombr VARCHAR(100) NOT NULL,
    descr VARCHAR(150) NULL,
    activ TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

/* ============================================================
   NIVEL 4 — PERMISOS BASE DEL SISTEMA
============================================================ */

INSERT INTO permi (codig, nombr, descr, activ)
VALUES
    ('USUAV', 'Ver usuarios', 'Consultar usuarios del sistema', 1),
    ('USUAG', 'Gestionar usuarios', 'Crear, editar y administrar usuarios', 1),
    ('TIENV', 'Ver tiendas', 'Consultar tiendas del sistema', 1),
    ('TIENG', 'Gestionar tiendas', 'Crear, editar y administrar tiendas', 1),
    ('PERMV', 'Ver permisos', 'Consultar permisos del sistema', 1),
    ('PERMG', 'Gestionar permisos', 'Administrar permisos y asignaciones', 1),
    ('CATEG', 'Gestionar categorías', 'Administrar categorías del catálogo', 1),
    ('CONFG', 'Gestionar configuración', 'Administrar configuración del sistema', 1),
    ('PRODV', 'Ver productos', 'Consultar productos del catálogo', 1),
    ('PRODG', 'Gestionar productos', 'Crear, editar y administrar productos', 1),
    ('VEHIV', 'Ver vehículos', 'Consultar vehículos del catálogo', 1),
    ('VEHIG', 'Gestionar vehículos', 'Crear, editar y administrar vehículos', 1),
    ('EQUIG', 'Gestionar equivalencia', 'Administrar equivalencia de productos y vehículos', 1),
    ('IMEXG', 'Gestionar importación y exportación', 'Gestionar procesos de importación y exportación del catálogo', 1),
    ('ALMAV', 'Ver almacenes', 'Consultar almacenes', 1),
    ('ALMAG', 'Gestionar almacenes', 'Crear, editar y administrar almacenes', 1),
    ('STOCV', 'Ver stock', 'Consultar existencias por almacén o tienda', 1),
    ('STOCG', 'Gestionar stock', 'Administrar existencias', 1),
    ('MOVIV', 'Ver movimientos', 'Consultar movimientos de inventario', 1),
    ('MOVIG', 'Gestionar movimientos', 'Registrar y administrar movimientos de inventario', 1),
    ('PROVV', 'Ver proveedores', 'Consultar proveedores', 1),
    ('PROVG', 'Gestionar proveedores', 'Crear, editar y administrar proveedores', 1),
    ('OCOMV', 'Ver órdenes internas', 'Consultar órdenes internas entre tiendas propias', 1),
    ('OCOMG', 'Gestionar órdenes internas', 'Crear, editar y administrar órdenes internas', 1),
    ('COMEV', 'Ver compra externa', 'Consultar compras externas', 1),
    ('ORCEV', 'Ver órdenes de compra externas', 'Consultar órdenes de compra externas', 1),
    ('ORCEG', 'Gestionar órdenes de compra externas', 'Crear y administrar órdenes de compra externas', 1),
    ('COMEG', 'Gestionar compra externa', 'Crear, editar y administrar compras externas', 1),
    ('RECEV', 'Ver recepción', 'Consultar recepciones de compras', 1),
    ('RECEG', 'Gestionar recepción', 'Registrar y administrar recepción de compras', 1),
    ('CLIEV', 'Ver clientes', 'Consultar clientes', 1),
    ('CLIEG', 'Gestionar clientes', 'Crear, editar y administrar clientes', 1),
    ('COTIG', 'Gestionar cotización', 'Crear, editar, guardar y cancelar cotizaciones', 1),
    ('VENTG', 'Gestionar ventas', 'Registrar y administrar operaciones de venta', 1),
    ('NOVEV', 'Ver notas de venta', 'Consultar notas de venta', 1),
    ('NOVEG', 'Gestionar notas de venta', 'Crear, editar y administrar notas de venta', 1),
    ('DEVOG', 'Gestionar devoluciones', 'Registrar y administrar devoluciones', 1),
    ('FACTV', 'Ver facturas', 'Consultar facturas', 1),
    ('FACTG', 'Gestionar facturas', 'Crear, editar y administrar facturas', 1),
    ('BOLEV', 'Ver boletas', 'Consultar boletas', 1),
    ('BOLEG', 'Gestionar boletas', 'Crear, editar y administrar boletas', 1),
    ('NCREG', 'Gestionar notas de crédito', 'Crear y administrar notas de crédito', 1),
    ('NDEBG', 'Gestionar notas de débito', 'Crear y administrar notas de débito', 1),
    ('GUIAG', 'Gestionar guías de remisión', 'Crear y administrar guías de remisión', 1),
    ('SUNAG', 'Gestionar SUNAT', 'Ejecutar operaciones autorizadas con SUNAT', 1),
    ('PAGOV', 'Ver pagos', 'Consultar registros de pago', 1),
    ('PAGOG', 'Gestionar pagos', 'Registrar y administrar pagos', 1),
    ('CAJAG', 'Gestionar caja', 'Administrar operaciones de caja', 1),
    ('CREDV', 'Ver créditos', 'Consultar créditos', 1),
    ('CREDG', 'Gestionar créditos', 'Crear y administrar créditos', 1),
    ('INFOV', 'Ver informes', 'Consultar informes del sistema', 1),
    ('AUDIV', 'Ver auditoría', 'Consultar registros de auditoría', 1);

/* ============================================================
   NIVEL 5 — ROL / PERMISO
============================================================ */

CREATE TABLE rolpe (
    rol INT UNSIGNED NOT NULL,
    permi INT UNSIGNED NOT NULL,

    PRIMARY KEY (rol, permi),

    CONSTRAINT fk002
        FOREIGN KEY (rol)
        REFERENCES rol(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk003
        FOREIGN KEY (permi)
        REFERENCES permi(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

/* ============================================================
   FIN — LOGIN
============================================================ */