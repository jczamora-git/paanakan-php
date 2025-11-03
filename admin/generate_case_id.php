<?php
// Include database connection
require '../connections/connections.php';
$pdo = connection();

// Function to generate the next Case ID
function generateCaseId($pdo) {
    // Get the last case_id from the patients table
    $query = "SELECT case_id FROM patients ORDER BY case_id DESC LIMIT 1";
    $stmt = $pdo->query($query);
    $last_case_id = $stmt->fetchColumn();

    if ($last_case_id) {
        // Extract the numeric part and increment it
        $last_number = (int)substr($last_case_id, 1);  // Remove the 'C' and convert to int
        $new_number = $last_number + 1;
        // Format the new case_id with leading zeros (e.g., C009)
        $new_case_id = 'C' . str_pad($new_number, 3, '0', STR_PAD_LEFT);
    } else {
        // Default to C001 if no case_id exists
        $new_case_id = 'C001';
    }

    return $new_case_id;
}

// Generate and echo the new Case ID
echo generateCaseId($pdo);
?>
