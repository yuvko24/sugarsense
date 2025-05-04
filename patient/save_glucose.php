<?php
session_start();

// Prevent browser from caching the page (ensures fresh data is always loaded)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Ensure the user is logged in as a patient
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'patient') {
    header("Location: ../general/index.html");
    exit;
}

// Load common functions
require_once '../general/functions.php';

// Connect to the SugarSense database
$host = "localhost";
$dbname = "maiav_sugarSense";
$username = "maiav_sugarSense";
$password = "MaiYuvalMichal!Sugar@";
$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->autocommit(false);

// Check if glucose level is normal for the given time of day
function isGlucoseNormal($slot, $value) {
    $slot = mb_strtolower($slot);
    if (strpos($slot, 'בצום') !== false || strpos($slot, 'לפני') !== false) {
        return $value <= 95;
    } elseif (strpos($slot, 'שעתיים') !== false) {
        return $value <= 120;
    } elseif (strpos($slot, 'שעה') !== false) {
        return $value <= 140;
    }
    return null;
}

$patientId = $_SESSION['user_id'];
$readingDates = $_POST['reading_date'] ?? [];
$timeSlots = $_POST['time_slot'] ?? [];
$glucoseLevels = $_POST['glucose_level'] ?? [];

$inserted = 0;
$skipped = 0;

$checkStmt = $conn->prepare("SELECT id FROM glucose_readings WHERE patient_id = ? AND reading_date = ? AND time_slot = ?");
$insertStmt = $conn->prepare("INSERT INTO glucose_readings (patient_id, reading_date, time_slot, glucose_level, is_normal) VALUES (?, ?, ?, ?, ?)");

for ($i = 0; $i < count($readingDates); $i++) {
    $date = $readingDates[$i];
    $slot = $timeSlots[$i];
    $level = floatval($glucoseLevels[$i]);

    if (!$date || !$slot || !$level) {
        continue;
    }

    // Check if a measurement already exists for this date and time slot
    $checkStmt->bind_param("iss", $patientId, $date, $slot);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult && $checkResult->num_rows > 0) {
        $skipped++;
        continue;
    }

    // If it doesn't exist – insert it
    $isNormal = isGlucoseNormal($slot, $level);
    $insertStmt->bind_param("issdi", $patientId, $date, $slot, $level, $isNormal);
    $insertStmt->execute();
    $inserted++;
}

$checkStmt->close();
$insertStmt->close();

// Log the glucose readings addition
if ($inserted > 0) {
    logAction($conn, $patientId, 'add_glucose', 'Added ' . $inserted . ' new glucose readings.');
    $conn->commit();
}

// Close the connection
$conn->close();

// Redirect back with success message and duplicate status
header("Location: add_glucose.php?success=1&count=$inserted&skipped=$skipped");
exit;
?>
