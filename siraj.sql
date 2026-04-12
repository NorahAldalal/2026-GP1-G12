-- ============================================================
--  SIRAJ — Refactored Database Schema v2
--  - Admin and Employee are now separate tables
--  - PasswordReset table removed (cookies used instead)
--  - Report references Employee directly
-- ============================================================

CREATE DATABASE IF NOT EXISTS `siraj`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `siraj`;

-- ─── Area ────────────────────────────────────────────────
CREATE TABLE `area` (
  `AreaID`          INT NOT NULL AUTO_INCREMENT,
  `AreaName`        VARCHAR(150) NOT NULL,
  `Latitude`        DECIMAL(10,7) NOT NULL,
  `Longitude`       DECIMAL(10,7) NOT NULL,
  `Pollution_level` ENUM('Low','Medium','High') DEFAULT 'Low',
  PRIMARY KEY (`AreaID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Admin ───────────────────────────────────────────────
CREATE TABLE `admin` (
  `AdminID`   INT NOT NULL AUTO_INCREMENT,
  `AdminName` VARCHAR(100) NOT NULL,
  `Email`     VARCHAR(255) NOT NULL,
  `Password`  VARCHAR(255) NOT NULL,
  `CreatedAt` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`AdminID`),
  UNIQUE KEY `uq_admin_email` (`Email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Employee ─────────────────────────────────────────────
CREATE TABLE `employee` (
  `EmployeeID`   INT NOT NULL AUTO_INCREMENT,
  `EmployeeCode` VARCHAR(50) DEFAULT NULL,
  `EmployeeName` VARCHAR(100) NOT NULL,
  `Email`        VARCHAR(255) NOT NULL,
  `Password`     VARCHAR(255) NOT NULL,
  `AreaID`       INT DEFAULT NULL,
  `AdminID`      INT DEFAULT NULL,
  `CreatedAt`    DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`EmployeeID`),
  UNIQUE KEY `uq_emp_email` (`Email`),
  CONSTRAINT `fk_emp_area`  FOREIGN KEY (`AreaID`)  REFERENCES `area`(`AreaID`)   ON DELETE SET NULL,
  CONSTRAINT `fk_emp_admin` FOREIGN KEY (`AdminID`) REFERENCES `admin`(`AdminID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Lamp ────────────────────────────────────────────────
CREATE TABLE `lamp` (
  `LampID`     INT NOT NULL AUTO_INCREMENT,
  `Status`     ENUM('on','off') DEFAULT 'on',
  `Lux_Value`  DECIMAL(8,2) DEFAULT 0.00,
  `AreaID`     INT NOT NULL,
  `offset_lat` DECIMAL(10,7) DEFAULT 0,
  `offset_lng` DECIMAL(10,7) DEFAULT 0,
  PRIMARY KEY (`LampID`),
  CONSTRAINT `fk_lamp_area` FOREIGN KEY (`AreaID`) REFERENCES `area`(`AreaID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── LampReading ──────────────────────────────────────────
CREATE TABLE `lampreading` (
  `readingID`      INT NOT NULL AUTO_INCREMENT,
  `LampID`         INT NOT NULL,
  `ambientLight`   DECIMAL(8,2) DEFAULT NULL,
  `motionDetected` TINYINT(1) DEFAULT 0,
  `readingTime`    DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`readingID`),
  CONSTRAINT `fk_reading_lamp` FOREIGN KEY (`LampID`) REFERENCES `lamp`(`LampID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Report ───────────────────────────────────────────────
CREATE TABLE `report` (
  `ReportID`   INT NOT NULL AUTO_INCREMENT,
  `LampID`     INT NOT NULL,
  `EmployeeID` INT NOT NULL,
  `Details`    TEXT NOT NULL,
  `Status`     ENUM('pending','in_progress','resolved') DEFAULT 'pending',
  `CreatedAt`  DATETIME DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt`  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ReportID`),
  CONSTRAINT `fk_report_lamp` FOREIGN KEY (`LampID`)     REFERENCES `lamp`(`LampID`)         ON DELETE CASCADE,
  CONSTRAINT `fk_report_emp`  FOREIGN KEY (`EmployeeID`) REFERENCES `employee`(`EmployeeID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Sample Data ──────────────────────────────────────────
INSERT INTO `area` (`AreaName`,`Latitude`,`Longitude`,`Pollution_level`) VALUES
('Downtown',          24.6877,46.7219,'High'),
('Al Hamra District', 24.6950,46.7350,'Medium'),
('Industrial Zone',   24.6700,46.7100,'Low'),
('Residential North', 24.7050,46.7150,'Medium');

INSERT INTO `lamp` (`Status`,`Lux_Value`,`AreaID`,`offset_lat`,`offset_lng`) VALUES
('on', 150,1, 0.0010, 0.0012),('off',0,1,0.0020,-0.0008),('on',170,1,-0.0005,0.0020),
('on', 130,2, 0.0015, 0.0005),('on', 150,2,-0.0010,0.0018),('off',0,2,0.0025,-0.0012),
('on', 110,3, 0.0008, 0.0010),('on', 145,3,-0.0015,0.0022),('off',0,3,0.0018,-0.0005),
('on', 160,4, 0.0012, 0.0015),('on', 135,4,-0.0008,0.0025),('off',0,4,0.0022,-0.0010);

-- Default admin — password: password
INSERT INTO `admin` (`AdminName`,`Email`,`Password`) VALUES
('Administrator','admin@siraj.city',
'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Sample employees — password: password
INSERT INTO `employee` (`EmployeeCode`,`EmployeeName`,`Email`,`Password`,`AreaID`,`AdminID`) VALUES
('11','Deemah','demohato@gmail.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',1,1),
('22','Aseel','AseelAbdulaziz771@gmail.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',2,1),
('33','Reema','Alnajimreema@gmail.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',4,1);
