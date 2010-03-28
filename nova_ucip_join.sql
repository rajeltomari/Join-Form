-- phpMyAdmin SQL Dump
-- version 3.2.5
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Mar 28, 2010 at 10:57 AM
-- Server version: 5.0.77
-- PHP Version: 5.3.2-1

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `nova`
--

-- --------------------------------------------------------

--
-- Table structure for table `nova_applications`
--

ALTER TABLE `nova_applications` ADD `ucip_member` TEXT NOT NULL ,
ADD `ucip_dbid` INT( 5 ) NULL 

-- --------------------------------------------------------

--
-- Table structure for table `nova_characters`
--

ALTER TABLE `nova_characters` ADD `ucip_member` ENUM( 'Yes', 'No' ) NOT NULL DEFAULT 'No',
ADD `ucip_dbid` INT( 5 ) NULL 

-- --------------------------------------------------------



