<?php
// Start or resume the session to access user-specific data
session_start();

// Connect to the SugarSense database and stop execution if connection fails
$host = "localhost";
$dbname = "maiav_sugarSense";
$username = "maiav_sugarSense";
$password = "MaiYuvalMichal!Sugar@";

require_once '../general/functions.php';

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get login credentials from the submitted form
$id_number = $_POST['id_number'];
$user_password = $_POST['password'];
$user_type_input = $_POST['user_type'];

// Look up the user in the database by their ID number
$sql = "SELECT * FROM users WHERE id_number = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $id_number);
$stmt->execute();
$result = $stmt->get_result();

// If a user with the provided ID number was found, fetch their data
if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    if (password_verify($user_password, $user['password'])) {
        if ($user['user_type'] !== $user_type_input) {
            logAction($conn, $user['id'], 'login_failed', 'Login failed: user type mismatch.');
            setcookie("login_error", "סוג המשתמש אינו תואם לפרטי ההתחברות.", time() + 5, "/");
            header("Location: index.html");
            exit;
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_type'] = $user['user_type'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['id_number'] = $user['id_number'];

        logAction($conn, $user['id'], 'login_success', 'User logged in successfully.');
        
        // Redirect the user to the appropriate dashboard based on their role
        if ($user_type_input === 'patient') {
            header("Location: ../patient/dashboard_patient.php");
        } elseif ($user_type_input === 'dietitian') {
            header("Location: ../dietitian/select_patient.php");
        } elseif ($user_type_input === 'doctor') {
            header("Location: ../doctor/select_patient_doctor.php");
        }
        exit;
    } else {
        logAction($conn, $user['id'], 'login_failed', 'Login failed: incorrect password for ID ' . $id_number);
        setcookie("login_error", "סיסמה שגויה. אנא נסה.י שוב.", time() + 5, "/");
        header("Location: index.html");
        exit;
    }
} else {
    logAction($conn, 0, 'login_failed', 'Login failed: ID not found ' . $id_number);
    setcookie("login_error", "משתמש.ת לא נמצא.ה במערכת.", time() + 5, "/");
    header("Location: index.html");
    exit;
}

$conn->close();
?>
