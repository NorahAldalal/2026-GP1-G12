-- phpMyAdmin SQL Dump
-- version 5.1.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: 12 أبريل 2026 الساعة 16:58
-- إصدار الخادم: 5.7.24
-- PHP Version: 8.3.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `siraj`
--

-- --------------------------------------------------------

--
-- بنية الجدول `admin`
--

CREATE TABLE `admin` (
  `AdminID` int(11) NOT NULL,
  `AdminCode` varchar(50) DEFAULT NULL,
  `AdminName` varchar(100) NOT NULL,
  `Email` varchar(255) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `CreatedAt` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- إرجاع أو استيراد بيانات الجدول `admin`
--

INSERT INTO `admin` (`AdminID`, `AdminCode`, `AdminName`, `Email`, `Password`, `CreatedAt`) VALUES
(1, NULL, 'Administrator', 'admin@siraj.city', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-04-12 12:35:39');

-- --------------------------------------------------------

--
-- بنية الجدول `area`
--

CREATE TABLE `area` (
  `AreaID` int(11) NOT NULL,
  `AreaName` varchar(150) NOT NULL,
  `Latitude` decimal(10,7) NOT NULL,
  `Longitude` decimal(10,7) NOT NULL,
  `Pollution_level` enum('Low','Medium','High') DEFAULT 'Low'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- إرجاع أو استيراد بيانات الجدول `area`
--

INSERT INTO `area` (`AreaID`, `AreaName`, `Latitude`, `Longitude`, `Pollution_level`) VALUES
(1, 'Downtown', '24.6877000', '46.7219000', 'High'),
(2, 'Al Hamra District', '24.6950000', '46.7350000', 'Medium'),
(3, 'Industrial Zone', '24.6700000', '46.7100000', 'Low'),
(4, 'Residential North', '24.7050000', '46.7150000', 'Medium');

-- --------------------------------------------------------

--
-- بنية الجدول `employee`
--

CREATE TABLE `employee` (
  `EmployeeID` int(11) NOT NULL,
  `EmployeeCode` varchar(50) DEFAULT NULL,
  `EmployeeName` varchar(100) NOT NULL,
  `Email` varchar(255) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `AreaID` int(11) DEFAULT NULL,
  `AdminID` int(11) DEFAULT NULL,
  `CreatedAt` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- إرجاع أو استيراد بيانات الجدول `employee`
--

INSERT INTO `employee` (`EmployeeID`, `EmployeeCode`, `EmployeeName`, `Email`, `Password`, `AreaID`, `AdminID`, `CreatedAt`) VALUES
(1, '11', 'Deemah', 'demohato@gmail.com', '$2y$10$fkoQGHEJMHr2He9Oqw.5UODmPAQNNKA.qIV7OUoKPXEA67GzpRqfG', 1, 1, '2026-04-12 12:35:39'),
(2, '22', 'Aseel', 'AseelAbdulaziz771@gmail.com', '$2y$10$9ixYAg9yvwaYEWk0BIeUd.JyoFot9ByJ9cc.pTdO46pQ6.pkhj3Wy', 2, 1, '2026-04-12 12:35:39'),
(3, '33', 'Reema', 'Alnajimreema@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 4, 1, '2026-04-12 12:35:39'),
(4, '55', 'Norah', 'Norahaldlal@gmail.com', '$2y$10$MUBZFZXBCYOk1QiyFpaapu8RqAXfGU.Qzslc2q/c8QohqahFhCcd.', 1, 1, '2026-04-12 12:45:13');

-- --------------------------------------------------------

--
-- بنية الجدول `lamp`
--

CREATE TABLE `lamp` (
  `LampID` int(11) NOT NULL,
  `Status` enum('on','off') DEFAULT 'on',
  `Lux_Value` decimal(8,2) DEFAULT '0.00',
  `AreaID` int(11) NOT NULL,
  `offset_lat` decimal(10,7) DEFAULT '0.0000000',
  `offset_lng` decimal(10,7) DEFAULT '0.0000000'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- إرجاع أو استيراد بيانات الجدول `lamp`
--

INSERT INTO `lamp` (`LampID`, `Status`, `Lux_Value`, `AreaID`, `offset_lat`, `offset_lng`) VALUES
(1, 'on', '150.00', 1, '0.0010000', '0.0012000'),
(2, 'off', '0.00', 1, '0.0020000', '-0.0008000'),
(3, 'on', '170.00', 1, '-0.0005000', '0.0020000'),
(4, 'on', '130.00', 2, '0.0015000', '0.0005000'),
(5, 'on', '150.00', 2, '-0.0010000', '0.0018000'),
(6, 'off', '0.00', 2, '0.0025000', '-0.0012000'),
(7, 'on', '110.00', 3, '0.0008000', '0.0010000'),
(8, 'on', '145.00', 3, '-0.0015000', '0.0022000'),
(9, 'off', '0.00', 3, '0.0018000', '-0.0005000'),
(10, 'on', '160.00', 4, '0.0012000', '0.0015000'),
(11, 'on', '135.00', 4, '-0.0008000', '0.0025000'),
(12, 'off', '0.00', 4, '0.0022000', '-0.0010000');

-- --------------------------------------------------------

--
-- بنية الجدول `lampreading`
--

CREATE TABLE `lampreading` (
  `readingID` int(11) NOT NULL,
  `LampID` int(11) NOT NULL,
  `ambientLight` decimal(8,2) DEFAULT NULL,
  `motionDetected` tinyint(1) DEFAULT '0',
  `readingTime` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- بنية الجدول `report`
--

CREATE TABLE `report` (
  `ReportID` int(11) NOT NULL,
  `LampID` int(11) NOT NULL,
  `EmployeeID` int(11) NOT NULL,
  `Details` text NOT NULL,
  `Status` enum('pending','in_progress','resolved') DEFAULT 'pending',
  `CreatedAt` datetime DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- إرجاع أو استيراد بيانات الجدول `report`
--

INSERT INTO `report` (`ReportID`, `LampID`, `EmployeeID`, `Details`, `Status`, `CreatedAt`, `UpdatedAt`) VALUES
(1, 2, 1, 'aaa', 'in_progress', '2026-04-12 12:41:09', '2026-04-12 12:42:22');

-- --------------------------------------------------------

--
-- بنية الجدول `weekly_reading`
--

CREATE TABLE `weekly_reading` (
  `WeekID` int(11) NOT NULL,
  `AreaID` int(11) NOT NULL,
  `WeekStart` date NOT NULL,
  `WeekEnd` date NOT NULL,
  `AvgAmbient` decimal(8,2) DEFAULT '0.00',
  `AvgLux` decimal(8,2) DEFAULT '0.00',
  `ReadingCount` int(11) DEFAULT '0',
  `CreatedAt` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- إرجاع أو استيراد بيانات الجدول `weekly_reading`
--

INSERT INTO `weekly_reading` (`WeekID`, `AreaID`, `WeekStart`, `WeekEnd`, `AvgAmbient`, `AvgLux`, `ReadingCount`, `CreatedAt`) VALUES
(1, 1, '2026-03-16', '2026-03-22', '82.50', '148.20', 2016, '2026-04-12 13:09:24'),
(2, 1, '2026-03-23', '2026-03-29', '78.30', '152.10', 2016, '2026-04-12 13:09:24'),
(3, 1, '2026-03-30', '2026-04-05', '85.70', '145.80', 2016, '2026-04-12 13:09:24'),
(4, 1, '2026-04-06', '2026-04-12', '79.90', '150.40', 2016, '2026-04-12 13:09:24'),
(5, 2, '2026-03-16', '2026-03-22', '65.10', '128.50', 2016, '2026-04-12 13:09:24'),
(6, 2, '2026-03-23', '2026-03-29', '70.40', '131.20', 2016, '2026-04-12 13:09:24'),
(7, 2, '2026-03-30', '2026-04-05', '68.80', '135.60', 2016, '2026-04-12 13:09:24'),
(8, 2, '2026-04-06', '2026-04-12', '72.30', '129.80', 2016, '2026-04-12 13:09:24'),
(9, 3, '2026-03-16', '2026-03-22', '45.20', '108.40', 2016, '2026-04-12 13:09:24'),
(10, 3, '2026-03-23', '2026-03-29', '48.60', '112.30', 2016, '2026-04-12 13:09:24'),
(11, 3, '2026-03-30', '2026-04-05', '43.90', '106.70', 2016, '2026-04-12 13:09:24'),
(12, 3, '2026-04-06', '2026-04-12', '50.10', '114.20', 2016, '2026-04-12 13:09:24'),
(13, 4, '2026-03-16', '2026-03-22', '58.70', '132.10', 2016, '2026-04-12 13:09:24'),
(14, 4, '2026-03-23', '2026-03-29', '61.20', '135.40', 2016, '2026-04-12 13:09:24'),
(15, 4, '2026-03-30', '2026-04-05', '55.80', '130.60', 2016, '2026-04-12 13:09:24'),
(16, 4, '2026-04-06', '2026-04-12', '63.40', '137.90', 2016, '2026-04-12 13:09:24');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`AdminID`),
  ADD UNIQUE KEY `uq_admin_email` (`Email`);

--
-- Indexes for table `area`
--
ALTER TABLE `area`
  ADD PRIMARY KEY (`AreaID`);

--
-- Indexes for table `employee`
--
ALTER TABLE `employee`
  ADD PRIMARY KEY (`EmployeeID`),
  ADD UNIQUE KEY `uq_emp_email` (`Email`),
  ADD KEY `fk_emp_area` (`AreaID`),
  ADD KEY `fk_emp_admin` (`AdminID`);

--
-- Indexes for table `lamp`
--
ALTER TABLE `lamp`
  ADD PRIMARY KEY (`LampID`),
  ADD KEY `fk_lamp_area` (`AreaID`);

--
-- Indexes for table `lampreading`
--
ALTER TABLE `lampreading`
  ADD PRIMARY KEY (`readingID`),
  ADD KEY `fk_reading_lamp` (`LampID`);

--
-- Indexes for table `report`
--
ALTER TABLE `report`
  ADD PRIMARY KEY (`ReportID`),
  ADD KEY `fk_report_lamp` (`LampID`),
  ADD KEY `fk_report_emp` (`EmployeeID`);

--
-- Indexes for table `weekly_reading`
--
ALTER TABLE `weekly_reading`
  ADD PRIMARY KEY (`WeekID`),
  ADD UNIQUE KEY `uq_area_week` (`AreaID`,`WeekStart`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `AdminID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `area`
--
ALTER TABLE `area`
  MODIFY `AreaID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `employee`
--
ALTER TABLE `employee`
  MODIFY `EmployeeID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `lamp`
--
ALTER TABLE `lamp`
  MODIFY `LampID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `lampreading`
--
ALTER TABLE `lampreading`
  MODIFY `readingID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `report`
--
ALTER TABLE `report`
  MODIFY `ReportID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `weekly_reading`
--
ALTER TABLE `weekly_reading`
  MODIFY `WeekID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- قيود الجداول المحفوظة
--

--
-- القيود للجدول `employee`
--
ALTER TABLE `employee`
  ADD CONSTRAINT `fk_emp_admin` FOREIGN KEY (`AdminID`) REFERENCES `admin` (`AdminID`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_emp_area` FOREIGN KEY (`AreaID`) REFERENCES `area` (`AreaID`) ON DELETE SET NULL;

--
-- القيود للجدول `lamp`
--
ALTER TABLE `lamp`
  ADD CONSTRAINT `fk_lamp_area` FOREIGN KEY (`AreaID`) REFERENCES `area` (`AreaID`) ON DELETE CASCADE;

--
-- القيود للجدول `lampreading`
--
ALTER TABLE `lampreading`
  ADD CONSTRAINT `fk_reading_lamp` FOREIGN KEY (`LampID`) REFERENCES `lamp` (`LampID`) ON DELETE CASCADE;

--
-- القيود للجدول `report`
--
ALTER TABLE `report`
  ADD CONSTRAINT `fk_report_emp` FOREIGN KEY (`EmployeeID`) REFERENCES `employee` (`EmployeeID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_report_lamp` FOREIGN KEY (`LampID`) REFERENCES `lamp` (`LampID`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
