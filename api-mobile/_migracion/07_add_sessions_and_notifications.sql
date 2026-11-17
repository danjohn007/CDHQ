-- Sesión persistente en dispositivo, notificaciones push y recuperación de contraseña.
-- Ejecutar en la misma base configurada para api-mobile, después de
-- 06_seed_human_rights.sql. Todas las sentencias usan IF NOT EXISTS, por lo que
-- el script puede volver a ejecutarse sin borrar información existente.
-- No modifica ni elimina usuarios, pagos, suscripciones ni credenciales.
SET NAMES utf8mb4;

-- Sesión persistente: cada dispositivo guarda un token propio. Cerrar la
-- aplicación ya no obliga a escribir de nuevo el correo y la contraseña.
CREATE TABLE IF NOT EXISTS `auth_tokens` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `token_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `device_name` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `platform` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_used_at` datetime DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  `revoked_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_auth_token_hash` (`token_hash`),
  KEY `idx_auth_token_user` (`user_id`, `revoked_at`, `expires_at`),
  CONSTRAINT `fk_auth_token_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Token de Expo para entregar notificaciones push al dispositivo.
CREATE TABLE IF NOT EXISTS `push_tokens` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `platform` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `failure_count` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_push_token` (`token`),
  KEY `idx_push_token_user` (`user_id`),
  CONSTRAINT `fk_push_token_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Recuperación de contraseña por correo con token de un solo uso.
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `token_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `requested_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_password_reset_hash` (`token_hash`),
  KEY `idx_password_reset_user` (`user_id`, `used_at`, `expires_at`),
  CONSTRAINT `fk_password_reset_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La tabla `notifications` ya se creó en 01_extend_for_portal.sql. Se repite
-- con IF NOT EXISTS únicamente para instalaciones que hayan omitido ese paso.
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `type` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `action_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `read_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notifications_user_read` (`user_id`, `read_at`, `created_at`),
  CONSTRAINT `fk_notification_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Registro de cuentas eliminadas por el propio usuario. Conserva únicamente el
-- correo cifrado en un hash para auditoría, sin datos personales recuperables.
CREATE TABLE IF NOT EXISTS `account_deletions` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `email_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `previous_user_id` int(10) UNSIGNED NOT NULL,
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deleted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_account_deletion_hash` (`email_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
