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
| Username | Password  |
|----------|-----------|
| admin    | admin123  |

> To change the password: generate a new bcrypt hash with `password_hash('yourpassword', PASSWORD_DEFAULT)` and UPDATE the admin table.

---

## Pages

| Page | File | Description |
|------|------|-------------|
| Login | `login.php` | Admin authentication |
| Dashboard | `dashboard.php` | Overview stats, section chart, schedule snapshot |
| Students | `students.php` | Full CRUD — add, edit, delete, search, filter |
| Classes | `classes.php` | View students grouped by class (Computer / Economics) |
| Timetable | `timetable.php` | Evening routine CRUD — 6PM to 10PM slots |

---

## Section → Class Assignment Logic

| Section | Class |
|---------|-------|
| A, B, C | Computer & Optional Maths |
| D, E    | Economics & Accounts |

This is automatically applied when adding a student — no manual assignment needed.

---

## File Structure
```
hostel/
├── index.php              # Root redirect
├── login.php              # Login page
├── logout.php             # Logout handler
├── dashboard.php          # Main dashboard
├── students.php           # Student CRUD
├── classes.php            # Class view
├── timetable.php          # Timetable CRUD
├── schema.sql             # Database schema + seed data
├── css/
│   └── style.css          # Full stylesheet
├── js/
│   └── app.js             # Modal helpers, class hints, alerts
└── includes/
    ├── config.php          # DB connection + helpers
    ├── auth.php            # Session/login guards
    └── navbar.php          # Shared navigation
```
