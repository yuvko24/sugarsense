<?php
// Start or resume the session
session_start();

// Load common functions
require_once '../general/functions.php';
require_once '../general/config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->autocommit(false);

// Log the logout action
if (isset($_SESSION['user_id'])) {
    logAction($conn, $_SESSION['user_id'], 'logout', 'User logged out.');
    $conn->commit();
}

// Destroy the session and redirect to login
session_unset();
session_destroy();
header("Location: index.html");
exit;
?>
