CREATE TABLE prescriptions (
    PrescriptionID INT AUTO_INCREMENT PRIMARY KEY,
    PatientID INT,
    DoctorID INT,
    Medication VARCHAR(255),
    Dosage VARCHAR(100),
    Frequency VARCHAR(100),
    Duration VARCHAR(50),
    DateIssued DATE,
    Status ENUM('Pending', 'Dispensed') DEFAULT 'Pending'
);
