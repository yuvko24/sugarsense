<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'doctor') {
    header("Location: ../general/index.html");
    exit;
}

$patientId = $_SESSION['current_patient'] ?? null;
$type = $_GET['type'] ?? '';

if (!$patientId || !in_array($type, ['nutrition', 'glucose'])) {
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

$stmt = $conn->prepare("SELECT full_name, profile_picture FROM users WHERE id = ?");
$stmt->bind_param("i", $patientId);
$stmt->execute();
$result = $stmt->get_result();
$patient = $result->fetch_assoc();
$patientName = $patient['full_name'];
$patientImage = getValidImage($patient['profile_picture']);

$conn->close();
?>

<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>תפריט לרופאה</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light d-flex flex-column min-vh-100">

<!-- Header-->
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

<main class="container text-center py-5">
  <?php if ($type === 'nutrition'): ?>
    <div class="position-relative text-center mb-4">
      <a href="dashboard_doctor.php?patient_id=<?= urlencode($patientId) ?>" class="btn btn-outline-secondary btn-sm position-absolute top-0 start-0 mt-1 ms-2">🔙 חזרה</a>
      <h2 class="mb-0">🍽️ תזונה יומית</h2>
    </div>
    <div class="d-grid gap-3 col-md-6 mx-auto">
      <a href="meal_log_doctor.php" class="btn btn-lg text-white" style="background-color: #f4b6bd;">📚 צפייה ביומן ארוחות</a>
      <a href="personal_menu_doctor.php" class="btn btn-lg text-white" style="background-color: #f4b6bd;">📋 צפייה בתפריט אישי</a>
    </div>

  <?php elseif ($type === 'glucose'): ?>
    <div class="position-relative text-center mb-4">
      <a href="dashboard_doctor.php?patient_id=<?= urlencode($patientId) ?>" class="btn btn-outline-secondary btn-sm position-absolute top-0 start-0 mt-1 ms-2">🔙 חזרה</a>
      <h2 class="mb-0">🩸 ניטורי סוכר</h2>
    </div>
    <div class="d-grid gap-3 col-md-6 mx-auto">
      <a href="glucose_history_doctor.php" class="btn btn-lg text-white" style="background-color: #f4b6bd;">📊 צפייה בהיסטוריית ניטורים</a>
      <a href="glucose_trends_doctor.php" class="btn btn-lg text-white" style="background-color: #f4b6bd;">📈 צפייה בגרף מגמות סוכר</a>
      <a href="glucose_summary_doctor.php" class="btn btn-lg text-white" style="background-color: #f4b6bd;">🧠 סיכום ניטורי סוכר</a>
    </div>
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
</body>
</html>
