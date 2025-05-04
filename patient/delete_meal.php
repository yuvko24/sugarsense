<?php
// Start or resume the session to access user-specific data
session_start();

// Ensure the user is logged in as a patient
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'patient') {
    header("Location: ../general/index.html");
    exit;
}

// Load common functions
require_once '../general/functions.php';

if (!isset($_GET['id'])) {
    header("Location: meal_log.php");
    exit;
}

$mealId = intval($_GET['id']);

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

$patientId = $_SESSION['user_id'];

// Retrieving meal details for deletion
$stmt = $conn->prepare("SELECT meal_date, meal_type FROM meals WHERE id = ? AND patient_id = ?");
$stmt->bind_param("ii", $mealId, $patientId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $conn->close();
    header("Location: meal_log.php");
    exit;
}

$meal = $result->fetch_assoc();
$mealDate = $meal['meal_date'];
$mealType = $meal['meal_type'];
$stmt->close();

// Deleting all meal rows by date, type, and patient
$deleteStmt = $conn->prepare("DELETE FROM meals WHERE patient_id = ? AND meal_date = ? AND meal_type = ?");
$deleteStmt->bind_param("iss", $patientId, $mealDate, $mealType);
$deleteStmt->execute();
$deleteStmt->close();

// Log the meal deletion
logAction($conn, $patientId, 'delete_meal', 'Deleted meal on ' . $mealDate . ' (' . $mealType . ')');
$conn->commit();

// Close the connection
$conn->close();

// Redirect to the meal log page
header("Location: meal_log.php?deleted=1");
exit;
?>
