-- ============================================================
--  Hostel Management System — Database Schema
-- ============================================================

CREATE DATABASE IF NOT EXISTS hostel_db;
USE hostel_db;

-- Admin login
CREATE TABLE IF NOT EXISTS admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin: admin / admin123
INSERT INTO admin (username, password)
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')
ON DUPLICATE KEY UPDATE username = username;

-- Students
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    section ENUM('A','B','C','D','E') NOT NULL,
    gender ENUM('Male','Female','Other') NOT NULL,
    room_number VARCHAR(10),
    phone VARCHAR(20),
    guardian_name VARCHAR(100),
    guardian_phone VARCHAR(20),
    enrolled_date DATE DEFAULT (CURRENT_DATE),
    status ENUM('Active','Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Classes (derived from section: A-C = Computer, D-E = Economics)
CREATE TABLE IF NOT EXISTS classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_name VARCHAR(100) NOT NULL,
    class_type ENUM('Computer','Economics') NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO classes (class_name, class_type, description) VALUES
('Computer & Optional Maths', 'Computer', 'For students in sections A, B, and C'),
('Economics & Accounts', 'Economics', 'For students in sections D and E');

-- Timetable (fixed hostel evening routine 6PM–10PM)
CREATE TABLE IF NOT EXISTS timetable (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slot_label VARCHAR(50) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    activity VARCHAR(100) NOT NULL,
    description VARCHAR(255),
    display_order INT DEFAULT 0
);

INSERT INTO timetable (slot_label, start_time, end_time, activity, description, display_order) VALUES
('Slot 1', '18:00:00', '18:30:00', 'Evening Tea & Refreshments', 'Students gather for evening tea', 1),
('Slot 2', '18:30:00', '19:30:00', 'Self Study — Session 1', 'Supervised independent study', 2),
('Slot 3', '19:30:00', '20:00:00', 'Dinner Break', 'Hostel dining hall', 3),
('Slot 4', '20:00:00', '21:00:00', 'Self Study — Session 2', 'Supervised independent study', 4),
('Slot 5', '21:00:00', '21:30:00', 'Optional Tutoring / Group Discussion', 'Teacher-assisted sessions or peer study', 5),
('Slot 6', '21:30:00', '22:00:00', 'Free Time / Personal Work', 'Reading, personal tasks', 6);
