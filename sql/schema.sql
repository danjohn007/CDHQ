-- ============================================================
-- Sistema CDHQ – Esquema completo de base de datos
-- MySQL 5.7 compatible
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `cdhq` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `cdhq`;

-- ─── USERS ──────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `users` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`       VARCHAR(120) NOT NULL,
    `email`      VARCHAR(160) NOT NULL UNIQUE,
    `password`   VARCHAR(255) NOT NULL,
    `role`       ENUM('SUPER_ADMIN','ADMIN_MEMBRESIAS','ADMIN_FINANCIERO','COORD_EVENTOS','EJECUTIVO_PROSPECTOS','AFILIADO')
                 NOT NULL DEFAULT 'AFILIADO',
    `active`     TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── AFFILIATES ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `affiliates` (
    `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `affiliate_number` VARCHAR(20)  NOT NULL UNIQUE,
    `user_id`          INT UNSIGNED NULL,
    `first_name`       VARCHAR(80)  NOT NULL,
    `last_name`        VARCHAR(80)  NOT NULL,
    `email`            VARCHAR(160) NULL,
    `phone`            VARCHAR(30)  NULL,
    `address`          VARCHAR(255) NULL,
    `municipio`        VARCHAR(80)  NULL COMMENT 'Municipio de residencia del afiliado',
    `estado_mexico`    VARCHAR(80)  NULL COMMENT 'Estado de la República Mexicana',
    `state`            ENUM('PROSPECTO','ACTIVO','SUSPENDIDO','VENCIDO') NOT NULL DEFAULT 'PROSPECTO',
    `notes`            TEXT NULL,
    `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_affiliate_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── AFFILIATE HISTORY ──────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `affiliate_history` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `affiliate_id` INT UNSIGNED NOT NULL,
    `field_changed`VARCHAR(60)  NOT NULL,
    `old_value`    TEXT NULL,
    `new_value`    TEXT NULL,
    `reason`       TEXT NULL,
    `changed_by`   INT UNSIGNED NULL,
    `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_ah_affiliate` FOREIGN KEY (`affiliate_id`) REFERENCES `affiliates`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ah_user`      FOREIGN KEY (`changed_by`)   REFERENCES `users`(`id`)      ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── SUBSCRIPTION PLANS ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `subscription_plans` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`          VARCHAR(80)     NOT NULL,
    `description`   TEXT            NULL,
    `price`         DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
    `duration_days` SMALLINT UNSIGNED NOT NULL DEFAULT 365,
    `active`        TINYINT(1)      NOT NULL DEFAULT 1,
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── SUBSCRIPTIONS ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `subscriptions` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `affiliate_id` INT UNSIGNED    NOT NULL,
    `plan_id`      INT UNSIGNED    NOT NULL,
    `start_date`   DATE            NOT NULL,
    `end_date`     DATE            NOT NULL,
    `status`       ENUM('ACTIVA','VENCIDA','SUSPENDIDA') NOT NULL DEFAULT 'ACTIVA',
    `created_at`   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_sub_affiliate` FOREIGN KEY (`affiliate_id`) REFERENCES `affiliates`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_sub_plan`      FOREIGN KEY (`plan_id`)      REFERENCES `subscription_plans`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── PAYMENTS ───────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `payments` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `affiliate_id` INT UNSIGNED    NULL,
    `amount`       DECIMAL(12,2)   NOT NULL,
    `type`         ENUM('MEMBRESIA','EVENTO','DONACION','OTRO') NOT NULL,
    `method`       ENUM('EFECTIVO','TRANSFERENCIA','TARJETA','PAYPAL','PENDIENTE','OTRO') NOT NULL DEFAULT 'EFECTIVO',
    `reference`    VARCHAR(120)    NULL,
    `payment_date` DATE            NOT NULL,
    `notes`        TEXT            NULL,
    `created_by`   INT UNSIGNED    NULL,
    `created_at`   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_payment_affiliate` FOREIGN KEY (`affiliate_id`) REFERENCES `affiliates`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_payment_user`      FOREIGN KEY (`created_by`)   REFERENCES `users`(`id`)      ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── EVENTS ─────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `events` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`        VARCHAR(160)  NOT NULL,
    `description` TEXT          NULL,
    `type`        ENUM('PRESENCIAL','VIRTUAL','HIBRIDO') NOT NULL DEFAULT 'PRESENCIAL',
    `date`        DATETIME      NOT NULL,
    `end_date`    DATETIME      NULL,
    `capacity`    SMALLINT UNSIGNED NULL DEFAULT 0,
    `venue`       VARCHAR(255)  NULL,
    `status`      ENUM('PROGRAMADO','EN_CURSO','FINALIZADO','CANCELADO') NOT NULL DEFAULT 'PROGRAMADO',
    `created_by`  INT UNSIGNED  NULL,
    `created_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_event_user` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── EVENT REGISTRATIONS ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `event_registrations` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `event_id`     INT UNSIGNED    NOT NULL,
    `affiliate_id` INT UNSIGNED    NOT NULL,
    `registered_at`DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `attended`     TINYINT(1)      NOT NULL DEFAULT 0,
    `qr_code`      VARCHAR(64)     NULL,
    UNIQUE KEY `uq_event_affiliate` (`event_id`,`affiliate_id`),
    CONSTRAINT `fk_er_event`     FOREIGN KEY (`event_id`)     REFERENCES `events`(`id`)     ON DELETE CASCADE,
    CONSTRAINT `fk_er_affiliate` FOREIGN KEY (`affiliate_id`) REFERENCES `affiliates`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── PROSPECTS ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `prospects` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`        VARCHAR(160)  NOT NULL,
    `email`       VARCHAR(160)  NULL,
    `phone`       VARCHAR(30)   NULL,
    `source`      VARCHAR(120)  NULL,
    `state`       ENUM('NUEVO','CONTACTADO','SEGUIMIENTO','CONVERTIDO') NOT NULL DEFAULT 'NUEVO',
    `notes`       TEXT          NULL,
    `assigned_to` INT UNSIGNED  NULL,
    `created_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_prospect_user` FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── PROSPECT NOTES ─────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `prospect_notes` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `prospect_id` INT UNSIGNED  NOT NULL,
    `note`        TEXT          NOT NULL,
    `created_by`  INT UNSIGNED  NULL,
    `created_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_pn_prospect` FOREIGN KEY (`prospect_id`) REFERENCES `prospects`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pn_user`     FOREIGN KEY (`created_by`)  REFERENCES `users`(`id`)    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── COUNCIL POSITIONS ──────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `council_positions` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `title`       VARCHAR(120)  NOT NULL,
    `level`       TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `is_unique`   TINYINT(1)    NOT NULL DEFAULT 1,
    `is_active`   TINYINT(1)    NOT NULL DEFAULT 1,
    `description` VARCHAR(255)  NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── COUNCIL ASSIGNMENTS ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `council_assignments` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `position_id`  INT UNSIGNED  NOT NULL,
    `affiliate_id` INT UNSIGNED  NOT NULL,
    `start_date`   DATE          NOT NULL,
    `end_date`     DATE          NULL,
    `status`       ENUM('ACTIVO','FINALIZADO') NOT NULL DEFAULT 'ACTIVO',
    CONSTRAINT `fk_ca_position`  FOREIGN KEY (`position_id`)  REFERENCES `council_positions`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ca_affiliate` FOREIGN KEY (`affiliate_id`) REFERENCES `affiliates`(`id`)        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── CONFIGURATIONS ─────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `configurations` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `key`         VARCHAR(80)   NOT NULL UNIQUE,
    `value`       TEXT          NULL,
    `group`       VARCHAR(40)   NOT NULL DEFAULT 'general',
    `description` VARCHAR(255)  NULL,
    `created_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── ACTIVITY LOG ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `activity_log` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`    INT UNSIGNED  NULL,
    `action`     VARCHAR(40)   NOT NULL,
    `entity`     VARCHAR(60)   NOT NULL,
    `entity_id`  INT UNSIGNED  NOT NULL DEFAULT 0,
    `details`    TEXT          NULL,
    `ip`         VARCHAR(45)   NULL,
    `created_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_al_user`   (`user_id`),
    INDEX `idx_al_action` (`action`),
    INDEX `idx_al_date`   (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── IOT DEVICES ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `iot_devices` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`          VARCHAR(80)   NOT NULL,
    `type`          ENUM('SHELLY','HIKVISION','GPS','OTHER') NOT NULL DEFAULT 'OTHER',
    `ip`            VARCHAR(45)   NULL,
    `port`          SMALLINT UNSIGNED NULL,
    `username`      VARCHAR(80)   NULL,
    `password_hash` VARCHAR(255)  NULL,
    `config_json`   TEXT          NULL,
    `active`        TINYINT(1)    NOT NULL DEFAULT 1,
    `created_at`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SAMPLE DATA (Querétaro context)
