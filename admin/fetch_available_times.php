<?php
require '../connections/connections.php';

$selectedDate = isset($_GET['date']) ? $_GET['date'] : date("Y-m-d");
$pdo = connection();

// Define time slots (7:00 AM - 4:00 PM, 1-hour intervals)
$timeSlots = [
    "07:00 AM", "08:00 AM", "09:00 AM", "10:00 AM",
    "11:00 AM", "12:00 PM", "01:00 PM", "02:00 PM",
    "03:00 PM", "04:00 PM"
];

// Check if the selected date is a weekend (Saturday or Sunday)
$dayOfWeek = date('N', strtotime($selectedDate)); // 6 = Saturday, 7 = Sunday
$isWeekend = ($dayOfWeek >= 6);

// Fetch booked slots from the database
$slotsQuery = "
    SELECT HOUR(scheduled_date) AS hour, COUNT(*) AS booked_count
    FROM Appointments
    WHERE DATE(scheduled_date) = :selectedDate
    GROUP BY HOUR(scheduled_date)";
$stmt = $pdo->prepare($slotsQuery);
$stmt->bindParam(':selectedDate', $selectedDate, PDO::PARAM_STR);

if ($stmt->execute()) {
    $bookedSlots = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // Fetch as [hour => booked_count]

    // Generate available slots
    $availableSlots = [];
    foreach ($timeSlots as $time) {
        $hour = date("G", strtotime($time)); // Convert time to 24-hour format
        $booked = $bookedSlots[$hour] ?? 0; // Get booked count or default to 0

        // Skip if the time slot is already booked or if it's a weekend
        if ($isWeekend || $booked > 0) {
            continue;
        }

        // Add available time slot to the list
        $availableSlots[] = $time;
    }

    // Return available slots as JSON
    echo json_encode($availableSlots);
} else {
    // If the query fails, return an error
    http_response_code(500);
    echo json_encode(["error" => "Database query failed"]);
}
?>
