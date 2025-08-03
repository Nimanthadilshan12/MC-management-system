CREATE TABLE form_subjects (
    subject_id INT AUTO_INCREMENT PRIMARY KEY,
    form_id INT NOT NULL,
    subject_name VARCHAR(100) NOT NULL,
    subject_code VARCHAR(50) NOT NULL,
    medical_dates VARCHAR(255) NOT NULL,
    place_of_issue VARCHAR(100) NOT NULL,
    FOREIGN KEY (form_id) REFERENCES medical_forms(form_id)
);