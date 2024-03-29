-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               5.7.29-0ubuntu0.16.04.1 - (Ubuntu)
-- Server OS:                    Linux
-- HeidiSQL Version:             11.1.0.6145
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

-- Dumping structure for table alina.tale
DROP TABLE IF EXISTS `tale`;
CREATE TABLE IF NOT EXISTS `tale` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `root_tale_id` bigint(20) unsigned DEFAULT NULL,
  `answer_to_tale_id` bigint(20) unsigned DEFAULT NULL,
  `level` tinyint(1) DEFAULT '0',
  `type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'POST' COMMENT 'POST, COMMENT, POSTCOMMENT',
  `owner_id` bigint(20) unsigned DEFAULT NULL,
  `header` text COLLATE utf8mb4_unicode_ci,
  `body` text COLLATE utf8mb4_unicode_ci,
  `body_txt` text COLLATE utf8mb4_unicode_ci,
  `created_at` bigint(20) DEFAULT NULL,
  `modified_at` bigint(20) DEFAULT NULL,
  `publish_at` bigint(20) DEFAULT NULL,
  `is_submitted` tinyint(1) DEFAULT '0',
  `lang` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT 'RU',
  `is_adult_denied` tinyint(4) DEFAULT '0',
  `is_draft` tinyint(4) DEFAULT '0',
  `editor_class` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'ck-content',
  `is_adv` tinyint(4) DEFAULT '0',
  `is_comment_denied` tinyint(4) DEFAULT '0',
  `iframe` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `IND_TALE_PUBLISH_AT` (`publish_at`),
  KEY `IND_TALE_CREATED_AT` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
