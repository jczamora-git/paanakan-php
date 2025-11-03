CREATE TABLE pap_smear (
    id INT PRIMARY KEY AUTO_INCREMENT,
    case_id VARCHAR(255) NOT NULL,
    transaction_id INT NOT NULL,
    specimen_type VARCHAR(255),
    interpretation_result TEXT,
    specimen_adequacy TEXT,
    remarks TEXT,
    processed_by VARCHAR(255),
    pathologist VARCHAR(255),
    report_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (case_id) REFERENCES patients(case_id),
    FOREIGN KEY (transaction_id) REFERENCES medical_transactions(transaction_id)
); 