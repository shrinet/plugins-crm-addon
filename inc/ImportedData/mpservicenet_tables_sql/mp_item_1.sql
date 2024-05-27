SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

CREATE TABLE `mp_item_1` (
  `id` bigint(20) NOT NULL,
  `status` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `cleaned_catalog_no` text COLLATE utf8mb4_unicode_520_ci,
  `p` text COLLATE utf8mb4_unicode_520_ci,
  `partno` text COLLATE utf8mb4_unicode_520_ci,
  `catalogno` text COLLATE utf8mb4_unicode_520_ci,
  `description` text COLLATE utf8mb4_unicode_520_ci,
  `prodclass` text COLLATE utf8mb4_unicode_520_ci,
  `dist` text COLLATE utf8mb4_unicode_520_ci,
  `distdesc` text COLLATE utf8mb4_unicode_520_ci,
  `listpric` text COLLATE utf8mb4_unicode_520_ci,
  `u` text COLLATE utf8mb4_unicode_520_ci,
  `d` text COLLATE utf8mb4_unicode_520_ci,
  `weight` text COLLATE utf8mb4_unicode_520_ci,
  `stdpk` text COLLATE utf8mb4_unicode_520_ci,
  `skidqty` text COLLATE utf8mb4_unicode_520_ci,
  `lt` text COLLATE utf8mb4_unicode_520_ci,
  `a` text COLLATE utf8mb4_unicode_520_ci,
  `pricdate` text COLLATE utf8mb4_unicode_520_ci,
  `instock` text COLLATE utf8mb4_unicode_520_ci,
  `dateavl1` text COLLATE utf8mb4_unicode_520_ci,
  `qtyavl1` text COLLATE utf8mb4_unicode_520_ci,
  `dateavl2` text COLLATE utf8mb4_unicode_520_ci,
  `qtyavl2` text COLLATE utf8mb4_unicode_520_ci,
  `dateavl3` text COLLATE utf8mb4_unicode_520_ci,
  `qtyavl3` text COLLATE utf8mb4_unicode_520_ci,
  `dateavl4` text COLLATE utf8mb4_unicode_520_ci,
  `qtyavl4` text COLLATE utf8mb4_unicode_520_ci,
  `dateavl5` text COLLATE utf8mb4_unicode_520_ci,
  `qtyavl5` text COLLATE utf8mb4_unicode_520_ci,
  `dateavl6` text COLLATE utf8mb4_unicode_520_ci,
  `qtyavl6` text COLLATE utf8mb4_unicode_520_ci,
  `dateavl7` text COLLATE utf8mb4_unicode_520_ci,
  `qtyavl7` text COLLATE utf8mb4_unicode_520_ci,
  `dateavl8` text COLLATE utf8mb4_unicode_520_ci,
  `qtyavl8` text COLLATE utf8mb4_unicode_520_ci,
  `dateavl9` text COLLATE utf8mb4_unicode_520_ci,
  `qtyavl9` text COLLATE utf8mb4_unicode_520_ci,
  `dateavl0` text COLLATE utf8mb4_unicode_520_ci,
  `qtyavl0` text COLLATE utf8mb4_unicode_520_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

ALTER TABLE `mp_item_1`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `mp_item_1`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;
COMMIT;
