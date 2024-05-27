SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

CREATE TABLE `mp_quote` (
  `id` bigint(20) NOT NULL,
  `status` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `cleaned_catalog_no` text COLLATE utf8mb4_unicode_520_ci,
  `quoteno` text COLLATE utf8mb4_unicode_520_ci,
  `cust` text COLLATE utf8mb4_unicode_520_ci,
  `snum` text COLLATE utf8mb4_unicode_520_ci,
  `sm` text COLLATE utf8mb4_unicode_520_ci,
  `customername` text COLLATE utf8mb4_unicode_520_ci,
  `entdate` text COLLATE utf8mb4_unicode_520_ci,
  `enduser` text COLLATE utf8mb4_unicode_520_ci,
  `qtby` text COLLATE utf8mb4_unicode_520_ci,
  `acceptby` text COLLATE utf8mb4_unicode_520_ci,
  `expireby` text COLLATE utf8mb4_unicode_520_ci,
  `stc` text COLLATE utf8mb4_unicode_520_ci,
  `item` text COLLATE utf8mb4_unicode_520_ci,
  `l` text COLLATE utf8mb4_unicode_520_ci,
  `partno` text COLLATE utf8mb4_unicode_520_ci,
  `catalogno` text COLLATE utf8mb4_unicode_520_ci,
  `description` text COLLATE utf8mb4_unicode_520_ci,
  `stocknumber` text COLLATE utf8mb4_unicode_520_ci,
  `qtqty` text COLLATE utf8mb4_unicode_520_ci,
  `price` text COLLATE utf8mb4_unicode_520_ci,
  `u` text COLLATE utf8mb4_unicode_520_ci,
  `ordqty` text COLLATE utf8mb4_unicode_520_ci,
  `extension` text COLLATE utf8mb4_unicode_520_ci,
  `skdqty` text COLLATE utf8mb4_unicode_520_ci,
  `moqty` text COLLATE utf8mb4_unicode_520_ci,
  `w` text COLLATE utf8mb4_unicode_520_ci,
  `leadtime` text COLLATE utf8mb4_unicode_520_ci,
  `stdpak` text COLLATE utf8mb4_unicode_520_ci,
  `projno` text COLLATE utf8mb4_unicode_520_ci,
  `upccode` text COLLATE utf8mb4_unicode_520_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

ALTER TABLE `mp_quote`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `mp_quote`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;
COMMIT;
