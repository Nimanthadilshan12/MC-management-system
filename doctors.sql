CREATE TABLE IF NOT EXISTS doctors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        UserID VARCHAR(50) UNIQUE NOT NULL,
        Password VARCHAR(255) NOT NULL,
        Fullname VARCHAR(100) NOT NULL,
        Email VARCHAR(100) NOT NULL,
        Contact_No VARCHAR(20) NOT NULL,
        Specialization VARCHAR(100),
        RegNo VARCHAR(50)
    );