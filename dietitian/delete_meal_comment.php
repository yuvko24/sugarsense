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

$mealId = $_GET['meal_id'] ?? null;
if (!$mealId) {
    header("Location: meal_log_dietitian.php");
    exit;
}

// Connect to the SugarSense database and stop execution if connection fails
require_once '../general/config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$mealStmt = $conn->prepare("SELECT meal_date, meal_type FROM meals WHERE id = ?");
$mealStmt->bind_param("i", $mealId);
$mealStmt->execute();
$mealResult = $mealStmt->get_result();
$mealInfo = $mealResult->fetch_assoc();
$mealStmt->close();

$mealDate = $mealInfo['meal_date'] ?? 'לא ידוע';
$mealType = $mealInfo['meal_type'] ?? 'לא ידוע';

// Delete the comment
$stmt = $conn->prepare("DELETE FROM meal_comments WHERE meal_id = ?");
$stmt->bind_param("i", $mealId);
$stmt->execute();
$stmt->close();

$dietitianId = $_SESSION['user_id'];
logAction($conn, $dietitianId, 'delete_meal_comment', "Deleted comment on meal ($mealType) at $mealDate");

$conn->close();

// Redirect back to meal log
header("Location: meal_log_dietitian.php");
exit;
?>