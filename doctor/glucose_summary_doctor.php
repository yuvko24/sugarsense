<?php
// Start or resume the session to access user-specific data
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'doctor') {
    header("Location: ../general/index.html");
    exit;
}

$patientId = $_SESSION['current_patient'] ?? null;
if (!$patientId) {
    header("Location: select_patient_doctor.php");
    exit;
}

require_once '../general/functions.php';
require_once '../general/config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$patientName = "לא ידוע";
$patientImage = "../Images/default.jpeg";
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

$timeSlotOrder = [
  "בצום (לפני ארוחת בוקר)" => 1,
  "שעתיים אחרי ארוחת בוקר" => 2,
  "לפני ארוחת צהריים" => 3,
  "שעתיים אחרי ארוחת צהריים" => 4,
  "לפני ארוחת ערב" => 5,
  "שעתיים אחרי ארוחת ערב" => 6,
  "לפני שינה" => 7
];

$statsBySlot = [];
$statsByDate = [];
$normalCount = 0;
$totalCount = 0;

$sqlSlot = "SELECT time_slot, ROUND(AVG(glucose_level), 1) AS avg_level FROM glucose_readings WHERE patient_id = ? GROUP BY time_slot";
$stmt = $conn->prepare($sqlSlot);
$stmt->bind_param("i", $patientId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $row['sort_order'] = $timeSlotOrder[$row['time_slot']] ?? 999;
    $statsBySlot[] = $row;
}
usort($statsBySlot, fn($a, $b) => $a['sort_order'] <=> $b['sort_order']);
$stmt->close();

$sqlDate = "SELECT reading_date, ROUND(AVG(glucose_level), 1) AS avg_level FROM glucose_readings WHERE patient_id = ? GROUP BY reading_date ORDER BY reading_date DESC LIMIT 10";
$stmt = $conn->prepare($sqlDate);
$stmt->bind_param("i", $patientId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $statsByDate[] = $row;
}
$stmt->close();

$sqlTotal = "SELECT is_normal, COUNT(*) as count FROM glucose_readings WHERE patient_id = ? GROUP BY is_normal";
$stmt = $conn->prepare($sqlTotal);
$stmt->bind_param("i", $patientId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    if ($row['is_normal'] == 1) $normalCount = $row['count'];
    $totalCount += $row['count'];
}
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>📊 סיכום ניטורים</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light d-flex flex-column min-vh-100">
<header>
<nav class="navbar navbar-expand-lg navbar-light position-relative" style="background-color: #d3d3d3;" dir="rtl">
  <div class="container-fluid">
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse justify-content-between" id="navbarNav">
      <ul class="navbar-nav me-auto d-flex flex-row-reverse gap-3">
        <li class="nav-item"><a class="nav-link text-black" href="select_patient_doctor.php">🔁 החלפת מטופלת</a></li>
      </ul>

      <div class="position-absolute top-50 start-50 translate-middle d-none d-lg-flex flex-column align-items-center">
        <img src="<?= htmlspecialchars($patientImage) ?>" alt="תמונת מטופלת" width="40" height="40" class="rounded-circle border border-secondary shadow-sm mb-1">
        <span class="fw-bold small"><?= htmlspecialchars($patientName) ?></span>
      </div>

      <div class="d-flex align-items-center gap-3">
        <a class="btn btn-outline-dark" href="../general/logout.php">🚪 התנתקות</a>
        <a class="navbar-brand me-3" href="select_patient_doctor.php">
          <img src="../Images/logo.jpg" alt="לוגו" width="50" height="50" class="rounded-circle border border-2 border-secondary shadow-sm">
        </a>
      </div>
    </div>
  </div>
</nav>
</header>

<main class="container py-4 flex-grow-1">
  <div class="d-flex justify-content-start mb-2">
    <a href="menu_doctor.php?type=glucose" class="btn btn-outline-secondary btn-sm">🔙 חזרה</a>
  </div>
  <h2 class="text-center mb-4">📊 סיכום ניטורי סוכר</h2>

  <div class="mb-4">
    <h5>1️⃣ ממוצע לפי קבוצת מדידה:</h5>
    <ul class="list-group">
      <?php foreach ($statsBySlot as $row): ?>
        <li class="list-group-item d-flex justify-content-between align-items-center">
          🕒 <?= htmlspecialchars($row['time_slot']) ?>
          <span class="badge bg-primary rounded-pill"><?= $row['avg_level'] ?> מ"ג/ד"ל</span>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>

  <div class="mb-4">
    <h5>2️⃣ ממוצע יומי (10 ימים אחרונים בהם נרשמו מדידות):</h5>
    <table class="table table-striped">
      <thead><tr><th>תאריך</th><th>ממוצע</th></tr></thead>
      <tbody>
        <?php foreach ($statsByDate as $row): ?>
          <tr>
            <td><?= date('d/m/Y', strtotime($row['reading_date'])) ?></td>
            <td><?= $row['avg_level'] ?> מ"ג/ד"ל</td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="alert alert-info text-center">
    ✅ <?= $normalCount ?> מתוך <?= $totalCount ?> מדידות היו בטווח התקין (<?= round(($normalCount / max(1, $totalCount)) * 100) ?>%)
  </div>
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
