CREATE TABLE prescriptions (
    prescription_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id VARCHAR(50),
    doctor_id VARCHAR(50),
    medication VARCHAR(100),
    dosage VARCHAR(100),
    prescribed_date DATE
);
INSERT INTO prescriptions (patient_id, doctor_id, medication, dosage, prescribed_date)
VALUES ('patient2', 'doctor1', 'Paracetamol', '500mg twice daily', '2025-06-25'),
       ('patient2', 'doctor1', 'Amoxicillin', '250mg three times daily', '2025-06-20');