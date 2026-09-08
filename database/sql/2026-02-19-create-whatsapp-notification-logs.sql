CREATE TABLE `whatsapp_notification_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` VARCHAR(50) NOT NULL COMMENT 'renewal_reminder, invoice',
  `member_id` BIGINT UNSIGNED NULL,
  `transaction_id` BIGINT UNSIGNED NULL,
  `recipient_phone` VARCHAR(25) NOT NULL,
  `message` TEXT NOT NULL,
  `status` ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
  `retry_count` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `provider_response` TEXT NULL,
  `sent_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_wa_logs_type_status` (`type`, `status`),
  KEY `idx_wa_logs_member_id` (`member_id`),
  KEY `idx_wa_logs_transaction_id` (`transaction_id`),
  KEY `idx_wa_logs_recipient_phone` (`recipient_phone`),
  KEY `idx_wa_logs_sent_at` (`sent_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

