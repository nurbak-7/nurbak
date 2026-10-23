/* ============================================================
   Ruta: datos/categ/venta/devol.sql
   Módulo: Ventas / Devoluciones

   FUNCIÓN:
   Registrar la devolución solicitada por el cliente.

   La devolución se registra primero como pendiente. Su aplicación
   física al inventario se hará cuando la devolución sea aprobada.

   RELACIONES LÓGICAS:
   - opera → VENTA.opera.id
   - clien → VENTA.clien.id
   - almac → INVEN.almac.id
   - produ → catal.produ.id
============================================================ */

CREATE TABLE IF NOT EXISTS devol (
    id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    opera  INT UNSIGNED NULL,
    clien  INT UNSIGNED NOT NULL,
    almac  INT UNSIGNED NOT NULL,

    estad  ENUM('pendiente', 'aprobada', 'anulada')
           NOT NULL DEFAULT 'pendiente',

    fecha  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    motvo  VARCHAR(255) NULL,

    INDEX ix015 (opera),
    INDEX ix016 (clien),
    INDEX ix017 (almac),
    INDEX ix018 (estad),

    CONSTRAINT fk_devol_opera
        FOREIGN KEY (opera)
        REFERENCES opera(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_devol_clien
        FOREIGN KEY (clien)
        REFERENCES clien(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS detdv (
    id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    devol  INT UNSIGNED NOT NULL,
    produ  INT UNSIGNED NOT NULL,
    canti  DECIMAL(14,3) NOT NULL,

    INDEX ix019 (devol),
    INDEX ix020 (produ),

    CONSTRAINT fk011
        FOREIGN KEY (devol)
        REFERENCES devol(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;