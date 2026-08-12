-- ═══════════════════════════════════════════════════════════════════════════
-- Migración 004 – Noticias, eventos y avisos hacia la aplicación móvil
--
-- Contexto: el portal ganó dos módulos que escriben en tablas que la app YA
-- lee: `content_posts` (pestaña «Novedades» y carrusel de inicio) y
-- `notifications` (avisos). Ninguna de las dos se crea desde el portal en
-- condiciones normales: vienen del repositorio CDHQ-Mobile
-- (`database/04_add_news_and_events.sql` y `database/01_extend_for_portal.sql`).
--
-- Esta migración existe para dos cosas:
--   1. Levantar `content_posts` si la instalación todavía no corrió la
--      migración de la app. Es idempotente: si ya existe, no la toca.
--   2. Indexar `notifications` por `type`, que es como el portal arma el
--      historial de avisos. Sin ese índice la consulta recorre toda la tabla,
--      y esa tabla crece con una fila POR PERSONA en cada envío.
--
-- Es segura de ejecutar varias veces.
-- ═══════════════════════════════════════════════════════════════════════════

SET NAMES utf8mb4;

-- ───────────────────────────────────────────────────────────────────────────
-- 1. Noticias y eventos
--    Definición idéntica a la de CDHQ-Mobile: las dos aplicaciones escriben
--    aquí y cualquier diferencia acabaría en registros que una acepta y la
--    otra no puede mostrar.
-- ───────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `content_posts` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` enum('NOTICIA','EVENTO') COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `summary` varchar(350) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `image_filename` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image_token` char(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('BORRADOR','PUBLICADA') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'BORRADOR',
  `featured` tinyint(1) NOT NULL DEFAULT '0',
  `published_at` datetime DEFAULT NULL,
  `event_start` datetime DEFAULT NULL,
  `event_end` datetime DEFAULT NULL,
  `event_location` varchar(180) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `event_address` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `registration_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `updated_by` int(10) UNSIGNED DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_content_posts_image_token` (`image_token`),
  KEY `idx_content_posts_publication` (`status`, `deleted_at`, `published_at`),
  KEY `idx_content_posts_featured` (`featured`, `status`, `published_at`),
  KEY `idx_content_posts_event` (`type`, `event_start`),
  CONSTRAINT `fk_content_posts_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_content_posts_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ───────────────────────────────────────────────────────────────────────────
-- 2. Índice del historial de avisos
--    `ADD INDEX IF NOT EXISTS` no existe en MySQL 8, así que se consulta
--    primero el catálogo y se ejecuta el ALTER sólo si hace falta. Este rodeo
--    es lo que permite volver a correr la migración sin que aborte.
-- ───────────────────────────────────────────────────────────────────────────
SET @existe_indice := (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'notifications'
      AND INDEX_NAME   = 'idx_notifications_type_created'
);

SET @sentencia := IF(@existe_indice = 0,
    'ALTER TABLE `notifications` ADD INDEX `idx_notifications_type_created` (`type`, `created_at`)',
    'DO 0'
);

PREPARE aplicar FROM @sentencia;
EXECUTE aplicar;
DEALLOCATE PREPARE aplicar;

-- ═══════════════════════════════════════════════════════════════════════════
-- Verificación posterior:
--   SHOW TABLES LIKE 'content_posts';            -- debe devolver 1 fila
--   SHOW INDEX FROM notifications
--     WHERE Key_name = 'idx_notifications_type_created';  -- debe devolver 2 filas
-- ═══════════════════════════════════════════════════════════════════════════
