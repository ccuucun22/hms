# CarePoint HMS — Hospital Management System

A complete, role-based Hospital Management System built with **Core PHP + MySQL + Bootstrap 5**.
No framework, no Composer, no build step — just upload and run on any PHP/MySQL hosting (or XAMPP/WAMP locally).

---

## ✅ What's included

- Role-based login with automatic redirect to the correct dashboard (Admin / Doctor / Receptionist / Accountant)
- Separate sidebar & menu for every role
- Patient registration & search
- Appointment booking — **checkup fee is generated automatically** from the doctor's consultation fee
- Patient admission — **room allocation** + optional **operation/surgery charges** (tracked separately from the checkup fee)
- Patient discharge — **room charges auto-calculated** (days admitted × room's per-day rate) and combined into one final, itemised bill
- Billing module — accountant can view, filter, and mark bills as paid (cash / card / online)
- Reports — revenue breakdown (checkup fees vs operation charges vs room charges) by date range
- Doctor module — today's schedule, appointment list, patient history (EHR-style)
- Admin module — manage doctors, manage rooms, manage staff accounts, hospital-wide reports
- Fully responsive (mobile, tablet, desktop) — sidebar collapses into a slide-out menu on small screens

---

## 📁 Folder Structure

```
hms/
├── index.php                 → Landing page + Login (entry point)
├── login_process.php         → Handles login & role-based redirect
├── logout.php
├── config/
│   └── db.php                 → Database connection settings (EDIT THIS)
├── database/
│   └── hms.sql                → Full database schema + demo seed data
├── includes/
│   ├── auth.php               → Session, access-control & helper functions
│   ├── header.php             → Shared topbar + layout opening
│   └── footer.php             → Shared layout closing + scripts
├── assets/
│   ├── css/style.css           → All styling (hospital theme, fully responsive)
│   └── js/script.js            → Sidebar toggle + small interactions
├── admin/          → Dashboard, Doctors, Rooms, Staff Accounts, Reports
├── doctor/         → Dashboard, My Appointments, My Patients
├── receptionist/   → Dashboard, Patients, Appointments, Admit Patient, Discharge Patient
└── accountant/     → Dashboard, Billing, Reports
```

---

## 🚀 Setup Instructions (XAMPP / Local Server)

1. **Copy the `hms` folder** into your server's web root:
   - XAMPP (Windows): `C:\xampp\htdocs\hms`
   - XAMPP (Mac/Linux): `/Applications/XAMPP/htdocs/hms` or `/opt/lampp/htdocs/hms`

2. **Start Apache and MySQL** from your XAMPP/WAMP control panel.

3. **Create the database:**
   - Open `phpMyAdmin` (usually `http://localhost/phpmyadmin`)
   - Click **Import** → choose the file `database/hms.sql` → click **Go**
   - This automatically creates the `carepoint_hms` database with all tables and demo data.

4. **Check your database credentials** in `config/db.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'carepoint_hms');
   define('DB_USER', 'root');
   define('DB_PASS', '');   // set this if your MySQL root user has a password
   ```

5. **Open the system in your browser:**
   ```
   http://localhost/hms/
   ```

That's it — no Composer, no npm install, no build process.

---

## 🔑 Demo Login Accounts

All demo accounts use the password: **`password123`**

| Role         | Email                       |
|--------------|------------------------------|
| Admin        | admin@carepoint.com          |
| Doctor       | ahmed.khan@carepoint.com     |
| Doctor (Surgeon) | sara.malik@carepoint.com |
| Receptionist | reception@carepoint.com      |
| Accountant   | accounts@carepoint.com       |

⚠️ **Change these passwords (or delete/recreate the accounts) before using this on a real, public server.**

---

## 🧭 How the core workflow works (exactly as requested)

1. **Receptionist books an appointment** → the system automatically generates the **checkup/consultation fee** based on the selected doctor.
2. **If the patient needs to be admitted**, the receptionist opens **Admit Patient**:
   - Selects an available **room** → room is allocated and marked "Occupied".
   - If the patient needs an **operation/surgery**, ticks the checkbox and enters the operation type + charges — these are recorded **separately** from the checkup fee.
3. **When the patient is ready to leave**, the receptionist opens **Discharge Patient**:
   - Selects the admitted patient → the system calculates **room charges** automatically: `(discharge date − admission date) × room's per-day rate`.
   - All three charges — **checkup fee + operation charges + room charges** — are combined into **one final bill**.
   - The room is automatically freed and becomes available again for the next patient.
4. **The Accountant** sees this bill in **Billing**, and can mark it as paid (cash/card/online).
5. **Admin & Accountant Reports** show the revenue breakdown by category and date range.

---

## 🔒 Security Notes

- Passwords are hashed with PHP's `password_hash()` (bcrypt) — never stored in plain text.
- All database queries use PDO **prepared statements** to prevent SQL injection.
- Every page checks the logged-in user's role (`require_role()`) before showing any data — a receptionist cannot open admin pages, a doctor cannot open billing pages, etc.
- All dynamic output is escaped with `htmlspecialchars()` to prevent XSS.

### Recommended before going live (production checklist):
- [ ] Change all demo account passwords
- [ ] Set a strong `DB_PASS` for your MySQL root/app user (don't use `root` with no password in production)
- [ ] Enable HTTPS (SSL certificate) on your hosting
- [ ] Set `session.cookie_secure` and `session.cookie_httponly` in `php.ini` once on HTTPS
- [ ] Take regular database backups (`mysqldump`)

---

## 🛠 Tech Stack

- **Backend:** Core PHP 8 (PDO + MySQLi-safe prepared statements)
- **Database:** MySQL / MariaDB
- **Frontend:** Bootstrap 5, Bootstrap Icons, Google Fonts (Inter + Poppins) — all loaded via CDN
- **No build tools required** — works on any standard shared hosting that supports PHP + MySQL

---

## 📌 Notes for Customization

- To add more room types, edit the `ENUM` list in the `rooms` table (`database/hms.sql`) and the `<select>` in `admin/rooms.php`.
- To change the hospital name/branding, edit the `<span>CarePoint</span>` text inside each `*/sidebar.php` file and `index.php`.
- All colors are defined as CSS variables at the top of `assets/css/style.css` (`:root { --primary: ... }`) — change them in one place to re-theme the whole system.

---

Built for a real, working hospital front-desk → ward → billing workflow. Every flow above (appointment → admission → operation → discharge → bill → payment) was tested end-to-end before delivery.
