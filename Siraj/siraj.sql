-- phpMyAdmin SQL Dump
-- version 5.1.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: 05 أبريل 2026 الساعة 20:29
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
-- بنية الجدول `area`
--

CREATE TABLE `area` (
  `AreaID` int(11) NOT NULL,
  `AreaName` varchar(150) NOT NULL,
  `Latitude` decimal(10,7) NOT NULL,
  `Longitude` decimal(10,7) NOT NULL,
  `Pollution_level` enum('Low','Medium','High') DEFAULT 'Low'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

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
-- بنية الجدول `lamp`
--

CREATE TABLE `lamp` (
  `LampID` int(11) NOT NULL,
  `Status` enum('on','off') DEFAULT 'on',
  `Lux_Value` decimal(8,2) DEFAULT '0.00',
  `AreaID` int(11) NOT NULL,
  `offset_lat` decimal(10,7) DEFAULT '0.0000000',
  `offset_lng` decimal(10,7) DEFAULT '0.0000000'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- بنية الجدول `passwordreset`
--

CREATE TABLE `passwordreset` (
  `id` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `Code` varchar(10) NOT NULL,
  `ExpiresAt` datetime NOT NULL,
  `Attempts` int(11) DEFAULT '0',
  `Used` tinyint(1) DEFAULT '0',
  `CreatedAt` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- إرجاع أو استيراد بيانات الجدول `passwordreset`
--

INSERT INTO `passwordreset` (`id`, `UserID`, `Code`, `ExpiresAt`, `Attempts`, `Used`, `CreatedAt`) VALUES
(2, 4, '3216', '2026-04-05 10:54:02', 0, 1, '2026-04-05 13:44:02');

-- --------------------------------------------------------

--
-- بنية الجدول `report`
--

CREATE TABLE `report` (
  `ReportID` int(11) NOT NULL,
  `LampID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `Details` text NOT NULL,
  `Status` enum('pending','in_progress','resolved') DEFAULT 'pending',
  `CreatedAt` datetime DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- إرجاع أو استيراد بيانات الجدول `report`
--

INSERT INTO `report` (`ReportID`, `LampID`, `UserID`, `Details`, `Status`, `CreatedAt`, `UpdatedAt`) VALUES
(1, 2, 4, 'zero lux', 'in_progress', '2026-04-05 13:35:45', '2026-04-05 13:36:53');

-- --------------------------------------------------------

--
-- بنية الجدول `user`
--

CREATE TABLE `user` (
  `UserID` int(11) NOT NULL,
  `EmployeeCode` varchar(50) DEFAULT NULL,
  `UserName` varchar(100) NOT NULL,
  `Email` varchar(255) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `Role` enum('admin','employee') DEFAULT 'employee',
  `AreaID` int(11) DEFAULT NULL,
  `AdminID` int(11) DEFAULT NULL,
  `CreatedAt` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- إرجاع أو استيراد بيانات الجدول `user`
--

INSERT INTO `user` (`UserID`, `EmployeeCode`, `UserName`, `Email`, `Password`, `Role`, `AreaID`, `AdminID`, `CreatedAt`) VALUES
(1, NULL, 'Administrator', 'admin@siraj.city', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NULL, NULL, '2026-04-04 17:08:29'),
(3, NULL, 'Nora', 'norahaldlal@gmail.com', '$2y$10$n6kkwtfV5DvQgw26mVDozObNSzDgyNr6DkaAesM.SXbT0jFtz1c32', 'admin', NULL, NULL, '2026-04-05 13:30:07'),
(4, '11', 'Deemah', 'demohato@gmail.com', '$2y$10$xA83RaW/8iDG1myp7FH/tutbhkNHNCruv0B4vsV4rvsHlInJqawym', 'employee', 1, 3, '2026-04-05 13:33:58'),
(5, '22', 'Aseel', 'AseelAbdulaziz771@gmail.com', '$2y$10$7heK0gVJ9yVxJhlz9S88aeHZYqD6/I3K56PmjEQRA5.lvYpI78lNW', 'employee', 2, 3, '2026-04-05 13:56:51'),
(6, '33', 'Reema', 'Alnajimreema@gmail.com', '$2y$10$pNmlurRJ5gkboI5v1nPFE.flTppNx6wKll7HXa8EjxdBy3xQn0XSW', 'employee', 4, 3, '2026-04-05 13:57:45');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `area`
--
ALTER TABLE `area`
  ADD PRIMARY KEY (`AreaID`);

--
-- Indexes for table `lamp`
--
ALTER TABLE `lamp`
  ADD PRIMARY KEY (`LampID`),
  ADD KEY `AreaID` (`AreaID`);

--
-- Indexes for table `lampreading`
--
ALTER TABLE `lampreading`
  ADD PRIMARY KEY (`readingID`),
  ADD KEY `LampID` (`LampID`);

--
-- Indexes for table `passwordreset`
--
ALTER TABLE `passwordreset`
  ADD PRIMARY KEY (`id`),
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `report`
--
ALTER TABLE `report`
  ADD PRIMARY KEY (`ReportID`),
  ADD KEY `LampID` (`LampID`),
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`UserID`),
  ADD UNIQUE KEY `Email` (`Email`),
  ADD KEY `fk_user_area` (`AreaID`),
  ADD KEY `fk_user_admin` (`AdminID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `area`
--
ALTER TABLE `area`
  MODIFY `AreaID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

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
-- AUTO_INCREMENT for table `passwordreset`
--
ALTER TABLE `passwordreset`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `report`
--
ALTER TABLE `report`
  MODIFY `ReportID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `UserID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- قيود الجداول المحفوظة
--

--
-- القيود للجدول `lamp`
--
ALTER TABLE `lamp`
  ADD CONSTRAINT `lamp_ibfk_1` FOREIGN KEY (`AreaID`) REFERENCES `area` (`AreaID`) ON DELETE CASCADE;

--
-- القيود للجدول `lampreading`
--
ALTER TABLE `lampreading`
  ADD CONSTRAINT `lampreading_ibfk_1` FOREIGN KEY (`LampID`) REFERENCES `lamp` (`LampID`) ON DELETE CASCADE;

--
-- القيود للجدول `passwordreset`
--
ALTER TABLE `passwordreset`
  ADD CONSTRAINT `passwordreset_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `user` (`UserID`) ON DELETE CASCADE;

--
-- القيود للجدول `report`
--
ALTER TABLE `report`
  ADD CONSTRAINT `report_ibfk_1` FOREIGN KEY (`LampID`) REFERENCES `lamp` (`LampID`) ON DELETE CASCADE,
  ADD CONSTRAINT `report_ibfk_2` FOREIGN KEY (`UserID`) REFERENCES `user` (`UserID`) ON DELETE CASCADE;

--
-- القيود للجدول `user`
--
ALTER TABLE `user`
  ADD CONSTRAINT `fk_user_admin` FOREIGN KEY (`AdminID`) REFERENCES `user` (`UserID`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_user_area` FOREIGN KEY (`AreaID`) REFERENCES `area` (`AreaID`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
