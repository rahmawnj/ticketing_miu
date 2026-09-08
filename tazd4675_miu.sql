-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Mar 17, 2026 at 02:59 PM
-- Server version: 11.4.10-MariaDB-cll-lve
-- PHP Version: 8.4.18

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tazd4675_miu`
--

-- --------------------------------------------------------

--
-- Table structure for table `detail_transactions`
--

CREATE TABLE `detail_transactions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `transaction_id` bigint(20) UNSIGNED NOT NULL,
  `ticket_id` bigint(20) UNSIGNED NOT NULL,
  `qty` int(11) NOT NULL,
  `total` int(11) NOT NULL,
  `ppn` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `ticket_code` varchar(255) DEFAULT NULL,
  `status` enum('open','close') NOT NULL DEFAULT 'open',
  `scanned` int(11) NOT NULL DEFAULT 0,
  `scanned_at` timestamp NULL DEFAULT NULL,
  `gate` int(11) DEFAULT NULL,
  `is_print` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gate_accesses`
--

CREATE TABLE `gate_accesses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `gate_access_id` char(25) NOT NULL,
  `name` varchar(255) NOT NULL,
  `is_active` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `gate_accesses`
--


-- --------------------------------------------------------

--
-- Table structure for table `gate_access_membership`
--

CREATE TABLE `gate_access_membership` (
  `gate_access_id` bigint(20) UNSIGNED NOT NULL,
  `membership_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `histories`
--

CREATE TABLE `histories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `member_id` int(11) DEFAULT 0,
  `gate` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `waktu` timestamp NULL DEFAULT NULL,
  `user_id` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `history_memberships`
--

CREATE TABLE `history_memberships` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `membership_id` bigint(20) UNSIGNED NOT NULL,
  `member_id` bigint(20) UNSIGNED NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `status` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `history_penyewaans`
--

CREATE TABLE `history_penyewaans` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `member_id` bigint(20) UNSIGNED NOT NULL,
  `penyewaan_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jenis_tickets`
--

CREATE TABLE `jenis_tickets` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nama_jenis` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `jenis_tickets`
--


-- --------------------------------------------------------

--
-- Table structure for table `limit_members`
--

CREATE TABLE `limit_members` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `limit` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `members`
--

CREATE TABLE `members` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `parent_id` int(11) DEFAULT 0,
  `membership_id` bigint(20) DEFAULT 0,
  `rfid` varchar(255) DEFAULT NULL,
  `no_ktp` varchar(255) DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `nama` varchar(255) NOT NULL,
  `alamat` text NOT NULL,
  `tgl_lahir` date NOT NULL,
  `tgl_register` date NOT NULL,
  `tgl_expired` date NOT NULL,
  `saldo` int(11) NOT NULL DEFAULT 0,
  `is_active` int(11) NOT NULL DEFAULT 0,
  `jenis_kelamin` varchar(255) DEFAULT NULL,
  `image_profile` varchar(255) DEFAULT NULL,
  `qr_code` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `limit` int(11) NOT NULL DEFAULT 0,
  `jenis_member` varchar(50) DEFAULT NULL,
  `access_used` int(11) NOT NULL DEFAULT 0,
  `member_code` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `memberships`
--

CREATE TABLE `memberships` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `duration_days` int(11) NOT NULL,
  `price` double NOT NULL,
  `max_person` int(11) NOT NULL DEFAULT 1,
  `is_active` int(11) NOT NULL DEFAULT 1,
  `use_ppn` tinyint(1) NOT NULL DEFAULT 0,
  `ppn` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `max_access` int(11) NOT NULL DEFAULT 0,
  `code` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `memberships`
--


-- --------------------------------------------------------

--
-- Table structure for table `membership_admin_fees`
--

CREATE TABLE `membership_admin_fees` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `admin_type` varchar(100) NOT NULL,
  `admin_fee` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--


-- --------------------------------------------------------

--
-- Table structure for table `model_has_permissions`
--

CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `model_has_roles`
--

CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `model_has_roles`
--

INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES
(1, 'App\\Models\\User', 1),
(2, 'App\\Models\\User', 2),
(2, 'App\\Models\\User', 3),
(2, 'App\\Models\\User', 4),
(1, 'App\\Models\\User', 5),
(1, 'App\\Models\\User', 6),
(1, 'App\\Models\\User', 7);

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `penyewaans`
--

CREATE TABLE `penyewaans` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `sewa_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `qty` int(11) NOT NULL DEFAULT 1,
  `metode` varchar(30) NOT NULL,
  `jumlah` int(11) NOT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `bayar` double NOT NULL,
  `kembali` double NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `penyewaans`
--


-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'master-access', 'web', '2026-01-30 07:32:23', '2026-01-30 07:32:23'),
(2, 'user-access', 'web', '2026-01-30 07:32:23', '2026-01-30 07:32:23'),
(3, 'ticket-access', 'web', '2026-01-30 07:32:23', '2026-01-30 07:32:23'),
(4, 'sewa-access', 'web', '2026-01-30 07:32:23', '2026-01-30 07:32:23'),
(5, 'member-access', 'web', '2026-01-30 07:32:23', '2026-01-30 07:32:23'),
(6, 'transaction-access', 'web', '2026-01-30 07:32:23', '2026-01-30 07:32:23'),
(7, 'penyewaan-access', 'web', '2026-01-30 07:32:23', '2026-01-30 07:32:23'),
(8, 'topup-access', 'web', '2026-01-30 07:32:23', '2026-01-30 07:32:23'),
(9, 'report-access', 'web', '2026-01-30 07:32:23', '2026-01-30 07:32:23'),
(10, 'report-transaction-access', 'web', '2026-01-30 07:32:23', '2026-01-30 07:32:23'),
(11, 'report-penyewaan-access', 'web', '2026-01-30 07:32:23', '2026-01-30 07:32:23'),
(12, 'transaction-delete', 'web', '2026-01-30 07:32:23', '2026-01-30 07:32:23'),
(13, 'penyewaan-delete', 'web', '2026-01-30 07:32:23', '2026-01-30 07:32:23'),
(14, 'topup-delete', 'web', '2026-01-30 07:32:23', '2026-01-30 07:32:23'),
(15, 'management-access', 'web', '2026-01-30 07:32:23', '2026-01-30 07:32:23');

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'Admin', 'web', '2026-01-30 07:32:23', '2026-01-30 07:32:23'),
(2, 'Kasir', 'web', '2026-01-30 08:29:18', '2026-01-30 08:29:18');

