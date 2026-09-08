-- Backup old settings table
DROP TABLE IF EXISTS settings_legacy;
RENAME TABLE settings TO settings_legacy;

-- New key-value settings table
CREATE TABLE settings (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `key` VARCHAR(191) NOT NULL UNIQUE,
  `value` TEXT NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migrate existing values (single-row legacy format -> key-value rows)
INSERT INTO settings (`key`, `value`, created_at, updated_at)
SELECT
  k.`key`,
  CASE k.`key`
    WHEN 'name' THEN COALESCE(s.name, '')
    WHEN 'logo' THEN COALESCE(s.logo, '')
    WHEN 'ucapan' THEN COALESCE(s.ucapan, '')
    WHEN 'deskripsi' THEN COALESCE(s.deskripsi, '')
    WHEN 'ppn' THEN CAST(COALESCE(s.ppn, 0) AS CHAR)
    WHEN 'member_suspend_before_days' THEN CAST(GREATEST(COALESCE(s.member_reminder_days, 7), 1) AS CHAR)
    WHEN 'member_suspend_after_days' THEN CAST(GREATEST(COALESCE(s.member_delete_grace_days, 30), 1) AS CHAR)
    WHEN 'print_mode' THEN COALESCE(s.print_mode, 'per_qty')
    WHEN 'dashboard_metric_mode' THEN COALESCE(s.dashboard_metric_mode, 'amount')
    WHEN 'whatsapp_enabled' THEN CAST(COALESCE(s.whatsapp_enabled, 0) AS CHAR)
    WHEN 'use_logo' THEN CAST(COALESCE(s.use_logo, 0) AS CHAR)
    ELSE ''
  END AS `value`,
  NOW(),
  NOW()
FROM (SELECT * FROM settings_legacy ORDER BY id ASC LIMIT 1) s
JOIN (
  SELECT 'name' AS `key`
  UNION ALL SELECT 'logo'
  UNION ALL SELECT 'ucapan'
  UNION ALL SELECT 'deskripsi'
  UNION ALL SELECT 'ppn'
  UNION ALL SELECT 'member_suspend_before_days'
  UNION ALL SELECT 'member_suspend_after_days'
  UNION ALL SELECT 'print_mode'
  UNION ALL SELECT 'dashboard_metric_mode'
  UNION ALL SELECT 'whatsapp_enabled'
  UNION ALL SELECT 'use_logo'
) k;
