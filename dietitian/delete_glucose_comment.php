<?php
// Start or resume the session to access user-specific data
session_start();

// Prevent browser from caching the page (ensures fresh data is always loaded)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Ensure the user is logged in as a dietitian
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'dietitian') {
    header("Location: ../general/index.html");
    exit;
}

require_once '../general/functions.php';

$readingId = $_GET['reading_id'] ?? null;
if (!$readingId) {
    header("Location: glucose_history_dietitian.php");
    exit;
}

// Connect to the SugarSense database and stop execution if connection fails
require_once '../general/config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$stmt = $conn->prepare("SELECT reading_date, time_slot FROM glucose_readings WHERE id = ?");
$stmt->bind_param("i", $readingId);
$stmt->execute();
$result = $stmt->get_result();
$reading = $result->fetch_assoc();
$stmt->close();

$readingDate = $reading['reading_date'] ?? 'לא ידוע';
$timeSlot = $reading['time_slot'] ?? 'לא ידוע';

// Remove the dietitian note
$updateStmt = $conn->prepare("UPDATE glucose_readings SET dietitian_note = NULL WHERE id = ?");
$updateStmt->bind_param("i", $readingId);
$updateStmt->execute();
$updateStmt->close();

$dietitianId = $_SESSION['user_id'];
logAction($conn, $dietitianId, 'delete_glucose_comment', "Deleted comment on glucose reading ($timeSlot) at $readingDate");

$conn->close();

// Redirect back
header("Location: glucose_history_dietitian.php");
exit;
?>