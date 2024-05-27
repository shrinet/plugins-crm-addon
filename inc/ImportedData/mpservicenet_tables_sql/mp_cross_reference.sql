SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

CREATE TABLE `mp_cross_reference` (
  `id` bigint(20) NOT NULL,
  `status` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `cleaned_catalog_no` text COLLATE utf8mb4_unicode_520_ci,
  `partnumber` text COLLATE utf8mb4_unicode_520_ci,
  `manufacturer` text COLLATE utf8mb4_unicode_520_ci,
  `mpscatalognumber` text COLLATE utf8mb4_unicode_520_ci,
  `compatibilityofcross` text COLLATE utf8mb4_unicode_520_ci,
  `notes` text COLLATE utf8mb4_unicode_520_ci,
  `cleaned_part_no` text COLLATE utf8mb4_unicode_520_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

ALTER TABLE `mp_cross_reference`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `mp_cross_reference`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;
COMMIT;
