-- ═══════════════════════════════════════════════════════════════════════════
-- Migración 003 – Verificación pública de credenciales
--
-- Contexto: el QR de las credenciales apuntaba a /affiliates/{id}, una ruta
-- privada que exige inicio de sesión, usando el id numérico y secuencial del
-- afiliado. Eso hacía el QR inútil al escanearlo y, peor aún, permitía
-- enumerar el padrón completo probando 1, 2, 3...
--
-- Esta migración agrega un token aleatorio e irrepetible por afiliado, que es
-- lo único que viaja dentro del QR. La página pública de verificación sólo
-- muestra los datos mínimos necesarios para acreditar la credencial.
-- ═══════════════════════════════════════════════════════════════════════════

-- 1. Columna del token de verificación
ALTER TABLE `affiliates`
    ADD COLUMN `verification_token` CHAR(32) NULL DEFAULT NULL
        COMMENT 'Token público e irreversible usado por el QR de la credencial'
        AFTER `affiliate_number`;

-- 2. Índice único (permite múltiples NULL, impide tokens repetidos)
ALTER TABLE `affiliates`
    ADD UNIQUE INDEX `uq_affiliate_verification_token` (`verification_token`);

-- 3. Backfill para los afiliados existentes.
--    Se usa UUID() + RAND() en lugar de RANDOM_BYTES() por compatibilidad
--    con MySQL 5.7 y MariaDB. Las altas nuevas generan el token en PHP con
--    random_bytes(), que sí es un generador criptográficamente seguro.
UPDATE `affiliates`
SET `verification_token` = SUBSTRING(SHA2(CONCAT(UUID(), RAND(), `id`, `affiliate_number`), 256), 1, 32)
WHERE `verification_token` IS NULL;

-- ═══════════════════════════════════════════════════════════════════════════
-- Verificación posterior (debe devolver 0 filas):
--   SELECT COUNT(*) FROM affiliates WHERE verification_token IS NULL;
-- ═══════════════════════════════════════════════════════════════════════════
