CREATE TABLE `mail` (
  `id` int(11) NOT NULL,
  `doctor_name` varchar(255) NOT NULL,
  `doctor_email` varchar(255) DEFAULT NULL,
  `patient_name` varchar(255) NOT NULL,
  `patient_age` int(11) DEFAULT NULL,
  `patient_condition` text NOT NULL,
  `patient_priority` varchar(50) DEFAULT NULL,
  `hospital_email` varchar(255) NOT NULL,
  `timestamp` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
