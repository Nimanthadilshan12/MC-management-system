CREATE TABLE patient_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id VARCHAR(50),
    doctor_id VARCHAR(50),
    visit_date DATE,
    diagnosis TEXT,
    treatment TEXT
);