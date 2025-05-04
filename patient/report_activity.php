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

// Get the logged-in patient's user ID from the session
$userId = $_SESSION['user_id'];

// Connect to the SugarSense database and stop execution if connection fails
$host = "localhost";
$dbname = "maiav_sugarSense";
$username = "maiav_sugarSense";
$password = "MaiYuvalMichal!Sugar@";
$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("שגיאה בחיבור למסד הנתונים: " . $conn->connect_error);
}

// Initialize today's date and message placeholders
$today = date('Y-m-d');
$successMessage = "";
$errorMessage = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $activityType = trim($_POST['activity_type']);
    $duration = (int)$_POST['duration'];
    $atGym = isset($_POST['at_gym']) ? 1 : 0;

    $reportDate = date('Y-m-d');

    // Check if there's already a report for today
    $check = "SELECT id FROM activity_reports WHERE patient_id = ? AND report_date = ?";
    $stmt = $conn->prepare($check);
    $stmt->bind_param("is", $userId, $reportDate);
    $stmt->execute();
    $stmt->store_result();

    // Insert the new activity report into the database or show an error if failed
    if ($stmt->num_rows > 0) {
        $errorMessage = "כבר דיווחת על פעילות גופנית היום. לא ניתן לדווח פעמיים.";
    } else {
        $insert = "INSERT INTO activity_reports (patient_id, report_date, activity_type, duration_minutes, performed_at_gym, created_at)
                   VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($insert);
        if ($stmt) {
            $stmt->bind_param("issii", $userId, $reportDate, $activityType, $duration, $atGym);
            if ($stmt->execute()) {
                $successMessage = "🎉 הדיווח נשמר בהצלחה!";
            } else {
                $errorMessage = "שגיאה בשמירת הדיווח: " . $stmt->error;
            }
        } else {
            $errorMessage = "שגיאה בהכנת השאילתה: " . $conn->error;
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>🏃 דיווח על פעילות גופנית</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light d-flex flex-column min-vh-100">

<!-- Header -->
<header>
  <nav class="navbar navbar-expand-lg navbar-light" style="background-color: #d3d3d3;" dir="rtl">
    <div class="container-fluid">
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
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
  <h2 class="mb-4 text-center">🏃 דיווח על פעילות גופנית</h2>

  <?php if ($successMessage): ?>
    <div class="alert alert-success text-center"><?= $successMessage ?></div>
  <?php elseif ($errorMessage): ?>
    <div class="alert alert-danger text-center"><?= $errorMessage ?></div>
  <?php else: ?>
    <div class="alert alert-info text-center">
      אנו סומכים עליך שתדווחי רק אם באמת ביצעת פעילות גופנית היום. הדיווח ניתן רק עבור היום הנוכחי. 💪
    </div>
  <?php endif; ?>

  <form method="POST" class="mx-auto" style="max-width: 600px;">
    <div class="mb-3">
      <label for="activity_type" class="form-label">סוג פעילות</label>
      <input type="text" class="form-control" id="activity_type" name="activity_type" required>
    </div>
    <div class="mb-3">
      <label for="duration" class="form-label">משך הפעילות (בדקות)</label>
      <input type="number" class="form-control" id="duration" name="duration" min="1" required>
    </div>
    <div class="form-check mb-4">
      <input class="form-check-input" type="checkbox" id="at_gym" name="at_gym">
      <label class="form-check-label" for="at_gym">
        ביצעתי את הפעילות במכון כושר שמצאתי באתר
      </label>
    </div>
    <button type="submit" class="btn btn-primary w-100">שלחי דיווח</button>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
