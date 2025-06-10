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

$mealId = $_POST['meal_id'] ?? null;
$commentText = trim($_POST['comment_text'] ?? '');
$patientId = $_POST['patient_id_number'] ?? null; 
$dietitianId = $_SESSION['user_id']; 

if (!$mealId || $commentText === '' || !$patientId) {
    header("Location: meal_log_dietitian.php");
    exit;
}

// Connect to the SugarSense database and stop execution if connection fails
require_once '../general/config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if a note already exists for the meal
$stmt = $conn->prepare("SELECT id FROM meal_comments WHERE meal_id = ?");
$stmt->bind_param("i", $mealId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // If exists – update the record
    $stmt->close();
    $updateStmt = $conn->prepare("UPDATE meal_comments SET comment_text = ? WHERE meal_id = ?");
    $updateStmt->bind_param("si", $commentText, $mealId);
    $updateStmt->execute();
    $updateStmt->close();
} else {
    // If not exists – add a new record with dietitian_id
    $stmt->close();
    $insertStmt = $conn->prepare("INSERT INTO meal_comments (meal_id, dietitian_id, comment_text) VALUES (?, ?, ?)");
    $insertStmt->bind_param("iis", $mealId, $dietitianId, $commentText);
    $insertStmt->execute();
    $insertStmt->close();
}

$conn->close();

// Return to meal diary with success message
header("Location: meal_log_dietitian.php?saved=1");
exit;
?>
