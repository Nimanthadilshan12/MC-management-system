CREATE TABLE certificate_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    form_type VARCHAR(50) NOT NULL,
    name VARCHAR(255) NOT NULL,
    address TEXT NOT NULL,
    year VARCHAR(50),
    level VARCHAR(50),
    semester VARCHAR(50),
    academic_year VARCHAR(50),
    semester_year VARCHAR(50),
    reg_no VARCHAR(50) NOT NULL,
    contact_no VARCHAR(50) NOT NULL,
    degree_programme VARCHAR(255),
    subjects TEXT,
    certificate_details TEXT NOT NULL,
    submission_date DATE NOT NULL DEFAULT CURRENT_DATE
);
