-- ============================================================
-- Migración 002: Agregar campos municipio y estado_mexico
--                a la tabla affiliates
-- Compatible con MySQL 5.7+
-- ============================================================
-- Añade dos columnas opcionales para registrar la ubicación
-- geográfica del afiliado dentro de México, sin afectar la
-- funcionalidad existente.
-- ============================================================

SET NAMES utf8mb4;

-- ─── 1. Columna municipio ────────────────────────────────────────────────────
ALTER TABLE `affiliates`
    ADD COLUMN IF NOT EXISTS `municipio` VARCHAR(80) NULL DEFAULT NULL
        COMMENT 'Municipio de residencia del afiliado'
        AFTER `address`;

-- ─── 2. Columna estado_mexico ────────────────────────────────────────────────
-- Se llama estado_mexico para distinguirla de `state` (estado del afiliado).
ALTER TABLE `affiliates`
    ADD COLUMN IF NOT EXISTS `estado_mexico` VARCHAR(80) NULL DEFAULT NULL
        COMMENT 'Estado de la República Mexicana'
        AFTER `municipio`;

-- ─── 3. Índice para consultas del dashboard ──────────────────────────────────
-- Permite agrupar afiliados por estado de México sin escanear la tabla completa.
DROP PROCEDURE IF EXISTS `_cdhq_add_index_if_not_exists`;

DELIMITER //
CREATE PROCEDURE `_cdhq_add_index_if_not_exists`(
    IN tbl  VARCHAR(64),
    IN idx  VARCHAR(64),
    IN cols TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = tbl
          AND INDEX_NAME   = idx
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
