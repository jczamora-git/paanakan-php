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
    `doctor_id` INT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id),
    FOREIGN KEY (case_id) REFERENCES patients(case_id),
    FOREIGN KEY (doctor_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Vital signs JSON structure example:
/*
{
    "blood_pressure": "120/80",
    "temperature": "36.8",
    "pulse_rate": "75",
    "respiratory_rate": "16",
    "weight": "65.5",
    "height": "165",
    "bmi": "24.1",
    "oxygen_saturation": "98"
}
*/

-- Add indexes for better query performance
CREATE INDEX idx_appointment_records_case_id ON appointment_records(case_id);
CREATE INDEX idx_appointment_records_appointment_type ON appointment_records(appointment_type);
CREATE INDEX idx_appointment_records_created_at ON appointment_records(created_at); 