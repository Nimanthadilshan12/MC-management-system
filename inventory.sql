-- inventory.sql
-- Creates the inventory table for the mc1 database
-- Used by manage_inventory.php to manage medication stock

-- Drop table if it exists
DROP TABLE IF EXISTS inventory;

-- Create inventory table
CREATE TABLE inventory (
    id INT PRIMARY KEY AUTO_INCREMENT,
    medication_name VARCHAR(100) NOT NULL,
    quantity INT NOT NULL CHECK (quantity >= 0),
    expiry_date DATE,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Insert sample data
INSERT INTO inventory (medication_name, quantity, expiry_date) VALUES
('Paracetamol', 100, '2026-12-31'),
('Amoxicillin', 50, '2025-06-30'),
('Ibuprofen', 75, '2027-03-15');