<?php
// Start or resume the session to access user-specific data
session_start();

// Prevent browser from caching the page (ensures fresh data is always loaded)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Ensure the user is logged in as a doctor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'doctor') {
    header("Location: ../general/index.html");
    exit;
}

$patientId = $_SESSION['current_patient'] ?? null;
if (!$patientId) {
    header("Location: select_patient_doctor.php");
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

require_once '../general/functions.php';

// Fetch patient details
$patientName = "לא ידוע";
$patientImage = "Images/default.jpeg";
$stmt = $conn->prepare("SELECT full_name, profile_picture FROM users WHERE id = ?");
$stmt->bind_param("i", $patientId);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $patientName = $row['full_name'];
    if (!empty($row['profile_picture'])) {
        $patientImage = getValidImage($row['profile_picture']);
    }
}
$stmt->close();

// Handle date filtering
$sortOrder = ($_GET['sort_order'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';

$query = "SELECT * FROM glucose_readings WHERE patient_id = ?";
$params = [$patientId];
$types = "i";

if (!empty($startDate)) {
    $query .= " AND reading_date >= ?";
    $params[] = $startDate;
    $types .= "s";
}
if (!empty($endDate)) {
    $query .= " AND reading_date <= ?";
    $params[] = $endDate;
    $types .= "s";
}

$query .= " ORDER BY reading_date $sortOrder,
  FIELD(time_slot, " . ($sortOrder === 'ASC' ? "
    'בצום (לפני ארוחת בוקר)',
    'שעתיים אחרי ארוחת בוקר',
    'לפני ארוחת צהריים',
    'שעתיים אחרי ארוחת צהריים',
    'לפני ארוחת ערב',
    'שעתיים אחרי ארוחת ערב',
    'לפני שינה'" : "
    'לפני שינה',
    'שעתיים אחרי ארוחת ערב',
    'לפני ארוחת ערב',
    'שעתיים אחרי ארוחת צהריים',
    'לפני ארוחת צהריים',
    'שעתיים אחרי ארוחת בוקר',
    'בצום (לפני ארוחת בוקר)'") . 
")";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();


$readings = [];
while ($row = $result->fetch_assoc()) {
    $readings[] = $row;
}
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ניטורי סוכר - רופאה</title>
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
        <li class="nav-item"><a class="nav-link text-black" href="select_patient_doctor.php">🔁 החלפת מטופלת</a></li>
      </ul>

      <div class="position-absolute top-50 start-50 translate-middle d-none d-lg-flex flex-column align-items-center">
        <img src="<?= htmlspecialchars($patientImage) ?>" alt="תמונת מטופלת" width="40" height="40"
             class="rounded-circle border border-secondary shadow-sm mb-1">
        <span class="fw-bold small"><?= htmlspecialchars($patientName) ?></span>
      </div>

      <div class="d-flex align-items-center gap-3">
        <a class="btn btn-outline-dark" href="../general/logout.php">🚪 התנתקות</a>
        <a class="navbar-brand me-3" href="select_patient_doctor.php">
          <img src="../Images/logo.jpg" alt="לוגו" width="50" height="50"
               class="rounded-circle border border-2 border-secondary shadow-sm">
        </a>
      </div>
    </div>
  </div>
</nav>
</header>

<!-- Main -->
<main class="container py-5 flex-grow-1">
  <div class="position-relative text-center mb-4">
    <a href="menu_doctor.php?type=glucose" class="btn btn-outline-secondary btn-sm position-absolute top-0 start-0 mt-1 ms-2">🔙 חזרה</a>
    <h2 class="mb-0">🩸 ניטורי סוכר</h2>
  </div>

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
      <select name="sort_order" id="sort_order" class="form-select" onchange="document.getElementById('filterForm').submit();">
        <option value="desc" <?= ($_GET['sort_order'] ?? '') === 'desc' ? 'selected' : '' ?>>מהחדש לישן</option>
        <option value="asc" <?= ($_GET['sort_order'] ?? '') === 'asc' ? 'selected' : '' ?>>מהישן לחדש</option>
      </select>
    </div>
    <div class="col-auto">
      <label class="form-label mb-0 invisible">פעולה</label>
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">🔎 סינון</button>
        <a href="glucose_history_doctor.php" class="btn btn-secondary">🔄 איפוס</a>
      </div>
    </div>
  </form>

  <?php if (isset($_GET['saved'])): ?>
    <div class="alert alert-success text-center">✅ ההערה נשמרה בהצלחה!</div>
  <?php endif; ?>

  <?php if (empty($readings)): ?>
    <p class="text-center">לא קיימים ניטורים להצגה.</p>
  <?php else: ?>
    <?php foreach ($readings as $reading): ?>
      <div class="card mb-4 shadow-sm">
        <div class="card-header text-white fw-bold" style="background-color: <?= $reading['is_normal'] ? '#6c757d' : '#dc3545' ?>;">
          📅 <?= (new DateTime($reading['reading_date']))->format('d/m/Y') ?> |
          🕒 <?= htmlspecialchars($reading['time_slot']) ?> |
          💉 <?= htmlspecialchars($reading['glucose_level']) ?> מ"ג/ד"ל |
          <?= $reading['is_normal'] ? '✅ תקין' : '⚠️ חריג' ?>
        </div>
        <div class="card-body">

          <?php if (!empty($reading['dietitian_note'])): ?>
            <div class="alert alert-info persistent mb-3">
              🗨️ <strong>הערת דיאטנית:</strong><br>
              <?= nl2br(htmlspecialchars($reading['dietitian_note'])); ?>
            </div>
          <?php endif; ?>

          <?php if (!empty($reading['doctor_note'])): ?>
            <div class="alert alert-warning persistent">
              💬 <strong>ההערה שלי:</strong><br>
              <?= nl2br(htmlspecialchars($reading['doctor_note'])); ?>
              <div class="mt-2 d-flex justify-content-end gap-2">
                <a href="edit_glucose_comment_doctor.php?reading_id=<?= $reading['id'] ?>" class="btn btn-sm btn-outline-primary">✏️ ערכי</a>
                <a href="delete_glucose_comment_doctor.php?reading_id=<?= $reading['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('האם את בטוחה שברצונך למחוק את ההערה?');">🗑️ מחקי</a>
              </div>
            </div>
          <?php else: ?>
            <form action="save_glucose_comment_doctor.php" method="POST">
              <div class="mb-3">
                <label class="form-label">💬 כתבי הערה:</label>
                <textarea name="comment_text" rows="2" class="form-control" placeholder="כתבי כאן את הערתך..."></textarea>
              </div>
              <input type="hidden" name="reading_id" value="<?= $reading['id'] ?>">
              <button type="submit" class="btn btn-primary">💾 שמירת הערה</button>
            </form>
          <?php endif; ?>

        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</main>

<footer class="text-white text-center py-4 mt-auto" style="background-color: #bcbcbc;">
  <div class="d-flex justify-content-center gap-3 mb-2">
    <a href="#" class="text-black"><i class="bi bi-facebook fs-5"></i></a>
    <a href="#" class="text-black"><i class="bi bi-instagram fs-5"></i></a>
    <a href="#" class="text-black"><i class="bi bi-twitter fs-5"></i></a>
  </div>
  <p class="mb-0 text-black">© 2025 SugarSense. כל הזכויות שמורות.</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
