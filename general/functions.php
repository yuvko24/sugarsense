<?php
function logAction($conn, $userId, $actionType, $description) {
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    $stmt = $conn->prepare("INSERT INTO logs (user_id, action_type, description, ip_address) VALUES (?, ?, ?, ?)");

    if ($userId === 0) {
        $userId = null;
    }

    $stmt->bind_param("isss", $userId, $actionType, $description, $ipAddress);
    $stmt->execute();
}

function getValidImage($path) {
    $cleanPath = trim($path, "/. ");
    $fullPath = dirname(__DIR__) . '/' . $cleanPath;
    return (file_exists($fullPath) && is_file($fullPath)) ? "../" . $cleanPath : "../Images/default.jpeg";
}
?>
