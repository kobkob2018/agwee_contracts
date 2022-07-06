-- phpMyAdmin SQL Dump
-- version 5.1.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jul 06, 2022 at 07:10 AM
-- Server version: 5.7.32-35-log
-- PHP Version: 7.4.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dbx1g5wqw5shbe`
--

-- --------------------------------------------------------

--
-- Table structure for table `ag_contract_apply`
--

CREATE TABLE `ag_contract_apply` (
  `id` int(11) NOT NULL,
  `unk` varchar(80) NOT NULL,
  `contract_id` int(11) NOT NULL,
  `landing_id` int(11) DEFAULT NULL,
  `title` varchar(150) NOT NULL DEFAULT '',
  `ip` varchar(50) DEFAULT '',
  `emails` varchar(150) DEFAULT NULL,
  `pdf_name` varchar(50) DEFAULT NULL,
  `pdf_path` varchar(150) DEFAULT NULL,
  `pdf_http` varchar(150) DEFAULT NULL,
  `sign_time` datetime NOT NULL,
  `emails_approved` varchar(250) DEFAULT NULL,
  `canceled` tinyint(4) NOT NULL DEFAULT '0',
  `fully_approved` tinyint(4) NOT NULL DEFAULT '0',
  `last_alert` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=hebrew;

-- --------------------------------------------------------

--
-- Table structure for table `ag_contract_apply_inputs`
--

CREATE TABLE `ag_contract_apply_inputs` (
  `id` int(11) NOT NULL,
  `unk` varchar(80) NOT NULL DEFAULT '0',
  `contract_id` int(11) NOT NULL,
  `contract_apply_id` int(11) NOT NULL,
  `input_name` varchar(80) NOT NULL,
  `input_value` text,
  `edit_by` int(11) NOT NULL,
  `edit_ip` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=hebrew;

-- --------------------------------------------------------

--
-- Table structure for table `ag_contract_apply_signatures`
--

CREATE TABLE `ag_contract_apply_signatures` (
  `id` int(11) NOT NULL,
  `unk` varchar(80) NOT NULL DEFAULT '0',
  `contract_id` int(11) NOT NULL,
  `contract_apply_id` int(11) NOT NULL,
  `input_name` varchar(80) NOT NULL,
  `input_value` text,
  `edit_by` int(11) NOT NULL,
  `edit_ip` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=hebrew;

-- --------------------------------------------------------

--
-- Table structure for table `ag_contract_apply_users`
--

CREATE TABLE `ag_contract_apply_users` (
  `id` int(11) NOT NULL,
  `unk` varchar(80) NOT NULL DEFAULT '0',
  `contract_apply_id` int(11) NOT NULL,
  `contract_user_id` int(11) NOT NULL,
  `firstname` varchar(80) DEFAULT NULL,
  `lastname` varchar(80) DEFAULT NULL,
  `email` varchar(80) DEFAULT NULL,
  `approve_ip` varchar(50) DEFAULT NULL,
  `approve_note` varchar(300) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=hebrew;

-- --------------------------------------------------------

--
-- Table structure for table `ag_contract_design`
--

CREATE TABLE `ag_contract_design` (
  `id` int(11) NOT NULL,
  `identifier` varchar(180) DEFAULT NULL,
  `unk` varchar(80) NOT NULL DEFAULT '0',
  `title` varchar(150) NOT NULL DEFAULT '',
  `content` longtext,
  `header_img` varchar(150) DEFAULT NULL,
  `footer_img` varchar(150) DEFAULT NULL,
  `canceled` tinyint(4) NOT NULL DEFAULT '0',
  `head_px` varchar(20) DEFAULT NULL,
  `foot_px` varchar(20) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=hebrew;

-- --------------------------------------------------------

--
-- Table structure for table `ag_contract_fields_settings`
--

CREATE TABLE `ag_contract_fields_settings` (
  `id` int(11) NOT NULL,
  `unk` varchar(80) NOT NULL DEFAULT '0',
  `contract_id` int(11) NOT NULL,
  `field_key` varchar(80) NOT NULL,
  `field_val` varchar(80) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=hebrew;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ag_contract_apply`
--
ALTER TABLE `ag_contract_apply`
  ADD PRIMARY KEY (`id`),
  ADD KEY `unk` (`unk`),
  ADD KEY `contract_id` (`contract_id`);

--
-- Indexes for table `ag_contract_apply_inputs`
--
ALTER TABLE `ag_contract_apply_inputs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `unk` (`unk`),
  ADD KEY `contract_id` (`contract_id`);

--
-- Indexes for table `ag_contract_apply_signatures`
--
ALTER TABLE `ag_contract_apply_signatures`
  ADD PRIMARY KEY (`id`),
  ADD KEY `unk` (`unk`),
  ADD KEY `contract_id` (`contract_id`);

--
-- Indexes for table `ag_contract_apply_users`
--
ALTER TABLE `ag_contract_apply_users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `unk` (`unk`),
  ADD KEY `contract_apply_id` (`contract_apply_id`);

--
-- Indexes for table `ag_contract_design`
--
ALTER TABLE `ag_contract_design`
  ADD PRIMARY KEY (`id`),
  ADD KEY `unk` (`unk`);

--
-- Indexes for table `ag_contract_fields_settings`
--
ALTER TABLE `ag_contract_fields_settings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `unk` (`unk`),
  ADD KEY `contract_id` (`contract_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ag_contract_apply`
--
ALTER TABLE `ag_contract_apply`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ag_contract_apply_inputs`
--
ALTER TABLE `ag_contract_apply_inputs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ag_contract_apply_signatures`
--
ALTER TABLE `ag_contract_apply_signatures`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ag_contract_apply_users`
--
ALTER TABLE `ag_contract_apply_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ag_contract_design`
--
ALTER TABLE `ag_contract_design`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ag_contract_fields_settings`
--
ALTER TABLE `ag_contract_fields_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
