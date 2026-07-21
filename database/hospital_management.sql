-- =====================================================================
-- Hospital Management System - Database Schema
-- =====================================================================
-- Import this file in phpMyAdmin (or `mysql -u root -p < hospital_management.sql`)
-- It creates the database, all tables, and starter/demo data.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS hospital_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hospital_management;

-- ---------------------------------------------------------------------
-- 1. USERS (system login accounts: admin / doctor / receptionist)
-- ---------------------------------------------------------------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    role ENUM('admin','doctor','receptionist') NOT NULL DEFAULT 'receptionist',
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 2. DEPARTMENTS
-- ---------------------------------------------------------------------
CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 3. DOCTORS
-- ---------------------------------------------------------------------
CREATE TABLE doctors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    name VARCHAR(100) NOT NULL,
    department_id INT NULL,
    specialization VARCHAR(100),
    qualification VARCHAR(150),
    gender ENUM('Male','Female','Other') DEFAULT 'Other',
    phone VARCHAR(20),
    email VARCHAR(100),
    consultation_fee DECIMAL(10,2) DEFAULT 0,
    available_days VARCHAR(100),
    available_time VARCHAR(100),
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 4. PATIENTS
-- ---------------------------------------------------------------------
CREATE TABLE patients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_code VARCHAR(20) UNIQUE,
    name VARCHAR(100) NOT NULL,
    dob DATE NULL,
    gender ENUM('Male','Female','Other') DEFAULT 'Other',
    blood_group VARCHAR(5),
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    emergency_contact_name VARCHAR(100),
    emergency_contact_phone VARCHAR(20),
    medical_history TEXT,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 5. APPOINTMENTS (OPD)
-- ---------------------------------------------------------------------
CREATE TABLE appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    reason VARCHAR(255),
    status ENUM('scheduled','completed','cancelled') DEFAULT 'scheduled',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 6. WARDS / ROOMS / BEDS
-- ---------------------------------------------------------------------
CREATE TABLE wards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ward_name VARCHAR(100) NOT NULL,
    ward_type ENUM('General','Semi-Private','Private','ICU') DEFAULT 'General',
    room_no VARCHAR(20),
    bed_no VARCHAR(20),
    charge_per_day DECIMAL(10,2) DEFAULT 0,
    status ENUM('available','occupied','maintenance') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 7. ADMISSIONS (IPD - in-patient department)
