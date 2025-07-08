CREATE TABLE feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(50) NOT NULL,
    feedback_text TEXT NOT NULL,
    submit_date DATETIME NOT NULL
);