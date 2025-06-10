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

// Connect to the SugarSense database and stop execution if connection fails
require_once '../general/config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the meal ID from the URL and ensure it exists
$mealId = $_GET['id'] ?? null;
$patientId = $_SESSION['user_id'];

if (!$mealId) {
    echo "⛔ מזהה ארוחה חסר.";
    exit;
}

// Retrieving meal date, time, and type by ID
$stmt = $conn->prepare("SELECT meal_date, meal_time, meal_type FROM meals WHERE id = ? AND patient_id = ?");
$stmt->bind_param("ii", $mealId, $patientId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo "😕 ארוחה לא נמצאה.";
    exit;
}
$mealInfo = $result->fetch_assoc();
$stmt->close();

$mealDate = $mealInfo['meal_date'];
$mealType = $mealInfo['meal_type'];

// Retrieving all meal components (grouped by date, type, and patient)
$stmt = $conn->prepare("
    SELECT m.id, m.meal_time, m.food_item_id, m.quantity_value, m.unit_id,
           fi.name AS food_name, qu.label AS unit_label
    FROM meals m
    JOIN food_items fi ON m.food_item_id = fi.id
    JOIN quantity_units qu ON m.unit_id = qu.id
    WHERE m.meal_date = ? AND m.meal_type = ? AND m.patient_id = ?
");
$stmt->bind_param("ssi", $mealDate, $mealType, $patientId); 
$stmt->execute();
$result = $stmt->get_result();

// Store all meal items in an array for display and extract the meal time
$mealItems = [];
$mealTime = '';
while ($row = $result->fetch_assoc()) {
    $mealItems[] = $row;
    $mealTime = $row['meal_time']; 
}
$stmt->close();

// Retrieve all available food items to populate the dropdown list
$foodItems = [];
$result = $conn->query("SELECT id, name FROM food_items ORDER BY name ASC");
while ($row = $result->fetch_assoc()) {
    $foodItems[] = $row;
}

// Retrieve all quantity units to populate the unit dropdowns
$units = [];
$result = $conn->query("SELECT id, label FROM quantity_units ORDER BY id ASC");
while ($row = $result->fetch_assoc()) {
    $units[] = $row;
}

$hebrewDate = (new DateTime($mealDate))->format('d.m.Y');

$conn->close();
?>

<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>עריכת ארוחה</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex flex-column min-vh-100">

<!-- Header -->
<header>
  <nav class="navbar navbar-expand-lg navbar-light" style="background-color: #d3d3d3;" dir="rtl">
    <div class="container-fluid">

      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
          aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse justify-content-between" id="navbarNav">
        <ul class="navbar-nav me-auto d-flex flex-row-reverse gap-3">
          <li class="nav-item"><a class="nav-link text-black" href="menu_patient.php?type=nutrition">🍽️ חזרה לתזונה יומית</a></li>
          <li class="nav-item"><a class="nav-link text-black" href="dashboard_patient.php">🏠 דף בית</a></li>
        </ul>
        <div class="d-flex align-items-center gap-3">
          <a class="btn btn-outline-dark" href="../general/logout.php">🚪 התנתקות</a>
          <a class="navbar-brand me-3" href="dashboard_patient.php">
            <img src="../Images/logo.jpg" alt="לוגו" width="50" height="50" class="rounded-circle border border-2 border-secondary shadow-sm">
          </a>
        </div>
      </div>
    </div>
  </nav>
</header>

<main class="container py-5">
  <h2 class="mb-4 text-center">✏️ עריכת ארוחה</h2>

  <form action="save_edited_meal.php" method="POST" class="bg-white p-4 rounded shadow-sm mx-auto" style="max-width: 700px;">
    <input type="hidden" name="meal_date" value="<?= htmlspecialchars($mealDate); ?>">
    <input type="hidden" name="meal_type" value="<?= htmlspecialchars($mealType); ?>">

    <div class="mb-3">
      <label class="form-label">📅 תאריך:</label>
      <input type="text" class="form-control" readonly value="<?= htmlspecialchars($hebrewDate); ?>">
    </div>

    <div class="mb-3">
      <label class="form-label">🍽️ סוג הארוחה:</label>
      <input type="text" class="form-control" readonly value="<?= htmlspecialchars($mealType); ?>">
    </div>

    <div class="mb-3">
      <label class="form-label">⏰ שעת הארוחה:</label>
      <input type="time" name="meal_time" class="form-control" required value="<?= htmlspecialchars($mealTime); ?>">
    </div>

    <div id="meal-items">
      <?php foreach ($mealItems as $index => $item): ?>
        <div class="meal-item row g-2 align-items-end mb-3">
          <div class="col-md-5">
            <label class="form-label">🥗 רכיב מזון:</label>
            <select name="food_item_id[]" class="form-select" required>
              <option value="">בחרי רכיב</option>
              <?php foreach ($foodItems as $food): ?>
                <option value="<?= $food['id'] ?>" <?= $food['id'] == $item['food_item_id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($food['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-3">
            <label class="form-label">⚖️ כמות:</label>
            <input type="number" name="quantity_value[]" step="0.25" min="0" class="form-control" required value="<?= htmlspecialchars($item['quantity_value']); ?>">
          </div>

          <div class="col-md-3">
            <label class="form-label">🔢 יחידה:</label>
            <select name="unit_id[]" class="form-select" required>
              <option value="">בחרי יחידה</option>
              <?php foreach ($units as $unit): ?>
                <option value="<?= $unit['id'] ?>" <?= $unit['id'] == $item['unit_id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($unit['label']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-1 text-center">
            <button type="button" class="btn btn-danger btn-sm mt-4" onclick="removeMealItem(this)">🗑️</button>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <button type="button" class="btn btn-secondary mb-3" onclick="addMealItem()">➕ הוסיפי רכיב נוסף</button>
    <br>
    <button type="submit" class="btn btn-primary">💾 שמרי שינויים</button>
    <a href="meal_log.php" class="btn btn-secondary">🔙 חזרה</a>
  </form>
</main>

<!-- Footer -->
<footer class="text-white text-center py-4 mt-auto" style="background-color: #bcbcbc;">
  <div class="d-flex justify-content-center gap-3 mb-2">
    <a href="#" class="text-black"><i class="bi bi-facebook fs-5"></i></a>
    <a href="#" class="text-black"><i class="bi bi-instagram fs-5"></i></a>
    <a href="#" class="text-black"><i class="bi bi-twitter fs-5"></i></a>
  </div>
  <p class="mb-0 text-black">© 2025 SugarSense. כל הזכויות שמורות.</p>
</footer>

<script>
  // Load food items and units into JavaScript variables for dynamic form creation
  const foodItems = <?= json_encode($foodItems) ?>;
  const units = <?= json_encode($units) ?>;

  // Remove a meal item row when the delete button is clicked
  function removeMealItem(btn) {
    const item = btn.closest('.meal-item');
    item.remove();
  }

  // Add a new meal item row dynamically with food and unit options
  function addMealItem() {
    const container = document.getElementById('meal-items');
    const div = document.createElement('div');
    div.className = 'meal-item row g-2 align-items-end mb-3';
    div.innerHTML = `
      <div class="col-md-5">
        <label class="form-label">🥗 רכיב מזון:</label>
        <select name="food_item_id[]" class="form-select" required>
          <option value="">בחרי רכיב</option>
          ${foodItems.map(item => `<option value="${item.id}">${item.name}</option>`).join('')}
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">⚖️ כמות:</label>
        <input type="number" name="quantity_value[]" step="0.25" min="0" class="form-control" required>
      </div>
      <div class="col-md-3">
        <label class="form-label">🔢 יחידה:</label>
        <select name="unit_id[]" class="form-select" required>
          <option value="">בחרי יחידה</option>
          ${units.map(unit => `<option value="${unit.id}">${unit.label}</option>`).join('')}
        </select>
      </div>
      <div class="col-md-1 text-center">
        <button type="button" class="btn btn-danger btn-sm mt-4" onclick="removeMealItem(this)">🗑️</button>
      </div>
    `;
    container.appendChild(div);
  }
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
