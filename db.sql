-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               8.4.3 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- HeidiSQL Version:             12.8.0.6908
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

-- Dumping structure for table api_gymmaster_id.gym_brands
CREATE TABLE IF NOT EXISTS `gym_brands` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `gym_code` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `city` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `gym_brands_gym_code_unique` (`gym_code`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table api_gymmaster_id.gym_brands: ~5 rows (approximately)
DELETE FROM `gym_brands`;
INSERT INTO `gym_brands` (`id`, `gym_code`, `name`, `city`, `address`, `description`, `created_at`, `updated_at`) VALUES
	(1, 'GM-BDG-01', 'GymMaster Braga', 'Bandung', 'Jl. Braga No. 10, Bandung', 'Cabang pusat GymMaster area Bandung.', '2026-03-31 10:21:38', '2026-03-31 10:21:38'),
	(2, 'GM-JKT-01', 'GymMaster Sudirman', 'Jakarta', 'Jl. Jend. Sudirman No. 88, Jakarta', 'Cabang premium untuk area Jakarta pusat.', '2026-03-31 10:21:38', '2026-03-31 10:21:38'),
	(3, 'GM-SBY-01', 'GymMaster Tunjungan', 'Surabaya', 'Jl. Tunjungan No. 15, Surabaya', 'Cabang GymMaster untuk area Surabaya.', '2026-03-31 10:21:38', '2026-03-31 10:21:38'),
	(4, 'GM-BKS-01', 'GymMaster Summarecon', 'Bekasi', 'Jl. Bulevar Summarecon No. 21, Bekasi', 'Cabang ramai untuk area Bekasi.', '2026-03-31 10:21:38', '2026-03-31 10:21:38'),
	(5, 'GM-YK-01', 'GymMaster Malioboro', 'Yogyakarta', 'Jl. Malioboro No. 50, Yogyakarta', 'Cabang strategis untuk area Yogyakarta.', '2026-03-31 10:21:38', '2026-03-31 10:21:38');

-- Dumping structure for table api_gymmaster_id.member_gym_joins
CREATE TABLE IF NOT EXISTS `member_gym_joins` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `gym_brand_id` bigint unsigned NOT NULL,
  `joined_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `member_gym_joins_user_brand_unique` (`user_id`,`gym_brand_id`),
  KEY `member_gym_joins_brand_index` (`gym_brand_id`),
  CONSTRAINT `member_gym_joins_gym_brand_id_foreign` FOREIGN KEY (`gym_brand_id`) REFERENCES `gym_brands` (`id`) ON DELETE CASCADE,
  CONSTRAINT `member_gym_joins_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table api_gymmaster_id.member_gym_joins: ~3 rows (approximately)
DELETE FROM `member_gym_joins`;
INSERT INTO `member_gym_joins` (`id`, `user_id`, `gym_brand_id`, `joined_at`, `created_at`) VALUES
	(1, 1, 1, '2026-03-31 10:21:38', '2026-03-31 10:21:38'),
	(2, 1, 2, '2026-03-31 10:21:38', '2026-03-31 10:21:38'),
	(3, 1, 3, '2026-03-31 10:22:26', '2026-03-31 10:22:26');

-- Dumping structure for table api_gymmaster_id.personal_access_tokens
CREATE TABLE IF NOT EXISTS `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_user_id_index` (`user_id`),
  CONSTRAINT `personal_access_tokens_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table api_gymmaster_id.personal_access_tokens: ~4 rows (approximately)
DELETE FROM `personal_access_tokens`;
INSERT INTO `personal_access_tokens` (`id`, `user_id`, `token`, `created_at`, `expires_at`) VALUES
	(1, 1, 'b8c2dad2e873d7235529afa32524ac8fdcf6a202d89e1ccf8694d02d0cab532d', '2026-03-31 10:06:26', NULL),
	(2, 1, '7fc96739549913bc1d0d3bbca6ac108c1358d4e32bea4b8edc874b3561bc9469', '2026-03-31 10:06:39', NULL),
	(3, 2, 'ba9fbc589b25993e4292fc4a9a9a80334718e0761590de4aab2839814e972d6e', '2026-03-31 10:16:12', NULL),
	(4, 2, '7561b355f3317bfd11b3218f21fc101f549e7e275808f13c1e55297aa9e0255d', '2026-03-31 10:16:22', NULL);

-- Dumping structure for table api_gymmaster_id.users
CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `member_code` varchar(24) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `province_id` int unsigned NOT NULL,
  `city_id` int unsigned NOT NULL,
  `district_id` int unsigned NOT NULL,
  `sub_district_id` int unsigned NOT NULL,
  `post_code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_member_code_unique` (`member_code`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table api_gymmaster_id.users: ~2 rows (approximately)
DELETE FROM `users`;
INSERT INTO `users` (`id`, `member_code`, `name`, `email`, `password`, `phone`, `province_id`, `city_id`, `district_id`, `sub_district_id`, `post_code`, `address`, `created_at`, `updated_at`) VALUES
	(1, 'ED2S-QKGX-GK07-M162', 'MEMBER NO TOKEN', 'aizen@dummy.com', 'password', '0811111111', 7, 119, 1515, 21012, '808', 'jalan tanpa token', '2026-03-31 09:54:20', '2026-03-31 10:08:32'),
	(2, '23QJ-Q1PB-K16T-FYMC', 'Jinnie', 'jinnie@gmail.com', 'jinnie@gmail.com', '0884748384', 12, 167, 2551, 29361, '46462', 'Bandung', '2026-03-31 10:16:12', '2026-03-31 10:16:12');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
