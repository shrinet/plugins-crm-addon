SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

CREATE TABLE `mp_order` (
  `id` bigint(20) NOT NULL,
  `status` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `cleaned_catalog_no` text COLLATE utf8mb4_unicode_520_ci,
  `t` text COLLATE utf8mb4_unicode_520_ci,
  `snum` text COLLATE utf8mb4_unicode_520_ci,
  `cust` text COLLATE utf8mb4_unicode_520_ci,
  `ordernum` text COLLATE utf8mb4_unicode_520_ci,
  `s` text COLLATE utf8mb4_unicode_520_ci,
  `item` text COLLATE utf8mb4_unicode_520_ci,
  `catalogno` text COLLATE utf8mb4_unicode_520_ci,
  `stockno` text COLLATE utf8mb4_unicode_520_ci,
  `qty` text COLLATE utf8mb4_unicode_520_ci,
  `price` text COLLATE utf8mb4_unicode_520_ci,
  `u` text COLLATE utf8mb4_unicode_520_ci,
  `extn` text COLLATE utf8mb4_unicode_520_ci,
  `r` text COLLATE utf8mb4_unicode_520_ci,
  `reqdate` text COLLATE utf8mb4_unicode_520_ci,
  `a` text COLLATE utf8mb4_unicode_520_ci,
  `ackdate` text COLLATE utf8mb4_unicode_520_ci,
  `astrsk` text COLLATE utf8mb4_unicode_520_ci,
  `avldate` text COLLATE utf8mb4_unicode_520_ci,
  `b` text COLLATE utf8mb4_unicode_520_ci,
  `shpdate` text COLLATE utf8mb4_unicode_520_ci,
  `pono` text COLLATE utf8mb4_unicode_520_ci,
  `podate` text COLLATE utf8mb4_unicode_520_ci,
  `entdate` text COLLATE utf8mb4_unicode_520_ci,
  `bfwdslshlnum` text COLLATE utf8mb4_unicode_520_ci,
  `scac` text COLLATE utf8mb4_unicode_520_ci,
  `prono` text COLLATE utf8mb4_unicode_520_ci,
  `custname` text COLLATE utf8mb4_unicode_520_ci,
  `shipto` text COLLATE utf8mb4_unicode_520_ci,
  `shipline2` text COLLATE utf8mb4_unicode_520_ci,
  `shipline3` text COLLATE utf8mb4_unicode_520_ci,
  `shipline4` text COLLATE utf8mb4_unicode_520_ci,
  `citystzip` text COLLATE utf8mb4_unicode_520_ci,
  `grpnum` text COLLATE utf8mb4_unicode_520_ci,
  `l` text COLLATE utf8mb4_unicode_520_ci,
  `packpo` text COLLATE utf8mb4_unicode_520_ci,
  `packcata` text COLLATE utf8mb4_unicode_520_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

ALTER TABLE `mp_order`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_length_pono` (`pono`(767)),
  ADD KEY `ix_length_s` (`s`(767)),
  ADD KEY `ix_length_catalogno` (`catalogno`(767)),
  ADD KEY `ix_length_ordernum` (`ordernum`(767)) USING BTREE;

ALTER TABLE `mp_order`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;
COMMIT;
