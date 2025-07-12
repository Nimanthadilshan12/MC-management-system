CREATE TABLE `prescriptions` (
  `PrescriptionID` int(11) NOT NULL AUTO_INCREMENT,
  `PatientID` varchar(50) NOT NULL,
  `DoctorID` varchar(50) NOT NULL,
  `Medication` text NOT NULL,
  `Dosage` varchar(100) NOT NULL,
  `DateIssued` date NOT NULL,
  `Status` enum('Pending', 'Dispensed') NOT NULL DEFAULT 'Pending',
  PRIMARY KEY (`PrescriptionID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Add example prescriptions:
INSERT INTO `prescriptions` (`PatientID`, `DoctorID`, `Medication`, `Dosage`, `DateIssued`, `Status`) VALUES
('pat001', 'DOC002', 'Paracetamol', '500mg twice a day', '2025-07-07', 'Pending'),
('pat002', 'DOC002', 'Amoxicillin', '250mg three times a day', '2025-07-06', 'Pending'),
('pat003', 'DOC002', 'Cetirizine', '10mg once daily', '2025-07-05', 'Dispensed');
