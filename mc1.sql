-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 05, 2025 at 08:49 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `mc1`
--

-- --------------------------------------------------------

--
-- Table structure for table `doctors`
--

CREATE TABLE `doctors` (
  `id` int(11) NOT NULL,
  `UserID` varchar(50) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `Fullname` varchar(100) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `Contact_No` varchar(20) NOT NULL,
  `Specialization` varchar(100) DEFAULT NULL,
  `RegNo` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `doctors`
--

INSERT INTO `doctors` (`id`, `UserID`, `Password`, `Fullname`, `Email`, `Contact_No`, `Specialization`, `RegNo`) VALUES
(1, 'DOC002', 'hashed_password123', 'Dr. John Smith', 'bhadrahashini2000@gmail.com', '0716565656', 'Cardiologist', 'REG12345');

-- --------------------------------------------------------

--
-- Table structure for table `emergency_notifications`
--

CREATE TABLE `emergency_notifications` (
  `id` int(11) NOT NULL,
  `doctor_name` varchar(100) NOT NULL,
  `your_email` varchar(255) NOT NULL,
  `patient_name` varchar(100) NOT NULL,
  `patient_age` int(11) NOT NULL,
  `patient_condition` text NOT NULL,
  `priority_level` enum('Low','Medium','High','Critical') NOT NULL,
  `hospital_email` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `id` int(11) NOT NULL,
  `UserID` varchar(50) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `Fullname` varchar(100) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `Contact_No` varchar(20) NOT NULL,
  `Age` int(11) DEFAULT NULL,
  `Gender` varchar(10) DEFAULT NULL,
  `Birth` date DEFAULT NULL,
  `Blood_Type` varchar(5) DEFAULT NULL,
  `Academic_Year` varchar(20) DEFAULT NULL,
  `Faculty` varchar(50) DEFAULT NULL,
  `Citizenship` varchar(50) DEFAULT NULL,
  `Any_allergies` text DEFAULT NULL,
  `Emergency_Contact` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`id`, `UserID`, `Password`, `Fullname`, `Email`, `Contact_No`, `Age`, `Gender`, `Birth`, `Blood_Type`, `Academic_Year`, `Faculty`, `Citizenship`, `Any_allergies`, `Emergency_Contact`) VALUES
(1, 'pat001', 'hashed_password_1', 'Alice Fernando', 'alice@example.com', '0771234567', 22, 'Female', '2003-04-12', 'A+', '3rd Year', 'Science', 'Sri Lankan', 'Penicillin', '0711234567'),
(2, 'pat002', 'hashed_password_2', 'Nimal Perera', 'nimal@example.com', '0769876543', 24, 'Male', '2001-09-05', 'O-', '4th Year', 'Engineering', 'Sri Lankan', 'None', '0701122334'),
(3, 'pat003', 'hashed_password_3', 'Tharushi Silva', 'tharushi@example.com', '0784567890', 20, 'Female', '2005-01-18', 'B+', '2nd Year', 'Arts', 'Sri Lankan', 'Peanuts', '0756677889');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `doctors`
--
ALTER TABLE `doctors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UserID` (`UserID`);

--
-- Indexes for table `emergency_notifications`
--
ALTER TABLE `emergency_notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UserID` (`UserID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `doctors`
--
ALTER TABLE `doctors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `emergency_notifications`
--
ALTER TABLE `emergency_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
