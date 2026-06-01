-- ============================================================
--  Hostel Management System — Database Schema (Full)
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
VALUES ('admin', '$2y$10$wsc4kmzVzPZttlbqz510s.2zK4r/Ox5kmfXO8FtM0Xy38P5H8CAcu')
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

-- Classes
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

-- Timetable
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

-- Attendance
CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    date DATE NOT NULL,
    status ENUM('Present','Absent','Leave') NOT NULL DEFAULT 'Present',
    marked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    UNIQUE KEY unique_attendance (student_id, date)
) ENGINE=InnoDB;

-- Fees
CREATE TABLE IF NOT EXISTS fees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    due_date DATE NOT NULL,
    paid_date DATE DEFAULT NULL,
    status ENUM('Paid','Unpaid','Overdue') NOT NULL DEFAULT 'Unpaid',
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Notices
CREATE TABLE IF NOT EXISTS notices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    body TEXT NOT NULL,
    priority ENUM('Normal','Important','Urgent') DEFAULT 'Normal',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);