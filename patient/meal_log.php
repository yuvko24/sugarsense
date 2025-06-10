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

$sortOrder = ($_GET['sort_order'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

// Connect to the SugarSense database and stop execution if connection fails
require_once '../general/config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the logged-in patient's user ID from the session
$patientId = $_SESSION['user_id'];

// Read date range from GET parameters if available
$startDate = $_GET['start_date'] ?? null;
$endDate = $_GET['end_date'] ?? null;

// Retrieving all patient's meals by date and time
$sql = "
    SELECT 
        m.id,
        m.meal_date,
        m.meal_time,
        m.meal_type,
        fi.name AS food_name,
        m.quantity_value,
        qu.label AS unit_label
    FROM meals m
    JOIN food_items fi ON m.food_item_id = fi.id
    JOIN quantity_units qu ON m.unit_id = qu.id
    WHERE m.patient_id = ?
";

// Filter by dates if selected
$params = [$patientId];
$types = "i";

if (!empty($startDate) && !empty($endDate)) {
    $sql .= " AND m.meal_date BETWEEN ? AND ?";
    $params[] = $startDate;
    $params[] = $endDate;
    $types .= "ss";
}

$sql .= " ORDER BY m.meal_date $sortOrder, 
          FIELD(m.meal_type, " . 
          ($sortOrder === 'ASC' ? 
            "'בוקר', 'צהריים', 'ביניים', 'ערב'" : 
            "'ערב', 'ביניים', 'צהריים', 'בוקר'") . 
          "), 
          m.meal_time " . 
          ($sortOrder === 'ASC' ? 'ASC' : 'DESC');

          
// Prepare and execute the query with the patient ID as a parameter
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$mealsByDateTime = [];

while ($row = $result->fetch_assoc()) {
    $key = $row['meal_date'] . ' ' . $row['meal_time'] . ' ' . $row['meal_type'];
    if (!isset($mealsByDateTime[$key])) {
        $mealsByDateTime[$key] = [
            'id' => $row['id'],
            'date' => $row['meal_date'],
            'time' => $row['meal_time'],
            'type' => $row['meal_type'],
            'items' => []
        ];
    }
    $mealsByDateTime[$key]['items'][] = $row;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>יומן ארוחות</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
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

<main class="container py-5 flex-grow-1">
  <h2 class="mb-4 text-center">📒 יומן הארוחות שלי</h2>

  <!-- Selecting date range and sort order -->
  <form method="GET" class="row gy-2 gx-3 align-items-center justify-content-center mb-5" id="filterForm">
    <div class="col-auto">
      <label for="start_date" class="form-label mb-0 small">מתאריך:</label>
      <input type="date" id="start_date" name="start_date" class="form-control" 
            value="<?= htmlspecialchars($startDate ?? '') ?>">
    </div>
    <div class="col-auto">
      <label for="end_date" class="form-label mb-0 small">עד תאריך:</label>
      <input type="date" id="end_date" name="end_date" class="form-control" 
            value="<?= htmlspecialchars($endDate ?? '') ?>">
    </div>
    <div class="col-auto">
      <label for="sort_order" class="form-label mb-0 small">סדר מיון:</label>
      <select name="sort_order" id="sort_order" class="form-select"
              onchange="document.getElementById('filterForm').submit();">
        <option value="desc" <?= ($_GET['sort_order'] ?? '') === 'desc' ? 'selected' : '' ?>>מהחדש לישן</option>
        <option value="asc" <?= ($_GET['sort_order'] ?? '') === 'asc' ? 'selected' : '' ?>>מהישן לחדש</option>
      </select>
    </div>
    <div class="col-auto">
      <label class="form-label mb-0 invisible">פעולה</label>
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">🔎 סינון</button>
        <a href="meal_log.php" class="btn btn-secondary">🔄 איפוס</a>
      </div>
    </div>
  </form>


  <?php if (isset($_GET['updated']) && $_GET['updated'] == 1): ?>
    <div class="alert alert-success text-center">✅ הארוחה עודכנה בהצלחה!</div>
  <?php endif; ?>
  <?php if (isset($_GET['deleted']) && $_GET['deleted'] == 1): ?>
    <div class="alert alert-success text-center">🗑️ הארוחה נמחקה בהצלחה.</div>
  <?php endif; ?>

  <?php if (empty($mealsByDateTime)): ?>
    <p class="text-center">לא הוזנו עדיין ארוחות בטווח הנבחר.</p>
  <?php else: ?>
    <?php foreach ($mealsByDateTime as $meal): ?>
      <?php $formattedDate = (new DateTime($meal['date']))->format('d/m/Y'); ?>
      <div class="card mb-4 shadow-sm">
        <div class="card-header text-white fw-bold py-3" style="background-color: #dc8e98;">
          <div class="row align-items-center">
            <div class="col-md-9 col-12">
              📅 <?= $formattedDate; ?> | 🍽️ <?= htmlspecialchars($meal['type']); ?> | 🕒 <?= htmlspecialchars(substr($meal['time'], 0, 5)); ?>
            </div>
            <div class="col-md-3 col-12 mt-2 mt-md-0 text-md-end text-center">
              <a href="edit_meal.php?id=<?= $meal['id'] ?>" class="btn btn-sm btn-outline-light ms-2">✏️ ערכי</a>
              <a href="delete_meal.php?id=<?= $meal['id'] ?>" class="btn btn-sm btn-outline-light"
                onclick="return confirm('האם את בטוחה שברצונך למחוק את הארוחה הזו?')">🗑️ מחקי</a>
            </div>
          </div>
        </div>
        <div class="card-body bg-white">
          <ul class="mb-0">
            <?php foreach ($meal['items'] as $item): ?>
              <li><?= htmlspecialchars($item['quantity_value']) . ' ' . htmlspecialchars($item['unit_label']) . ' ' . htmlspecialchars($item['food_name']); ?></li>
            <?php endforeach; ?>
          </ul>

          <?php
          // Display the dietitian's note if it exists
          $mealId = $meal['id'];
          $commentText = '';

          $conn = new mysqli($host, $username, $password, $dbname);
          if (!$conn->connect_error) {
              $stmt = $conn->prepare("SELECT comment_text FROM meal_comments WHERE meal_id = ?");
              $stmt->bind_param("i", $mealId);
              $stmt->execute();
              $result = $stmt->get_result();
              if ($row = $result->fetch_assoc()) {
                  $commentText = $row['comment_text'];
              }
              $stmt->close();
              $conn->close();
          }
          ?>

          <?php if (!empty($commentText)): ?>
            <div class="alert alert-info mt-3">
              🗨️ <strong>הערת דיאטנית:</strong><br>
              <?= nl2br(htmlspecialchars($commentText)); ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  window.addEventListener("pageshow", function (event) {
    if (event.persisted || (window.performance && window.performance.getEntriesByType("navigation")[0]?.type === "back_forward")) {
      window.location.reload();
    }
  });
</script>

</body>
</html>