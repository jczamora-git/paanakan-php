<?php

class HealthRecords {
    private $conn;

    // Constructor to initialize the PDO connection
    public function __construct($db) {
        $this->conn = $db;
    }

    // Method to insert a new health record entry
    public function createHealthRecord($case_id, $admission_id = null, $prenatal_record_id = null, $transaction_id = null, $appointment_id = null, $service_id = null) {
        // Prepare SQL query to insert a new health record entry
        $query = "INSERT INTO health_records (case_id, admission_id, prenatal_record_id, transaction_id, appointment_id, service_id, created_at)
                  VALUES (:case_id, :admission_id, :prenatal_record_id, :transaction_id, :appointment_id, :service_id, NOW())";
        
        $stmt = $this->conn->prepare($query);

        // Bind the parameters to prevent SQL injection
        $stmt->bindParam(":case_id", $case_id);
        $stmt->bindParam(":admission_id", $admission_id);
        $stmt->bindParam(":prenatal_record_id", $prenatal_record_id);
        $stmt->bindParam(":transaction_id", $transaction_id);
        $stmt->bindParam(":appointment_id", $appointment_id);
        $stmt->bindParam(":service_id", $service_id);

        // Execute the query
        try {
            if ($stmt->execute()) {
                return true; // Successfully inserted
            }
            return false; // Failed to insert
        } catch (PDOException $e) {
            // Log error if any
            error_log("Error inserting health record: " . $e->getMessage());
            return false;
        }
    }

    // Method to fetch health records
    public function getHealthRecords($limit = 10, $offset = 0) {
        // Query to retrieve health records
        $query = "SELECT hr.record_id, hr.case_id, hr.admission_id, hr.prenatal_record_id, hr.transaction_id, hr.appointment_id, hr.service_id, hr.created_at,
                          p.first_name, p.last_name
                  FROM health_records hr
                  JOIN patients p ON hr.case_id = p.case_id
                  ORDER BY hr.created_at DESC
                  LIMIT :limit OFFSET :offset";

        // Prepare and execute the query
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        // Return fetched records
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Method to fetch a specific health record by record_id
    public function getHealthRecordById($record_id) {
        // Query to fetch a specific health record
        $query = "SELECT hr.record_id, hr.case_id, hr.admission_id, hr.prenatal_record_id, hr.transaction_id, hr.appointment_id, hr.service_id, hr.created_at,
                          p.first_name, p.last_name
                  FROM health_records hr
                  JOIN patients p ON hr.case_id = p.case_id
                  WHERE hr.record_id = :record_id";

        // Prepare and execute the query
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':record_id', $record_id, PDO::PARAM_INT);
        $stmt->execute();

        // Return fetched record
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Method to update a health record
    public function updateHealthRecord($record_id, $admission_id = null, $prenatal_record_id = null, $transaction_id = null, $appointment_id = null, $service_id = null) {
        // Prepare SQL query to update an existing health record
        $query = "UPDATE health_records SET 
                    admission_id = :admission_id,
                    prenatal_record_id = :prenatal_record_id,
                    transaction_id = :transaction_id,
                    appointment_id = :appointment_id,
                    service_id = :service_id
                  WHERE record_id = :record_id";

        $stmt = $this->conn->prepare($query);

        // Bind the parameters to prevent SQL injection
        $stmt->bindParam(":record_id", $record_id, PDO::PARAM_INT);
        $stmt->bindParam(":admission_id", $admission_id);
        $stmt->bindParam(":prenatal_record_id", $prenatal_record_id);
        $stmt->bindParam(":transaction_id", $transaction_id);
        $stmt->bindParam(":appointment_id", $appointment_id);
        $stmt->bindParam(":service_id", $service_id);

        // Execute the query
        try {
            if ($stmt->execute()) {
                return true; // Successfully updated
            }
            return false; // Failed to update
        } catch (PDOException $e) {
            // Log error if any
            error_log("Error updating health record: " . $e->getMessage());
            return false;
        }
    }

    // Method to delete a health record
    public function deleteHealthRecord($record_id) {
        // Prepare SQL query to delete a health record
        $query = "DELETE FROM health_records WHERE record_id = :record_id";

        $stmt = $this->conn->prepare($query);

        // Bind the parameter to prevent SQL injection
        $stmt->bindParam(":record_id", $record_id, PDO::PARAM_INT);

        // Execute the query
        try {
            if ($stmt->execute()) {
                return true; // Successfully deleted
            }
            return false; // Failed to delete
        } catch (PDOException $e) {
            // Log error if any
            error_log("Error deleting health record: " . $e->getMessage());
            return false;
        }
    }
}

?>