-- --------------------------------------------------------

--
-- Table structure for table `role_has_permissions`
--

CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_has_permissions`
--

INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES
(1, 1),
(2, 1),
(3, 1),
(4, 1),
(5, 1),
(6, 1),
(7, 1),
(8, 1),
(9, 1),
(10, 1),
(11, 1),
(12, 1),
(13, 1),
(14, 1),
(15, 1),
(3, 2),
(4, 2),
(5, 2),
(6, 2),
(9, 2),
(10, 2);

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `ucapan` varchar(255) DEFAULT NULL,
  `deskripsi` varchar(255) DEFAULT NULL,
  `ppn` int(11) DEFAULT NULL,
  `member_reminder_days` int(11) NOT NULL DEFAULT 7,
  `member_delete_grace_days` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `use_logo` int(11) NOT NULL DEFAULT 1,
  `print_mode` varchar(20) NOT NULL DEFAULT 'per_qty',
  `dashboard_metric_mode` varchar(20) NOT NULL DEFAULT 'amount',
  `whatsapp_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `key` varchar(191) NOT NULL,
  `value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `name`, `logo`, `ucapan`, `deskripsi`, `ppn`, `member_reminder_days`, `member_delete_grace_days`, `created_at`, `updated_at`, `use_logo`, `print_mode`, `dashboard_metric_mode`, `whatsapp_enabled`, `key`, `value`) VALUES
(1, 'ANWA PURI RESIDENCE SPORT CLUB', 'logo/260216042041133.png', 'Terima kasih atas kunjungan anda', 'WA: 0812xxxx | IG: @sportclub_id', 0, 7, 0, NULL, '2026-02-20 07:24:21', 1, 'per_ticket', 'count', 0, '', NULL),
(2, NULL, NULL, NULL, NULL, NULL, 7, 0, '2026-02-26 04:22:44', '2026-03-16 05:17:07', 1, 'per_qty', 'amount', 0, 'name', 'MARI ISI ULANG'),
(3, NULL, NULL, NULL, NULL, NULL, 7, 0, '2026-02-26 04:22:44', '2026-02-26 04:22:44', 1, 'per_qty', 'amount', 0, 'ucapan', 'Terima kasih atas kunjungan anda'),
(4, NULL, NULL, NULL, NULL, NULL, 7, 0, '2026-02-26 04:22:44', '2026-03-16 03:14:46', 1, 'per_qty', 'amount', 0, 'deskripsi', '-'),
(5, NULL, NULL, NULL, NULL, NULL, 7, 0, '2026-02-26 04:22:44', '2026-03-17 05:01:44', 1, 'per_qty', 'amount', 0, 'ppn', '0'),
(6, NULL, NULL, NULL, NULL, NULL, 7, 0, '2026-02-26 04:22:44', '2026-02-26 04:22:44', 1, 'per_qty', 'amount', 0, 'member_suspend_before_days', '7'),
(7, NULL, NULL, NULL, NULL, NULL, 7, 0, '2026-02-26 04:22:44', '2026-02-26 08:22:23', 1, 'per_qty', 'amount', 0, 'member_suspend_after_days', '30'),
(8, NULL, NULL, NULL, NULL, NULL, 7, 0, '2026-02-26 04:22:44', '2026-02-26 04:22:51', 1, 'per_qty', 'amount', 0, 'member_reactivation_admin_fee', '2500'),
(9, NULL, NULL, NULL, NULL, NULL, 7, 0, '2026-02-26 04:22:44', '2026-03-03 09:33:17', 1, 'per_qty', 'amount', 0, 'print_mode', 'per_ticket'),
(10, NULL, NULL, NULL, NULL, NULL, 7, 0, '2026-02-26 04:22:44', '2026-03-06 06:28:53', 1, 'per_qty', 'amount', 0, 'ticket_print_orientation', 'with_summary'),
(11, NULL, NULL, NULL, NULL, NULL, 7, 0, '2026-02-26 04:22:44', '2026-02-26 08:22:11', 1, 'per_qty', 'amount', 0, 'dashboard_metric_mode', 'count'),
(12, NULL, NULL, NULL, NULL, NULL, 7, 0, '2026-02-26 04:22:44', '2026-02-26 08:51:37', 1, 'per_qty', 'amount', 0, 'use_logo', '1'),
(13, NULL, NULL, NULL, NULL, NULL, 7, 0, '2026-02-26 04:22:44', '2026-02-26 04:22:44', 1, 'per_qty', 'amount', 0, 'whatsapp_enabled', '0'),
(14, NULL, NULL, NULL, NULL, NULL, 7, 0, '2026-02-26 04:22:44', '2026-03-16 03:14:46', 1, 'per_qty', 'amount', 0, 'logo', 'logo/260316101446919.png'),
(15, NULL, NULL, NULL, NULL, NULL, 7, 0, '2026-03-03 09:16:09', '2026-03-16 05:55:40', 1, 'per_qty', 'amount', 0, 'renewal_notice_club_name', 'MIU'),
(16, NULL, NULL, NULL, NULL, NULL, 7, 0, '2026-03-03 09:16:09', '2026-03-16 05:55:40', 1, 'per_qty', 'amount', 0, 'renewal_notice_bank_account', '-'),
(17, NULL, NULL, NULL, NULL, NULL, 7, 0, '2026-03-03 09:16:09', '2026-03-16 05:55:40', 1, 'per_qty', 'amount', 0, 'renewal_notice_admin_phone', '-'),
(18, NULL, NULL, NULL, NULL, NULL, 7, 0, '2026-03-03 09:16:09', '2026-03-16 05:55:40', 1, 'per_qty', 'amount', 0, 'renewal_notice_body_template', '-'),
(19, NULL, NULL, NULL, NULL, NULL, 7, 0, '2026-03-06 06:19:32', '2026-03-17 05:01:27', 1, 'per_qty', 'amount', 0, 'ticket_code_mode', 'unique'),
(20, NULL, NULL, NULL, NULL, NULL, 7, 0, '2026-03-13 06:26:34', '2026-03-13 06:33:50', 1, 'per_qty', 'amount', 0, 'website_status', '1'),
(21, NULL, NULL, NULL, NULL, NULL, 7, 0, '2026-03-13 07:01:42', '2026-03-13 07:01:42', 1, 'per_qty', 'amount', 0, 'ticket_valid_days', '1'),
(22, NULL, NULL, NULL, NULL, NULL, 7, 0, '2026-03-13 07:01:42', '2026-03-17 05:01:00', 1, 'per_qty', 'amount', 0, 'ticket_scan_limit', '3');

-- --------------------------------------------------------

--
-- Table structure for table `settings_legacy`
--

CREATE TABLE `settings_legacy` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `key` varchar(191) NOT NULL,
  `value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sewa`
--

CREATE TABLE `sewa` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `harga` int(11) NOT NULL,
  `device` int(11) NOT NULL,
  `use_time` tinyint(1) NOT NULL DEFAULT 1,
  `use_ppn` tinyint(1) NOT NULL DEFAULT 0,
  `ppn` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_nominal_flexible` tinyint(1) NOT NULL DEFAULT 0,
  `print_qr` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sewa`
--


-- --------------------------------------------------------

--
-- Table structure for table `terusans`
--

CREATE TABLE `terusans` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `tripod` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `terusan_ticket`
--

CREATE TABLE `terusan_ticket` (
  `terusan_id` bigint(20) UNSIGNED NOT NULL,
  `ticket_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tickets`
--

CREATE TABLE `tickets` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `jenis_ticket_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `harga` int(11) NOT NULL,
  `tripod` int(11) NOT NULL,
  `use_ppn` tinyint(1) NOT NULL DEFAULT 0,
  `ppn` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tickets`
--


-- --------------------------------------------------------

--
-- Table structure for table `topups`
--

CREATE TABLE `topups` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `member_id` bigint(20) UNSIGNED NOT NULL,
  `jumlah` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `ticket_id` bigint(20) UNSIGNED DEFAULT 0,
  `member_id` bigint(20) UNSIGNED DEFAULT NULL,
  `member_info` varchar(255) DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `no_trx` int(11) NOT NULL,
  `ticket_code` varchar(255) NOT NULL,
  `transaction_type` enum('renewal','ticket','registration','rental') NOT NULL DEFAULT 'ticket',
  `tipe` enum('group','individual') NOT NULL DEFAULT 'group',
  `amount` int(11) NOT NULL DEFAULT 0,
  `disc` int(11) NOT NULL DEFAULT 0,
  `metode` varchar(30) DEFAULT NULL,
  `discount` int(11) NOT NULL DEFAULT 0,
  `amount_scanned` int(11) NOT NULL DEFAULT 0,
  `status` enum('open','closed') NOT NULL DEFAULT 'open',
  `gate` int(11) DEFAULT NULL,
  `is_active` int(11) NOT NULL DEFAULT 0,
  `ppn` decimal(12,2) NOT NULL DEFAULT 0.00,
  `admin_fee` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_print` int(11) DEFAULT 0,
  `bayar` double NOT NULL DEFAULT 0,
  `kembali` double NOT NULL DEFAULT 0,
  `nama_kartu` varchar(100) DEFAULT NULL,
  `no_kartu` varchar(100) DEFAULT NULL,
  `bank` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `uid` char(30) DEFAULT NULL,
  `is_active` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `password`, `foto`, `created_at`, `updated_at`, `uid`, `is_active`) VALUES
