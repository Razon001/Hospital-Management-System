# MediCore HMS — Hospital Management System

A complete, production-style hospital management system built in **PHP (PDO/MySQL)** with **Bootstrap 5**. No framework required — just PHP, MySQL, and a web server.

## Features

- **Role-based access** — Admin, Doctor, and Receptionist roles, each with a tailored dashboard and permissions
- **Patient management** — Registration, full medical dossier (appointments, admissions, records, bills in one place), search
- **Doctor management** — Profiles, departments, schedules, optional login account creation
- **Appointments (OPD)** — Booking with automatic double-booking prevention, status tracking, doctor-scoped views
- **Admissions (IPD)** — Ward/bed allocation, admit & discharge workflow that **automatically frees the bed and drafts a ward-stay bill** based on length of stay
- **Wards & beds** — Availability tracking (General / Semi-Private / Private / ICU)
- **Billing** — Multi-line invoices, discounts/tax, partial payments, printable invoice view
- **Pharmacy** — Medicine inventory with low-stock and expiry alerts
- **Medical records** — Diagnoses & prescriptions tied to patients and doctors
- **Security** — Prepared statements everywhere, bcrypt password hashing, CSRF tokens on all forms, session-based auth, output escaping

## Requirements

- PHP 7.4+ (tested on 8.3) with the `pdo_mysql` extension
- MySQL 5.7+ or MariaDB 10.4+
- Apache/Nginx (e.g. via XAMPP, WAMP, MAMP, or Laragon) — or PHP's built-in server for quick testing

## Installation

1. **Copy the files**
   Place the `hospital-management-system` folder inside your server's web root (e.g. `htdocs` for XAMPP, `www` for WAMP).

2. **Create the database**
   Open phpMyAdmin (or the `mysql` CLI) and import `database/hospital_management.sql`. It will:
   - Create the `hospital_management` database
   - Create all 11 tables with relationships
   - Seed demo departments, doctors, patients, wards, and login accounts

   CLI alternative:
   ```bash
   mysql -u root -p < database/hospital_management.sql
   ```

3. **Configure the database connection**
   Edit `config/database.php` if your MySQL credentials differ from the defaults (`root` / no password / `localhost`):
   ```php
   $DB_HOST = 'localhost';
   $DB_NAME = 'hospital_management';
   $DB_USER = 'root';
   $DB_PASS = '';
   ```

4. **Open it in your browser**
   Navigate to `http://localhost/hospital-management-system/login.php` (adjust the path to match where you placed the folder).

   To try it instantly without Apache, you can also use PHP's built-in server from inside the project folder:
   ```bash
   php -S localhost:8000
   ```
   then visit `http://localhost:8000/login.php`.

## Demo Logins

| Role | Username | Password |
|---|---|---|
| Admin | `admin` | `admin123` |
| Doctor | `dr.ayesha` | `doctor123` |
| Doctor | `dr.farid` | `doctor123` |
| Receptionist | `reception` | `reception123` |

**Change these passwords (or delete the demo accounts) before using this anywhere beyond your own testing.**

## How the roles differ

- **Admin** — full access to every module, including departments, doctor management, and can create doctor login accounts.
- **Receptionist** — manages patients, appointments, admissions, billing, and pharmacy. Cannot see clinical notes/prescriptions and cannot manage doctors or departments.
- **Doctor** — sees only their own appointments/schedule, can update appointment status and add consultation notes, can view all patients and pharmacy stock (read-only) for treatment purposes, and add medical records. No access to billing.

## Project Structure

```
hospital-management-system/
├── config/database.php        PDO connection (edit your DB credentials here)
├── includes/                  Shared header, sidebar, footer, auth & helper functions
├── assets/css, assets/js      Design system + small UI behaviors
├── database/hospital_management.sql
├── login.php / logout.php / index.php   (dashboard)
├── patients/, doctors/, departments/, appointments/,
│   wards/, admissions/, billing/, pharmacy/, medical_records/
│   Each module follows the same pattern: list.php, form.php (add+edit), delete.php
```

## Notable design decisions

- **Combined add/edit forms** — each module uses one `form.php` for both creating and editing, keyed off an optional `?id=` parameter, to keep the codebase easy to navigate.
- **Hard delete vs. deactivate** — deleting a doctor or patient also removes their historical appointments/records (the database enforces this via cascading foreign keys). For day-to-day use, prefer changing their **Status to Inactive** in the edit form to preserve history; delete confirmations warn about this.
- **Wards cannot be deleted once they have admission history** — this protects historical billing/admission records. Set a ward to "Maintenance" instead of deleting it.
- **Discharge auto-billing** — discharging a patient calculates `days stayed × ward daily rate` and creates a draft bill automatically, which staff can review and add further charges to before payment.

## Security notes

This is a strong self-hosted foundation, not a certified HIPAA/GDPR-compliant product out of the box. Before any real-world clinical use:
- Change all demo passwords immediately
- Serve the site over HTTPS
- Review your local healthcare data-protection regulations
- Consider adding audit logging and automated backups

## Extending it

Some natural next additions: lab test orders/results, SMS/email appointment reminders, multi-branch support, and an audit trail. The modular file structure (one folder per feature) makes each of these additive rather than a rewrite.
