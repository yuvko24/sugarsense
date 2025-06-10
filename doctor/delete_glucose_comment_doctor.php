<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'doctor') {
    header("Location: ../general/index.html");
    exit;
}

$readingId = $_GET['reading_id'] ?? null;
if (!$readingId) {
    header("Location: glucose_history_doctor.php");
    exit;
}

// Connect to database
require_once '../general/config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Remove the doctor note
$stmt = $conn->prepare("UPDATE glucose_readings SET doctor_note = NULL WHERE id = ?");
$stmt->bind_param("i", $readingId);
$stmt->execute();
$stmt->close();
$conn->close();

// Redirect back
header("Location: glucose_history_doctor.php");
exit;
?>
