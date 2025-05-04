<?php
// // Connect to the SugarSense database and stop execution if connection fails
$host = "localhost";
$dbname = "maiav_sugarSense";
$username = "maiav_sugarSense";
$password = "MaiYuvalMichal!Sugar@";

$conn = new mysqli($host, $username, $password, $dbname);
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
