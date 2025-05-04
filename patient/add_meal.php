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
    echo "<script>window.location.href='../general/index.html';</script>";
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

// Fetching food components
$foodItems = [];
$result = $conn->query("SELECT id, name FROM food_items ORDER BY name ASC");
while ($row = $result->fetch_assoc()) {
    $foodItems[] = $row;
}

// Fetching measurement units
$quantityUnits = [];
$result = $conn->query("SELECT id, label FROM quantity_units ORDER BY id ASC");
while ($row = $result->fetch_assoc()) {
    $quantityUnits[] = $row;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>הוספת ארוחה</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light d-flex flex-column min-vh-100">

<!-- Header-->
<header>
  <nav class="navbar navbar-expand-lg navbar-light" style="background-color: #d3d3d3;" dir="rtl">
    <div class="container-fluid">

      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
          aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
      </button>
      
      <div class="collapse navbar-collapse justify-content-between" id="navbarNav">
        <ul class="navbar-nav me-auto d-flex flex-row-reverse gap-3">
          <li class="nav-item"><a class="nav-link text-black" href="menu_patient.php?type=gamification">🍬 נקודות מתוקות</a></li>
          <li class="nav-item"><a class="nav-link text-black" href="menu_patient.php?type=activity">🏃 פעילות גופנית</a></li>
          <li class="nav-item"><a class="nav-link text-black" href="menu_patient.php?type=glucose">🩸 ניטורי סוכר</a></li>
          <li class="nav-item"><a class="nav-link text-black" href="menu_patient.php?type=nutrition">🍽️ תזונה יומית</a></li>
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
  <h2 class="mb-4 text-center">➕ הוספת ארוחה</h2>

  <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
    <div class="alert alert-success text-center" role="alert">
      ✅ הארוחה נוספה בהצלחה!
    </div>
  <?php endif; ?>

  <?php if (isset($_GET['duplicate']) && $_GET['duplicate'] == 1): ?>
    <div class="alert alert-danger text-center" role="alert">
      ⚠️ כבר הוזנה ארוחה לתאריך ולסוג שנבחרו. ניתן לערוך את הארוחה הקיימת דרך יומן הארוחות.
    </div>
  <?php endif; ?>

  <form action="save_meal.php" method="POST" class="bg-white p-4 rounded shadow-sm mx-auto" style="max-width: 700px;">
    <div class="mb-3">
      <label class="form-label">📅 תאריך הארוחה:</label>
      <?php $today = date('Y-m-d'); ?>
      <input type="date" name="meal_date" class="form-control" required max="<?= $today ?>">
    </div>
    <div class="mb-3">
      <label class="form-label">⏰ שעת הארוחה:</label>
      <input type="time" name="meal_time" class="form-control" required>
    </div>

    <div class="mb-3">
      <label class="form-label">🍽️ סוג הארוחה:</label>
      <select name="meal_type" class="form-select" required>
        <option value="">בחרי</option>
        <option value="בוקר">ארוחת בוקר</option>
        <option value="צהריים">ארוחת צהריים</option>
        <option value="ערב">ארוחת ערב</option>
        <option value="ביניים">ארוחת ביניים</option>
      </select>
    </div>

    <div id="meal-items">
      <div class="meal-item row g-2 align-items-end mb-3">
        <div class="col-md-5">
          <label class="form-label">🥗 רכיב מזון:</label>
          <select name="food_item_id[]" class="form-select" required>
            <option value="">בחרי רכיב</option>
            <?php foreach ($foodItems as $item): ?>
              <option value="<?= $item['id']; ?>"><?= htmlspecialchars($item['name']); ?></option>
            <?php endforeach; ?>
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
            <?php foreach ($quantityUnits as $unit): ?>
              <option value="<?= $unit['id']; ?>"><?= htmlspecialchars($unit['label']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </div>

    <button type="button" class="btn btn-secondary mb-3" onclick="addMealItem()">➕ הוסיפי רכיב נוסף</button>
    <br>
    <button type="submit" class="btn btn-primary">📂 שמרי ארוחה</button>
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
  // Add a new empty meal item row to the form dynamically
  function addMealItem() {
    const container = document.getElementById('meal-items');
    const item = container.querySelector('.meal-item');
    const clone = item.cloneNode(true);
    clone.querySelectorAll('select, input').forEach(el => el.value = '');
    container.appendChild(clone);
  }

  // Automatically fade out and remove success alert after a few seconds
  const successAlert = document.querySelector('.alert-success');
  if (successAlert) {
    setTimeout(() => {
      successAlert.style.transition = "opacity 0.5s ease";
      successAlert.style.opacity = "0";
      setTimeout(() => successAlert.remove(), 500);
    }, 3000);
  }
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Force page reload if user navigates back using browser history to prevent showing outdated cached content -->
<script>
  window.addEventListener("pageshow", function (event) {
    if (event.persisted || (window.performance && window.performance.getEntriesByType("navigation")[0]?.type === "back_forward")) {
      window.location.reload();
    }
  });
</script>

</body>
</html>