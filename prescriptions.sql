CREATE TABLE prescriptions (
    PrescriptionID INT AUTO_INCREMENT PRIMARY KEY,
    PatientID VARCHAR(20),
    DoctorID VARCHAR(20),
    Medication TEXT,
    Dosage VARCHAR(255),
    DateIssued DATE,
    Status ENUM('Pending', 'Dispensed') DEFAULT 'Pending'
    
);

ALTER TABLE prescriptions ADD COLUMN DosageAmount INT DEFAULT 0;