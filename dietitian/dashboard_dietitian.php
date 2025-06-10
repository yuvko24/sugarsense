<?php
// Start or resume the session to access user-specific data
session_start();

// Prevent browser from caching the page (ensures fresh data is always loaded)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Ensure the user is logged in as a dietitian
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'dietitian' || !isset($_GET['patient_id'])) {
  header("Location: ../general/index.html");
    exit;
}

// Connect to the SugarSense database and stop execution if connection fails
require_once '../general/config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

require_once '../general/functions.php';

$patientId = intval($_GET['patient_id']);

$_SESSION['current_patient'] = $patientId;

// Retrieving dietitian's name and photo
$dietitianName = $_SESSION['full_name'];
$dietitianImage = "Images/default.jpeg";

$stmt = $conn->prepare("SELECT profile_picture FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $dietitianImage = getValidImage($row['profile_picture']);
}

// Retrieving patient's details
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
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>פרופיל מטופלת - דיאטנית | SugarSense</title>
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
            <li class="nav-item"><a class="nav-link text-black" href="select_patient.php">🔁 החלפת מטופלת</a></li>
          </ul>

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
 
<!-- Main -->
<main class="container py-5 text-center">
  <img src="<?= htmlspecialchars($dietitianImage) ?>" class="rounded-circle mb-3" alt="תמונת דיאטנית" width="90" height="90">
  <h2>שלום, <?= htmlspecialchars($dietitianName) ?>! 🌸</h2>
  <p class="text-muted">כאן תוכלי ללוות את המטופלות שלך מקרוב</p>

  <h4 class="mt-5">בחרת במטופלת:</h4>
  <div class="d-flex flex-column align-items-center">
    <img src="<?= htmlspecialchars($patientImage) ?>" class="rounded-circle border border-2 shadow-sm mb-2" alt="תמונת מטופלת" width="80" height="80">
    <h5 class="text-dark"><?= htmlspecialchars($patientName) ?></h5>
  </div>

  <div class="row justify-content-center mt-5">
    <!-- Daily Nutrition -->
  <div class="col-md-5 mb-3">
    <div class="card shadow-sm h-100 text-center text-white" 
        style="background-image: url('../Images/nutrition.png'); background-size: cover; background-position: center; opacity: 0.5;">
      <div class="card-body" style="background-color: rgba(0,0,0,0.4);">
        <h5 class="card-title">🍽️ תזונה יומית</h5>
        <p class="card-text">צפייה ביומן הארוחות, הוספת הערות ועדכון תפריט אישי למטופלת.</p>
        <a href="menu_dietitian.php?type=nutrition" class="btn btn-light">למעבר</a>
      </div>
    </div>
  </div>

  <!-- Glucose Monitoring -->
  <div class="col-md-5 mb-3">
    <div class="card shadow-sm h-100 text-center text-white" 
        style="background-image: url('../Images/glucose.png'); background-size: cover; background-position: center; opacity: 0.5;">
      <div class="card-body" style="background-color: rgba(0,0,0,0.4);">
        <h5 class="card-title">🩸 ניטורי סוכר</h5>
        <p class="card-text">מעקב יומי אחר רמות הסוכר של המטופלת והוספת הערות.</p>
        <a href="menu_dietitian.php?type=glucose" class="btn btn-light">למעבר</a>
      </div>
    </div>
  </div>
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
