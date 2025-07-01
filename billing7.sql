CREATE TABLE billing (
    BillID INT AUTO_INCREMENT PRIMARY KEY,
    PatientID INT,
    Amount DECIMAL(10, 2),
    DateIssued DATE,
    Paid ENUM('Yes', 'No') DEFAULT 'No'
);
