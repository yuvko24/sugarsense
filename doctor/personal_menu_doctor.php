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
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
  $patientName = $row['full_name'];
  if (!empty($row['profile_picture'])) {
      $patientImage = getValidImage($row['profile_picture']);
  }
}
$stmt->close();

$mealTypes = ['בוקר', 'צהריים', 'ערב', 'ביניים'];
$menu = [];
foreach ($mealTypes as $type) {
    $stmt = $conn->prepare("SELECT option_text FROM personal_meal_plans WHERE patient_id = ? AND meal_type = ?");
    $stmt->bind_param("is", $patientId, $type);
    $stmt->execute();
    $result = $stmt->get_result();
    $menu[$type] = [];
    while ($row = $result->fetch_assoc()) {
        $menu[$type][] = $row['option_text'];
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>תפריט אישי</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light d-flex flex-column min-vh-100">

<!-- Header -->
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

<main class="container py-5 flex-grow-1">
  <div class="position-relative text-center mb-4">
    <a href="menu_doctor.php?type=nutrition" class="btn btn-outline-secondary btn-sm position-absolute top-0 start-0 mt-1 ms-2">🔙 חזרה</a>
    <h2 class="mb-0">📋 תפריט אישי</h2>
  </div>

  <?php foreach ($menu as $mealType => $options): ?>
    <div class="card mb-4 shadow-sm">
      <div class="card-header text-white fw-bold" style="background-color: #dc8e98;">
        🍽️ <?= htmlspecialchars($mealType); ?>
      </div>
      <div class="card-body bg-white">
        <?php if (count($options) > 0): ?>
          <ul class="list-group list-group-flush">
            <?php foreach ($options as $opt): ?>
              <li class="list-group-item"><?= htmlspecialchars($opt); ?></li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <p class="text-muted">אין המלצות עדכון לארוחה זו.</p>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
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
