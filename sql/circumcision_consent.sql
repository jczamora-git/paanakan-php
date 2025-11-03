CREATE TABLE circumcision_consent (
    id INT PRIMARY KEY AUTO_INCREMENT,
    case_id VARCHAR(255) NOT NULL,
    transaction_id INT NOT NULL,
    -- Child Information
    child_name VARCHAR(255) NOT NULL,
    child_age INT NOT NULL,
    child_birthdate DATE NOT NULL,
    -- Parent/Guardian Information
    parent_name VARCHAR(255) NOT NULL,
    parent_relationship VARCHAR(50) NOT NULL,
    parent_contact VARCHAR(50),
    parent_address TEXT,
    -- Consent Details
    consent_date DATE NOT NULL,
    witness_name VARCHAR(255),
    doctor_name VARCHAR(255) NOT NULL,
    scheduled_date DATE,
    -- Medical Information
    medical_conditions TEXT,
    allergies TEXT,
    medications TEXT,
    -- Acknowledgments (Boolean fields for different consent aspects)
    acknowledge_procedure BOOLEAN DEFAULT FALSE,
    acknowledge_risks BOOLEAN DEFAULT FALSE,
    acknowledge_aftercare BOOLEAN DEFAULT FALSE,
    acknowledge_questions BOOLEAN DEFAULT FALSE,
    -- Additional Information
    special_instructions TEXT,
    remarks TEXT,
    -- Signatures (Could store image paths or digital signature data)
    parent_signature TEXT,
    doctor_signature TEXT,
    witness_signature TEXT,
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    -- Foreign Keys
    FOREIGN KEY (case_id) REFERENCES patients(case_id),
    FOREIGN KEY (transaction_id) REFERENCES medical_transactions(transaction_id)
); 