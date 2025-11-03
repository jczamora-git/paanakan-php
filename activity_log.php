<?php

class ActivityLog {
    private $conn;

    // Constructor to initialize the PDO connection
    public function __construct($db) {
        $this->conn = $db;
    }

    // Method to log user activity
    public function logActivity($user_id, $action_desc) {
        // Prepare SQL query to insert a new activity log entry
        $query = "INSERT INTO activity_log (user_id, action, timestamp) 
                  VALUES (:user_id, :action_desc, NOW())";
        $stmt = $this->conn->prepare($query);

        // Bind the parameters to prevent SQL injection
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":action_desc", $action_desc);

        // Execute the query
        try {
            if ($stmt->execute()) {
                return true; // Successfully logged
            }
            return false; // Failed to log
        } catch (PDOException $e) {
            // Log error if any
            error_log("Error logging activity: " . $e->getMessage());
            return false;
        }
    }

    // Method to fetch activity logs for reporting
    public function getActivityLogs() {
        // Query to retrieve the logs
        $query = "SELECT al.log_id, u.first_name, u.last_name, al.action, al.timestamp
                  FROM activity_log al
                  JOIN users u ON al.user_id = u.user_id
                  ORDER BY al.timestamp DESC";

        // Prepare and execute the query
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        // Return fetched records
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