-- ============================================================

-- Users (password for all = Admin123!)
-- Hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
INSERT INTO `users` (`name`, `email`, `password`, `role`, `active`) VALUES
('Administrador General',   'admin@cdhq.org',       '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'SUPER_ADMIN',          1),
('María González López',    'membresias@cdhq.org',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ADMIN_MEMBRESIAS',     1),
('Carlos Reyes Mendoza',    'finanzas@cdhq.org',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ADMIN_FINANCIERO',     1),
('Ana Martínez Torres',     'eventos@cdhq.org',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'COORD_EVENTOS',        1),
('Luis Ramírez Vargas',     'prospectos@cdhq.org',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'EJECUTIVO_PROSPECTOS', 1);

-- Subscription plans
INSERT INTO `subscription_plans` (`name`, `description`, `price`, `duration_days`, `active`) VALUES
('Plan Anual Individual',  'Membresía estándar para personas físicas. Incluye todos los beneficios.',  800.00, 365, 1),
('Plan Semestral',         'Membresía por 6 meses. Ideal para evaluar los beneficios del consejo.',    450.00, 180, 1),
('Plan Corporativo Anual', 'Membresía para organizaciones y personas morales. Hasta 3 representantes.',1500.00, 365, 1);

-- Affiliates (sample 30 – national coverage, 2026 dates)
INSERT INTO `affiliates` (`affiliate_number`, `user_id`, `first_name`, `last_name`, `email`, `phone`, `address`, `municipio`, `estado_mexico`, `state`, `created_at`) VALUES
-- Querétaro
('CDHQ-2026-0001', NULL, 'Roberto',   'Hernández Castillo',  'roberto.hernandez@email.com', '4421234567', 'Av. Universidad 123',              'Querétaro',          'Querétaro',          'ACTIVO',     '2026-01-05 09:00:00'),
('CDHQ-2026-0002', NULL, 'Patricia',  'Morales Sánchez',     'patricia.morales@email.com',  '4427654321', 'Blvd. Bernardo Quintana 456',       'Querétaro',          'Querétaro',          'ACTIVO',     '2026-01-12 10:30:00'),
('CDHQ-2026-0003', NULL, 'José',      'Gutiérrez Pérez',     'jose.gutierrez@email.com',    '4429876543', 'Calle Corregidora 789, Centro',     'Querétaro',          'Querétaro',          'ACTIVO',     '2026-01-20 11:00:00'),
('CDHQ-2026-0004', NULL, 'Laura',     'Jiménez Flores',      'laura.jimenez@email.com',     '4421357924', 'Privada Las Flores 12',             'El Marqués',         'Querétaro',          'ACTIVO',     '2026-02-03 09:15:00'),
('CDHQ-2026-0005', NULL, 'Miguel',    'Delgado Ruiz',        'miguel.delgado@email.com',    '4422468013', 'Av. 5 de Febrero 321',              'Corregidora',        'Querétaro',          'ACTIVO',     '2026-02-10 14:00:00'),
('CDHQ-2026-0006', NULL, 'Carmen',    'Vázquez Luna',        'carmen.vazquez@email.com',    '4423579124', 'Calzada de los Arcos 654',          'Querétaro',          'Querétaro',          'SUSPENDIDO', '2026-02-18 08:45:00'),
('CDHQ-2026-0007', NULL, 'Fernando',  'Cruz Medina',         'fernando.cruz@email.com',     '4424680235', 'Av. Tecnológico 987, Juriquilla',   'Querétaro',          'Querétaro',          'VENCIDO',    '2026-03-01 10:00:00'),
('CDHQ-2026-0008', NULL, 'Daniela',   'Ramos Ortega',        'daniela.ramos@email.com',     '4425791346', 'Paseo de las Haciendas 147',        'Querétaro',          'Querétaro',          'ACTIVO',     '2026-03-08 11:30:00'),
('CDHQ-2026-0009', NULL, 'Alejandro', 'Torres Mendez',       'alejandro.torres@email.com',  '4426802457', 'Blvd. Villas del Mesón 258',        'San Juan del Río',   'Querétaro',          'PROSPECTO',  '2026-03-15 16:00:00'),
('CDHQ-2026-0010', NULL, 'Sofía',     'Fuentes García',      'sofia.fuentes@email.com',     '4427913568', 'Av. Ignacio Pérez 369',             'Querétaro',          'Querétaro',          'ACTIVO',     '2026-03-22 09:00:00'),
-- Ciudad de México
('CDHQ-2026-0011', NULL, 'Andrea',    'Ramírez Villanueva',  'andrea.ramirez@email.com',    '5512345678', 'Insurgentes Sur 1605, Col. San José','Benito Juárez',     'Ciudad de México',   'ACTIVO',     '2026-01-08 10:00:00'),
('CDHQ-2026-0012', NULL, 'Carlos',    'Mendoza Espinoza',    'carlos.mendoza@email.com',    '5523456789', 'Av. Reforma 222, Cuauhtémoc',       'Cuauhtémoc',         'Ciudad de México',   'ACTIVO',     '2026-01-25 12:00:00'),
('CDHQ-2026-0013', NULL, 'Verónica',  'López Castañeda',     'veronica.lopez@email.com',    '5534567890', 'Xochimilco 45, Coyoacán',           'Coyoacán',           'Ciudad de México',   'VENCIDO',    '2026-02-14 09:30:00'),
-- Jalisco
('CDHQ-2026-0014', NULL, 'Luis',      'Sánchez Barrera',     'luis.sanchez@email.com',      '3312345678', 'Av. Vallarta 3300, Zapopan',        'Zapopan',            'Jalisco',            'ACTIVO',     '2026-01-15 11:00:00'),
('CDHQ-2026-0015', NULL, 'Mariana',   'Torres Ibáñez',       'mariana.torres@email.com',    '3323456789', 'López Cotilla 1018, GDL',           'Guadalajara',        'Jalisco',            'ACTIVO',     '2026-02-20 13:00:00'),
('CDHQ-2026-0016', NULL, 'Ricardo',   'Flores Navarro',      'ricardo.flores@email.com',    '3334567890', 'Av. Revolución 150, Tlaquepaque',   'Tlaquepaque',        'Jalisco',            'PROSPECTO',  '2026-03-05 10:15:00'),
-- Nuevo León
('CDHQ-2026-0017', NULL, 'Sandra',    'García Moreno',       'sandra.garcia@email.com',     '8112345678', 'Av. Constitución 500, Monterrey',   'Monterrey',          'Nuevo León',         'ACTIVO',     '2026-01-18 09:00:00'),
('CDHQ-2026-0018', NULL, 'Jorge',     'Martínez Rodríguez',  'jorge.martinez@email.com',    '8123456789', 'Lazaro Cárdenas 2400, San Pedro',   'San Pedro Garza García','Nuevo León',      'ACTIVO',     '2026-02-07 14:30:00'),
-- Puebla
('CDHQ-2026-0019', NULL, 'Elena',     'Castro Herrera',      'elena.castro@email.com',      '2221234567', 'Av. Juárez 2701, Puebla',           'Puebla',             'Puebla',             'ACTIVO',     '2026-01-22 10:00:00'),
('CDHQ-2026-0020', NULL, 'Ernesto',   'Vargas Díaz',         'ernesto.vargas@email.com',    '2232345678', 'Blvd. Valsequillo 600, Puebla',     'Puebla',             'Puebla',             'VENCIDO',    '2026-02-28 11:00:00'),
-- Guanajuato
('CDHQ-2026-0021', NULL, 'Diana',     'Rojas Serrano',       'diana.rojas@email.com',       '4771234567', 'Blvd. Torres Landa 702, León',      'León',               'Guanajuato',         'ACTIVO',     '2026-02-01 09:30:00'),
('CDHQ-2026-0022', NULL, 'Héctor',    'Peña Montoya',        'hector.pena@email.com',       '4732345678', 'Av. Insurgentes 200, GTO',          'Guanajuato',         'Guanajuato',         'ACTIVO',     '2026-03-12 10:00:00'),
-- Veracruz
('CDHQ-2026-0023', NULL, 'Gabriela',  'Silva Espinosa',      'gabriela.silva@email.com',    '2291234567', 'Av. Independencia 45, Veracruz',    'Veracruz',           'Veracruz',           'ACTIVO',     '2026-01-30 12:00:00'),
-- Yucatán
('CDHQ-2026-0024', NULL, 'Rodrigo',   'Núñez Alvarado',      'rodrigo.nunez@email.com',     '9991234567', 'Calle 60 x 55, Centro, Mérida',     'Mérida',             'Yucatán',            'ACTIVO',     '2026-02-15 11:15:00'),
-- Sonora
('CDHQ-2026-0025', NULL, 'Claudia',   'Medina Ríos',         'claudia.medina@email.com',    '6621234567', 'Blvd. Morelos 100, Hermosillo',     'Hermosillo',         'Sonora',             'ACTIVO',     '2026-03-03 09:00:00'),
-- Chihuahua
('CDHQ-2026-0026', NULL, 'Francisco', 'Ortega Peralta',      'francisco.ortega@email.com',  '6141234567', 'Av. Tecnológico 800, Chihuahua',    'Chihuahua',          'Chihuahua',          'ACTIVO',     '2026-01-10 10:00:00'),
-- Oaxaca
('CDHQ-2026-0027', NULL, 'Isabel',    'Cruz Guzmán',         'isabel.cruz@email.com',       '9511234567', 'Macedonio Alcalá 804, Oaxaca',      'Oaxaca de Juárez',   'Oaxaca',             'ACTIVO',     '2026-02-25 13:30:00'),
-- Coahuila
('CDHQ-2026-0028', NULL, 'Arturo',    'Luna Vega',           'arturo.luna@email.com',       '8441234567', 'Blvd. Constitución 200, Saltillo',  'Saltillo',           'Coahuila',           'PROSPECTO',  '2026-03-18 10:45:00'),
-- Tamaulipas
('CDHQ-2026-0029', NULL, 'Beatriz',   'Soto Acevedo',        'beatriz.soto@email.com',      '8341234567', 'Av. Hidalgo 150, Tampico',          'Tampico',            'Tamaulipas',         'ACTIVO',     '2026-03-25 09:30:00'),
-- Michoacán
('CDHQ-2026-0030', NULL, 'Óscar',     'Aguilar Romero',      'oscar.aguilar@email.com',     '4431234567', 'Guillermo Prieto 250, Morelia',     'Morelia',            'Michoacán',          'ACTIVO',     '2026-04-01 11:00:00');

-- Subscriptions (for active affiliates – 2026 dates)
INSERT INTO `subscriptions` (`affiliate_id`, `plan_id`, `start_date`, `end_date`, `status`) VALUES
(1,  1, '2026-01-05', '2027-01-04', 'ACTIVA'),
(2,  1, '2026-01-12', '2027-01-11', 'ACTIVA'),
(3,  1, '2026-01-20', '2027-01-19', 'ACTIVA'),
(4,  2, '2026-02-03', '2026-08-02', 'ACTIVA'),
(5,  1, '2026-02-10', '2027-02-09', 'ACTIVA'),
(7,  1, '2026-03-01', '2026-03-31', 'VENCIDA'),
(8,  3, '2026-03-08', '2027-03-07', 'ACTIVA'),
(10, 1, '2026-03-22', '2027-03-21', 'ACTIVA'),
(11, 1, '2026-01-08', '2027-01-07', 'ACTIVA'),
(12, 1, '2026-01-25', '2027-01-24', 'ACTIVA'),
(14, 1, '2026-01-15', '2027-01-14', 'ACTIVA'),
(15, 2, '2026-02-20', '2026-08-19', 'ACTIVA'),
(17, 1, '2026-01-18', '2027-01-17', 'ACTIVA'),
(18, 3, '2026-02-07', '2027-02-06', 'ACTIVA'),
(19, 1, '2026-01-22', '2027-01-21', 'ACTIVA'),
(21, 1, '2026-02-01', '2027-01-31', 'ACTIVA'),
(22, 1, '2026-03-12', '2027-03-11', 'ACTIVA'),
(23, 1, '2026-01-30', '2027-01-29', 'ACTIVA'),
(24, 2, '2026-02-15', '2026-08-14', 'ACTIVA'),
(25, 1, '2026-03-03', '2027-03-02', 'ACTIVA'),
(26, 1, '2026-01-10', '2027-01-09', 'ACTIVA'),
(27, 1, '2026-02-25', '2027-02-24', 'ACTIVA'),
(29, 1, '2026-03-25', '2027-03-24', 'ACTIVA'),
(30, 1, '2026-04-01', '2027-03-31', 'ACTIVA');

-- Payments (2026 dates)
INSERT INTO `payments` (`affiliate_id`, `amount`, `type`, `method`, `reference`, `payment_date`, `notes`) VALUES
(1,  800.00, 'MEMBRESIA', 'TRANSFERENCIA', 'REF-2026-001', '2026-01-05', 'Pago anual'),
(2,  800.00, 'MEMBRESIA', 'EFECTIVO',      NULL,            '2026-01-12', 'Pago anual'),
(3,  800.00, 'MEMBRESIA', 'TARJETA',       'TRX-2026-003', '2026-01-20', 'Pago anual'),
(4,  450.00, 'MEMBRESIA', 'TRANSFERENCIA', 'REF-2026-004', '2026-02-03', 'Plan semestral'),
(5,  800.00, 'MEMBRESIA', 'PAYPAL',        'PP-2026-005',  '2026-02-10', 'Pago anual'),
(8, 1500.00, 'MEMBRESIA', 'TRANSFERENCIA', 'REF-2026-008', '2026-03-08', 'Plan corporativo'),
(10, 800.00, 'MEMBRESIA', 'EFECTIVO',      NULL,            '2026-03-22', 'Pago anual'),
(11, 800.00, 'MEMBRESIA', 'TRANSFERENCIA', 'REF-2026-011', '2026-01-08', 'Pago anual CDMX'),
(12, 800.00, 'MEMBRESIA', 'TARJETA',       'TRX-2026-012', '2026-01-25', 'Pago anual CDMX'),
(14, 800.00, 'MEMBRESIA', 'TRANSFERENCIA', 'REF-2026-014', '2026-01-15', 'Pago anual GDL'),
(15, 450.00, 'MEMBRESIA', 'EFECTIVO',      NULL,            '2026-02-20', 'Plan semestral GDL'),
(17, 800.00, 'MEMBRESIA', 'TRANSFERENCIA', 'REF-2026-017', '2026-01-18', 'Pago anual MTY'),
(18,1500.00, 'MEMBRESIA', 'TRANSFERENCIA', 'REF-2026-018', '2026-02-07', 'Plan corporativo MTY'),
(19, 800.00, 'MEMBRESIA', 'TARJETA',       'TRX-2026-019', '2026-01-22', 'Pago anual PUE'),
(21, 800.00, 'MEMBRESIA', 'EFECTIVO',      NULL,            '2026-02-01', 'Pago anual GTO'),
(22, 800.00, 'MEMBRESIA', 'TRANSFERENCIA', 'REF-2026-022', '2026-03-12', 'Pago anual GTO'),
(23, 800.00, 'MEMBRESIA', 'PAYPAL',        'PP-2026-023',  '2026-01-30', 'Pago anual VER'),
(24, 450.00, 'MEMBRESIA', 'TRANSFERENCIA', 'REF-2026-024', '2026-02-15', 'Plan semestral YUC'),
(25, 800.00, 'MEMBRESIA', 'EFECTIVO',      NULL,            '2026-03-03', 'Pago anual SON'),
(26, 800.00, 'MEMBRESIA', 'TARJETA',       'TRX-2026-026', '2026-01-10', 'Pago anual CHH'),
(27, 800.00, 'MEMBRESIA', 'TRANSFERENCIA', 'REF-2026-027', '2026-02-25', 'Pago anual OAX'),
(29, 800.00, 'MEMBRESIA', 'EFECTIVO',      NULL,            '2026-03-25', 'Pago anual TAM'),
(30, 800.00, 'MEMBRESIA', 'TRANSFERENCIA', 'REF-2026-030', '2026-04-01', 'Pago anual MICH'),
(NULL, 3000.00,'DONACION','TRANSFERENCIA','DON-2026-001',  '2026-02-14', 'Donación Día Internacional de los Derechos'),
(1,    150.00,'EVENTO',  'EFECTIVO',       NULL,            '2026-03-10', 'Evento de capacitación');

-- Events (2026 dates)
INSERT INTO `events` (`name`, `description`, `type`, `date`, `end_date`, `capacity`, `venue`, `status`, `created_by`) VALUES
('Foro Nacional de Derechos Humanos 2026',
 'Encuentro anual de organizaciones civiles para analizar el estado de los derechos humanos en México. Ponentes nacionales e internacionales.',
 'PRESENCIAL', '2026-02-20 09:00:00', '2026-02-20 18:00:00', 300,
 'Centro de Convenciones Querétaro, Blvd. Bernardo Quintana 9001', 'FINALIZADO', 1),
('Taller: Mecanismos de Defensa Jurídica',
 'Taller práctico sobre herramientas legales para la defensa de derechos humanos. Cupo limitado.',
 'PRESENCIAL', '2026-03-05 10:00:00', '2026-03-05 14:00:00', 30,
 'Sala de capacitación CDHQ, Centro Histórico', 'FINALIZADO', 1),
('Webinar: Derechos Digitales y Privacidad',
 'Sesión virtual sobre el derecho a la privacidad en el entorno digital y las herramientas para protegerlo.',
 'VIRTUAL', '2026-03-18 17:00:00', '2026-03-18 19:00:00', 0,
 'https://meet.jit.si/cdhq-webinar-mar2026', 'FINALIZADO', 4),
('Conferencia: Acceso a la Justicia para Grupos Vulnerables',
 'Análisis de las barreras que enfrentan grupos vulnerables para acceder a la justicia en México.',
 'PRESENCIAL', '2026-05-10 10:00:00', NULL, 80,
 'Auditorio de la Universidad Autónoma de Querétaro', 'PROGRAMADO', 1),
('Capacitación Interna: Sistema de Afiliación CDHQ',
 'Sesión de capacitación para el equipo del consejo sobre el uso del sistema de afiliación.',
 'HIBRIDO', '2026-05-22 09:00:00', '2026-05-22 13:00:00', 25,
 'Oficinas CDHQ / Enlace Teams', 'PROGRAMADO', 4);

-- Event registrations (for completed events)
INSERT INTO `event_registrations` (`event_id`, `affiliate_id`, `attended`, `qr_code`) VALUES
(1, 1, 1, 'qr-ev1-aff1'), (1, 2, 1, 'qr-ev1-aff2'), (1, 3, 1, 'qr-ev1-aff3'),
(1, 4, 0, 'qr-ev1-aff4'), (1, 5, 1, 'qr-ev1-aff5'), (1, 8, 1, 'qr-ev1-aff8'),
(2, 1, 1, 'qr-ev2-aff1'), (2, 3, 1, 'qr-ev2-aff3'), (2, 5, 0, 'qr-ev2-aff5'),
(3, 2, 1, 'qr-ev3-aff2'), (3, 4, 1, 'qr-ev3-aff4'), (3, 10,1, 'qr-ev3-aff10');

-- Prospects
INSERT INTO `prospects` (`name`, `email`, `phone`, `source`, `state`, `notes`, `assigned_to`) VALUES
('Beatriz Soto Ramírez',    'beatriz.soto@gmail.com',    '4428765432', 'Referido por afiliado',  'NUEVO',       'Interesada en derechos laborales',            5),
('Ernesto Navarro Castro',  'ernesto.navarro@outlook.com','4429876543','Redes sociales - Twitter','CONTACTADO',  'Primer contacto realizado por WhatsApp',     5),
('Graciela Peña Domínguez', 'graciela.pena@hotmail.com', '4421234560', 'Evento foro CDHQ',       'SEGUIMIENTO', 'Asistió al Foro 2024, muy interesada',        5),
('Héctor Moreno Ibarra',    'hector.moreno@empresa.com', '4422345671', 'Empresas – visita',      'SEGUIMIENTO', 'Empresa de tecnología, busca membresía corp.', 2),
('Isabel Castro Fuentes',   'isabel.castro@gmail.com',   '4423456782', 'Página web',             'NUEVO',       'Solicitó información por formulario web',     5);

-- Council positions
INSERT INTO `council_positions` (`title`, `level`, `is_unique`, `is_active`, `description`) VALUES
('Presidente/a del Consejo',       1, 1, 1, 'Máxima autoridad del consejo directivo'),
('Vicepresidente/a',               1, 1, 1, 'Suple al presidente en caso de ausencia'),
('Secretario/a General',           2, 1, 1, 'Gestión administrativa y actas de sesión'),
('Tesorero/a',                     2, 1, 1, 'Responsable de finanzas y patrimonio'),
('Vocal de Derechos Civiles',      3, 0, 1, 'Área de derechos civiles y políticos'),
('Vocal de Derechos Económicos',   3, 0, 1, 'Área de derechos económicos y sociales'),
('Coordinador/a de Eventos',       3, 1, 1, 'Organización de eventos y capacitaciones'),
('Coordinador/a de Comunicación',  3, 1, 1, 'Gestión de medios y redes sociales');

-- Council assignments
INSERT INTO `council_assignments` (`position_id`, `affiliate_id`, `start_date`, `status`) VALUES
(1, 1, '2024-01-01', 'ACTIVO'),
(2, 2, '2024-01-01', 'ACTIVO'),
(3, 3, '2024-01-01', 'ACTIVO'),
(4, 8, '2024-01-01', 'ACTIVO'),
(5, 5, '2024-01-01', 'ACTIVO'),
(7, 4, '2024-01-01', 'ACTIVO');

-- Configurations
INSERT INTO `configurations` (`key`, `value`, `group`, `description`) VALUES
('site_name',          'Sistema CDHQ',                              'general',   'Nombre del sistema'),
('logo_url',           '',                                          'general',   'URL del logotipo'),
('contact_phone',      '+52 442 123 4567',                         'general',   'Teléfono principal'),
('contact_email',      'contacto@cdhq.org',                        'general',   'Correo de contacto'),
('office_hours',       'Lunes a Viernes 9:00 – 18:00 hrs',        'general',   'Horario de atención'),
('primary_color',      '#4f46e5',                                   'general',   'Color primario (hex)'),
('smtp_host',          'smtp.mailtrap.io',                          'email',     'Servidor SMTP'),
('smtp_port',          '587',                                        'email',     'Puerto SMTP'),
('smtp_user',          '',                                          'email',     'Usuario SMTP'),
('smtp_password',      '',                                          'email',     'Contraseña SMTP'),
('smtp_from',          'noreply@cdhq.org',                         'email',     'Correo remitente'),
('smtp_from_name',     'Consejo de Derechos Humanos Querétaro',    'email',     'Nombre remitente'),
('paypal_client_id',   '',                                          'payments',  'PayPal Client ID'),
('paypal_client_secret','',                                         'payments',  'PayPal Client Secret'),
('paypal_mode',        'sandbox',                                   'payments',  'Modo PayPal'),
('currency',           'MXN',                                       'payments',  'Moneda'),
('whatsapp_token',     '',                                          'payments',  'WhatsApp API Token'),
('whatsapp_number',    '',                                          'payments',  'Número WhatsApp'),
('shelly_server',      '',                                          'iot',       'Servidor Shelly Cloud'),
('shelly_auth_key',    '',                                          'iot',       'Shelly Auth Key'),
('hikvision_ip',       '',                                          'iot',       'IP HikVision NVR'),
('hikvision_port',     '80',                                        'iot',       'Puerto HikVision'),
('hikvision_user',     'admin',                                     'iot',       'Usuario HikVision'),
('hikvision_password', '',                                          'iot',       'Contraseña HikVision'),
('gps_api_url',        '',                                          'iot',       'URL API GPS Tracker'),
('gps_api_key',        '',                                          'iot',       'API Key GPS'),
('qr_api_url',         'https://chart.googleapis.com/chart',       'advanced',  'URL API QR Code'),
('session_lifetime',   '7200',                                      'advanced',  'Tiempo de sesión en segundos'),
('items_per_page',     '20',                                        'advanced',  'Elementos por página');

SET FOREIGN_KEY_CHECKS = 1;
