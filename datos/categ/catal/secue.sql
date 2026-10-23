/* ============================================================
   Ruta: datos/categ/catal/secue.sql
   Función: Inicializar el contador transaccional para ASINC, LOTES y ERROR.

   MIGRACIÓN PARA LA BASE EXISTENTE EN TiDB
   - Hacer copia de seguridad antes de ejecutar.
   - Pausar temporalmente TODOS los escritores de ASINC, LOTES y ERROR.
   - Ejecutar UNA VEZ antes de desplegar el backend corregido.
   - No elimina, modifica ni renumera los registros existentes.
   - Inicializa cada contador desde el mayor ID ya guardado.
   - Si se ejecuta otra vez, no reduce un contador existente.
   ============================================================ */

CREATE TABLE IF NOT EXISTS catal.secue (
    clave VARCHAR(5) NOT NULL PRIMARY KEY,
    valor BIGINT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

INSERT INTO catal.secue (clave, valor)
SELECT 'asinc', COALESCE(MAX(id), 0)
FROM catal.asinc
ON DUPLICATE KEY UPDATE
    valor = GREATEST(valor, VALUES(valor));

INSERT INTO catal.secue (clave, valor)
SELECT 'lotes', COALESCE(MAX(id), 0)
FROM catal.lotes
ON DUPLICATE KEY UPDATE
    valor = GREATEST(valor, VALUES(valor));

INSERT INTO catal.secue (clave, valor)
SELECT 'error', COALESCE(MAX(id), 0)
FROM catal.error
ON DUPLICATE KEY UPDATE
    valor = GREATEST(valor, VALUES(valor));

SELECT clave, valor
FROM catal.secue
ORDER BY clave;
