<?php
// Start or resume the session to access user-specific data
session_start();

// Ensure the user is logged in as a patient
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'patient') {
    header("Location: ../general/index.html");
    exit;
}

// Connect to the SugarSense database and stop execution if connection fails
$host = "localhost";
$dbname = "maiav_sugarSense";
$username = "maiav_sugarSense";
$password = "MaiYuvalMichal!Sugar@";
$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Collect updated meal data from the form submission
$patientId = $_SESSION['user_id'];
$mealDate = $_POST['meal_date'];
$mealType = $_POST['meal_type'];
$newMealTime = $_POST['meal_time'];

$foodItemIds = $_POST['food_item_id'];
$quantityValues = $_POST['quantity_value'];
$unitIds = $_POST['unit_id'];

// Delete all existing meal items for this date and type before inserting updated ones
$deleteStmt = $conn->prepare("DELETE FROM meals WHERE patient_id = ? AND meal_date = ? AND meal_type = ?");
$deleteStmt->bind_param("iss", $patientId, $mealDate, $mealType);
$deleteStmt->execute();
$deleteStmt->close();

// Insert each new meal item into the database
$insertStmt = $conn->prepare("
    INSERT INTO meals (patient_id, meal_type, food_item_id, quantity_value, unit_id, meal_date, meal_time)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");

for ($i = 0; $i < count($foodItemIds); $i++) {
    $foodItemId = $foodItemIds[$i];
    $quantity = $quantityValues[$i];
    $unitId = $unitIds[$i];

    if ($foodItemId && $quantity && $unitId) {
        $insertStmt->bind_param("isidiss", $patientId, $mealType, $foodItemId, $quantity, $unitId, $mealDate, $newMealTime);
        $insertStmt->execute();
    }
}

$insertStmt->close();
$conn->close();

header("Location: meal_log.php?updated=1");
exit;
?>
