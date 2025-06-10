<?php
// Start or resume the session to access user-specific data
session_start();

// Prevent browser from caching the page (ensures fresh data is always loaded)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Ensure the user is logged in as a doctor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'doctor') {
    header("Location: ../general/index.html");
    exit;
}

$readingId = $_POST['reading_id'] ?? null;
$commentText = trim($_POST['comment_text'] ?? '');

if (!$readingId || $commentText === '') {
    header("Location: glucose_history_doctor.php");
    exit;
}

// Connect to the SugarSense database and stop execution if connection fails
require_once '../general/config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$stmt = $conn->prepare("UPDATE glucose_readings SET doctor_note = ? WHERE id = ?");
$stmt->bind_param("si", $commentText, $readingId);
$stmt->execute();
$stmt->close();
$conn->close();

header("Location: glucose_history_doctor.php?saved=1");
exit;
