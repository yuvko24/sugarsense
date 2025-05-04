<?php
// Start or resume the session to access user-specific data
session_start();

// Prevent browser from caching the page (ensures fresh data is always loaded)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Ensure the user is logged in as a doctor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'doctor' || !isset($_GET['patient_id'])) {
    header("Location: ../genral/index.html");
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

$patientId = intval($_GET['patient_id']);
$_SESSION['current_patient'] = $patientId;

$doctorName = $_SESSION['full_name'];
$doctorImage = "Images/default.jpeg";

$stmt = $conn->prepare("SELECT profile_picture FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $doctorImage = getValidImage($row['profile_picture']);
}

$stmt = $conn->prepare("SELECT full_name, profile_picture FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['current_patient']);
$stmt->execute();
$patientResult = $stmt->get_result();
$patient = $patientResult->fetch_assoc();
$patientName = $patient['full_name'];
$patientImage = getValidImage($patient['profile_picture']);

$conn->close();
?>

<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>פרופיל מטופלת - רופאה | SugarSense</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light d-flex flex-column min-vh-100">

<header>
  <nav class="navbar navbar-expand-lg navbar-light" style="background-color: #d3d3d3;" dir="rtl">
    <div class="container-fluid">
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse justify-content-between" id="navbarNav">
        <ul class="navbar-nav me-auto d-flex flex-row-reverse gap-3">
          <li class="nav-item"><a class="nav-link text-black" href="select_patient_doctor.php">🔁 החלפת מטופלת</a></li>
        </ul>
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

<main class="container py-5 text-center">
  <img src="<?= htmlspecialchars($doctorImage) ?>" class="rounded-circle mb-3" alt="תמונת רופאה" width="90" height="90">
  <h2>שלום, <?= htmlspecialchars($doctorName) ?>! 🩺</h2>
  <p class="text-muted">כאן תוכלי לעקוב אחר המטופלות שלך</p>

  <h4 class="mt-5">בחרת במטופלת:</h4>
  <div class="d-flex flex-column align-items-center">
    <img src="<?= htmlspecialchars($patientImage) ?>" class="rounded-circle border border-2 shadow-sm mb-2" alt="תמונת מטופלת" width="80" height="80">
    <h5 class="text-dark"><?= htmlspecialchars($patientName) ?></h5>
  </div>

  <div class="row justify-content-center mt-5">
    <!-- Nutrition -->
    <div class="col-md-4 mb-3">
      <div class="card shadow-sm h-100 text-center text-white"
          style="background-image: url('../Images/nutrition.png'); background-size: cover; background-position: center; opacity: 0.5;">
        <div class="card-body" style="background-color: rgba(0,0,0,0.4);">
          <h5 class="card-title">🍽️ תזונה יומית</h5>
          <p class="card-text">צפייה ביומן הארוחות ובתפריט האישי שניתנו למטופלת.</p>
          <a href="menu_doctor.php?type=nutrition" class="btn btn-light">למעבר</a>
        </div>
      </div>
    </div>

    <!-- Glucose -->
    <div class="col-md-4 mb-3">
      <div class="card shadow-sm h-100 text-center text-white"
          style="background-image: url('../Images/glucose.png'); background-size: cover; background-position: center; opacity: 0.5;">
        <div class="card-body" style="background-color: rgba(0,0,0,0.4);">
          <h5 class="card-title">🩸 ניטורי סוכר</h5>
          <p class="card-text">מעקב אחר רמות הסוכר והערות שהוזנו על ידי הדיאטנית.</p>
          <a href="menu_doctor.php?type=glucose" class="btn btn-light">למעבר</a>
        </div>
      </div>
    </div>

    <!-- Sugar Trends -->
    <div class="col-md-4 mb-3">
      <div class="card shadow-sm h-100 text-center text-white"
          style="background-image: url('../Images/graphs.png'); background-size: cover; background-position: center; opacity: 0.5;">
        <div class="card-body" style="background-color: rgba(0,0,0,0.4);">
          <h5 class="card-title">📈 גרף מגמות סוכר</h5>
          <p class="card-text">מעקב ויזואלי אחרי מגמות רמות הסוכר של המטופלת, כולל פילטרים לזיהוי תבניות חריגות.</p>
          <a href="glucose_trends_doctor.php" class="btn btn-light">למעבר</a>
        </div>
      </div>
    </div>
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
