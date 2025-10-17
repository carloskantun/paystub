-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:3306
-- Tiempo de generación: 16-09-2025 a las 10:48:48
-- Versión del servidor: 5.7.23-23
-- Versión de PHP: 8.1.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `createpa_com`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `actor` enum('user','bot') COLLATE utf8_unicode_ci NOT NULL,
  `action` enum('create','regenerate','resend') COLLATE utf8_unicode_ci NOT NULL,
  `order_id` char(36) COLLATE utf8_unicode_ci DEFAULT NULL,
  `meta_json` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `calc_overrides`
--

CREATE TABLE `calc_overrides` (
  `id` int(11) NOT NULL,
  `tenant_id` int(11) DEFAULT NULL,
  `year` smallint(6) DEFAULT NULL,
  `key_name` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `value_numeric` decimal(14,4) DEFAULT NULL,
  `value_json` json DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `deductions`
--

CREATE TABLE `deductions` (
  `order_id` char(36) COLLATE utf8_unicode_ci NOT NULL,
  `stub_index` int(11) NOT NULL,
  `label` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `pretax` tinyint(1) DEFAULT '0',
  `current_amount` decimal(10,2) NOT NULL,
  `ytd_amount` decimal(10,2) NOT NULL,
  `sort_order` int(11) DEFAULT '0',
  `origin` enum('seed','calc','manual') COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `earnings`
--

CREATE TABLE `earnings` (
  `order_id` char(36) COLLATE utf8_unicode_ci NOT NULL,
  `stub_index` int(11) NOT NULL,
  `label` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `hours` decimal(10,2) DEFAULT NULL,
  `rate` decimal(10,2) DEFAULT NULL,
  `current_amount` decimal(10,2) NOT NULL,
  `ytd_amount` decimal(10,2) NOT NULL,
  `sort_order` int(11) DEFAULT '0',
  `origin` enum('seed','calc','manual') COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `federal_brackets`
--

CREATE TABLE `federal_brackets` (
  `id` int(11) NOT NULL,
  `year` smallint(6) DEFAULT NULL,
  `filing_status` enum('single','married') COLLATE utf8_unicode_ci NOT NULL,
  `bracket_order` int(11) NOT NULL,
  `threshold` decimal(12,2) NOT NULL,
  `rate` decimal(6,5) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `fica_limits`
--

CREATE TABLE `fica_limits` (
  `year` smallint(6) NOT NULL,
  `ss_wage_base` decimal(12,2) DEFAULT NULL,
  `ss_rate_employee` decimal(6,5) DEFAULT NULL,
  `medicare_rate` decimal(6,5) DEFAULT NULL,
  `addl_medicare_threshold_single` decimal(12,2) DEFAULT NULL,
  `addl_medicare_rate` decimal(6,5) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `orders`
--

CREATE TABLE `orders` (
  `id` char(36) COLLATE utf8_unicode_ci NOT NULL,
  `tenant_id` int(11) DEFAULT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `status` enum('draft','pending','paid') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'draft',
  `template_key` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `pay_schedule` enum('weekly','biweekly','semi-monthly','monthly') COLLATE utf8_unicode_ci NOT NULL,
  `count_stubs` int(11) NOT NULL,
  `bundle_mode` enum('combined','separate') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'separate',
  `period_start` date DEFAULT NULL,
  `period_end` date DEFAULT NULL,
  `pay_date` date DEFAULT NULL,
  `gross` decimal(10,2) DEFAULT NULL,
  `net` decimal(10,2) DEFAULT NULL,
  `fit_taxable_wages` decimal(10,2) DEFAULT NULL,
  `taxes_total` decimal(10,2) DEFAULT NULL,
  `deductions_total` decimal(10,2) DEFAULT NULL,
  `employee_json` json DEFAULT NULL,
  `employer_json` json DEFAULT NULL,
  `pdf_path` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `version_of` char(36) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `payments`
--

CREATE TABLE `payments` (
  `order_id` char(36) COLLATE utf8_unicode_ci NOT NULL,
  `provider` enum('stripe','mp') COLLATE utf8_unicode_ci NOT NULL,
  `session_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `status` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `currency` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `amount_total` decimal(10,2) DEFAULT NULL,
  `webhook_payload_json` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `state_tax_rates`
--

CREATE TABLE `state_tax_rates` (
  `id` int(11) NOT NULL,
  `state_code` char(2) COLLATE utf8_unicode_ci DEFAULT NULL,
  `year` smallint(6) DEFAULT NULL,
  `rate` decimal(6,5) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `taxes`
--

CREATE TABLE `taxes` (
  `order_id` char(36) COLLATE utf8_unicode_ci NOT NULL,
  `stub_index` int(11) NOT NULL,
  `label` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `current_amount` decimal(10,2) NOT NULL,
  `ytd_amount` decimal(10,2) NOT NULL,
  `sort_order` int(11) DEFAULT '0',
  `origin` enum('seed','calc','manual') COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tax_years`
--

CREATE TABLE `tax_years` (
  `year` smallint(6) NOT NULL,
  `standard_deduction_single` decimal(10,2) DEFAULT NULL,
  `standard_deduction_married` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tenants`
--

CREATE TABLE `tenants` (
  `id` int(11) NOT NULL,
  `domain` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `brand_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `logo_url` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `currency` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `watermark_text` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `price_per_stub` decimal(10,2) DEFAULT NULL,
  `stripe_pk` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `stripe_sk` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `mp_keys` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email_from` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indices de la tabla `calc_overrides`
--
ALTER TABLE `calc_overrides`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tenant_id` (`tenant_id`,`year`,`key_name`);

--
-- Indices de la tabla `deductions`
--
ALTER TABLE `deductions`
  ADD PRIMARY KEY (`order_id`,`stub_index`,`label`);

--
-- Indices de la tabla `earnings`
--
ALTER TABLE `earnings`
  ADD PRIMARY KEY (`order_id`,`stub_index`,`label`);

--
-- Indices de la tabla `federal_brackets`
--
ALTER TABLE `federal_brackets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `year` (`year`,`filing_status`,`bracket_order`);

--
-- Indices de la tabla `fica_limits`
--
ALTER TABLE `fica_limits`
  ADD PRIMARY KEY (`year`);

--
-- Indices de la tabla `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tenant_id` (`tenant_id`);

--
-- Indices de la tabla `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`order_id`,`provider`);

--
-- Indices de la tabla `state_tax_rates`
--
ALTER TABLE `state_tax_rates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `state_code` (`state_code`,`year`);

--
-- Indices de la tabla `taxes`
--
ALTER TABLE `taxes`
  ADD PRIMARY KEY (`order_id`,`stub_index`,`label`);

--
-- Indices de la tabla `tax_years`
--
ALTER TABLE `tax_years`
  ADD PRIMARY KEY (`year`);

--
-- Indices de la tabla `tenants`
--
ALTER TABLE `tenants`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `calc_overrides`
--
ALTER TABLE `calc_overrides`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `federal_brackets`
--
ALTER TABLE `federal_brackets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `state_tax_rates`
--
ALTER TABLE `state_tax_rates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tenants`
--
ALTER TABLE `tenants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`);

--
-- Filtros para la tabla `deductions`
--
ALTER TABLE `deductions`
  ADD CONSTRAINT `deductions_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`);

--
-- Filtros para la tabla `earnings`
--
ALTER TABLE `earnings`
  ADD CONSTRAINT `earnings_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`);

--
-- Filtros para la tabla `federal_brackets`
--
ALTER TABLE `federal_brackets`
  ADD CONSTRAINT `federal_brackets_ibfk_1` FOREIGN KEY (`year`) REFERENCES `tax_years` (`year`);

--
-- Filtros para la tabla `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`);

--
-- Filtros para la tabla `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`);

--
-- Filtros para la tabla `taxes`
--
ALTER TABLE `taxes`
  ADD CONSTRAINT `taxes_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
