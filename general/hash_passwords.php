<?php
// Connect to the SugarSense database and stop execution if connection fails
require_once '../general/config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Update the user's password in the database with the hashed version
$sql = "SELECT id, password FROM users";
$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    $id = $row['id'];
    $plain_password = $row['password'];
    $hashed = password_hash($plain_password, PASSWORD_DEFAULT);

    $update_sql = "UPDATE users SET password = ? WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("si", $hashed, $id);
    $stmt->execute();
}

// Close connection and confirm completion
echo "סיסמאות עודכנו בהצלחה!";
$conn->close();
?>
