CREATE TABLE medications (
    medication_id INT AUTO_INCREMENT PRIMARY KEY,
    prescription_id VARCHAR(50) NOT NULL,
    medicine_name VARCHAR(100) NOT NULL,
    dosage VARCHAR(50),
    frequency ENUM('Once daily', 'Twice daily', 'Three times daily', 'As needed') NOT NULL,
    time ENUM('Morning', 'Afternoon', 'Evening', 'Bedtime') NOT NULL,
    FOREIGN KEY (prescription_id) REFERENCES prescriptions(prescription_id)
);