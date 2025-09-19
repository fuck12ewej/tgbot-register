-- SQL скрипт для создания базы данных Telegram-бота
-- Создайте базу данных и выполните этот скрипт

-- Создание базы данных (если не существует)
CREATE DATABASE IF NOT EXISTS `telegram_bot` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Использование базы данных
USE `telegram_bot`;

-- Таблица пользователей
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `telegram_id` BIGINT UNIQUE NOT NULL,
    `username` VARCHAR(255) NULL,
    `email` VARCHAR(255) UNIQUE NULL,
    `verified` TINYINT(1) DEFAULT 0,
    `ref_code` VARCHAR(32) UNIQUE NOT NULL,
    `ref_by` VARCHAR(32) NULL,
    `date_reg` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `last_activity` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_telegram_id` (`telegram_id`),
    INDEX `idx_email` (`email`),
    INDEX `idx_ref_code` (`ref_code`),
    INDEX `idx_ref_by` (`ref_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица рефералов
CREATE TABLE IF NOT EXISTS `referrals` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `referrer_id` INT NOT NULL,
    `invited_id` INT NOT NULL,
    `date_invited` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `verified_at` TIMESTAMP NULL,
    FOREIGN KEY (`referrer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`invited_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_referral` (`referrer_id`, `invited_id`),
    INDEX `idx_referrer` (`referrer_id`),
    INDEX `idx_invited` (`invited_id`),
    INDEX `idx_date_invited` (`date_invited`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица токенов для подтверждения email
CREATE TABLE IF NOT EXISTS `email_verification_tokens` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `telegram_id` BIGINT NOT NULL,
    `token` VARCHAR(64) UNIQUE NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `expires_at` TIMESTAMP NOT NULL,
    `used` TINYINT(1) DEFAULT 0,
    `verified_at` TIMESTAMP NULL,
    INDEX `idx_telegram_id` (`telegram_id`),
    INDEX `idx_token` (`token`),
    INDEX `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица для хранения сессий (ожидание ввода email и т.д.)
CREATE TABLE IF NOT EXISTS `user_sessions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `telegram_id` BIGINT NOT NULL,
    `session_type` VARCHAR(50) NOT NULL,
    `session_data` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `expires_at` TIMESTAMP NOT NULL,
    INDEX `idx_telegram_id` (`telegram_id`),
    INDEX `idx_session_type` (`session_type`),
    INDEX `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица для логов активности (опционально)
CREATE TABLE IF NOT EXISTS `activity_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `telegram_id` BIGINT NOT NULL,
    `action` VARCHAR(100) NOT NULL,
    `details` TEXT NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_telegram_id` (`telegram_id`),
    INDEX `idx_action` (`action`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица для настроек бота (опционально)
CREATE TABLE IF NOT EXISTS `bot_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) UNIQUE NOT NULL,
    `setting_value` TEXT NULL,
    `description` TEXT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Вставка начальных настроек
INSERT INTO `bot_settings` (`setting_key`, `setting_value`, `description`) VALUES
('maintenance_mode', '0', 'Режим технического обслуживания (0 - выключен, 1 - включён)'),
('max_referrals_per_user', '100', 'Максимальное количество рефералов на пользователя'),
('email_verification_timeout', '24', 'Время жизни токена подтверждения email (часы)'),
('welcome_message', 'Добро пожаловать в наш бот!', 'Приветственное сообщение для новых пользователей'),
('referral_bonus', '100', 'Бонус за подтверждённого реферала (в условных единицах)')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

-- Создание триггеров для автоматического обновления verified_at в referrals
DELIMITER $$

CREATE TRIGGER `update_referral_verified_at` 
AFTER UPDATE ON `users`
FOR EACH ROW
BEGIN
    IF NEW.verified = 1 AND OLD.verified = 0 THEN
        UPDATE `referrals` 
        SET `verified_at` = NOW() 
        WHERE `invited_id` = NEW.id AND `verified_at` IS NULL;
    END IF;
END$$

DELIMITER ;

-- Создание процедуры для очистки истёкших токенов
DELIMITER $$

CREATE PROCEDURE `CleanExpiredTokens`()
BEGIN
    DELETE FROM `email_verification_tokens` WHERE `expires_at` < NOW() AND `used` = 0;
    DELETE FROM `user_sessions` WHERE `expires_at` < NOW();
    DELETE FROM `activity_logs` WHERE `created_at` < DATE_SUB(NOW(), INTERVAL 30 DAY);
END$$

DELIMITER ;

-- Создание события для автоматической очистки (требует включённый планировщик событий)
-- SET GLOBAL event_scheduler = ON;

-- CREATE EVENT IF NOT EXISTS `cleanup_expired_data`
-- ON SCHEDULE EVERY 1 HOUR
-- DO
--   CALL CleanExpiredTokens();

-- Создание индексов для оптимизации производительности
CREATE INDEX `idx_users_verified` ON `users`(`verified`);
CREATE INDEX `idx_users_date_reg` ON `users`(`date_reg`);
CREATE INDEX `idx_referrals_verified_at` ON `referrals`(`verified_at`);

-- Вставка тестового пользователя (для разработки)
-- INSERT INTO `users` (`telegram_id`, `username`, `email`, `verified`, `ref_code`) 
-- VALUES (123456789, 'testuser', 'test@example.com', 1, 'test123');

-- Просмотр созданных таблиц
SHOW TABLES;

-- Просмотр структуры основных таблиц
DESCRIBE `users`;
DESCRIBE `referrals`;
DESCRIBE `email_verification_tokens`;
