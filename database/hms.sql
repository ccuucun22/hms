-- =========================================================
-- CarePoint HMS - Hospital Management System
-- Database Schema + Seed Data
-- =========================================================

CREATE DATABASE IF NOT EXISTS carepoint_hms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE carepoint_hms;

-- ---------------------------------------------------------
-- Users (login accounts - admin, doctor, receptionist, accountant)
-- ---------------------------------------------------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','doctor','receptionist','accountant') NOT NULL,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ---------------------------------------------------------
-- Doctors (linked to a user login account)
-- ---------------------------------------------------------
CREATE TABLE doctors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    specialization VARCHAR(100) NOT NULL,
    consultation_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
    is_surgeon TINYINT(1) DEFAULT 0,
    status ENUM('active','inactive') DEFAULT 'active',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ---------------------------------------------------------
-- Patients
-- ---------------------------------------------------------
CREATE TABLE patients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mrn VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    cnic VARCHAR(20),
    age INT,
    gender ENUM('Male','Female','Other'),
    contact VARCHAR(20),
    address VARCHAR(255),
    blood_group VARCHAR(5),
    emergency_contact VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ---------------------------------------------------------
-- Rooms (Room / Ward management)
-- ---------------------------------------------------------
CREATE TABLE rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_no VARCHAR(20) NOT NULL UNIQUE,
    room_type ENUM('General','Private','ICU','Emergency') NOT NULL,
    per_day_rate DECIMAL(10,2) NOT NULL,
    status ENUM('available','occupied','maintenance') DEFAULT 'available'
);

-- ---------------------------------------------------------
-- Appointments (Checkup booking -> generates checkup fee)
-- ---------------------------------------------------------
CREATE TABLE appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    checkup_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
    status ENUM('scheduled','completed','cancelled') DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
);

-- ---------------------------------------------------------
-- Admissions (Admit -> Room Allocation -> Discharge)
-- ---------------------------------------------------------
CREATE TABLE admissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    room_id INT NOT NULL,
    appointment_id INT NULL,
    operation_required TINYINT(1) DEFAULT 0,
    admission_date DATE NOT NULL,
    discharge_date DATE NULL,
    status ENUM('admitted','discharged') DEFAULT 'admitted',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL
);

-- ---------------------------------------------------------
-- Operations / Surgeries (separate charges from checkup fee)
-- ---------------------------------------------------------
CREATE TABLE operations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admission_id INT NOT NULL,
    doctor_id INT NOT NULL,
    operation_type VARCHAR(150) NOT NULL,
    charges DECIMAL(10,2) NOT NULL DEFAULT 0,
    operation_date DATE NOT NULL,
    notes VARCHAR(255),
    FOREIGN KEY (admission_id) REFERENCES admissions(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
);

-- ---------------------------------------------------------
-- Bills (Final combined invoice: checkup + operation + room)
-- ---------------------------------------------------------
CREATE TABLE bills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    appointment_id INT NULL,
    admission_id INT NULL,
    checkup_fee DECIMAL(10,2) DEFAULT 0,
    operation_charges DECIMAL(10,2) DEFAULT 0,
    room_charges DECIMAL(10,2) DEFAULT 0,
    total_amount DECIMAL(10,2) DEFAULT 0,
    payment_status ENUM('unpaid','paid','partial') DEFAULT 'unpaid',
    payment_method ENUM('cash','card','online','none') DEFAULT 'none',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL,
    FOREIGN KEY (admission_id) REFERENCES admissions(id) ON DELETE SET NULL
);

-- =========================================================
-- SEED DATA
-- =========================================================

-- Default password for ALL seed accounts below is:  password123
-- (bcrypt hash generated with PHP password_hash())
INSERT INTO users (name, email, password, role) VALUES
('System Admin', 'admin@carepoint.com', '$2y$10$J/7mwOmOTJ8xTzUjiqFeWuu2678wm8s2gYCTcny7Hlpw1E0RiZxCO', 'admin'),
('Ahmed Khan', 'ahmed.khan@carepoint.com', '$2y$10$J/7mwOmOTJ8xTzUjiqFeWuu2678wm8s2gYCTcny7Hlpw1E0RiZxCO', 'doctor'),
('Sara Malik', 'sara.malik@carepoint.com', '$2y$10$J/7mwOmOTJ8xTzUjiqFeWuu2678wm8s2gYCTcny7Hlpw1E0RiZxCO', 'doctor'),
('Ayesha Front Desk', 'reception@carepoint.com', '$2y$10$J/7mwOmOTJ8xTzUjiqFeWuu2678wm8s2gYCTcny7Hlpw1E0RiZxCO', 'receptionist'),
('Bilal Accounts', 'accounts@carepoint.com', '$2y$10$J/7mwOmOTJ8xTzUjiqFeWuu2678wm8s2gYCTcny7Hlpw1E0RiZxCO', 'accountant');

INSERT INTO doctors (user_id, specialization, consultation_fee, is_surgeon) VALUES
(2, 'General Physician', 1500.00, 0),
(3, 'General Surgeon', 2500.00, 1);

INSERT INTO rooms (room_no, room_type, per_day_rate, status) VALUES
('G-101', 'General', 2000.00, 'available'),
('G-102', 'General', 2000.00, 'available'),
('P-201', 'Private', 5000.00, 'available'),
('P-202', 'Private', 5000.00, 'available'),
('ICU-01', 'ICU', 12000.00, 'available'),
('ICU-02', 'ICU', 12000.00, 'available');

INSERT INTO patients (mrn, name, cnic, age, gender, contact, address, blood_group, emergency_contact) VALUES
('MRN-1001', 'Imran Sheikh', '35202-1234567-1', 42, 'Male', '0300-1234567', 'Bahawalnagar, Punjab', 'B+', '0301-7654321'),
('MRN-1002', 'Fatima Noor', '35202-7654321-2', 29, 'Female', '0312-9876543', 'Bahawalnagar, Punjab', 'O+', '0333-1112233');
