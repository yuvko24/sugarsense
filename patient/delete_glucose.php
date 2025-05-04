<?php
// Start or resume the session to access user-specific data
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

if (!isset($_GET['id'])) {
    header("Location: glucose_history.php");
    exit;
}

$readingId = intval($_GET['id']);

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

// Deleting glucose measurement for the current user
$stmt = $conn->prepare("DELETE FROM glucose_readings WHERE id = ? AND patient_id = ?");
$stmt->bind_param("ii", $readingId, $_SESSION['user_id']);
$stmt->execute();
$stmt->close();

// Log the deletion
logAction($conn, $_SESSION['user_id'], 'delete_glucose', 'Deleted glucose reading ID ' . $readingId);
$conn->commit();

// Close the connection
$conn->close();

// Redirect to the glucose history page
header("Location: glucose_history.php?deleted=1");
exit;
?>
