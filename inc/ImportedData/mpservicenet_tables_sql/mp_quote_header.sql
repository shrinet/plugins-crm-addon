SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

CREATE TABLE `mp_quote_header` (
  `id` bigint(20) NOT NULL,
  `status` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `quoteno` text COLLATE utf8mb4_unicode_520_ci,
  `cust` text COLLATE utf8mb4_unicode_520_ci,
  `snum` text COLLATE utf8mb4_unicode_520_ci,
  `sm` text COLLATE utf8mb4_unicode_520_ci,
  `custname` text COLLATE utf8mb4_unicode_520_ci,
  `quotdate` text COLLATE utf8mb4_unicode_520_ci,
  `enduser` text COLLATE utf8mb4_unicode_520_ci,
  `by` text COLLATE utf8mb4_unicode_520_ci,
  `accdate` text COLLATE utf8mb4_unicode_520_ci,
  `expdate` text COLLATE utf8mb4_unicode_520_ci,
  `stc` text COLLATE utf8mb4_unicode_520_ci,
  `inquiryno` text COLLATE utf8mb4_unicode_520_ci,
  `inqdate` text COLLATE utf8mb4_unicode_520_ci,
  `trm` text COLLATE utf8mb4_unicode_520_ci,
  `addrline1` text COLLATE utf8mb4_unicode_520_ci,
  `addrline2` text COLLATE utf8mb4_unicode_520_ci,
  `addrline3` text COLLATE utf8mb4_unicode_520_ci,
  `addrline4` text COLLATE utf8mb4_unicode_520_ci,
  `addrline5` text COLLATE utf8mb4_unicode_520_ci,
  `projno` text COLLATE utf8mb4_unicode_520_ci,
  `projectname` text COLLATE utf8mb4_unicode_520_ci,
  `aq` text COLLATE utf8mb4_unicode_520_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

ALTER TABLE `mp_quote_header`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `mp_quote_header`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;
COMMIT;
