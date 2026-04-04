-- ============================================================
-- Migración 002: Agregar campos municipio y estado_mexico
--                a la tabla affiliates
-- Compatible con MySQL 5.7
-- ============================================================

SET NAMES utf8mb4;

-- ─── 1. Columna municipio ────────────────────────────────────────────────────
SET @__cdhq_db = DATABASE();

SET @__cdhq_col_exists = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @__cdhq_db
    AND TABLE_NAME   = 'affiliates'
    AND COLUMN_NAME  = 'municipio'
);

SET @__cdhq_sql = IF(
  @__cdhq_col_exists = 0,
  'ALTER TABLE `affiliates` ADD COLUMN `municipio` VARCHAR(80) NULL DEFAULT NULL COMMENT ''Municipio de residencia del afiliado'' AFTER `address`',
  'SELECT 1'
);

PREPARE __cdhq_stmt FROM @__cdhq_sql;
EXECUTE __cdhq_stmt;
DEALLOCATE PREPARE __cdhq_stmt;

-- ─── 2. Columna estado_mexico ────────────────────────────────────────────────
SET @__cdhq_col_exists = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @__cdhq_db
    AND TABLE_NAME   = 'affiliates'
    AND COLUMN_NAME  = 'estado_mexico'
);

SET @__cdhq_sql = IF(
  @__cdhq_col_exists = 0,
  'ALTER TABLE `affiliates` ADD COLUMN `estado_mexico` VARCHAR(80) NULL DEFAULT NULL COMMENT ''Estado de la República Mexicana'' AFTER `municipio`',
  'SELECT 1'
);

PREPARE __cdhq_stmt FROM @__cdhq_sql;
EXECUTE __cdhq_stmt;
DEALLOCATE PREPARE __cdhq_stmt;

-- ─── 3. Índice para consultas del dashboard ──────────────────────────────────
-- Índice idempotente y sin choque de collations: comparación BINARY.
DROP PROCEDURE IF EXISTS `_cdhq_add_index_if_not_exists`;

DELIMITER //
CREATE PROCEDURE `_cdhq_add_index_if_not_exists`(
    IN tbl  VARCHAR(64),
    IN idx  VARCHAR(64),
    IN cols TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND BINARY TABLE_NAME = BINARY tbl
          AND BINARY INDEX_NAME = BINARY idx
    ) THEN
        SET @sql = CONCAT('ALTER TABLE `', tbl, '` ADD INDEX `', idx, '` (', cols, ')');
        PREPARE _stmt FROM @sql;
        EXECUTE _stmt;
        DEALLOCATE PREPARE _stmt;
    END IF;
END//
DELIMITER ;

CALL `_cdhq_add_index_if_not_exists`('affiliates', 'idx_affiliates_estado_mexico', '`estado_mexico`');

DROP PROCEDURE IF EXISTS `_cdhq_add_index_if_not_exists`;
