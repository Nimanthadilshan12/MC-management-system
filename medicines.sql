CREATE TABLE medicines (
    MedicineID INT PRIMARY KEY AUTO_INCREMENT,
    Name VARCHAR(100) NOT NULL,
    Quantity INT NOT NULL,
    ExpiryDate DATE NOT NULL
);