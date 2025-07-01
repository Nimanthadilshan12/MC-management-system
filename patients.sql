CREATE TABLE IF NOT EXISTS patients (
        id INT AUTO_INCREMENT PRIMARY KEY,
        UserID VARCHAR(50) UNIQUE NOT NULL,
        Password VARCHAR(255) NOT NULL,
        Fullname VARCHAR(100) NOT NULL,
        Email VARCHAR(100) NOT NULL,
        Contact_No VARCHAR(20) NOT NULL,
        Age INT,
        Gender VARCHAR(10),
        Birth DATE,
        Blood_Type VARCHAR(5),
        Academic_Year VARCHAR(20),
        Faculty VARCHAR(50),
        Citizenship VARCHAR(50),
        Any_allergies TEXT,
        Emergency_Contact VARCHAR(20)
    );