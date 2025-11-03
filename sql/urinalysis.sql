CREATE TABLE urinalysis (
    id INT PRIMARY KEY AUTO_INCREMENT,
    case_id VARCHAR(255) NOT NULL,
    transaction_id INT NOT NULL,
    -- Physical Examination
    color VARCHAR(50),
    transparency VARCHAR(50),
    ph DECIMAL(4,2),
    specific_gravity DECIMAL(4,3),
    -- Chemical Examination
    protein VARCHAR(50),
    glucose VARCHAR(50),
    leukocyte_esterase VARCHAR(50),
    nitrite VARCHAR(50),
    urobilinogen VARCHAR(50),
    blood VARCHAR(50),
    ketone VARCHAR(50),
    bilirubin VARCHAR(50),
    -- Microscopic Examination
    rbc VARCHAR(50),
    wbc VARCHAR(50),
    epithelial_cells VARCHAR(50),
    mucus_threads VARCHAR(50),
    bacteria VARCHAR(50),
    amorphous_urates VARCHAR(50),
    calcium_oxalate VARCHAR(50),
    triple_phosphate VARCHAR(50),
    -- Others and Processing
    others TEXT,
    remarks TEXT,
    medical_technologist VARCHAR(255),
    pathologist VARCHAR(255),
    report_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (case_id) REFERENCES patients(case_id),
    FOREIGN KEY (transaction_id) REFERENCES medical_transactions(transaction_id)
); 