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

-- Affiliates (sample 10)
INSERT INTO `affiliates` (`affiliate_number`, `user_id`, `first_name`, `last_name`, `email`, `phone`, `address`, `state`) VALUES
('CDHQ-2024-0001', NULL, 'Roberto',  'Hernández Castillo',  'roberto.hernandez@email.com', '4421234567', 'Av. Universidad 123, Querétaro, Qro.', 'ACTIVO'),
('CDHQ-2024-0002', NULL, 'Patricia', 'Morales Sánchez',     'patricia.morales@email.com',  '4427654321', 'Blvd. Bernardo Quintana 456, Querétaro', 'ACTIVO'),
('CDHQ-2024-0003', NULL, 'José',     'Gutiérrez Pérez',     'jose.gutierrez@email.com',    '4429876543', 'Calle Corregidora 789, Centro, Querétaro', 'ACTIVO'),
('CDHQ-2024-0004', NULL, 'Laura',    'Jiménez Flores',      'laura.jimenez@email.com',     '4421357924', 'Privada Las Flores 12, El Marqués, Qro.', 'ACTIVO'),
('CDHQ-2024-0005', NULL, 'Miguel',   'Delgado Ruiz',        'miguel.delgado@email.com',    '4422468013', 'Av. 5 de Febrero 321, Querétaro', 'ACTIVO'),
('CDHQ-2024-0006', NULL, 'Carmen',   'Vázquez Luna',        'carmen.vazquez@email.com',    '4423579124', 'Calzada de los Arcos 654, Querétaro', 'SUSPENDIDO'),
('CDHQ-2024-0007', NULL, 'Fernando', 'Cruz Medina',         'fernando.cruz@email.com',     '4424680235', 'Av. Tecnológico 987, Juriquilla, Qro.', 'VENCIDO'),
('CDHQ-2024-0008', NULL, 'Daniela',  'Ramos Ortega',        'daniela.ramos@email.com',     '4425791346', 'Paseo de las Haciendas 147, Querétaro', 'ACTIVO'),
('CDHQ-2024-0009', NULL, 'Alejandro','Torres Mendez',       'alejandro.torres@email.com',  '4426802457', 'Blvd. Villas del Mesón 258, Querétaro', 'PROSPECTO'),
('CDHQ-2024-0010', NULL, 'Sofía',    'Fuentes García',      'sofia.fuentes@email.com',     '4427913568', 'Av. Ignacio Pérez 369, Querétaro', 'ACTIVO');

-- Subscriptions (for active affiliates)
INSERT INTO `subscriptions` (`affiliate_id`, `plan_id`, `start_date`, `end_date`, `status`) VALUES
(1, 1, '2024-01-15', '2025-01-14', 'ACTIVA'),
(2, 1, '2024-02-01', '2025-01-31', 'ACTIVA'),
(3, 1, '2024-03-10', '2025-03-09', 'ACTIVA'),
(4, 2, '2024-04-01', '2024-09-27', 'VENCIDA'),
(5, 1, '2024-04-15', '2025-04-14', 'ACTIVA'),
(7, 1, '2023-06-01', '2024-05-31', 'VENCIDA'),
(8, 3, '2024-05-01', '2025-04-30', 'ACTIVA'),
(10,1, '2024-06-01', '2025-05-31', 'ACTIVA');

-- Payments
INSERT INTO `payments` (`affiliate_id`, `amount`, `type`, `method`, `reference`, `payment_date`, `notes`) VALUES
(1, 800.00, 'MEMBRESIA', 'TRANSFERENCIA', 'REF-2024-001', '2024-01-15', 'Pago anual'),
(2, 800.00, 'MEMBRESIA', 'EFECTIVO',      NULL,            '2024-02-01', 'Pago anual'),
(3, 800.00, 'MEMBRESIA', 'TARJETA',       'TRX-78432',    '2024-03-10', 'Pago anual'),
(4, 450.00, 'MEMBRESIA', 'TRANSFERENCIA', 'REF-2024-004', '2024-04-01', 'Plan semestral'),
(5, 800.00, 'MEMBRESIA', 'PAYPAL',        'PP-23456',     '2024-04-15', 'Pago anual'),
(8,1500.00, 'MEMBRESIA', 'TRANSFERENCIA', 'REF-2024-008', '2024-05-01', 'Plan corporativo'),
(10, 800.00,'MEMBRESIA', 'EFECTIVO',      NULL,            '2024-06-01', 'Pago anual'),
(NULL, 2000.00,'DONACION','TRANSFERENCIA','DON-2024-001', '2024-07-20', 'Donación anónima al consejo'),
(1,    150.00,'EVENTO',  'EFECTIVO',      NULL,            '2024-08-10', 'Evento de capacitación');

-- Events
INSERT INTO `events` (`name`, `description`, `type`, `date`, `end_date`, `capacity`, `venue`, `status`, `created_by`) VALUES
('Foro de Derechos Humanos Querétaro 2024',
 'Encuentro anual de organizaciones civiles para analizar el estado de los derechos humanos en Querétaro. Contaremos con ponentes nacionales e internacionales.',
 'PRESENCIAL', '2024-09-15 09:00:00', '2024-09-15 18:00:00', 200,
 'Centro de Convenciones Querétaro, Blvd. Bernardo Quintana 9001', 'FINALIZADO', 1),
('Taller: Mecanismos de Defensa Jurídica',
 'Taller práctico sobre herramientas legales para la defensa de derechos humanos. Cupo limitado.',
 'PRESENCIAL', '2024-10-22 10:00:00', '2024-10-22 14:00:00', 30,
 'Sala de capacitación CDHQ, Centro Histórico', 'FINALIZADO', 1),
('Webinar: Derechos Digitales y Privacidad',
 'Sesión virtual sobre el derecho a la privacidad en el entorno digital y las herramientas para protegerlo.',
 'VIRTUAL', '2024-11-05 17:00:00', '2024-11-05 19:00:00', 0,
 'https://meet.jit.si/cdhq-webinar-nov2024', 'FINALIZADO', 4),
('Conferencia: Acceso a la Justicia para Grupos Vulnerables',
 'Análisis de las barreras que enfrentan grupos vulnerables para acceder a la justicia en México.',
 'PRESENCIAL', '2025-02-14 10:00:00', NULL, 80,
 'Auditorio de la Universidad Autónoma de Querétaro', 'PROGRAMADO', 1),
('Capacitación Interna: Sistema de Afiliación CDHQ',
 'Sesión de capacitación para el equipo del consejo sobre el uso del nuevo sistema de afiliación.',
 'HIBRIDO', '2025-03-01 09:00:00', '2025-03-01 13:00:00', 25,
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