(1, 'Super Admin', 'superadmin', '$2y$10$sswDhoSF0XL0ePqzbGcZJOHuLzwe5TezY5l9DYQvXPzdR2iB79EaK', NULL, '2026-01-30 07:32:23', '2026-01-30 08:26:42', NULL, 1),
(2, 'DIVA', 'DIVA', '$2y$10$mcPSv/7mCO010Go3kGJFOuarb53PATgaMeZi.pOzhe/FCdQkV9eVa', NULL, '2026-02-03 07:07:22', '2026-02-03 07:10:00', NULL, 1),
(3, 'KEVIN', 'KEVIN', '$2y$10$2C7obZUng.O47ULQdZpxBO3hQHiOS4CxQC2p0UFHe3En4c2UCnIr.', NULL, '2026-02-03 07:07:47', '2026-02-03 07:07:47', NULL, 1),
(4, 'MAYA', 'MAYA', '$2y$10$A/ZMIBDvb1Z7nFynj7pfYetRXQFzjtdGR/laSgkfb.MghuY.N1A3.', NULL, '2026-02-03 07:08:14', '2026-03-04 02:14:36', NULL, 1),
(5, 'MULI', 'MULI', '$2y$10$u9cxNuvO57mREpFBdK4NlumQeOluvkvTF/skXYJ4/U4/41N50fXSy', NULL, '2026-02-03 07:15:49', '2026-03-04 02:04:13', NULL, 1),
(6, 'HERNIE', 'HERNIE', '$2y$10$vM5u5YelgUUGJig/ktVaauElUq9/rIZkKf/A4dHasLabvp1Mlub..', NULL, '2026-02-03 07:16:11', '2026-02-03 07:16:11', NULL, 1),
(7, 'Vicky', 'vicky', '$2y$10$WbJO3FUzoNnFmtLK15s3.u6qyMXb/BqMh1uURGG1yxq1e8aNbttt2', NULL, '2026-02-20 07:13:46', '2026-02-20 07:13:46', NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `whatsapp_notification_logs`
--

CREATE TABLE `whatsapp_notification_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `type` varchar(50) NOT NULL COMMENT 'renewal_reminder, invoice',
  `member_id` bigint(20) UNSIGNED DEFAULT NULL,
  `transaction_id` bigint(20) UNSIGNED DEFAULT NULL,
  `recipient_phone` varchar(25) NOT NULL,
  `message` text NOT NULL,
  `status` enum('pending','sent','failed') NOT NULL DEFAULT 'pending',
  `retry_count` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `provider_response` text DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `detail_transactions`
--
ALTER TABLE `detail_transactions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `gate_accesses`
--
ALTER TABLE `gate_accesses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `gate_accesses_gate_access_id_unique` (`gate_access_id`);

--
-- Indexes for table `gate_access_membership`
--
ALTER TABLE `gate_access_membership`
  ADD KEY `gate_access_membership_gate_access_id_foreign` (`gate_access_id`),
  ADD KEY `gate_access_membership_membership_id_foreign` (`membership_id`);

--
-- Indexes for table `histories`
--
ALTER TABLE `histories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `history_memberships`
--
ALTER TABLE `history_memberships`
  ADD PRIMARY KEY (`id`),
  ADD KEY `history_memberships_membership_id_foreign` (`membership_id`),
  ADD KEY `history_memberships_member_id_foreign` (`member_id`);

--
-- Indexes for table `history_penyewaans`
--
ALTER TABLE `history_penyewaans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `history_penyewaans_member_id_foreign` (`member_id`),
  ADD KEY `history_penyewaans_penyewaan_id_foreign` (`penyewaan_id`);

--
-- Indexes for table `jenis_tickets`
--
ALTER TABLE `jenis_tickets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `limit_members`
--
ALTER TABLE `limit_members`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `members`
--
ALTER TABLE `members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `members_member_code_unique` (`member_code`),
  ADD UNIQUE KEY `members_rfid_unique` (`rfid`);

--
-- Indexes for table `memberships`
--
ALTER TABLE `memberships`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `membership_admin_fees`
--
ALTER TABLE `membership_admin_fees`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  ADD KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  ADD KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD KEY `password_resets_email_index` (`email`);

--
-- Indexes for table `penyewaans`
--
ALTER TABLE `penyewaans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `penyewaans_sewa_id_foreign` (`sewa_id`),
  ADD KEY `penyewaans_user_id_foreign` (`user_id`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`role_id`),
  ADD KEY `role_has_permissions_role_id_foreign` (`role_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `key` (`key`);

--
-- Indexes for table `settings_legacy`
--
ALTER TABLE `settings_legacy`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `key` (`key`);

--
-- Indexes for table `sewa`
--
ALTER TABLE `sewa`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `terusans`
--
ALTER TABLE `terusans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `terusans_tripod_unique` (`tripod`);

--
-- Indexes for table `terusan_ticket`
--
ALTER TABLE `terusan_ticket`
  ADD KEY `terusan_ticket_terusan_id_foreign` (`terusan_id`),
  ADD KEY `terusan_ticket_ticket_id_foreign` (`ticket_id`);

--
-- Indexes for table `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tickets_jenis_ticket_id_foreign` (`jenis_ticket_id`);

--
-- Indexes for table `topups`
--
ALTER TABLE `topups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `topups_member_id_foreign` (`member_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transactions_user_id_foreign` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_username_unique` (`username`),
  ADD UNIQUE KEY `users_uid_unique` (`uid`);

--
-- Indexes for table `whatsapp_notification_logs`
--
ALTER TABLE `whatsapp_notification_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_wa_logs_type_status` (`type`,`status`),
  ADD KEY `idx_wa_logs_member_id` (`member_id`),
  ADD KEY `idx_wa_logs_transaction_id` (`transaction_id`),
  ADD KEY `idx_wa_logs_recipient_phone` (`recipient_phone`),
  ADD KEY `idx_wa_logs_sent_at` (`sent_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `detail_transactions`
--
ALTER TABLE `detail_transactions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gate_accesses`
--
ALTER TABLE `gate_accesses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `histories`
--
ALTER TABLE `histories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `history_memberships`
--
ALTER TABLE `history_memberships`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `history_penyewaans`
--
ALTER TABLE `history_penyewaans`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jenis_tickets`
--
ALTER TABLE `jenis_tickets`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `limit_members`
--
ALTER TABLE `limit_members`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `members`
--
ALTER TABLE `members`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `memberships`
--
ALTER TABLE `memberships`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `membership_admin_fees`
--
ALTER TABLE `membership_admin_fees`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `penyewaans`
--
ALTER TABLE `penyewaans`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `settings_legacy`
--
ALTER TABLE `settings_legacy`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sewa`
--
ALTER TABLE `sewa`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `terusans`
--
ALTER TABLE `terusans`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tickets`
--
ALTER TABLE `tickets`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `topups`
--
ALTER TABLE `topups`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `whatsapp_notification_logs`
--
ALTER TABLE `whatsapp_notification_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `gate_access_membership`
--
ALTER TABLE `gate_access_membership`
  ADD CONSTRAINT `gate_access_membership_gate_access_id_foreign` FOREIGN KEY (`gate_access_id`) REFERENCES `gate_accesses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `gate_access_membership_membership_id_foreign` FOREIGN KEY (`membership_id`) REFERENCES `memberships` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `history_memberships`
--
ALTER TABLE `history_memberships`
  ADD CONSTRAINT `history_memberships_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `history_memberships_membership_id_foreign` FOREIGN KEY (`membership_id`) REFERENCES `memberships` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `history_penyewaans`
--
ALTER TABLE `history_penyewaans`
  ADD CONSTRAINT `history_penyewaans_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`),
  ADD CONSTRAINT `history_penyewaans_penyewaan_id_foreign` FOREIGN KEY (`penyewaan_id`) REFERENCES `penyewaans` (`id`);

--
-- Constraints for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `penyewaans`
--
ALTER TABLE `penyewaans`
  ADD CONSTRAINT `penyewaans_sewa_id_foreign` FOREIGN KEY (`sewa_id`) REFERENCES `sewa` (`id`),
  ADD CONSTRAINT `penyewaans_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `terusan_ticket`
--
ALTER TABLE `terusan_ticket`
  ADD CONSTRAINT `terusan_ticket_terusan_id_foreign` FOREIGN KEY (`terusan_id`) REFERENCES `terusans` (`id`),
  ADD CONSTRAINT `terusan_ticket_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`);

--
-- Constraints for table `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `tickets_jenis_ticket_id_foreign` FOREIGN KEY (`jenis_ticket_id`) REFERENCES `jenis_tickets` (`id`);

--
-- Constraints for table `topups`
--
ALTER TABLE `topups`
  ADD CONSTRAINT `topups_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`);

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
