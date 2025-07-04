<?php
// Start or resume the session to access user-specific data
session_start();

// Prevent browser from caching the page (ensures fresh data is always loaded)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Ensure the user is logged in as a dietitian
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'dietitian') {
    header("Location: ../general/index.html");
    exit;
}

$patientId = $_SESSION['current_patient'] ?? null;
if (!$patientId) {
    header("Location: select_patient.php");
    exit;
}

// Connect to the SugarSense database and stop execution if connection fails
require_once '../general/config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

require_once '../general/functions.php';

$patientName = "לא ידוע";
$patientImage = "Images/default.jpeg";

$stmt = $conn->prepare("SELECT full_name, profile_picture FROM users WHERE id = ?");
$stmt->bind_param("i", $patientId);
$stmt->execute();
$patientResult = $stmt->get_result();
if ($row = $patientResult->fetch_assoc()) {
  $patientName = $row['full_name'];
  if (!empty($row['profile_picture'])) {
      $patientImage = getValidImage($row['profile_picture']);
  }
}
$stmt->close();

$startDate = $_GET['start_date'] ?? null;
$endDate = $_GET['end_date'] ?? null;

$sortOrder = ($_GET['sort_order'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

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

if (!empty($startDate) && !empty($endDate)) {
    $sql .= " AND m.meal_date BETWEEN ? AND ?";
}

$sql .= " ORDER BY m.meal_date $sortOrder, 
          FIELD(m.meal_type, " . 
          ($sortOrder === 'ASC' ? 
            "'בוקר', 'צהריים', 'ביניים', 'ערב'" : 
            "'ערב', 'ביניים', 'צהריים', 'בוקר'") . 
          "), 
          m.meal_time " . 
          ($sortOrder === 'ASC' ? 'ASC' : 'DESC');

$stmt = $conn->prepare($sql);
if (!empty($startDate) && !empty($endDate)) {
    $stmt->bind_param("iss", $patientId, $startDate, $endDate);
} else {
    $stmt->bind_param("i", $patientId);
}
$stmt->execute();
$result = $stmt->get_result();

$meals = [];

while ($row = $result->fetch_assoc()) {
    $key = $row['meal_date'] . ' ' . $row['meal_time'] . ' ' . $row['meal_type'];
    if (!isset($meals[$key])) {
        $meals[$key] = [
            'id' => $row['id'],
            'date' => $row['meal_date'],
            'time' => $row['meal_time'],
            'type' => $row['meal_type'],
            'items' => []
        ];
    }
    $meals[$key]['items'][] = $row;
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>יומן ארוחות - דיאטנית</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light d-flex flex-column min-vh-100">

<header>
<nav class="navbar navbar-expand-lg navbar-light position-relative" style="background-color: #d3d3d3;" dir="rtl">
  <div class="container-fluid">
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
      aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-between" id="navbarNav">
      <ul class="navbar-nav me-auto d-flex flex-row-reverse gap-3">
        <li class="nav-item"><a class="nav-link text-black" href="select_patient.php">🔁 החלפת מטופלת</a></li>
      </ul>
      <div class="position-absolute top-50 start-50 translate-middle d-none d-lg-flex flex-column align-items-center">
        <img src="<?= htmlspecialchars($patientImage) ?>" alt="תמונת מטופלת" width="40" height="40"
             class="rounded-circle border border-secondary shadow-sm mb-1">
        <span class="fw-bold small"><?= htmlspecialchars($patientName) ?></span>
      </div>
      <div class="d-flex align-items-center gap-3">
        <a class="btn btn-outline-dark" href="../general/logout.php">🚪 התנתקות</a>
        <a class="navbar-brand me-3" href="select_patient.php">
          <img src="../Images/logo.jpg" alt="לוגו" width="50" height="50"
               class="rounded-circle border border-2 border-secondary shadow-sm">
        </a>
      </div>
    </div>
  </div>
</nav>
</header>

<main class="container py-5 flex-grow-1">
  <div class="position-relative text-center mb-4">
    <a href="menu_dietitian.php?type=nutrition" class="btn btn-outline-secondary btn-sm position-absolute top-0 start-0 mt-1 ms-2">🔙 חזרה</a>
    <h2 class="mb-4">📒 יומן הארוחות</h2>

    <form method="GET" class="row gy-2 gx-3 align-items-center justify-content-center mb-5" id="filterForm">
      <div class="col-auto">
        <label for="start_date" class="form-label mb-0 small">מתאריך:</label>
        <input type="date" id="start_date" name="start_date" class="form-control" value="<?= htmlspecialchars($startDate ?? '') ?>">
      </div>
      <div class="col-auto">
        <label for="end_date" class="form-label mb-0 small">עד תאריך:</label>
        <input type="date" id="end_date" name="end_date" class="form-control" value="<?= htmlspecialchars($endDate ?? '') ?>">
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
          <a href="meal_log_dietitian.php" class="btn btn-secondary">🔄 איפוס</a>
        </div>
      </div>
    </form>

  </div>

  <?php if (empty($meals)): ?>
    <p class="text-center">לא נמצאו ארוחות למטופלת זו.</p>
  <?php else: ?>
    <?php foreach ($meals as $meal): ?>
      <div class="card mb-4 shadow-sm">
        <div class="card-header text-white fw-bold" style="background-color: #dc8e98;">
          📅 <?= (new DateTime($meal['date']))->format('d/m/Y'); ?>
          | 🍽️ <?= htmlspecialchars($meal['type']); ?> 
          | 🕒 <?= substr($meal['time'], 0, 5); ?>
        </div>
        <div class="card-body">
          <ul class="mb-3">
            <?php foreach ($meal['items'] as $item): ?>
              <li><?= htmlspecialchars($item['quantity_value']) . ' ' . htmlspecialchars($item['unit_label']) . ' ' . htmlspecialchars($item['food_name']); ?></li>
            <?php endforeach; ?>
          </ul>

          <!-- View or add dietitian's note -->
          <?php
            $mealId = $meal['id'];
            $existingComment = '';
            $commentStmt = $conn->prepare("SELECT comment_text FROM meal_comments WHERE meal_id = ?");
            $commentStmt->bind_param("i", $mealId);
            $commentStmt->execute();
            $commentResult = $commentStmt->get_result();
            if ($row = $commentResult->fetch_assoc()) {
                $existingComment = $row['comment_text'];
            }
            $commentStmt->close();
          ?>

          <?php if (!empty($existingComment)): ?>
            <div class="alert alert-info mt-3">
              🗨️ <strong>ההערה שלי:</strong><br>
                  <?= nl2br(htmlspecialchars($existingComment)); ?>
                  <div class="mt-2 d-flex justify-content-end gap-2">
                    <a href="edit_meal_comment.php?meal_id=<?= $mealId ?>" class="btn btn-sm btn-outline-primary">✏️ ערכי</a>
                    <a href="delete_meal_comment.php?meal_id=<?= $mealId ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('האם את בטוחה שברצונך למחוק את ההערה?');">🗑️ מחקי</a>
                  </div>
            </div>
          <?php else: ?>
            <form action="save_meal_comment.php" method="POST">
              <div class="mb-3">
                <label class="form-label">🗨️ כתבי הערה עבור ארוחה זו:</label>
                <textarea name="comment_text" rows="2" class="form-control" placeholder="כתבי כאן את הערתך..."></textarea>
              </div>
              <input type="hidden" name="meal_id" value="<?= $mealId; ?>">
              <input type="hidden" name="patient_id_number" value="<?= $patientId; ?>">
              <button type="submit" class="btn btn-primary">💾 שמירת הערה</button>
            </form>
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

<script>
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
</body>
</html>