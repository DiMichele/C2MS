-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Creato il: Giu 23, 2025 alle 00:57
-- Versione del server: 10.4.32-MariaDB
-- Versione PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `c2ms_db`
--

-- --------------------------------------------------------

--
-- Struttura della tabella `activity_attachments`
--

CREATE TABLE `activity_attachments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `activity_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  `type` enum('link','file','document','image') NOT NULL DEFAULT 'link',
  `file_name` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `mime_type` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `activity_attachments`
--

INSERT INTO `activity_attachments` (`id`, `activity_id`, `title`, `url`, `type`, `file_name`, `file_path`, `file_size`, `mime_type`, `created_at`, `updated_at`) VALUES
(1, 1, 'Modulo di inventario', 'https://example.com/modulo-inventario', 'link', NULL, NULL, NULL, NULL, '2025-06-22 20:08:38', '2025-06-22 20:08:38'),
(2, 3, 'Programma corso', 'https://example.com/programma-corso-primo-soccorso', 'link', NULL, NULL, NULL, NULL, '2025-06-22 20:08:38', '2025-06-22 20:08:38'),
(3, 3, 'Lista materiale', 'https://example.com/lista-materiale-corso', 'link', NULL, NULL, NULL, NULL, '2025-06-22 20:08:38', '2025-06-22 20:08:38'),
(4, 5, 'Documenti di viaggio', 'https://example.com/documenti-viaggio', 'link', NULL, NULL, NULL, NULL, '2025-06-22 20:08:38', '2025-06-22 20:08:38'),
(5, 5, 'Programma addestramento', 'https://example.com/programma-addestramento', 'link', NULL, NULL, NULL, NULL, '2025-06-22 20:08:38', '2025-06-22 20:08:38'),
(6, 7, 'Scheda tecnica sistema', 'https://example.com/scheda-tecnica', 'link', NULL, NULL, NULL, NULL, '2025-06-22 20:08:38', '2025-06-22 20:08:38');

-- --------------------------------------------------------

--
-- Struttura della tabella `activity_militare`
--

CREATE TABLE `activity_militare` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `activity_id` bigint(20) UNSIGNED NOT NULL,
  `militare_id` bigint(20) UNSIGNED NOT NULL,
  `role` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `activity_militare`
--

