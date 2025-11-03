CREATE TABLE IF NOT EXISTS `staff` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `staff_id` VARCHAR(50) UNIQUE NOT NULL,
    `first_name` VARCHAR(100) NOT NULL,
    `middle_name` VARCHAR(100),
    `last_name` VARCHAR(100) NOT NULL,
    `role` ENUM('Doctor', 'Nurse', 'Medical Technologist', 'Midwife', 'Staff') NOT NULL,
    `specialization` VARCHAR(100),
    `license_number` VARCHAR(50),
    `contact_number` VARCHAR(20),
    `email` VARCHAR(100),
    `address` TEXT,
    `date_of_birth` DATE,
    `gender` ENUM('Male', 'Female', 'Other') NOT NULL,
    `date_hired` DATE NOT NULL,
    `status` ENUM('Active', 'Inactive', 'On Leave') DEFAULT 'Active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add indexes for better query performance
CREATE INDEX idx_staff_staff_id ON staff(staff_id);
CREATE INDEX idx_staff_role ON staff(role);
CREATE INDEX idx_staff_status ON staff(status);

-- Now let's modify the appointment_records table to reference staff instead of users
CREATE TABLE IF NOT EXISTS `appointment_records` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `appointment_id` INT NOT NULL,
    `case_id` VARCHAR(255) NOT NULL,
    `appointment_type` ENUM('Regular Checkup', 'Follow-up', 'Under Observation', 'Pre-Natal Checkup', 'Post-Natal Checkup', 'Medical Consultation', 'Vaccination') NOT NULL,
    `vital_signs` JSON,
    `chief_complaint` TEXT,
    `diagnosis` TEXT,
    `treatment_plan` TEXT,
    `prescription` TEXT,
    `lab_requests` TEXT,
    `notes` TEXT,
    `next_appointment` DATE,
    `staff_id` INT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id),
    FOREIGN KEY (case_id) REFERENCES patients(case_id),
    FOREIGN KEY (staff_id) REFERENCES staff(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add indexes for appointment_records
CREATE INDEX idx_appointment_records_case_id ON appointment_records(case_id);
CREATE INDEX idx_appointment_records_appointment_type ON appointment_records(appointment_type);
CREATE INDEX idx_appointment_records_created_at ON appointment_records(created_at); 