-- ============================================================
-- Migración 001: Soporte a correcciones de navegación y rutas
-- Compatible con MySQL 5.7+
-- ============================================================
-- Aplica los siguientes ajustes sin afectar la funcionalidad
-- existente:
--   1. Índice compuesto en affiliates(user_id, state) para
--      consultas rápidas de credencial por usuario autenticado.
--   2. Vista v_user_credential para obtener la credencial activa
--      del usuario en sesión (útil en la barra lateral).
--   3. Índice compuesto en activity_log para el módulo Config.
-- ============================================================

SET NAMES utf8mb4;

-- ─── Helper: procedimiento para agregar índice si no existe ──────────────────
-- Evita fallos al ejecutar la migración más de una vez.

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

-- ─── 1. Índice para búsqueda de afiliado por usuario ────────────────────────
-- Permite localizar el ID de afiliado del usuario en sesión en O(log n).
CALL `_cdhq_add_index_if_not_exists`('affiliates', 'idx_affiliates_user_state', '`user_id`, `state`');

-- ─── 2. Vista: credencial activa por usuario ────────────────────────────────
-- Devuelve exactamente UNA fila por usuario: la suscripción activa con la
-- fecha de vencimiento más lejana (o NULL si no hay suscripción activa).
-- Útil para el enlace dinámico "Mi Credencial" en la barra lateral.

CREATE OR REPLACE VIEW `v_user_credential` AS
SELECT
    u.id                                        AS user_id,
    a.id                                        AS affiliate_id,
    a.affiliate_number,
    CONCAT(a.first_name, ' ', a.last_name)      AS full_name,
    a.state                                     AS affiliate_state,
    s.id                                        AS subscription_id,
    s.status                                    AS subscription_status,
    s.end_date                                  AS subscription_end
FROM `users`  u
JOIN `affiliates` a ON a.user_id = u.id
LEFT JOIN `subscriptions` s
       ON s.id = (
           SELECT id FROM `subscriptions`
           WHERE affiliate_id = a.id
             AND status = 'ACTIVA'
           ORDER BY end_date DESC
           LIMIT 1
       );

-- ─── 3. Índice compuesto en activity_log ────────────────────────────────────
-- El módulo de Configuración consulta activity_log con JOIN a users y
-- ordenamiento por created_at. Este índice compuesto mejora esa consulta.
CALL `_cdhq_add_index_if_not_exists`('activity_log', 'idx_al_user_date', '`user_id`, `created_at`');

-- ─── Limpieza del procedimiento auxiliar ────────────────────────────────────
DROP PROCEDURE IF EXISTS `_cdhq_add_index_if_not_exists`;
