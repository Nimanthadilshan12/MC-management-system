CREATE TABLE settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value TEXT NOT NULL,
    setting_description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO settings (setting_key, setting_value, setting_description) VALUES
('opening_time', '08:00', 'Clinic opening time (HH:MM)'),
('closing_time', '17:00', 'Clinic closing time (HH:MM)'),
('operation_days', '1,2,3,4,5', 'Days of operation (1=Monday, ..., 7=Sunday)'),
('emergency_contact_number', '+94-123-456-7890', 'Medical centre emergency contact number'),
('admin_email', 'admin@medicalcentre.lk', 'Administrative email address'),
('maintenance_mode', '0', 'Enable (1) or disable (0) maintenance mode'),
('maintenance_message', 'The system is under maintenance. Please try again later.', 'Message displayed during maintenance mode');