INSERT INTO `activity_militare` (`id`, `activity_id`, `militare_id`, `role`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 9, NULL, NULL, NULL, NULL),
(2, 1, 11, NULL, NULL, NULL, NULL),
(3, 2, 9, NULL, NULL, NULL, NULL),
(4, 2, 12, NULL, NULL, NULL, NULL),
(5, 2, 16, NULL, NULL, NULL, NULL),
(6, 3, 2, NULL, NULL, NULL, NULL),
(7, 3, 4, NULL, NULL, NULL, NULL),
(8, 3, 7, NULL, NULL, NULL, NULL),
(9, 3, 14, NULL, NULL, NULL, NULL),
(10, 4, 3, NULL, NULL, NULL, NULL),
(11, 4, 13, NULL, NULL, NULL, NULL),
(12, 5, 3, NULL, NULL, NULL, NULL),
(13, 5, 4, NULL, NULL, NULL, NULL),
(14, 5, 6, NULL, NULL, NULL, NULL),
(15, 5, 13, NULL, NULL, NULL, NULL),
(16, 5, 16, NULL, NULL, NULL, NULL),
(17, 6, 1, NULL, NULL, NULL, NULL),
(18, 6, 8, NULL, NULL, NULL, NULL),
(19, 6, 14, NULL, NULL, NULL, NULL),
(20, 7, 16, NULL, NULL, NULL, NULL),
(21, 7, 20, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Struttura della tabella `board_activities`
--

CREATE TABLE `board_activities` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `column_id` bigint(20) UNSIGNED NOT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `order` int(11) NOT NULL DEFAULT 0,
  `status` enum('active','completed','cancelled','on_hold') NOT NULL DEFAULT 'active',
  `priority` varchar(255) NOT NULL DEFAULT 'normal',
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `board_activities`
--

INSERT INTO `board_activities` (`id`, `title`, `description`, `start_date`, `end_date`, `column_id`, `created_by`, `order`, `status`, `priority`, `tags`, `created_at`, `updated_at`) VALUES
(1, 'Revisione equipaggiamento tattico', 'Controllare ed aggiornare l\'inventario dell\'equipaggiamento tattico in uso. Verificare lo stato di manutenzione e riportare eventuali problemi.', '2025-06-24', '2025-06-27', 1, 1, 1, 'active', 'normal', NULL, '2025-06-22 20:08:38', '2025-06-22 20:08:38'),
(2, 'Preparazione esercitazione annuale', 'Preparare briefing e documenti per l\'esercitazione annuale. Coordinare con gli altri reparti per la logistica e programmazione.', '2025-06-25', '2025-07-02', 1, 1, 2, 'active', 'normal', NULL, '2025-06-22 20:08:38', '2025-06-22 20:08:38'),
(3, 'Corso di primo soccorso', 'Organizzazione corso base di primo soccorso per tutto il personale. Prenotare aula, definire orari e preparare materiale didattico.', '2025-07-07', '2025-07-09', 2, 1, 1, 'active', 'normal', NULL, '2025-06-22 20:08:38', '2025-06-22 20:08:38'),
(4, 'Aggiornamento procedure operative', 'Revisione e aggiornamento delle procedure operative standard secondo le nuove direttive. Preparare documentazione e presentazione per il briefing.', '2025-07-12', '2025-07-17', 2, 1, 2, 'active', 'normal', NULL, '2025-06-22 20:08:38', '2025-06-22 20:08:38'),
(5, 'Missione addestramento congiunto', 'Partecipazione all\'addestramento congiunto con le forze alleate. Organizzare trasferta, alloggi e programma dettagliato.', '2025-07-22', '2025-07-29', 3, 1, 1, 'active', 'normal', NULL, '2025-06-22 20:08:38', '2025-06-22 20:08:38'),
(6, 'Ispezione straordinaria', 'Preparazione documentazione e personale per ispezione straordinaria prevista dalla Direzione. Organizzare briefing preparatorio e verificare tutti i reparti.', '2025-06-23', '2025-06-24', 4, 1, 1, 'active', 'normal', NULL, '2025-06-22 20:08:38', '2025-06-22 20:08:38'),
(7, 'Manutenzione sistema comunicazioni', 'Manutenzione urgente del sistema di comunicazioni a seguito di guasto rilevato. Contattare assistenza tecnica e coordinare intervento.', '2025-06-22', '2025-06-23', 4, 1, 2, 'active', 'normal', NULL, '2025-06-22 20:08:38', '2025-06-22 20:08:38');

-- --------------------------------------------------------

--
-- Struttura della tabella `board_columns`
--

CREATE TABLE `board_columns` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `order` int(11) NOT NULL DEFAULT 0,
  `color` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `board_columns`
--

INSERT INTO `board_columns` (`id`, `name`, `slug`, `order`, `color`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'In Scadenza', 'in-scadenza', 1, NULL, 1, '2025-06-22 15:37:35', '2025-06-22 15:37:35'),
(2, 'Pianificate', 'pianificate', 2, NULL, 1, '2025-06-22 15:37:35', '2025-06-22 15:37:35'),
(3, 'Fuori Porta', 'fuori-porta', 3, NULL, 1, '2025-06-22 15:37:35', '2025-06-22 15:37:35'),
(4, 'Urgenti', 'urgenti', 4, NULL, 1, '2025-06-22 15:37:35', '2025-06-22 15:37:35');

-- --------------------------------------------------------

--
-- Struttura della tabella `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `certificati`
--

CREATE TABLE `certificati` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `militare_id` bigint(20) UNSIGNED NOT NULL,
  `tipo` varchar(100) NOT NULL,
  `data_ottenimento` date NOT NULL,
  `data_scadenza` date NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `durata` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `certificati_lavoratori`
--

CREATE TABLE `certificati_lavoratori` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `militare_id` bigint(20) UNSIGNED NOT NULL,
  `tipo` varchar(100) NOT NULL,
  `data_ottenimento` date NOT NULL,
  `data_scadenza` date NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `in_scadenza` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `certificati_lavoratori`
--

INSERT INTO `certificati_lavoratori` (`id`, `militare_id`, `tipo`, `data_ottenimento`, `data_scadenza`, `file_path`, `note`, `in_scadenza`, `created_at`, `updated_at`) VALUES
(1, 1, 'corsi_lavoratori_4h', '2022-04-22', '2027-04-22', NULL, '✅ Certificato valido fino al 22/04/2027', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(2, 1, 'corsi_lavoratori_8h', '2024-01-22', '2029-01-22', NULL, '✅ Certificato valido fino al 22/01/2029', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(3, 1, 'corsi_lavoratori_preposti', '2024-11-22', '2029-11-22', NULL, '✅ Certificato valido fino al 22/11/2029', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(4, 1, 'corsi_lavoratori_dirigenti', '2024-04-22', '2029-04-22', NULL, '✅ Certificato valido fino al 22/04/2029', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(5, 2, 'corsi_lavoratori_4h', '2023-08-22', '2028-08-22', NULL, '✅ Certificato valido fino al 22/08/2028', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(6, 2, 'corsi_lavoratori_preposti', '2023-12-22', '2028-12-22', NULL, '✅ Certificato valido fino al 22/12/2028', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(7, 3, 'corsi_lavoratori_4h', '2022-09-22', '2027-09-22', NULL, '✅ Certificato valido fino al 22/09/2027', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(8, 3, 'corsi_lavoratori_8h', '2022-02-22', '2027-02-22', NULL, '✅ Certificato valido fino al 22/02/2027', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(9, 3, 'corsi_lavoratori_preposti', '2024-02-22', '2029-02-22', NULL, '✅ Certificato valido fino al 22/02/2029', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(10, 4, 'corsi_lavoratori_4h', '2020-06-22', '2025-06-22', NULL, '⚠️ CERTIFICATO SCADUTO - Necessario rinnovo urgente', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(11, 4, 'corsi_lavoratori_8h', '2023-02-22', '2028-02-22', NULL, '✅ Certificato valido fino al 22/02/2028', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(12, 4, 'corsi_lavoratori_preposti', '2023-01-22', '2028-01-22', NULL, '✅ Certificato valido fino al 22/01/2028', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(13, 5, 'corsi_lavoratori_4h', '2021-10-22', '2026-10-22', NULL, '✅ Certificato valido fino al 22/10/2026', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(14, 5, 'corsi_lavoratori_8h', '2024-06-22', '2029-06-22', NULL, '✅ Certificato valido fino al 22/06/2029', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(15, 5, 'corsi_lavoratori_preposti', '2022-10-22', '2027-10-22', NULL, '✅ Certificato valido fino al 22/10/2027', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(16, 6, 'corsi_lavoratori_4h', '2024-11-22', '2029-11-22', NULL, '✅ Certificato valido fino al 22/11/2029', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(17, 6, 'corsi_lavoratori_8h', '2024-03-22', '2029-03-22', NULL, '✅ Certificato valido fino al 22/03/2029', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(18, 7, 'corsi_lavoratori_4h', '2024-03-22', '2029-03-22', NULL, '✅ Certificato valido fino al 22/03/2029', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(19, 7, 'corsi_lavoratori_8h', '2022-10-22', '2027-10-22', NULL, '✅ Certificato valido fino al 22/10/2027', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(20, 7, 'corsi_lavoratori_preposti', '2024-01-22', '2029-01-22', NULL, '✅ Certificato valido fino al 22/01/2029', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(21, 8, 'corsi_lavoratori_4h', '2020-04-22', '2025-04-22', NULL, '⚠️ CERTIFICATO SCADUTO - Necessario rinnovo urgente', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(22, 8, 'corsi_lavoratori_8h', '2021-11-22', '2026-11-22', NULL, '✅ Certificato valido fino al 22/11/2026', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(23, 9, 'corsi_lavoratori_4h', '2022-05-22', '2027-05-22', NULL, '✅ Certificato valido fino al 22/05/2027', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(24, 9, 'corsi_lavoratori_8h', '2020-10-22', '2026-01-22', NULL, '🔔 Certificato in scadenza - Programmato rinnovo per Jan 2026', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(25, 9, 'corsi_lavoratori_preposti', '2020-12-22', '2026-03-22', NULL, '🔔 Certificato in scadenza - Programmato rinnovo per Mar 2026', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(26, 10, 'corsi_lavoratori_4h', '2019-07-22', '2024-07-22', NULL, '⚠️ CERTIFICATO SCADUTO - Necessario rinnovo urgente', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(27, 10, 'corsi_lavoratori_8h', '2024-07-22', '2029-07-22', NULL, '✅ Certificato valido fino al 22/07/2029', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(28, 11, 'corsi_lavoratori_4h', '2025-05-22', '2030-05-22', NULL, '✅ Certificato valido fino al 22/05/2030', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(29, 11, 'corsi_lavoratori_8h', '2019-12-22', '2024-12-22', NULL, '⚠️ CERTIFICATO SCADUTO - Necessario rinnovo urgente', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(30, 11, 'corsi_lavoratori_preposti', '2020-10-22', '2026-01-22', NULL, '🔔 Certificato in scadenza - Programmato rinnovo per Jan 2026', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(31, 12, 'corsi_lavoratori_4h', '2025-01-22', '2030-01-22', NULL, '✅ Certificato valido fino al 22/01/2030', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(32, 12, 'corsi_lavoratori_8h', '2023-09-22', '2028-09-22', NULL, '✅ Certificato valido fino al 22/09/2028', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(33, 13, 'corsi_lavoratori_4h', '2024-11-22', '2029-11-22', NULL, '✅ Certificato valido fino al 22/11/2029', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(34, 13, 'corsi_lavoratori_8h', '2023-09-22', '2028-09-22', NULL, '✅ Certificato valido fino al 22/09/2028', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(35, 13, 'corsi_lavoratori_preposti', '2022-03-22', '2027-03-22', NULL, '✅ Certificato valido fino al 22/03/2027', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(36, 14, 'corsi_lavoratori_4h', '2020-08-22', '2025-11-22', NULL, '🔔 Certificato in scadenza - Programmato rinnovo per Nov 2025', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(37, 14, 'corsi_lavoratori_8h', '2024-01-22', '2029-01-22', NULL, '✅ Certificato valido fino al 22/01/2029', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(38, 15, 'corsi_lavoratori_4h', '2025-04-22', '2030-04-22', NULL, '✅ Certificato valido fino al 22/04/2030', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(39, 15, 'corsi_lavoratori_8h', '2024-03-22', '2029-03-22', NULL, '✅ Certificato valido fino al 22/03/2029', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(40, 16, 'corsi_lavoratori_4h', '2020-08-22', '2025-11-22', NULL, '🔔 Certificato in scadenza - Programmato rinnovo per Nov 2025', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(41, 16, 'corsi_lavoratori_8h', '2022-08-22', '2027-08-22', NULL, '✅ Certificato valido fino al 22/08/2027', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(42, 17, 'corsi_lavoratori_4h', '2022-05-22', '2027-05-22', NULL, '✅ Certificato valido fino al 22/05/2027', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(43, 17, 'corsi_lavoratori_8h', '2022-12-22', '2027-12-22', NULL, '✅ Certificato valido fino al 22/12/2027', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(44, 18, 'corsi_lavoratori_4h', '2023-09-22', '2028-09-22', NULL, '✅ Certificato valido fino al 22/09/2028', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(45, 18, 'corsi_lavoratori_8h', '2019-06-22', '2024-06-22', NULL, '⚠️ CERTIFICATO SCADUTO - Necessario rinnovo urgente', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(46, 19, 'corsi_lavoratori_4h', '2019-12-22', '2024-12-22', NULL, '⚠️ CERTIFICATO SCADUTO - Necessario rinnovo urgente', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(47, 19, 'corsi_lavoratori_8h', '2022-10-22', '2027-10-22', NULL, '✅ Certificato valido fino al 22/10/2027', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(48, 20, 'corsi_lavoratori_4h', '2021-12-22', '2026-12-22', NULL, '✅ Certificato valido fino al 22/12/2026', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(49, 20, 'corsi_lavoratori_8h', '2023-12-22', '2028-12-22', NULL, '✅ Certificato valido fino al 22/12/2028', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07');

-- --------------------------------------------------------

--
-- Struttura della tabella `compagnie`
--

CREATE TABLE `compagnie` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nome` varchar(100) NOT NULL,
  `descrizione` varchar(255) DEFAULT NULL,
  `codice` varchar(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `compagnie`
--

INSERT INTO `compagnie` (`id`, `nome`, `descrizione`, `codice`, `created_at`, `updated_at`) VALUES
(1, '1ª Compagnia Trasmissioni', 'Compagnia specializzata in comunicazioni e supporto tecnico', '1CT', '2025-06-22 20:17:06', '2025-06-22 20:17:06');

-- --------------------------------------------------------

--
-- Struttura della tabella `eventi`
--

CREATE TABLE `eventi` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `militare_id` bigint(20) UNSIGNED NOT NULL,
  `tipologia` varchar(100) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `data_inizio` date NOT NULL,
  `data_fine` date NOT NULL,
  `localita` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `stato` enum('programmato','in_corso','completato','annullato') NOT NULL DEFAULT 'programmato',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `eventi`
--

INSERT INTO `eventi` (`id`, `militare_id`, `tipologia`, `nome`, `data_inizio`, `data_fine`, `localita`, `note`, `stato`, `created_at`, `updated_at`) VALUES
(1, 1, 'Addestramento', 'Servizio Guardia Speciale', '2025-06-30', '2025-07-04', 'Centro Logistico', 'Evento programmato secondo calendario operativo.', 'programmato', '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(2, 1, 'Missione', 'Corso Primo Soccorso', '2025-06-27', '2025-06-29', 'Poligono di Tiro', 'Evento programmato secondo calendario operativo.', 'programmato', '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(3, 1, 'Missione', 'Servizio Guardia Speciale', '2025-07-14', '2025-07-20', 'Caserma Centrale', 'Evento programmato secondo calendario operativo.', 'programmato', '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(4, 2, 'Corso', 'Esercitazione Alpha-7', '2025-07-16', '2025-07-18', 'Sede Distaccata', 'Evento programmato secondo calendario operativo.', 'programmato', '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(5, 2, 'Addestramento', 'Addestramento Sistemi', '2025-07-22', '2025-07-27', 'Campo Addestramento Nord', 'Evento programmato secondo calendario operativo.', 'programmato', '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(6, 3, 'Missione', 'Corso Specializzazione', '2025-07-11', '2025-07-18', 'Caserma Centrale', 'Evento programmato secondo calendario operativo.', 'programmato', '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(7, 3, 'Esercitazione', 'Corso Primo Soccorso', '2025-05-29', '2025-06-03', 'Caserma Centrale', 'Evento programmato secondo calendario operativo.', 'completato', '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(8, 4, 'Servizio Speciale', 'Addestramento Sistemi', '2025-06-13', '2025-06-20', 'Campo Addestramento Nord', 'Evento programmato secondo calendario operativo.', 'completato', '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(9, 4, 'Missione', 'Corso Specializzazione', '2025-07-09', '2025-07-12', 'Caserma Centrale', 'Evento programmato secondo calendario operativo.', 'programmato', '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(10, 5, 'Addestramento', 'Missione Supporto Logistico', '2025-06-23', '2025-06-26', 'Sede Distaccata', 'Evento programmato secondo calendario operativo.', 'programmato', '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(11, 5, 'Servizio Speciale', 'Addestramento Comunicazioni', '2025-06-08', '2025-06-10', 'Campo Addestramento Nord', 'Evento programmato secondo calendario operativo.', 'completato', '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(12, 6, 'Servizio Speciale', 'Corso Specializzazione', '2025-07-18', '2025-07-20', 'Campo Esercitazioni', 'Evento programmato secondo calendario operativo.', 'programmato', '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(13, 6, 'Esercitazione', 'Esercitazione Congiunta', '2025-06-11', '2025-06-18', 'Caserma Centrale', 'Evento programmato secondo calendario operativo.', 'completato', '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(14, 7, 'Corso', 'Addestramento Sistemi', '2025-06-26', '2025-07-02', 'Base Operativa Roma', 'Evento programmato secondo calendario operativo.', 'programmato', '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(15, 7, 'Addestramento', 'Esercitazione Alpha-7', '2025-08-19', '2025-08-20', 'Base Operativa Roma', 'Evento programmato secondo calendario operativo.', 'programmato', '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(16, 8, 'Missione', 'Esercitazione Notturna', '2025-06-07', '2025-06-09', 'Centro Logistico', 'Evento programmato secondo calendario operativo.', 'completato', '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(17, 8, 'Corso', 'Corso Specializzazione', '2025-08-18', '2025-08-23', 'Struttura Esterna', 'Evento programmato secondo calendario operativo.', 'programmato', '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(18, 9, 'Corso', 'Corso Specializzazione', '2025-07-28', '2025-08-04', 'Centro Formazione', 'Evento programmato secondo calendario operativo.', 'programmato', '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(19, 9, 'Corso', 'Addestramento Sistemi', '2025-08-16', '2025-08-23', 'Campo Esercitazioni', 'Evento programmato secondo calendario operativo.', 'programmato', '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(20, 9, 'Esercitazione', 'Corso Aggiornamento Tecnico', '2025-07-11', '2025-07-15', 'Base Operativa Roma', 'Evento programmato secondo calendario operativo.', 'programmato', '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(21, 10, 'Esercitazione', 'Esercitazione Alpha-7', '2025-06-19', '2025-06-24', 'Struttura Esterna', 'Evento programmato secondo calendario operativo.', 'completato', '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(22, 10, 'Corso', 'Addestramento Sistemi', '2025-05-23', '2025-05-30', 'Centro Tecnico Milano', 'Evento programmato secondo calendario operativo.', 'completato', '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(23, 11, 'Servizio Speciale', 'Esercitazione Congiunta', '2025-05-27', '2025-06-03', 'Sede Distaccata', 'Evento programmato secondo calendario operativo.', 'completato', '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(24, 11, 'Missione', 'Esercitazione Alpha-7', '2025-06-04', '2025-06-10', 'Centro Tecnico Milano', 'Evento programmato secondo calendario operativo.', 'completato', '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(25, 11, 'Corso', 'Servizio Guardia Speciale', '2025-06-03', '2025-06-06', 'Sede Distaccata', 'Evento programmato secondo calendario operativo.', 'completato', '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(26, 12, 'Corso', 'Addestramento Comunicazioni', '2025-07-12', '2025-07-19', 'Centro Formazione', 'Evento programmato secondo calendario operativo.', 'programmato', '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(27, 12, 'Missione', 'Corso Specializzazione', '2025-06-07', '2025-06-11', 'Sede Distaccata', 'Evento programmato secondo calendario operativo.', 'completato', '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(28, 13, 'Corso', 'Esercitazione Alpha-7', '2025-07-04', '2025-07-07', 'Centro Formazione', 'Evento programmato secondo calendario operativo.', 'programmato', '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(29, 14, 'Addestramento', 'Esercitazione Congiunta', '2025-07-10', '2025-07-13', 'Centro Tecnico Milano', 'Evento programmato secondo calendario operativo.', 'programmato', '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(30, 14, 'Addestramento', 'Esercitazione Congiunta', '2025-06-26', '2025-07-03', 'Caserma Centrale', 'Evento programmato secondo calendario operativo.', 'programmato', '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(31, 14, 'Servizio Speciale', 'Missione Supporto Logistico', '2025-08-16', '2025-08-18', 'Centro Tecnico Milano', 'Evento programmato secondo calendario operativo.', 'programmato', '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(32, 15, 'Corso', 'Addestramento Comunicazioni', '2025-06-11', '2025-06-13', 'Poligono di Tiro', 'Evento programmato secondo calendario operativo.', 'completato', '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(33, 15, 'Addestramento', 'Addestramento Comunicazioni', '2025-08-11', '2025-08-17', 'Campo Esercitazioni', 'Evento programmato secondo calendario operativo.', 'programmato', '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(34, 16, 'Servizio Speciale', 'Missione Supporto Logistico', '2025-06-01', '2025-06-02', 'Poligono di Tiro', 'Evento programmato secondo calendario operativo.', 'completato', '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(35, 17, 'Servizio Speciale', 'Servizio Guardia Speciale', '2025-06-29', '2025-07-04', 'Centro Logistico', 'Evento programmato secondo calendario operativo.', 'programmato', '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(36, 17, 'Servizio Speciale', 'Corso Specializzazione', '2025-06-01', '2025-06-05', 'Base Operativa Roma', 'Evento programmato secondo calendario operativo.', 'completato', '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(37, 18, 'Missione', 'Missione Supporto Logistico', '2025-08-21', '2025-08-26', 'Campo Esercitazioni', 'Evento programmato secondo calendario operativo.', 'programmato', '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(38, 18, 'Esercitazione', 'Addestramento Comunicazioni', '2025-06-05', '2025-06-09', 'Centro Formazione', 'Evento programmato secondo calendario operativo.', 'completato', '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(39, 19, 'Corso', 'Corso Specializzazione', '2025-07-06', '2025-07-07', 'Sede Distaccata', 'Evento programmato secondo calendario operativo.', 'programmato', '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(40, 20, 'Corso', 'Corso Specializzazione', '2025-06-23', '2025-06-30', 'Base Operativa Roma', 'Evento programmato secondo calendario operativo.', 'programmato', '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(41, 20, 'Corso', 'Corso Primo Soccorso', '2025-07-01', '2025-07-05', 'Campo Esercitazioni', 'Evento programmato secondo calendario operativo.', 'programmato', '2025-06-22 20:17:07', '2025-06-22 20:17:07');

-- --------------------------------------------------------

--
-- Struttura della tabella `failed_jobs`
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
-- Struttura della tabella `gradi`
--

CREATE TABLE `gradi` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nome` varchar(50) NOT NULL,
  `ordine` int(11) NOT NULL,
  `abbreviazione` varchar(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `gradi`
--

INSERT INTO `gradi` (`id`, `nome`, `ordine`, `abbreviazione`, `created_at`, `updated_at`) VALUES
(1, 'Soldato', 1, 'Sol.', '2025-06-22 20:17:06', '2025-06-22 20:17:06'),
(2, 'Caporale', 2, 'Cap.', '2025-06-22 20:17:06', '2025-06-22 20:17:06'),
(3, 'Caporal Maggiore', 3, 'C.M.', '2025-06-22 20:17:06', '2025-06-22 20:17:06'),
(4, 'Sergente', 4, 'Serg.', '2025-06-22 20:17:06', '2025-06-22 20:17:06'),
(5, 'Maresciallo', 5, 'Mar.', '2025-06-22 20:17:06', '2025-06-22 20:17:06'),
(6, 'Tenente', 6, 'Ten.', '2025-06-22 20:17:06', '2025-06-22 20:17:06'),
(7, 'Capitano', 7, 'Cap.', '2025-06-22 20:17:06', '2025-06-22 20:17:06');

-- --------------------------------------------------------

--
-- Struttura della tabella `idoneita`
--

CREATE TABLE `idoneita` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `militare_id` bigint(20) UNSIGNED NOT NULL,
  `tipo` varchar(100) NOT NULL,
  `data_ottenimento` date NOT NULL,
  `data_scadenza` date NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `in_scadenza` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `idoneita`
--

INSERT INTO `idoneita` (`id`, `militare_id`, `tipo`, `data_ottenimento`, `data_scadenza`, `file_path`, `note`, `in_scadenza`, `created_at`, `updated_at`) VALUES
(1, 1, 'idoneita', '2025-04-22', '2026-04-22', NULL, '✅ Idoneità valida fino al 22/04/2026 - Controlli regolari', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(2, 1, 'idoneita_mansione', '2025-03-22', '2026-03-22', NULL, '✅ Idoneità valida fino al 22/03/2026 - Controlli regolari', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(3, 1, 'idoneita_smi', '2024-11-22', '2025-11-22', NULL, '✅ Idoneità valida fino al 22/11/2025 - Controlli regolari', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(4, 2, 'idoneita', '2024-10-22', '2025-10-22', NULL, '✅ Idoneità valida fino al 22/10/2025 - Controlli regolari', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(5, 2, 'idoneita_mansione', '2025-05-22', '2026-05-22', NULL, '✅ Idoneità valida fino al 22/05/2026 - Controlli regolari', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(6, 2, 'idoneita_smi', '2025-05-22', '2026-05-22', NULL, '✅ Idoneità valida fino al 22/05/2026 - Controlli regolari', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(7, 3, 'idoneita', '2023-12-22', '2024-12-22', NULL, '🚨 IDONEITÀ SCADUTA - Visita medica urgente richiesta', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(8, 3, 'idoneita_mansione', '2024-11-22', '2025-11-22', NULL, '✅ Idoneità valida fino al 22/11/2025 - Controlli regolari', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(9, 3, 'idoneita_smi', '2024-08-22', '2025-08-22', NULL, '⏰ Idoneità in scadenza - Prenotare visita medica entro 22/08/2025', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(10, 4, 'idoneita', '2025-04-22', '2026-04-22', NULL, '✅ Idoneità valida fino al 22/04/2026 - Controlli regolari', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(11, 4, 'idoneita_smi', '2024-12-22', '2025-12-22', NULL, '✅ Idoneità valida fino al 22/12/2025 - Controlli regolari', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(12, 5, 'idoneita', '2023-09-22', '2024-09-22', NULL, '🚨 IDONEITÀ SCADUTA - Visita medica urgente richiesta', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(13, 5, 'idoneita_mansione', '2025-02-22', '2026-02-22', NULL, '✅ Idoneità valida fino al 22/02/2026 - Controlli regolari', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(14, 5, 'idoneita_smi', '2024-07-22', '2025-07-22', NULL, '⏰ Idoneità in scadenza - Prenotare visita medica entro 22/07/2025', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(15, 6, 'idoneita', '2025-02-22', '2026-02-22', NULL, '✅ Idoneità valida fino al 22/02/2026 - Controlli regolari', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(16, 6, 'idoneita_mansione', '2025-03-22', '2026-03-22', NULL, '✅ Idoneità valida fino al 22/03/2026 - Controlli regolari', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(17, 6, 'idoneita_smi', '2025-05-22', '2026-05-22', NULL, '✅ Idoneità valida fino al 22/05/2026 - Controlli regolari', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(18, 7, 'idoneita', '2024-10-22', '2025-10-22', NULL, '✅ Idoneità valida fino al 22/10/2025 - Controlli regolari', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(19, 7, 'idoneita_mansione', '2025-04-22', '2026-04-22', NULL, '✅ Idoneità valida fino al 22/04/2026 - Controlli regolari', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(20, 7, 'idoneita_smi', '2024-09-22', '2025-09-22', NULL, '⏰ Idoneità in scadenza - Prenotare visita medica entro 22/09/2025', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(21, 8, 'idoneita', '2025-04-22', '2026-04-22', NULL, '✅ Idoneità valida fino al 22/04/2026 - Controlli regolari', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(22, 8, 'idoneita_mansione', '2025-04-22', '2026-04-22', NULL, '✅ Idoneità valida fino al 22/04/2026 - Controlli regolari', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(23, 8, 'idoneita_smi', '2025-05-22', '2026-05-22', NULL, '✅ Idoneità valida fino al 22/05/2026 - Controlli regolari', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(24, 9, 'idoneita', '2024-10-22', '2025-10-22', NULL, '✅ Idoneità valida fino al 22/10/2025 - Controlli regolari', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(25, 9, 'idoneita_mansione', '2024-11-22', '2025-11-22', NULL, '✅ Idoneità valida fino al 22/11/2025 - Controlli regolari', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(26, 10, 'idoneita', '2024-08-22', '2025-08-22', NULL, '⏰ Idoneità in scadenza - Prenotare visita medica entro 22/08/2025', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(27, 10, 'idoneita_mansione', '2024-12-22', '2025-12-22', NULL, '✅ Idoneità valida fino al 22/12/2025 - Controlli regolari', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(28, 11, 'idoneita', '2025-04-22', '2026-04-22', NULL, '✅ Idoneità valida fino al 22/04/2026 - Controlli regolari', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(29, 11, 'idoneita_mansione', '2024-07-22', '2025-07-22', NULL, '⏰ Idoneità in scadenza - Prenotare visita medica entro 22/07/2025', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(30, 12, 'idoneita', '2025-04-22', '2026-04-22', NULL, '✅ Idoneità valida fino al 22/04/2026 - Controlli regolari', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(31, 12, 'idoneita_mansione', '2025-05-22', '2026-05-22', NULL, '✅ Idoneità valida fino al 22/05/2026 - Controlli regolari', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(32, 13, 'idoneita', '2024-10-22', '2025-10-22', NULL, '✅ Idoneità valida fino al 22/10/2025 - Controlli regolari', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(33, 13, 'idoneita_mansione', '2025-03-22', '2026-03-22', NULL, '✅ Idoneità valida fino al 22/03/2026 - Controlli regolari', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(34, 13, 'idoneita_smi', '2024-10-22', '2025-10-22', NULL, '✅ Idoneità valida fino al 22/10/2025 - Controlli regolari', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(35, 14, 'idoneita', '2024-12-22', '2025-12-22', NULL, '✅ Idoneità valida fino al 22/12/2025 - Controlli regolari', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(36, 14, 'idoneita_mansione', '2025-05-22', '2026-05-22', NULL, '✅ Idoneità valida fino al 22/05/2026 - Controlli regolari', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(37, 15, 'idoneita', '2024-08-22', '2025-08-22', NULL, '⏰ Idoneità in scadenza - Prenotare visita medica entro 22/08/2025', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(38, 15, 'idoneita_mansione', '2024-07-22', '2025-07-22', NULL, '⏰ Idoneità in scadenza - Prenotare visita medica entro 22/07/2025', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(39, 16, 'idoneita', '2024-12-22', '2025-12-22', NULL, '✅ Idoneità valida fino al 22/12/2025 - Controlli regolari', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(40, 16, 'idoneita_mansione', '2024-12-22', '2025-12-22', NULL, '✅ Idoneità valida fino al 22/12/2025 - Controlli regolari', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(41, 17, 'idoneita', '2025-05-22', '2026-05-22', NULL, '✅ Idoneità valida fino al 22/05/2026 - Controlli regolari', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(42, 17, 'idoneita_mansione', '2025-02-22', '2026-02-22', NULL, '✅ Idoneità valida fino al 22/02/2026 - Controlli regolari', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(43, 18, 'idoneita', '2024-12-22', '2025-12-22', NULL, '✅ Idoneità valida fino al 22/12/2025 - Controlli regolari', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(44, 18, 'idoneita_mansione', '2024-11-22', '2025-11-22', NULL, '✅ Idoneità valida fino al 22/11/2025 - Controlli regolari', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(45, 20, 'idoneita', '2024-10-22', '2025-10-22', NULL, '✅ Idoneità valida fino al 22/10/2025 - Controlli regolari', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(46, 20, 'idoneita_mansione', '2025-05-22', '2026-05-22', NULL, '✅ Idoneità valida fino al 22/05/2026 - Controlli regolari', 0, '2025-06-22 20:17:07', '2025-06-22 20:17:07');

-- --------------------------------------------------------

--
-- Struttura della tabella `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `mansioni`
--

CREATE TABLE `mansioni` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nome` varchar(100) NOT NULL,
  `descrizione` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `mansioni`
--

INSERT INTO `mansioni` (`id`, `nome`, `descrizione`, `created_at`, `updated_at`) VALUES
(1, 'Addetto amministrazione', 'Gestione pratiche amministrative e burocratiche', NULL, NULL),
(2, 'Addetto logistica', 'Gestione rifornimenti e movimentazione materiali', NULL, NULL),
(3, 'Addetto armeria', 'Custodia e manutenzione armamenti', NULL, NULL),
(4, 'Addetto sala operativa', 'Monitoraggio e coordinamento operazioni', NULL, NULL),
(5, 'Autista', 'Conduzione mezzi militari', NULL, NULL),
(6, 'Comandante di plotone', 'Comando e coordinamento plotone', NULL, NULL),
(7, 'Comandante di compagnia', 'Comando e coordinamento compagnia', NULL, NULL),
(8, 'Addetto infermeria', 'Assistenza sanitaria di base', NULL, NULL),
(9, 'Addetto informatico', 'Gestione sistemi informatici', NULL, NULL),
(10, 'Addetto radio', 'Gestione comunicazioni radio', NULL, NULL),
(11, 'Addetto fureria', 'Gestione economato e vettovagliamento', NULL, NULL),
(12, 'Addetto MGE', 'Gestione mezzi ed equipaggiamenti', NULL, NULL);

-- --------------------------------------------------------

--
-- Struttura della tabella `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2024_03_28_000000_final_sige_bat_database', 1),
(2, '2025_03_29_130926_create_notas_table', 1),
(3, '2025_03_29_135257_remove_reparto_table', 1),
(4, '2025_03_29_135302_remove_reparto_id_from_militari', 1),
(5, '2025_06_18_170921_create_militare_valutazioni_table', 1),
(6, '2025_06_18_172745_add_note_positive_negative_to_militare_valutazioni_table', 1),
(7, '2025_06_19_083526_add_autonomia_to_militare_valutazioni_table', 1),
(8, '2025_06_22_141919_add_foto_path_to_militari_table', 1),
(9, '2025_06_22_172353_create_board_columns_table', 1),
(10, '2025_06_22_172415_create_board_activities_table', 1),
(11, '2025_06_22_172452_create_activity_attachments_table', 1),
(12, '2025_06_22_172635_create_activity_militare_table', 1),
(13, '2025_06_22_174040_create_eventi_table', 2);

-- --------------------------------------------------------

--
-- Struttura della tabella `militare_valutazioni`
--

CREATE TABLE `militare_valutazioni` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `militare_id` bigint(20) UNSIGNED NOT NULL,
  `valutatore_id` bigint(20) UNSIGNED NOT NULL,
  `precisione_lavoro` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Punteggio 1-5 per precisione nel lavoro',
  `affidabilita` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Punteggio 1-5 per affidabilità',
  `capacita_tecnica` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Punteggio 1-5 per capacità tecnica',
  `collaborazione` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Punteggio 1-5 per collaborazione',
  `iniziativa` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Punteggio 1-5 per iniziativa',
  `autonomia` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Punteggio 1-5 per autonomia',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `note_positive` text DEFAULT NULL COMMENT 'Note positive sulla valutazione',
  `note_negative` text DEFAULT NULL COMMENT 'Note negative o aree di miglioramento'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `militare_valutazioni`
--

INSERT INTO `militare_valutazioni` (`id`, `militare_id`, `valutatore_id`, `precisione_lavoro`, `affidabilita`, `capacita_tecnica`, `collaborazione`, `iniziativa`, `autonomia`, `created_at`, `updated_at`, `note_positive`, `note_negative`) VALUES
(1, 1, 2, 5, 3, 4, 4, 4, 4, '2025-06-22 20:17:07', '2025-06-22 20:17:07', 'Mostra iniziativa e proattività nelle attività quotidiane', NULL),
(2, 2, 2, 4, 5, 3, 5, 3, 3, '2025-06-22 20:17:07', '2025-06-22 20:17:07', 'Eccellente capacità di lavorare in team e supportare i colleghi', 'COMPETENZE DIGITALI: Da migliorare l\'utilizzo degli strumenti software avanzati. Previsto affiancamento con personale esperto.'),
(3, 3, 2, 4, 3, 5, 4, 3, 4, '2025-06-22 20:17:07', '2025-06-22 20:17:07', 'Molto preciso nell\'esecuzione dei compiti assegnati', 'COLLABORAZIONE: Migliorare l\'integrazione con altri reparti. Organizzare incontri interdisciplinari.'),
(4, 4, 2, 4, 5, 3, 5, 3, 3, '2025-06-22 20:17:07', '2025-06-22 20:17:07', 'Mantiene sempre un atteggiamento positivo e costruttivo', NULL),
(5, 5, 2, 4, 3, 5, 5, 3, 5, '2025-06-22 20:17:07', '2025-06-22 20:17:07', 'Ottima capacità di apprendimento e adattamento', 'PROATTIVITÀ: Incoraggiare maggiore iniziativa personale nei progetti di miglioramento. Assegnare responsabilità specifiche.'),
(6, 6, 2, 3, 5, 4, 5, 4, 3, '2025-06-22 20:17:07', '2025-06-22 20:17:07', 'Mostra iniziativa e proattività nelle attività quotidiane', 'DOCUMENTAZIONE: Migliorare la completezza nella redazione dei rapporti. Fornire template standardizzati.'),
(7, 7, 2, 5, 5, 4, 4, 4, 4, '2025-06-22 20:17:07', '2025-06-22 20:17:07', 'Dimostra sempre grande professionalità e dedizione nel lavoro', 'COLLABORAZIONE: Migliorare l\'integrazione con altri reparti. Organizzare incontri interdisciplinari.'),
(8, 8, 2, 5, 3, 4, 4, 4, 4, '2025-06-22 20:17:07', '2025-06-22 20:17:07', 'Dimostra sempre grande professionalità e dedizione nel lavoro', 'COMPETENZE DIGITALI: Da migliorare l\'utilizzo degli strumenti software avanzati. Previsto affiancamento con personale esperto.'),
(9, 9, 2, 3, 5, 4, 4, 3, 4, '2025-06-22 20:17:07', '2025-06-22 20:17:07', 'Eccellente capacità di lavorare in team e supportare i colleghi', NULL),
(10, 10, 2, 4, 3, 5, 4, 3, 4, '2025-06-22 20:17:07', '2025-06-22 20:17:07', 'Molto preciso nell\'esecuzione dei compiti assegnati', 'COMPETENZE DIGITALI: Da migliorare l\'utilizzo degli strumenti software avanzati. Previsto affiancamento con personale esperto.'),
(11, 11, 2, 4, 5, 3, 5, 5, 5, '2025-06-22 20:17:07', '2025-06-22 20:17:07', 'Competenze tecniche superiori alla media del grado', NULL),
(12, 12, 2, 4, 4, 5, 4, 3, 5, '2025-06-22 20:17:07', '2025-06-22 20:17:07', 'Molto preciso nell\'esecuzione dei compiti assegnati', 'COLLABORAZIONE: Migliorare l\'integrazione con altri reparti. Organizzare incontri interdisciplinari.'),
(13, 13, 2, 4, 5, 5, 5, 4, 4, '2025-06-22 20:17:07', '2025-06-22 20:17:07', 'Dimostra leadership naturale e capacità di coordinamento', 'PROBLEM SOLVING: Sviluppare approccio più sistematico nella risoluzione dei problemi. Corso di metodologie analitiche.'),
(14, 14, 2, 3, 3, 5, 5, 5, 3, '2025-06-22 20:17:07', '2025-06-22 20:17:07', 'Competenze tecniche superiori alla media del grado', NULL),
(15, 15, 2, 3, 3, 4, 4, 5, 4, '2025-06-22 20:17:07', '2025-06-22 20:17:07', 'Molto preciso nell\'esecuzione dei compiti assegnati', NULL),
(16, 16, 2, 3, 4, 4, 4, 4, 3, '2025-06-22 20:17:07', '2025-06-22 20:17:07', 'Eccellente gestione dello stress e delle situazioni complesse', NULL),
(17, 17, 2, 4, 4, 3, 4, 5, 3, '2025-06-22 20:17:07', '2025-06-22 20:17:07', 'Competenze tecniche superiori alla media del grado', 'COMUNICAZIONE: Sviluppare maggiore chiarezza nell\'esposizione durante i briefing. Suggerito corso di public speaking.'),
(18, 18, 2, 3, 4, 4, 5, 4, 4, '2025-06-22 20:17:07', '2025-06-22 20:17:07', 'Dimostra leadership naturale e capacità di coordinamento', 'PROBLEM SOLVING: Sviluppare approccio più sistematico nella risoluzione dei problemi. Corso di metodologie analitiche.'),
(19, 19, 2, 4, 4, 5, 5, 5, 3, '2025-06-22 20:17:07', '2025-06-22 20:17:07', 'Dimostra leadership naturale e capacità di coordinamento', NULL),
(20, 20, 2, 4, 5, 5, 4, 4, 4, '2025-06-22 20:17:07', '2025-06-22 20:17:07', 'Mostra iniziativa e proattività nelle attività quotidiane', 'GESTIONE TEMPO: Ottimizzare la pianificazione delle attività quotidiane. Implementare strumenti di time management.');

-- --------------------------------------------------------

--
-- Struttura della tabella `militari`
--

CREATE TABLE `militari` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nome` varchar(100) NOT NULL,
  `cognome` varchar(100) NOT NULL,
  `grado_id` bigint(20) UNSIGNED DEFAULT NULL,
  `compagnia_id` bigint(20) UNSIGNED DEFAULT NULL,
  `plotone_id` bigint(20) UNSIGNED DEFAULT NULL,
  `polo_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ruolo_id` bigint(20) UNSIGNED DEFAULT NULL,
  `mansione_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ruolo` varchar(50) NOT NULL DEFAULT 'Lavoratore',
  `certificati_note` text DEFAULT NULL,
  `idoneita_note` text DEFAULT NULL,
  `data_nascita` date DEFAULT NULL,
  `codice_fiscale` varchar(16) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `foto_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `militari`
--

INSERT INTO `militari` (`id`, `nome`, `cognome`, `grado_id`, `compagnia_id`, `plotone_id`, `polo_id`, `ruolo_id`, `mansione_id`, `ruolo`, `certificati_note`, `idoneita_note`, `data_nascita`, `codice_fiscale`, `email`, `telefono`, `note`, `foto_path`, `created_at`, `updated_at`) VALUES
(1, 'Mario', 'Rossi', 7, 1, NULL, NULL, 3, 7, 'Lavoratore', 'Tutti i certificati aggiornati. Corso comando completato nel 2023.', 'Idoneità al comando confermata. Visite mediche regolari.', NULL, NULL, NULL, NULL, 'Comandante di compagnia. Esperienza pluriennale nel comando. Ottima leadership.', 'militari/Rossi_Mario_1/foto_profilo.png', '2025-06-22 20:17:07', '2025-06-22 20:40:20'),
(2, 'Paolo', 'Verdi', 6, 1, 1, NULL, 2, 6, 'Lavoratore', 'Corsi di specializzazione completati. Aggiornamento previsto per marzo 2025.', 'Idoneità piena. Controlli sanitari regolari.', NULL, NULL, NULL, NULL, 'Comandante 1° Plotone. Giovane ma molto determinato.', 'militari/Verdi_Paolo_2/foto_profilo.png', '2025-06-22 20:17:07', '2025-06-22 20:41:43'),
(3, 'Luca', 'Bianchi', 6, 1, 2, NULL, 2, 6, 'Lavoratore', 'Certificazioni logistiche aggiornate. Corso NATO completato.', 'Idoneità confermata. Specializzazione in operazioni complesse.', NULL, NULL, NULL, NULL, 'Comandante 2° Plotone. Specialista in logistica.', 'militari/Bianchi_Luca_3/foto_profilo.png', '2025-06-22 20:17:07', '2025-06-22 20:41:05'),
(4, 'Andrea', 'Neri', 6, 1, 3, NULL, 2, 6, 'Lavoratore', 'Certificazioni tecniche avanzate. Corso specialistico completato.', 'Idoneità tecnica superiore. Abilitazioni speciali confermate.', NULL, NULL, NULL, NULL, 'Comandante 3° Plotone. Esperto in sistemi tecnici.', 'militari/Neri_Andrea_4/foto_profilo.png', '2025-06-22 20:17:07', '2025-06-22 20:41:20'),
(5, 'Marco', 'Blu', 5, 1, 1, 2, 2, 9, 'Lavoratore', 'Certificazioni informatiche avanzate. Corso cybersecurity 2024.', 'Idoneità per sistemi classificati. Nulla osta sicurezza.', NULL, NULL, NULL, NULL, 'Responsabile Polo Informatico. Esperto in cybersecurity.', 'militari/Blu_Marco_5/foto_profilo.png', '2025-06-22 20:17:07', '2025-06-22 20:41:55'),
(6, 'Giulia', 'Ferrari', 4, 1, 1, 2, 1, 9, 'Lavoratore', 'Certificazioni Microsoft e Linux aggiornate.', 'Idoneità per lavoro su sistemi sensibili.', NULL, NULL, NULL, NULL, 'Specialista sistemi informatici. Ottima competenza tecnica.', 'militari/Ferrari_Giulia_6/foto_profilo.png', '2025-06-22 20:17:07', '2025-06-22 20:42:33'),
(7, 'Francesco', 'Romano', 4, 1, 1, 6, 2, 10, 'Lavoratore', 'Certificazioni radio avanzate. Corso NATO COMMS.', 'Idoneità per operazioni radio classificate.', NULL, NULL, NULL, NULL, 'Responsabile comunicazioni radio. Esperienza operativa.', 'militari/Romano_Francesco_7/foto_profilo.png', '2025-06-22 20:17:07', '2025-06-22 20:43:43'),
(8, 'Elena', 'Conti', 3, 1, 1, 6, 1, 10, 'Lavoratore', 'Certificazioni base radio aggiornate.', 'Idoneità per turni notturni e operazioni prolungate.', NULL, NULL, NULL, NULL, 'Operatrice radio esperta. Precisione e affidabilità.', 'militari/Conti_Elena_8/foto_profilo.png', '2025-06-22 20:17:07', '2025-06-22 20:44:01'),
(9, 'Claudio', 'Martini', 5, 1, 2, 3, 2, 12, 'Lavoratore', 'Patenti speciali e certificazioni meccaniche.', 'Idoneità per conduzione mezzi pesanti.', NULL, NULL, NULL, NULL, 'Responsabile MGE. Esperto in mezzi militari.', 'militari/Martini_Claudio_9/foto_profilo.png', '2025-06-22 20:17:07', '2025-06-22 20:42:10'),
(10, 'Sara', 'Ricci', 3, 1, 2, 3, 1, 12, 'Lavoratore', 'Certificazioni meccaniche di base.', 'Idoneità per lavori di manutenzione.', NULL, NULL, NULL, NULL, 'Specialista manutenzione mezzi. Molto precisa.', 'militari/Ricci_Sara_10/foto_profilo.png', '2025-06-22 20:17:07', '2025-06-22 20:45:07'),
(11, 'Roberto', 'Galli', 4, 1, 2, 4, 2, 11, 'Lavoratore', 'Certificazioni amministrative e contabili.', 'Idoneità per gestione fondi e materiali.', NULL, NULL, NULL, NULL, 'Responsabile Fureria. Gestione economato impeccabile.', 'militari/Galli_Roberto_11/foto_profilo.png', '2025-06-22 20:17:07', '2025-06-22 20:43:13'),
(12, 'Anna', 'Moretti', 2, 1, 2, 4, 1, 11, 'Lavoratore', 'Corsi base amministrazione completati.', 'Idoneità per mansioni amministrative.', NULL, NULL, NULL, NULL, 'Addetta fureria. Organizzata e metodica.', 'militari/Moretti_Anna_12/foto_profilo.png', '2025-06-22 20:17:07', '2025-06-22 20:46:09'),
(13, 'Giovanni', 'Lombardi', 4, 1, 3, 7, 2, 3, 'Lavoratore', 'Certificazioni armiere e sicurezza.', 'Idoneità per gestione armamenti.', NULL, NULL, NULL, NULL, 'Responsabile Armeria. Massima precisione e sicurezza.', 'militari/Lombardi_Giovanni_13/foto_profilo.png', '2025-06-22 20:17:07', '2025-06-22 20:43:25'),
(14, 'Chiara', 'Esposito', 3, 1, 3, 5, 1, 1, 'Lavoratore', 'Certificazioni SIGE e database.', 'Idoneità per sistemi informativi militari.', NULL, NULL, NULL, NULL, 'Specialista SIGE. Competenze informatiche avanzate.', 'militari/Esposito_Chiara_14/foto_profilo.png', '2025-06-22 20:17:07', '2025-06-22 20:44:28'),
(15, 'Davide', 'Russo', 3, 1, 3, 1, 1, 1, 'Lavoratore', 'Certificazioni comunicazioni satellitari.', 'Idoneità per operazioni satellitari.', NULL, NULL, NULL, NULL, 'Operatore satellitare. Competenze tecniche specialistiche.', 'militari/Russo_Davide_15/foto_profilo.png', '2025-06-22 20:17:07', '2025-06-22 20:45:29'),
(16, 'Matteo', 'Costa', 2, 1, 1, 2, 1, 5, 'Lavoratore', 'Patenti militari aggiornate.', 'Idoneità alla guida confermata.', NULL, NULL, NULL, NULL, 'Autista del 1° Plotone. Guida sicura e responsabile.', 'militari/Costa_Matteo_16/foto_profilo.png', '2025-06-22 20:17:07', '2025-06-22 20:45:52'),
(17, 'Federica', 'Mancini', 1, 1, 1, 6, 1, 4, 'Lavoratore', 'Corsi base operativi completati.', 'Idoneità per turni operativi.', NULL, NULL, NULL, NULL, 'Operatrice sala operativa. Attenzione e precisione.', 'militari/Mancini_Federica_17/foto_profilo.png', '2025-06-22 20:17:07', '2025-06-22 20:46:34'),
(18, 'Simone', 'Barbieri', 2, 1, 2, 3, 1, 2, 'Lavoratore', 'Certificazioni logistiche di base.', 'Idoneità per movimentazione carichi.', NULL, NULL, NULL, NULL, 'Addetto logistica 2° Plotone. Organizzazione impeccabile.', 'militari/Barbieri_Simone_18/foto_profilo.png', '2025-06-22 20:17:07', '2025-06-22 20:45:40'),
(19, 'Valentina', 'Santoro', 1, 1, 3, 7, 1, 8, 'Lavoratore', 'Corso primo soccorso e BLS.', 'Idoneità sanitaria per assistenza.', NULL, NULL, NULL, NULL, 'Addetta infermeria. Preparazione sanitaria di base.', 'militari/Santoro_Valentina_19/foto_profilo.png', '2025-06-22 20:17:07', '2025-06-22 20:46:45'),
(20, 'Alessandro', 'De Luca', 1, 1, 3, 5, 1, 4, 'Lavoratore', 'Corsi base in corso di completamento.', 'Idoneità di base confermata.', NULL, NULL, NULL, NULL, 'Operatore tecnico 3° Plotone. In formazione.', 'militari/De Luca_Alessandro_20/foto_profilo.png', '2025-06-22 20:17:07', '2025-06-22 20:46:23');

-- --------------------------------------------------------

--
-- Struttura della tabella `notas`
--

CREATE TABLE `notas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `militare_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `contenuto` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `plotoni`
--

CREATE TABLE `plotoni` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nome` varchar(100) NOT NULL,
  `compagnia_id` bigint(20) UNSIGNED NOT NULL,
  `descrizione` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `plotoni`
--

INSERT INTO `plotoni` (`id`, `nome`, `compagnia_id`, `descrizione`, `created_at`, `updated_at`) VALUES
(1, '1° Plotone Trasmissioni', 1, 'Plotone operativo principale', '2025-06-22 20:17:06', '2025-06-22 20:17:06'),
(2, '2° Plotone Supporto', 1, 'Plotone supporto logistico', '2025-06-22 20:17:06', '2025-06-22 20:17:06'),
(3, '3° Plotone Tecnico', 1, 'Plotone manutenzione tecnica', '2025-06-22 20:17:06', '2025-06-22 20:17:06');

-- --------------------------------------------------------

--
-- Struttura della tabella `poli`
--

CREATE TABLE `poli` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nome` varchar(100) NOT NULL,
  `compagnia_id` bigint(20) UNSIGNED NOT NULL,
  `descrizione` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `poli`
--

INSERT INTO `poli` (`id`, `nome`, `compagnia_id`, `descrizione`, `created_at`, `updated_at`) VALUES
(1, 'Polo Satellitare', 1, 'Gestione comunicazioni satellitari', '2025-06-22 20:17:06', '2025-06-22 20:17:06'),
(2, 'Polo Informatico', 1, 'Gestione sistemi informatici', '2025-06-22 20:17:06', '2025-06-22 20:17:06'),
(3, 'Polo MGE', 1, 'Gestione mezzi ed equipaggiamenti', '2025-06-22 20:17:06', '2025-06-22 20:17:06'),
(4, 'Fureria', 1, 'Gestione economato e vettovagliamento', '2025-06-22 20:17:06', '2025-06-22 20:17:06'),
(5, 'SIGE', 1, 'Sistema Informativo per la Gestione dell\'Esercito', '2025-06-22 20:17:06', '2025-06-22 20:17:06'),
(6, 'Polo Radio', 1, 'Gestione comunicazioni radio', '2025-06-22 20:17:06', '2025-06-22 20:17:06'),
(7, 'Armeria', 1, 'Custodia e manutenzione armamenti', '2025-06-22 20:17:06', '2025-06-22 20:17:06');

-- --------------------------------------------------------

--
-- Struttura della tabella `presenze`
--

CREATE TABLE `presenze` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `militare_id` bigint(20) UNSIGNED NOT NULL,
  `data` date NOT NULL,
  `stato` enum('Presente','Assente','Permesso','Licenza','Missione') NOT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `presenze`
--

INSERT INTO `presenze` (`id`, `militare_id`, `data`, `stato`, `note`, `created_at`, `updated_at`) VALUES
(1, 1, '2025-05-23', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(2, 1, '2025-05-26', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(3, 1, '2025-05-27', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(4, 1, '2025-05-28', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(5, 1, '2025-05-29', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(6, 1, '2025-05-30', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(7, 1, '2025-06-02', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(8, 1, '2025-06-03', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(9, 1, '2025-06-04', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(10, 1, '2025-06-05', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(11, 1, '2025-06-06', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(12, 1, '2025-06-09', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(13, 1, '2025-06-10', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(14, 1, '2025-06-11', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(15, 1, '2025-06-12', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(16, 1, '2025-06-13', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(17, 1, '2025-06-16', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(18, 1, '2025-06-17', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(19, 1, '2025-06-18', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(20, 1, '2025-06-19', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(21, 1, '2025-06-20', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(22, 2, '2025-05-23', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(23, 2, '2025-05-26', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(24, 2, '2025-05-27', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(25, 2, '2025-05-28', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(26, 2, '2025-05-29', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(27, 2, '2025-05-30', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(28, 2, '2025-06-02', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(29, 2, '2025-06-03', 'Assente', 'Assenza giustificata', '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(30, 2, '2025-06-04', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(31, 2, '2025-06-05', 'Assente', 'Assenza giustificata', '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(32, 2, '2025-06-06', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(33, 2, '2025-06-09', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(34, 2, '2025-06-10', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(35, 2, '2025-06-11', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(36, 2, '2025-06-12', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(37, 2, '2025-06-13', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(38, 2, '2025-06-16', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(39, 2, '2025-06-17', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(40, 2, '2025-06-18', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(41, 2, '2025-06-19', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(42, 2, '2025-06-20', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(43, 3, '2025-05-23', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(44, 3, '2025-05-26', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(45, 3, '2025-05-27', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(46, 3, '2025-05-28', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(47, 3, '2025-05-29', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(48, 3, '2025-05-30', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(49, 3, '2025-06-02', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(50, 3, '2025-06-03', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(51, 3, '2025-06-04', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(52, 3, '2025-06-05', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(53, 3, '2025-06-06', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(54, 3, '2025-06-09', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(55, 3, '2025-06-10', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(56, 3, '2025-06-11', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(57, 3, '2025-06-12', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(58, 3, '2025-06-13', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(59, 3, '2025-06-16', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(60, 3, '2025-06-17', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(61, 3, '2025-06-18', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(62, 3, '2025-06-19', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(63, 3, '2025-06-20', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(64, 4, '2025-05-23', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(65, 4, '2025-05-26', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(66, 4, '2025-05-27', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(67, 4, '2025-05-28', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(68, 4, '2025-05-29', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(69, 4, '2025-05-30', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(70, 4, '2025-06-02', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(71, 4, '2025-06-03', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(72, 4, '2025-06-04', 'Assente', 'Assenza giustificata', '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(73, 4, '2025-06-05', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(74, 4, '2025-06-06', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(75, 4, '2025-06-09', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(76, 4, '2025-06-10', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(77, 4, '2025-06-11', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(78, 4, '2025-06-12', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(79, 4, '2025-06-13', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(80, 4, '2025-06-16', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(81, 4, '2025-06-17', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(82, 4, '2025-06-18', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(83, 4, '2025-06-19', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(84, 4, '2025-06-20', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(85, 5, '2025-05-23', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(86, 5, '2025-05-26', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(87, 5, '2025-05-27', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(88, 5, '2025-05-28', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(89, 5, '2025-05-29', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(90, 5, '2025-05-30', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(91, 5, '2025-06-02', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(92, 5, '2025-06-03', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(93, 5, '2025-06-04', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(94, 5, '2025-06-05', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(95, 5, '2025-06-06', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(96, 5, '2025-06-09', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(97, 5, '2025-06-10', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(98, 5, '2025-06-11', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(99, 5, '2025-06-12', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(100, 5, '2025-06-13', 'Assente', 'Assenza giustificata', '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(101, 5, '2025-06-16', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(102, 5, '2025-06-17', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(103, 5, '2025-06-18', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(104, 5, '2025-06-19', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(105, 5, '2025-06-20', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(106, 6, '2025-05-23', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(107, 6, '2025-05-26', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(108, 6, '2025-05-27', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(109, 6, '2025-05-28', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(110, 6, '2025-05-29', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(111, 6, '2025-05-30', 'Assente', 'Assenza giustificata', '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(112, 6, '2025-06-02', 'Assente', 'Assenza giustificata', '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(113, 6, '2025-06-03', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(114, 6, '2025-06-04', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(115, 6, '2025-06-05', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(116, 6, '2025-06-06', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(117, 6, '2025-06-09', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(118, 6, '2025-06-10', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(119, 6, '2025-06-11', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(120, 6, '2025-06-12', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(121, 6, '2025-06-13', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(122, 6, '2025-06-16', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(123, 6, '2025-06-17', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(124, 6, '2025-06-18', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(125, 6, '2025-06-19', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(126, 6, '2025-06-20', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(127, 7, '2025-05-23', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(128, 7, '2025-05-26', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(129, 7, '2025-05-27', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(130, 7, '2025-05-28', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(131, 7, '2025-05-29', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(132, 7, '2025-05-30', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(133, 7, '2025-06-02', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(134, 7, '2025-06-03', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(135, 7, '2025-06-04', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(136, 7, '2025-06-05', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(137, 7, '2025-06-06', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(138, 7, '2025-06-09', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(139, 7, '2025-06-10', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(140, 7, '2025-06-11', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(141, 7, '2025-06-12', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(142, 7, '2025-06-13', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(143, 7, '2025-06-16', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(144, 7, '2025-06-17', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(145, 7, '2025-06-18', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(146, 7, '2025-06-19', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(147, 7, '2025-06-20', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(148, 8, '2025-05-23', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(149, 8, '2025-05-26', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(150, 8, '2025-05-27', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(151, 8, '2025-05-28', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(152, 8, '2025-05-29', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(153, 8, '2025-05-30', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(154, 8, '2025-06-02', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(155, 8, '2025-06-03', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(156, 8, '2025-06-04', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(157, 8, '2025-06-05', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(158, 8, '2025-06-06', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(159, 8, '2025-06-09', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(160, 8, '2025-06-10', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(161, 8, '2025-06-11', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(162, 8, '2025-06-12', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(163, 8, '2025-06-13', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(164, 8, '2025-06-16', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(165, 8, '2025-06-17', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(166, 8, '2025-06-18', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(167, 8, '2025-06-19', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(168, 8, '2025-06-20', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(169, 9, '2025-05-23', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(170, 9, '2025-05-26', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(171, 9, '2025-05-27', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(172, 9, '2025-05-28', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(173, 9, '2025-05-29', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(174, 9, '2025-05-30', 'Assente', 'Assenza giustificata', '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(175, 9, '2025-06-02', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(176, 9, '2025-06-03', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(177, 9, '2025-06-04', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(178, 9, '2025-06-05', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(179, 9, '2025-06-06', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(180, 9, '2025-06-09', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(181, 9, '2025-06-10', 'Assente', 'Assenza giustificata', '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(182, 9, '2025-06-11', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(183, 9, '2025-06-12', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(184, 9, '2025-06-13', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(185, 9, '2025-06-16', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(186, 9, '2025-06-17', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(187, 9, '2025-06-18', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(188, 9, '2025-06-19', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(189, 9, '2025-06-20', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(190, 10, '2025-05-23', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(191, 10, '2025-05-26', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(192, 10, '2025-05-27', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(193, 10, '2025-05-28', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(194, 10, '2025-05-29', 'Assente', 'Assenza giustificata', '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(195, 10, '2025-05-30', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(196, 10, '2025-06-02', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(197, 10, '2025-06-03', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(198, 10, '2025-06-04', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(199, 10, '2025-06-05', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(200, 10, '2025-06-06', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(201, 10, '2025-06-09', 'Assente', 'Assenza giustificata', '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(202, 10, '2025-06-10', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(203, 10, '2025-06-11', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(204, 10, '2025-06-12', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(205, 10, '2025-06-13', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(206, 10, '2025-06-16', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(207, 10, '2025-06-17', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(208, 10, '2025-06-18', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(209, 10, '2025-06-19', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(210, 10, '2025-06-20', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(211, 11, '2025-05-23', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(212, 11, '2025-05-26', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(213, 11, '2025-05-27', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(214, 11, '2025-05-28', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(215, 11, '2025-05-29', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(216, 11, '2025-05-30', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(217, 11, '2025-06-02', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(218, 11, '2025-06-03', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(219, 11, '2025-06-04', 'Assente', 'Assenza giustificata', '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(220, 11, '2025-06-05', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(221, 11, '2025-06-06', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(222, 11, '2025-06-09', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(223, 11, '2025-06-10', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(224, 11, '2025-06-11', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(225, 11, '2025-06-12', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(226, 11, '2025-06-13', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(227, 11, '2025-06-16', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(228, 11, '2025-06-17', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(229, 11, '2025-06-18', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(230, 11, '2025-06-19', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(231, 11, '2025-06-20', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(232, 12, '2025-05-23', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(233, 12, '2025-05-26', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(234, 12, '2025-05-27', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(235, 12, '2025-05-28', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(236, 12, '2025-05-29', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(237, 12, '2025-05-30', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(238, 12, '2025-06-02', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(239, 12, '2025-06-03', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(240, 12, '2025-06-04', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(241, 12, '2025-06-05', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(242, 12, '2025-06-06', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(243, 12, '2025-06-09', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(244, 12, '2025-06-10', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(245, 12, '2025-06-11', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(246, 12, '2025-06-12', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(247, 12, '2025-06-13', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(248, 12, '2025-06-16', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(249, 12, '2025-06-17', 'Assente', 'Assenza giustificata', '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(250, 12, '2025-06-18', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(251, 12, '2025-06-19', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(252, 12, '2025-06-20', 'Assente', 'Assenza giustificata', '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(253, 13, '2025-05-23', 'Assente', 'Assenza giustificata', '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(254, 13, '2025-05-26', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(255, 13, '2025-05-27', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(256, 13, '2025-05-28', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(257, 13, '2025-05-29', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(258, 13, '2025-05-30', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(259, 13, '2025-06-02', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(260, 13, '2025-06-03', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(261, 13, '2025-06-04', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(262, 13, '2025-06-05', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(263, 13, '2025-06-06', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(264, 13, '2025-06-09', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(265, 13, '2025-06-10', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(266, 13, '2025-06-11', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(267, 13, '2025-06-12', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(268, 13, '2025-06-13', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(269, 13, '2025-06-16', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(270, 13, '2025-06-17', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(271, 13, '2025-06-18', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(272, 13, '2025-06-19', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(273, 13, '2025-06-20', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(274, 14, '2025-05-23', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(275, 14, '2025-05-26', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(276, 14, '2025-05-27', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(277, 14, '2025-05-28', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(278, 14, '2025-05-29', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(279, 14, '2025-05-30', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(280, 14, '2025-06-02', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(281, 14, '2025-06-03', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(282, 14, '2025-06-04', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(283, 14, '2025-06-05', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(284, 14, '2025-06-06', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(285, 14, '2025-06-09', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(286, 14, '2025-06-10', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(287, 14, '2025-06-11', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(288, 14, '2025-06-12', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(289, 14, '2025-06-13', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(290, 14, '2025-06-16', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(291, 14, '2025-06-17', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(292, 14, '2025-06-18', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(293, 14, '2025-06-19', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(294, 14, '2025-06-20', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(295, 15, '2025-05-23', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(296, 15, '2025-05-26', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(297, 15, '2025-05-27', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(298, 15, '2025-05-28', 'Assente', 'Assenza giustificata', '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(299, 15, '2025-05-29', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(300, 15, '2025-05-30', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(301, 15, '2025-06-02', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(302, 15, '2025-06-03', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(303, 15, '2025-06-04', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(304, 15, '2025-06-05', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(305, 15, '2025-06-06', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(306, 15, '2025-06-09', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(307, 15, '2025-06-10', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(308, 15, '2025-06-11', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(309, 15, '2025-06-12', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(310, 15, '2025-06-13', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(311, 15, '2025-06-16', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(312, 15, '2025-06-17', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(313, 15, '2025-06-18', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(314, 15, '2025-06-19', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(315, 15, '2025-06-20', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(316, 16, '2025-05-23', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(317, 16, '2025-05-26', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(318, 16, '2025-05-27', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(319, 16, '2025-05-28', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(320, 16, '2025-05-29', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(321, 16, '2025-05-30', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(322, 16, '2025-06-02', 'Assente', 'Assenza giustificata', '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(323, 16, '2025-06-03', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(324, 16, '2025-06-04', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(325, 16, '2025-06-05', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(326, 16, '2025-06-06', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(327, 16, '2025-06-09', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(328, 16, '2025-06-10', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(329, 16, '2025-06-11', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(330, 16, '2025-06-12', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(331, 16, '2025-06-13', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(332, 16, '2025-06-16', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(333, 16, '2025-06-17', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(334, 16, '2025-06-18', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(335, 16, '2025-06-19', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(336, 16, '2025-06-20', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(337, 17, '2025-05-23', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(338, 17, '2025-05-26', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(339, 17, '2025-05-27', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(340, 17, '2025-05-28', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(341, 17, '2025-05-29', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(342, 17, '2025-05-30', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(343, 17, '2025-06-02', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(344, 17, '2025-06-03', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(345, 17, '2025-06-04', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(346, 17, '2025-06-05', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(347, 17, '2025-06-06', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(348, 17, '2025-06-09', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(349, 17, '2025-06-10', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(350, 17, '2025-06-11', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(351, 17, '2025-06-12', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(352, 17, '2025-06-13', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(353, 17, '2025-06-16', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(354, 17, '2025-06-17', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(355, 17, '2025-06-18', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(356, 17, '2025-06-19', 'Presente', NULL, '2025-06-22 20:17:07', '2025-06-22 20:17:07'),
(357, 17, '2025-06-20', 'Presente', NULL, '2025-06-22 20:17:08', '2025-06-22 20:17:08'),
(358, 18, '2025-05-23', 'Presente', NULL, '2025-06-22 20:17:08', '2025-06-22 20:17:08'),
(359, 18, '2025-05-26', 'Presente', NULL, '2025-06-22 20:17:08', '2025-06-22 20:17:08'),
(360, 18, '2025-05-27', 'Assente', 'Assenza giustificata', '2025-06-22 20:17:08', '2025-06-22 20:17:08'),
(361, 18, '2025-05-28', 'Presente', NULL, '2025-06-22 20:17:08', '2025-06-22 20:17:08'),
(362, 18, '2025-05-29', 'Presente', NULL, '2025-06-22 20:17:08', '2025-06-22 20:17:08'),
(363, 18, '2025-05-30', 'Presente', NULL, '2025-06-22 20:17:08', '2025-06-22 20:17:08'),
(364, 18, '2025-06-02', 'Presente', NULL, '2025-06-22 20:17:08', '2025-06-22 20:17:08'),
(365, 18, '2025-06-03', 'Presente', NULL, '2025-06-22 20:17:08', '2025-06-22 20:17:08'),
(366, 18, '2025-06-04', 'Presente', NULL, '2025-06-22 20:17:08', '2025-06-22 20:17:08'),
(367, 18, '2025-06-05', 'Presente', NULL, '2025-06-22 20:17:08', '2025-06-22 20:17:08'),
(368, 18, '2025-06-06', 'Presente', NULL, '2025-06-22 20:17:08', '2025-06-22 20:17:08'),
(369, 18, '2025-06-09', 'Presente', NULL, '2025-06-22 20:17:08', '2025-06-22 20:17:08'),
(370, 18, '2025-06-10', 'Presente', NULL, '2025-06-22 20:17:08', '2025-06-22 20:17:08'),
(371, 18, '2025-06-11', 'Presente', NULL, '2025-06-22 20:17:08', '2025-06-22 20:17:08'),
(372, 18, '2025-06-12', 'Presente', NULL, '2025-06-22 20:17:08', '2025-06-22 20:17:08'),
(373, 18, '2025-06-13', 'Assente', 'Assenza giustificata', '2025-06-22 20:17:08', '2025-06-22 20:17:08'),
(374, 18, '2025-06-16', 'Presente', NULL, '2025-06-22 20:17:08', '2025-06-22 20:17:08'),
(375, 18, '2025-06-17', 'Presente', NULL, '2025-06-22 20:17:08', '2025-06-22 20:17:08'),
(376, 18, '2025-06-18', 'Presente', NULL, '2025-06-22 20:17:08', '2025-06-22 20:17:08'),
(377, 18, '2025-06-19', 'Presente', NULL, '2025-06-22 20:17:08', '2025-06-22 20:17:08'),
(378, 18, '2025-06-20', 'Presente', NULL, '2025-06-22 20:17:08', '2025-06-22 20:17:08'),
(379, 19, '2025-05-23', 'Presente', NULL, '2025-06-22 20:17:08', '2025-06-22 20:17:08'),
(380, 19, '2025-05-26', 'Presente', NULL, '2025-06-22 20:17:08', '2025-06-22 20:17:08'),
(381, 19, '2025-05-27', 'Presente', NULL, '2025-06-22 20:17:08', '2025-06-22 20:17:08'),
(382, 19, '2025-05-28', 'Presente', NULL, '2025-06-22 20:17:08', '2025-06-22 20:17:08'),
(383, 19, '2025-05-29', 'Presente', NULL, '2025-06-22 20:17:08', '2025-06-22 20:17:08'),
(384, 19, '2025-05-30', 'Presente', NULL, '2025-06-22 20:17:08', '2025-06-22 20:17:08'),
(385, 19, '2025-06-02', 'Presente', NULL, '2025-06-22 20:17:08', '2025-06-22 20:17:08'),
(386, 19, '2025-06-03', 'Presente', NULL, '2025-06-22 20:17:08', '2025-06-22 20:17:08'),
(387, 19, '2025-06-04', 'Presente', NULL, '2025-06-22 20:17:08', '2025-06-22 20:17:08'),
(388, 19, '2025-06-05', 'Assente', 'Assenza giustificata', '2025-06-22 20:17:08', '2025-06-22 20:17:08'),
(389, 19, '2025-06-06', 'Presente', NULL, '2025-06-22 20:17:08', '2025-06-22 20:17:08'),
(390, 19, '2025-06-09', 'Presente', NULL, '2025-06-22 20:17:08', '2025-06-22 20:17:08'),
(391, 19, '2025-06-10', 'Presente', NULL, '2025-06-22 20:17:08', '2025-06-22 20:17:08'),
(392, 19, '2025-06-11', 'Presente', NULL, '2025-06-22 20:17:08', '2025-06-22 20:17:08'),
(393, 19, '2025-06-12', 'Presente', NULL, '2025-06-22 20:17:08', '2025-06-22 20:17:08'),
(394, 19, '2025-06-13', 'Presente', NULL, '2025-06-22 20:17:08', '2025-06-22 20:17:08'),
(395, 19, '2025-06-16', 'Presente', NULL, '2025-06-22 20:17:08', '2025-06-22 20:17:08'),
(396, 19, '2025-06-17', 'Presente', NULL, '2025-06-22 20:17:08', '2025-06-22 20:17:08'),
(397, 19, '2025-06-18', 'Presente', NULL, '2025-06-22 20:17:08', '2025-06-22 20:17:08'),
(398, 19, '2025-06-19', 'Presente', NULL, '2025-06-22 20:17:08', '2025-06-22 20:17:08'),
(399, 19, '2025-06-20', 'Presente', NULL, '2025-06-22 20:17:08', '2025-06-22 20:17:08'),
(400, 20, '2025-05-23', 'Presente', NULL, '2025-06-22 20:17:08', '2025-06-22 20:17:08'),
(401, 20, '2025-05-26', 'Presente', NULL, '2025-06-22 20:17:08', '2025-06-22 20:17:08'),
(402, 20, '2025-05-27', 'Presente', NULL, '2025-06-22 20:17:08', '2025-06-22 20:17:08'),
(403, 20, '2025-05-28', 'Presente', NULL, '2025-06-22 20:17:08', '2025-06-22 20:17:08'),
(404, 20, '2025-05-29', 'Presente', NULL, '2025-06-22 20:17:08', '2025-06-22 20:17:08'),
(405, 20, '2025-05-30', 'Presente', NULL, '2025-06-22 20:17:08', '2025-06-22 20:17:08'),
(406, 20, '2025-06-02', 'Presente', NULL, '2025-06-22 20:17:08', '2025-06-22 20:17:08'),
(407, 20, '2025-06-03', 'Presente', NULL, '2025-06-22 20:17:08', '2025-06-22 20:17:08'),
(408, 20, '2025-06-04', 'Presente', NULL, '2025-06-22 20:17:08', '2025-06-22 20:17:08'),
(409, 20, '2025-06-05', 'Presente', NULL, '2025-06-22 20:17:08', '2025-06-22 20:17:08'),
(410, 20, '2025-06-06', 'Presente', NULL, '2025-06-22 20:17:08', '2025-06-22 20:17:08'),
(411, 20, '2025-06-09', 'Presente', NULL, '2025-06-22 20:17:08', '2025-06-22 20:17:08'),
(412, 20, '2025-06-10', 'Presente', NULL, '2025-06-22 20:17:08', '2025-06-22 20:17:08'),
(413, 20, '2025-06-11', 'Presente', NULL, '2025-06-22 20:17:08', '2025-06-22 20:17:08'),
(414, 20, '2025-06-12', 'Presente', NULL, '2025-06-22 20:17:08', '2025-06-22 20:17:08'),
(415, 20, '2025-06-13', 'Presente', NULL, '2025-06-22 20:17:08', '2025-06-22 20:17:08'),
(416, 20, '2025-06-16', 'Assente', 'Assenza giustificata', '2025-06-22 20:17:08', '2025-06-22 20:17:08'),
(417, 20, '2025-06-17', 'Assente', 'Assenza giustificata', '2025-06-22 20:17:08', '2025-06-22 20:17:08'),
(418, 20, '2025-06-18', 'Presente', NULL, '2025-06-22 20:17:08', '2025-06-22 20:17:08'),
(419, 20, '2025-06-19', 'Assente', 'Assenza giustificata', '2025-06-22 20:17:08', '2025-06-22 20:17:08'),
(420, 20, '2025-06-20', 'Presente', NULL, '2025-06-22 20:17:08', '2025-06-22 20:17:08'),
(421, 18, '2025-06-22', 'Assente', NULL, '2025-06-22 20:53:47', '2025-06-22 20:53:47'),
(422, 3, '2025-06-22', 'Assente', NULL, '2025-06-22 20:53:47', '2025-06-22 20:53:47'),
(423, 5, '2025-06-22', 'Presente', NULL, '2025-06-22 20:53:47', '2025-06-22 20:53:47'),
(424, 8, '2025-06-22', 'Presente', NULL, '2025-06-22 20:53:47', '2025-06-22 20:53:47'),
(425, 16, '2025-06-22', 'Presente', NULL, '2025-06-22 20:53:47', '2025-06-22 20:53:47'),
(426, 20, '2025-06-22', 'Presente', NULL, '2025-06-22 20:53:47', '2025-06-22 20:53:47'),
(427, 14, '2025-06-22', 'Assente', NULL, '2025-06-22 20:53:47', '2025-06-22 20:53:47'),
(428, 6, '2025-06-22', 'Presente', NULL, '2025-06-22 20:53:47', '2025-06-22 20:53:47'),
(429, 11, '2025-06-22', 'Presente', NULL, '2025-06-22 20:53:47', '2025-06-22 20:53:47'),
(430, 13, '2025-06-22', 'Assente', NULL, '2025-06-22 20:53:47', '2025-06-22 20:53:47'),
(431, 17, '2025-06-22', 'Presente', NULL, '2025-06-22 20:53:47', '2025-06-22 20:53:47'),
(432, 9, '2025-06-22', 'Presente', NULL, '2025-06-22 20:53:47', '2025-06-22 20:53:47'),
(433, 12, '2025-06-22', 'Presente', NULL, '2025-06-22 20:53:47', '2025-06-22 20:53:47'),
(434, 4, '2025-06-22', 'Presente', NULL, '2025-06-22 20:53:47', '2025-06-22 20:53:47'),
(435, 10, '2025-06-22', 'Presente', NULL, '2025-06-22 20:53:47', '2025-06-22 20:53:47'),
(436, 7, '2025-06-22', 'Presente', NULL, '2025-06-22 20:53:47', '2025-06-22 20:53:47'),
(437, 1, '2025-06-22', 'Presente', NULL, '2025-06-22 20:53:47', '2025-06-22 20:53:47'),
(438, 15, '2025-06-22', 'Presente', NULL, '2025-06-22 20:53:47', '2025-06-22 20:53:47'),
(439, 19, '2025-06-22', 'Presente', NULL, '2025-06-22 20:53:47', '2025-06-22 20:53:47'),
(440, 2, '2025-06-22', 'Presente', NULL, '2025-06-22 20:53:47', '2025-06-22 20:53:47');

-- --------------------------------------------------------

--
-- Struttura della tabella `ruoli`
--

CREATE TABLE `ruoli` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nome` varchar(50) NOT NULL,
  `descrizione` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `ruoli`
--

INSERT INTO `ruoli` (`id`, `nome`, `descrizione`, `created_at`, `updated_at`) VALUES
(1, 'Lavoratore', 'Personale di base senza responsabilità di supervisione', NULL, NULL),
(2, 'Preposto', 'Personale con responsabilità di supervisione e coordinamento', NULL, NULL),
(3, 'Dirigente', 'Personale con responsabilità di comando e gestione', NULL, NULL);

-- --------------------------------------------------------

--
-- Struttura della tabella `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('5ifctnp4CmCCbbxwgiCSLx0rFEHaS4tJn9WL1VkZ', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiQ2JTeDNEVmVoZGdKcDIzY2lERHpzeUxyNVJmeHJFdVpiSlBXTlRUSiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mzc6Imh0dHA6Ly9sb2NhbGhvc3QvQzJNUy9wdWJsaWMvbWlsaXRhcmUiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1750632838),
('XSJrcbFqVAqxknYz5w4mmSK6GRFnE4YlO645Lb0r', NULL, '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; it-IT) WindowsPowerShell/5.1.26100.4202', 'YToyOntzOjY6Il90b2tlbiI7czo0MDoiOUh2bHdvOVZ5QzBoSXl6dHpLYnhhdWhodVhhQmhKOGhwWUw0TTk2RCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1750631672);

-- --------------------------------------------------------

--
-- Struttura della tabella `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Amministratore', 'admin@sige.it', '2025-06-22 15:37:35', '$2y$12$G.kEw/.pajwIOW4I2EMZFO8tvnNzbshPwIb/zJmKP5n4T2peV6HGe', NULL, '2025-06-22 15:37:35', '2025-06-22 15:37:35'),
(2, 'Amministratore Sistema', 'admin@c2ms.local', NULL, '$2y$12$a6HSFQu6zBNJePWv5AovaeJUvyiLO7ZqEdTqPftaCsFibExQfB85W', NULL, '2025-06-22 20:07:42', '2025-06-22 20:07:42');

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `activity_attachments`
--
ALTER TABLE `activity_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `activity_attachments_activity_id_foreign` (`activity_id`);

--
-- Indici per le tabelle `activity_militare`
--
ALTER TABLE `activity_militare`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `activity_militare_activity_id_militare_id_unique` (`activity_id`,`militare_id`),
  ADD KEY `activity_militare_militare_id_foreign` (`militare_id`);

--
-- Indici per le tabelle `board_activities`
--
ALTER TABLE `board_activities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `board_activities_column_id_foreign` (`column_id`),
  ADD KEY `board_activities_created_by_foreign` (`created_by`);

--
-- Indici per le tabelle `board_columns`
--
ALTER TABLE `board_columns`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `board_columns_slug_unique` (`slug`);

--
-- Indici per le tabelle `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Indici per le tabelle `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Indici per le tabelle `certificati`
--
ALTER TABLE `certificati`
  ADD PRIMARY KEY (`id`),
  ADD KEY `certificati_militare_id_foreign` (`militare_id`);

--
-- Indici per le tabelle `certificati_lavoratori`
--
ALTER TABLE `certificati_lavoratori`
  ADD PRIMARY KEY (`id`),
  ADD KEY `certificati_lavoratori_militare_id_tipo_data_scadenza_index` (`militare_id`,`tipo`,`data_scadenza`);

--
-- Indici per le tabelle `compagnie`
--
ALTER TABLE `compagnie`
  ADD PRIMARY KEY (`id`);

--
-- Indici per le tabelle `eventi`
--
ALTER TABLE `eventi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `eventi_militare_id_data_inizio_data_fine_index` (`militare_id`,`data_inizio`,`data_fine`),
  ADD KEY `eventi_tipologia_index` (`tipologia`),
  ADD KEY `eventi_stato_index` (`stato`);

--
-- Indici per le tabelle `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indici per le tabelle `gradi`
--
ALTER TABLE `gradi`
  ADD PRIMARY KEY (`id`);

--
-- Indici per le tabelle `idoneita`
--
ALTER TABLE `idoneita`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idoneita_militare_id_tipo_data_scadenza_index` (`militare_id`,`tipo`,`data_scadenza`);

--
-- Indici per le tabelle `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indici per le tabelle `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indici per le tabelle `mansioni`
--
ALTER TABLE `mansioni`
  ADD PRIMARY KEY (`id`);

--
-- Indici per le tabelle `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indici per le tabelle `militare_valutazioni`
--
ALTER TABLE `militare_valutazioni`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_militare_valutatore` (`militare_id`,`valutatore_id`),
  ADD KEY `militare_valutazioni_valutatore_id_foreign` (`valutatore_id`);

--
-- Indici per le tabelle `militari`
--
ALTER TABLE `militari`
  ADD PRIMARY KEY (`id`),
  ADD KEY `militari_compagnia_id_foreign` (`compagnia_id`),
  ADD KEY `militari_plotone_id_foreign` (`plotone_id`),
  ADD KEY `militari_polo_id_foreign` (`polo_id`),
  ADD KEY `militari_ruolo_id_foreign` (`ruolo_id`),
  ADD KEY `militari_mansione_id_foreign` (`mansione_id`),
  ADD KEY `militari_cognome_nome_index` (`cognome`,`nome`),
  ADD KEY `militari_grado_id_index` (`grado_id`);

--
-- Indici per le tabelle `notas`
--
ALTER TABLE `notas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `notas_militare_id_user_id_unique` (`militare_id`,`user_id`),
  ADD KEY `notas_user_id_foreign` (`user_id`);

--
-- Indici per le tabelle `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indici per le tabelle `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- Indici per le tabelle `plotoni`
--
ALTER TABLE `plotoni`
  ADD PRIMARY KEY (`id`),
  ADD KEY `plotoni_compagnia_id_foreign` (`compagnia_id`);

--
-- Indici per le tabelle `poli`
--
ALTER TABLE `poli`
  ADD PRIMARY KEY (`id`),
  ADD KEY `poli_compagnia_id_foreign` (`compagnia_id`);

--
-- Indici per le tabelle `presenze`
--
ALTER TABLE `presenze`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `presenze_militare_id_data_unique` (`militare_id`,`data`);

--
-- Indici per le tabelle `ruoli`
--
ALTER TABLE `ruoli`
  ADD PRIMARY KEY (`id`);

--
-- Indici per le tabelle `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indici per le tabelle `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT per le tabelle scaricate
--

--
-- AUTO_INCREMENT per la tabella `activity_attachments`
--
ALTER TABLE `activity_attachments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT per la tabella `activity_militare`
--
ALTER TABLE `activity_militare`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT per la tabella `board_activities`
--
ALTER TABLE `board_activities`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT per la tabella `board_columns`
--
ALTER TABLE `board_columns`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT per la tabella `certificati`
--
ALTER TABLE `certificati`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `certificati_lavoratori`
--
ALTER TABLE `certificati_lavoratori`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT per la tabella `compagnie`
--
ALTER TABLE `compagnie`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT per la tabella `eventi`
--
ALTER TABLE `eventi`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT per la tabella `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `gradi`
--
ALTER TABLE `gradi`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT per la tabella `idoneita`
--
ALTER TABLE `idoneita`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT per la tabella `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `mansioni`
--
ALTER TABLE `mansioni`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT per la tabella `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT per la tabella `militare_valutazioni`
--
ALTER TABLE `militare_valutazioni`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT per la tabella `militari`
--
ALTER TABLE `militari`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT per la tabella `notas`
--
ALTER TABLE `notas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `plotoni`
--
ALTER TABLE `plotoni`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT per la tabella `poli`
--
ALTER TABLE `poli`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT per la tabella `presenze`
--
ALTER TABLE `presenze`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=441;

--
-- AUTO_INCREMENT per la tabella `ruoli`
--
ALTER TABLE `ruoli`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT per la tabella `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Limiti per le tabelle scaricate
--

--
-- Limiti per la tabella `activity_attachments`
--
ALTER TABLE `activity_attachments`
  ADD CONSTRAINT `activity_attachments_activity_id_foreign` FOREIGN KEY (`activity_id`) REFERENCES `board_activities` (`id`) ON DELETE CASCADE;

--
-- Limiti per la tabella `activity_militare`
--
ALTER TABLE `activity_militare`
  ADD CONSTRAINT `activity_militare_activity_id_foreign` FOREIGN KEY (`activity_id`) REFERENCES `board_activities` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `activity_militare_militare_id_foreign` FOREIGN KEY (`militare_id`) REFERENCES `militari` (`id`) ON DELETE CASCADE;

--
-- Limiti per la tabella `board_activities`
--
ALTER TABLE `board_activities`
  ADD CONSTRAINT `board_activities_column_id_foreign` FOREIGN KEY (`column_id`) REFERENCES `board_columns` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `board_activities_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Limiti per la tabella `certificati`
--
ALTER TABLE `certificati`
  ADD CONSTRAINT `certificati_militare_id_foreign` FOREIGN KEY (`militare_id`) REFERENCES `militari` (`id`) ON DELETE CASCADE;

--
-- Limiti per la tabella `certificati_lavoratori`
--
ALTER TABLE `certificati_lavoratori`
  ADD CONSTRAINT `certificati_lavoratori_militare_id_foreign` FOREIGN KEY (`militare_id`) REFERENCES `militari` (`id`) ON DELETE CASCADE;

--
-- Limiti per la tabella `eventi`
--
ALTER TABLE `eventi`
  ADD CONSTRAINT `eventi_militare_id_foreign` FOREIGN KEY (`militare_id`) REFERENCES `militari` (`id`) ON DELETE CASCADE;

--
-- Limiti per la tabella `idoneita`
--
ALTER TABLE `idoneita`
  ADD CONSTRAINT `idoneita_militare_id_foreign` FOREIGN KEY (`militare_id`) REFERENCES `militari` (`id`) ON DELETE CASCADE;

--
-- Limiti per la tabella `militare_valutazioni`
--
ALTER TABLE `militare_valutazioni`
  ADD CONSTRAINT `militare_valutazioni_militare_id_foreign` FOREIGN KEY (`militare_id`) REFERENCES `militari` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `militare_valutazioni_valutatore_id_foreign` FOREIGN KEY (`valutatore_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Limiti per la tabella `militari`
--
ALTER TABLE `militari`
  ADD CONSTRAINT `militari_compagnia_id_foreign` FOREIGN KEY (`compagnia_id`) REFERENCES `compagnie` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `militari_grado_id_foreign` FOREIGN KEY (`grado_id`) REFERENCES `gradi` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `militari_mansione_id_foreign` FOREIGN KEY (`mansione_id`) REFERENCES `mansioni` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `militari_plotone_id_foreign` FOREIGN KEY (`plotone_id`) REFERENCES `plotoni` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `militari_polo_id_foreign` FOREIGN KEY (`polo_id`) REFERENCES `poli` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `militari_ruolo_id_foreign` FOREIGN KEY (`ruolo_id`) REFERENCES `ruoli` (`id`) ON DELETE SET NULL;

--
-- Limiti per la tabella `notas`
--
ALTER TABLE `notas`
  ADD CONSTRAINT `notas_militare_id_foreign` FOREIGN KEY (`militare_id`) REFERENCES `militari` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notas_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Limiti per la tabella `plotoni`
--
ALTER TABLE `plotoni`
  ADD CONSTRAINT `plotoni_compagnia_id_foreign` FOREIGN KEY (`compagnia_id`) REFERENCES `compagnie` (`id`) ON DELETE CASCADE;

--
-- Limiti per la tabella `poli`
--
ALTER TABLE `poli`
  ADD CONSTRAINT `poli_compagnia_id_foreign` FOREIGN KEY (`compagnia_id`) REFERENCES `compagnie` (`id`) ON DELETE CASCADE;

--
-- Limiti per la tabella `presenze`
--
ALTER TABLE `presenze`
  ADD CONSTRAINT `presenze_militare_id_foreign` FOREIGN KEY (`militare_id`) REFERENCES `militari` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
