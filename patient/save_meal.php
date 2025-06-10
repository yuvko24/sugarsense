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

// Connect to the SugarSense database
require_once '../general/config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->autocommit(false);

// Retrieve submitted meal data
$patientId = $_SESSION['user_id'];
$mealType = $_POST['meal_type'];
$mealDate = $_POST['meal_date'];
$mealTime = $_POST['meal_time'];
$foodItemIds = $_POST['food_item_id'];
$quantityValues = $_POST['quantity_value'];
$unitIds = $_POST['unit_id'];

// Check if a meal already exists for the same date and type
$checkStmt = $conn->prepare("SELECT COUNT(*) FROM meals WHERE patient_id = ? AND meal_type = ? AND meal_date = ?");
$checkStmt->bind_param("iss", $patientId, $mealType, $mealDate);
$checkStmt->execute();
$checkStmt->bind_result($existing);
$checkStmt->fetch();
$checkStmt->close();

if ($existing > 0) {
    $conn->close();
    header("Location: add_meal.php?duplicate=1");
    exit;
}

// Inserting all meal components
$stmt = $conn->prepare("INSERT INTO meals (patient_id, meal_type, food_item_id, quantity_value, unit_id, meal_date, meal_time)
                        VALUES (?, ?, ?, ?, ?, ?, ?)");

for ($i = 0; $i < count($foodItemIds); $i++) {
    $foodItemId = $foodItemIds[$i];
    $quantityValue = $quantityValues[$i];
    $unitId = $unitIds[$i];

    if ($foodItemId && $quantityValue && $unitId) {
        $stmt->bind_param("isidiss", $patientId, $mealType, $foodItemId, $quantityValue, $unitId, $mealDate, $mealTime);
        $stmt->execute();
    }
}

$stmt->close();

// Log the meal addition
logAction($conn, $patientId, 'add_meal', 'Added meal on ' . $mealDate . ' (' . $mealType . ')');
$conn->commit();

// Close the connection
$conn->close();

// Redirect to menu_patient.php after successful addition
header("Location: menu_patient.php?type=nutrition&success=1");
exit;
?>
