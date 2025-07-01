CREATE TABLE controlled_log (
    LogID INT AUTO_INCREMENT PRIMARY KEY,
    MedicineName VARCHAR(255),
    Quantity INT,
    Action ENUM('Received', 'Dispensed'),
    DateLogged DATE
);
