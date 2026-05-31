# Hostel Management System

A full CRUD hostel management system built with HTML, CSS, JavaScript, PHP, and MySQL (XAMPP).

---

## Setup Instructions

### 1. Requirements

- XAMPP (Apache + MySQL + PHP 8.0+)

### 2. File Placement

Copy the entire `hostel/` folder into your XAMPP `htdocs` directory:

```
C:/xampp/htdocs/hostel/
```

### 3. Database Setup

1. Start XAMPP — enable **Apache** and **MySQL**
2. Open **phpMyAdmin**: http://localhost/phpmyadmin
3. Click **Import** → select `hostel/schema.sql` → click **Go**

That creates the `hostel_db` database with all tables and default data.

### 4. Run the App

Open your browser: http://localhost/hostel/

---

## Default Login

| Username | Password |
| -------- | -------- |
| admin    | admin123 |

> To change the password: there is a quick action option for you to change your password.

---

## Pages

| Page           | File                  | Description                                           |
| -------------- | --------------------- | ----------------------------------------------------- |
| Login          | `login.php`           | Admin authentication                                  |
| Dashboard      | `dashboard.php`       | Overview stats, section chart, schedule snapshot      |
| Students       | `students.php`        | Full CRUD — add, edit, delete, search, filter         |
| Classes        | `classes.php`         | View students grouped by class (Computer / Economics) |
| Timetable      | `timetable.php`       | Evening routine CRUD — 6PM to 10PM slots              |
| Attendance     | `attendance.php`      | Make you able to take attendance of students          |
| Fee            | `fees.php `           | You can add fees to your student                      |
| Reset Password | `change-password.php` | You can change your password                          |
| Notices        | `notices.php`         | You can add notices and check it in the dashboard     |

---

## Section → Class Assignment Logic

| Section | Class                     |
| ------- | ------------------------- |
| A, B, C | Computer & Optional Maths |
| D, E    | Economics & Accounts      |

This is automatically applied when adding a student — no manual assignment needed.

---

## File Structure

```
Hostel/
├── index.php                  # Root redirect
├── login.php                  # Login page
├── logout.php                 # Logout handler
├── student.php                # Student portal login
├── student-dashboard.php      # Student portal dashboard
├── dashboard.php              # Admin dashboard
├── students.php               # Student CRUD
├── classes.php                # Class view
├── timetable.php              # Timetable CRUD
├── attendance.php             # Attendance tracking
├── fees.php                   # Fee management
├── notices.php                # Notice board
├── change-password.php        # Admin password change
├── generate-students.php      # Demo student generator (delete before live)
├── schema.sql                 # Database schema + seed data
├── css/
│   └── style.css              # Full stylesheet
├── js/
│   └── app.js                 # Modal helpers, class hints, alerts
└── includes/
    ├── config.php             # DB connection + helpers
    ├── auth.php               # Session/login guards
    └── navbar.php             # Shared navigation
```