-- ---------------------------------------------------------------------
CREATE TABLE admissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    ward_id INT NOT NULL,
    admission_date DATETIME NOT NULL,
    discharge_date DATETIME NULL,
    diagnosis TEXT,
    status ENUM('admitted','discharged') DEFAULT 'admitted',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
    FOREIGN KEY (ward_id) REFERENCES wards(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 8. MEDICINES (pharmacy inventory)
-- ---------------------------------------------------------------------
CREATE TABLE medicines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    category VARCHAR(100),
    manufacturer VARCHAR(150),
    quantity INT DEFAULT 0,
    unit_price DECIMAL(10,2) DEFAULT 0,
    expiry_date DATE NULL,
    reorder_level INT DEFAULT 10,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 9. MEDICAL RECORDS (visit notes / diagnosis / prescriptions)
-- ---------------------------------------------------------------------
CREATE TABLE medical_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_id INT NULL,
    diagnosis TEXT,
    prescription TEXT,
    notes TEXT,
    record_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 10. BILLS
-- ---------------------------------------------------------------------
CREATE TABLE bills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bill_no VARCHAR(20) UNIQUE,
    patient_id INT NOT NULL,
    bill_type ENUM('OPD','IPD','Pharmacy','Lab','Other') DEFAULT 'OPD',
    reference_id INT NULL,
    total_amount DECIMAL(10,2) DEFAULT 0,
    discount DECIMAL(10,2) DEFAULT 0,
    tax DECIMAL(10,2) DEFAULT 0,
    net_amount DECIMAL(10,2) DEFAULT 0,
    paid_amount DECIMAL(10,2) DEFAULT 0,
    payment_method ENUM('Cash','Card','Insurance','Online') DEFAULT 'Cash',
    status ENUM('paid','partial','unpaid') DEFAULT 'unpaid',
    bill_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 11. BILL ITEMS (line items per bill)
-- ---------------------------------------------------------------------
CREATE TABLE bill_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bill_id INT NOT NULL,
    description VARCHAR(255) NOT NULL,
    quantity INT DEFAULT 1,
    unit_price DECIMAL(10,2) DEFAULT 0,
    amount DECIMAL(10,2) DEFAULT 0,
    FOREIGN KEY (bill_id) REFERENCES bills(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- SEED / DEMO DATA
-- =====================================================================

-- Default admin login -> username: admin | password: admin123
INSERT INTO users (username, password, full_name, email, phone, role, status) VALUES
('admin', '$2y$10$98hdCm2V6LHUV3JMuUZ.1u.Pm.OV8PxZutzLT/7lPT0VUhfdQbXO.', 'System Administrator', 'admin@hospital.test', '0100000000', 'admin', 'active');

-- Departments
INSERT INTO departments (name, description) VALUES
('Cardiology', 'Diagnosis and treatment of heart and blood vessel conditions'),
('Neurology', 'Disorders of the brain, spinal cord and nervous system'),
('General Medicine', 'Primary care and treatment of common adult illnesses'),
('Orthopedics', 'Bones, joints, ligaments, tendons and muscles'),
('Pediatrics', 'Medical care of infants, children and adolescents');

-- Doctors (2 also get login accounts created below)
INSERT INTO doctors (user_id, name, department_id, specialization, qualification, gender, phone, email, consultation_fee, available_days, available_time, status) VALUES
(NULL, 'Dr. Ayesha Rahman', 1, 'Interventional Cardiologist', 'MBBS, MD (Cardiology)', 'Female', '01711000001', 'ayesha.rahman@hospital.test', 800.00, 'Mon,Tue,Wed,Thu', '09:00-15:00', 'active'),
(NULL, 'Dr. Farid Islam', 2, 'Neurologist', 'MBBS, FCPS (Neurology)', 'Male', '01711000002', 'farid.islam@hospital.test', 900.00, 'Sun,Mon,Wed', '10:00-16:00', 'active'),
(NULL, 'Dr. Nusrat Jahan', 3, 'General Physician', 'MBBS, MD (Internal Medicine)', 'Female', '01711000003', 'nusrat.jahan@hospital.test', 500.00, 'Sat,Sun,Mon,Tue,Wed', '08:00-14:00', 'active'),
(NULL, 'Dr. Kamal Hossain', 4, 'Orthopedic Surgeon', 'MBBS, MS (Orthopedics)', 'Male', '01711000004', 'kamal.hossain@hospital.test', 700.00, 'Tue,Thu,Sat', '14:00-20:00', 'active'),
(NULL, 'Dr. Shirin Akter', 5, 'Pediatrician', 'MBBS, DCH', 'Female', '01711000005', 'shirin.akter@hospital.test', 600.00, 'Sat,Sun,Mon,Tue,Wed,Thu', '09:00-13:00', 'active');

-- Sample doctor login accounts (username: dr.ayesha / dr.farid, password: doctor123)
INSERT INTO users (username, password, full_name, email, phone, role, status) VALUES
('dr.ayesha', '$2y$10$iaKNnhvUbaYjsyrAziAzqOtvKMwedGEtGKG48u0ydK0K2FmNOuCcS', 'Dr. Ayesha Rahman', 'ayesha.rahman@hospital.test', '01711000001', 'doctor', 'active'),
('dr.farid', '$2y$10$P843MYjz1ccAN3tXcqYdeO7qGV1p6xIv.F8UujN..AzfjAMJN/tty', 'Dr. Farid Islam', 'farid.islam@hospital.test', '01711000002', 'doctor', 'active');

UPDATE doctors SET user_id = (SELECT id FROM users WHERE username = 'dr.ayesha') WHERE name = 'Dr. Ayesha Rahman';
UPDATE doctors SET user_id = (SELECT id FROM users WHERE username = 'dr.farid') WHERE name = 'Dr. Farid Islam';

-- Sample receptionist login (username: reception, password: reception123)
INSERT INTO users (username, password, full_name, email, phone, role, status) VALUES
('reception', '$2y$10$hmWjm0oqR73acc9o6MqErerrzn/2wiW78EWz71Xx2ExX0gVx9Qi6u', 'Front Desk Officer', 'frontdesk@hospital.test', '01711000009', 'receptionist', 'active');

-- Wards / Beds
INSERT INTO wards (ward_name, ward_type, room_no, bed_no, charge_per_day, status) VALUES
('General Ward A', 'General', 'R-101', 'B-01', 800.00, 'available'),
('General Ward A', 'General', 'R-101', 'B-02', 800.00, 'available'),
('Semi-Private Ward', 'Semi-Private', 'R-201', 'B-01', 1500.00, 'available'),
('Private Ward', 'Private', 'R-301', 'B-01', 3000.00, 'available'),
('ICU', 'ICU', 'R-401', 'B-01', 6000.00, 'available');

-- Patients
INSERT INTO patients (patient_code, name, dob, gender, blood_group, phone, email, address, emergency_contact_name, emergency_contact_phone, medical_history, status) VALUES
('PAT0001', 'Mohammad Rafiq', '1985-03-14', 'Male', 'B+', '01911000001', 'rafiq@example.test', 'House 12, Road 5, Dhanmondi, Dhaka', 'Salma Rafiq', '01911000011', 'Hypertension, diagnosed 2019', 'active'),
('PAT0002', 'Nasrin Sultana', '1992-07-22', 'Female', 'O+', '01911000002', 'nasrin@example.test', 'House 45, Road 2, Mirpur, Dhaka', 'Karim Sultan', '01911000012', 'No significant history', 'active'),
('PAT0003', 'Abdul Karim', '1978-11-02', 'Male', 'A+', '01911000003', 'karim@example.test', 'Village Road, Savar, Dhaka', 'Fatima Karim', '01911000013', 'Type 2 diabetes', 'active'),
('PAT0004', 'Taslima Begum', '2001-01-30', 'Female', 'AB-', '01911000004', 'taslima@example.test', 'House 8, Uttara, Dhaka', 'Jamal Uddin', '01911000014', 'Asthma since childhood', 'active'),
('PAT0005', 'Rakibul Hasan', '2015-05-18', 'Male', 'B-', '01911000005', 'parent.rakib@example.test', 'House 21, Banasree, Dhaka', 'Hasan Ali', '01911000015', 'None on record', 'active');

-- Sample appointments (mix of past/completed and upcoming/scheduled)
INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, reason, status, notes) VALUES
(1, 1, CURDATE(), '10:00:00', 'Routine cardiac check-up', 'scheduled', NULL),
(2, 3, CURDATE(), '11:30:00', 'Fever and body ache', 'scheduled', NULL),
(3, 3, CURDATE() - INTERVAL 2 DAY, '09:00:00', 'Diabetes follow-up', 'completed', 'Blood sugar levels stable, continue current medication.'),
(4, 2, CURDATE() + INTERVAL 1 DAY, '14:00:00', 'Recurring headaches', 'scheduled', NULL),
(5, 5, CURDATE() - INTERVAL 5 DAY, '09:30:00', 'Regular child wellness visit', 'completed', 'Growth on track, vaccinations up to date.');

-- Sample medical record for the completed visits
INSERT INTO medical_records (patient_id, doctor_id, appointment_id, diagnosis, prescription, notes, record_date) VALUES
(3, 3, 3, 'Type 2 Diabetes Mellitus - controlled', 'Metformin 500mg twice daily after meals', 'Advised diet control and 30 minutes of daily walking.', NOW() - INTERVAL 2 DAY),
(5, 5, 5, 'Healthy - routine check', 'Multivitamin syrup 5ml once daily', 'Next wellness visit due in 6 months.', NOW() - INTERVAL 5 DAY);

-- Sample medicines
INSERT INTO medicines (name, category, manufacturer, quantity, unit_price, expiry_date, reorder_level) VALUES
('Paracetamol 500mg', 'Analgesic', 'Square Pharmaceuticals', 500, 1.50, CURDATE() + INTERVAL 18 MONTH, 100),
('Metformin 500mg', 'Antidiabetic', 'Beximco Pharma', 300, 2.20, CURDATE() + INTERVAL 12 MONTH, 50),
('Amoxicillin 250mg', 'Antibiotic', 'Incepta Pharmaceuticals', 8, 3.00, CURDATE() + INTERVAL 20 DAY, 40),
('Cetirizine 10mg', 'Antihistamine', 'ACI Limited', 200, 1.00, CURDATE() + INTERVAL 24 MONTH, 30),
('Omeprazole 20mg', 'Antacid', 'Square Pharmaceuticals', 5, 2.50, CURDATE() - INTERVAL 3 DAY, 25);

-- Sample bill for a completed OPD visit
INSERT INTO bills (bill_no, patient_id, bill_type, reference_id, total_amount, discount, tax, net_amount, paid_amount, payment_method, status, bill_date) VALUES
('BILL0001', 3, 'OPD', 3, 500.00, 0.00, 0.00, 500.00, 500.00, 'Cash', 'paid', NOW() - INTERVAL 2 DAY);

INSERT INTO bill_items (bill_id, description, quantity, unit_price, amount) VALUES
(1, 'Consultation - Dr. Nusrat Jahan (General Medicine)', 1, 500.00, 500.00);

-- =====================================================================
-- End of schema. Default logins created by this file:
--   Admin        -> admin      / admin123
--   Doctor       -> dr.ayesha  / doctor123
--   Doctor       -> dr.farid   / doctor123
--   Receptionist -> reception  / reception123
-- IMPORTANT: change these passwords after first login.
-- =====================================================================
