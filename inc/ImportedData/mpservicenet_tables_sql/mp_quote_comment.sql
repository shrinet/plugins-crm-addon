SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

CREATE TABLE `mp_quote_comment` (
  `id` bigint(20) NOT NULL,
  `status` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `quoteno` text COLLATE utf8mb4_unicode_520_ci,
  `item` text COLLATE utf8mb4_unicode_520_ci,
  `lnum` text COLLATE utf8mb4_unicode_520_ci,
  `comment` text COLLATE utf8mb4_unicode_520_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

ALTER TABLE `mp_quote_comment`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `mp_quote_comment`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;
COMMIT;
