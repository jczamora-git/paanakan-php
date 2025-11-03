<?php
require 'connections/connections.php'; // Database connection

$selectedDate = isset($_GET['date']) ? $_GET['date'] : date("Y-m-d");
$pdo = connection();

// Fetch available slots for the given date
echo generateAvailableSlotsTable($pdo, $selectedDate);

function generateAvailableSlotsTable($pdo, $selectedDate) {
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
    $stmt->execute();
    $bookedSlots = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // Fetch as [hour => booked_count]

    // Generate table HTML
    $output = "<div class='custom-table-container'>";
    $output .= "<table class='custom-table'>";
    $output .= "<thead>
                    <tr>
                        <th>Time Slot</th>
                        <th>Status</th>
                    </tr>
                </thead>";
    $output .= "<tbody>";

    foreach ($timeSlots as $time) {
        $hour = date("G", strtotime($time)); // Convert time to 24-hour format
        $booked = $bookedSlots[$hour] ?? 0; // Get booked count or default to 0

        // Determine status
        $status = $isWeekend ? "Unavailable (Weekend)" : (($booked > 0) ? 'Reserved' : 'Available');

        // Determine row class
        $rowClass = $isWeekend ? 'disabled' : (($status == 'Reserved') ? 'disabled' : 'selectable-row');

        $output .= "<tr class='{$rowClass}' data-time='{$time}'>
                        <td>{$time}</td>
                        <td>{$status}</td>
                    </tr>";
    }

    $output .= "</tbody></table>";
    $output .= "</div>";

    return $output;
}

?>



        <style>
            /* Custom Table Container */
        .custom-table-container {
            width: 100%;
            overflow-x: auto;
            margin-top: 20px;
            display: flex;
            justify-content: center;
        }

        /* Custom Table Styling */
        .custom-table {
            width: 100%;
            max-width: auto;
            border-collapse: collapse;
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.2);
        }

        /* Table Header */
        .custom-table thead tr {
            background-color: #2E8B57;
            color: white;
            text-align: center;
            font-weight: bold;
        }

        /* Table Cells */
        .custom-table th, .custom-table td {
            padding: 12px;
            text-align: center;
        }

        /* Row Hover Effect */
        .selectable-row {
            cursor: pointer;
            transition: background-color 0.3s ease-in-out, color 0.3s;
        }

        .selectable-row:hover {
            background-color: #e0f5e9 !important; /* Light green hover */
        }

        /* Selected Row */
        .selectable-row.selected-row {
            background-color: #2E8B57 !important;
            color: white !important;
            font-weight: bold !important;
        }

        /* Disabled Row */
        .selectable-row.disabled {
            background-color: #f8f8f8 !important;
            color: #aaa !important;
            pointer-events: none;
            font-weight: bold;
        }

        </style>