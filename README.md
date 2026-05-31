# Hostel Management System

A full CRUD hostel management system built with HTML, CSS, JavaScript, PHP, and MySQL (XAMPP). No frameworks used — everything is written from scratch.

---

## Setup Instructions

### 1. Requirements

- XAMPP (Apache + MySQL + PHP 8.0+)

### 2. File Placement

Copy the `Hostel/` folder into your XAMPP `htdocs` directory:

```
C:/xampp/htdocs/Hostel/
```

### 3. Database Setup

1. Start XAMPP — enable **Apache** and **MySQL**
2. Open **phpMyAdmin**: http://localhost/phpmyadmin
3. Click **Import** → select `schema.sql` → click **Go**

This creates the `hostel_db` database with all tables and default seed data.

### 4. Run the App

Open your browser: http://localhost/Hostel/

---

## Default Login

| Role    | URL            | Username | Password                |
| ------- | -------------- | -------- | ----------------------- |
| Admin   | `/login.php`   | admin    | admin123                |
| Student | `/student.php` | —        | Full name as registered |

> Admin password can be changed from the dashboard Quick Actions panel.

> Naturally an error will be shown if you enter a student that isn't registered yet.

---

## Pages

### Admin Portal

| Page            | File                  | Description                                                  |
| --------------- | --------------------- | ------------------------------------------------------------ |
| Login           | `login.php`           | Admin authentication                                         |
| Dashboard       | `dashboard.php`       | Stats overview, notices snapshot, quick actions              |
| Students        | `students.php`        | Full CRUD — add, edit, delete, search, filter                |
| Classes         | `classes.php`         | Students grouped by class stream                             |
| Timetable       | `timetable.php`       | Evening routine CRUD — 6PM to 10PM                           |
| Attendance      | `attendance.php`      | Mark daily attendance, view monthly report                   |
| Fees            | `fees.php`            | Add fee records, mark as paid, track overdue                 |
| Notices         | `notices.php`         | Post announcements with Normal / Important / Urgent priority |
| Change Password | `change-password.php` | Update admin account password                                |

### Student Portal

| Page              | File                    | Description                                |
| ----------------- | ----------------------- | ------------------------------------------ |
| Student Login     | `student.php`           | Name-based lookup — no password needed     |
| Student Dashboard | `student-dashboard.php` | Profile, class info, timetable, classmates |

---

## Section → Class Assignment Logic

| Section | Class                     |
| ------- | ------------------------- |
| A, B, C | Computer & Optional Maths |
| D, E    | Economics & Accounts      |

Assignment is automatic — selecting a section when adding a student determines their class with no manual step needed.

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

## Notes

- No Bootstrap, Tailwind, jQuery, or any external framework used
- Passwords are hashed using PHP `password_hash()` / `password_verify()`
- Admin and student sessions are completely independent (`hms_admin` and `hms_student` cookies)
- SQL queries use prepared statements throughout to prevent injection
