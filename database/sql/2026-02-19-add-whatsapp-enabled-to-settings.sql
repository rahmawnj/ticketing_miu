ALTER TABLE `settings`
ADD COLUMN `whatsapp_enabled` TINYINT(1) NOT NULL DEFAULT 0
AFTER `dashboard_metric_mode`;

