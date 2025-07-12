CREATE TABLE medical_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    certificate_number VARCHAR(50) NOT NULL,
    patient_name VARCHAR(100) NOT NULL,
    address TEXT NOT NULL,
    employment VARCHAR(100),
    signs_symptoms TEXT,
    medical_opinion TEXT,
    recommendations TEXT,
    fit_for_duty ENUM('Yes', 'No') NOT NULL,
    absence_period VARCHAR(50),
    medical_officer_signature TEXT NOT NULL, -- Stores base64-encoded signature
    submission_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);