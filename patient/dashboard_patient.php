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

// Get the logged-in patient's user ID from the session
$userId = $_SESSION['user_id'];

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

// Check if the image file exists; if not, return a default image path
$fullName = "לא ידוע";
$patientImage = "../Images/default.jpeg";
$dietitianName = "לא ידוע";
$dietitianImage = "../Images/default.jpeg";
$doctorName = "לא ידוע";
$doctorImage = "../Images/default.jpeg";

// Fetch the current sweet points balance for the logged-in patient
$sweetPoints = 0;
$pointsQuery = "SELECT sweet_points FROM users WHERE id = ?";
$pointsStmt = $conn->prepare($pointsQuery);
$pointsStmt->bind_param("i", $userId);
$pointsStmt->execute();
$pointsResult = $pointsStmt->get_result();
if ($pointsResult && $pointsRow = $pointsResult->fetch_assoc()) {
    $sweetPoints = $pointsRow['sweet_points'];
}

// Retrieve the patient's full name and profile picture from the database
$patientQuery = "SELECT full_name, profile_picture FROM users WHERE id = ?";
$patientStmt = $conn->prepare($patientQuery);
$patientStmt->bind_param("i", $userId);
$patientStmt->execute();
$patientResult = $patientStmt->get_result();
if ($patientResult && $row = $patientResult->fetch_assoc()) {
    $fullName = $row['full_name'];
    if (!empty($row['profile_picture'])) {
        $patientImage = getValidImage($row['profile_picture']);
    }
}

// Retrieve the full name and profile picture of the patient's assigned dietitian
$dietitianQuery = "SELECT u.full_name, u.profile_picture 
                   FROM dietitian_patient dp 
                   JOIN users u ON dp.dietitian_id = u.id 
                   WHERE dp.patient_id = ?";
$stmt = $conn->prepare($dietitianQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $row = $result->fetch_assoc()) {
    $dietitianName = $row['full_name'];
    if (!empty($row['profile_picture'])) {
        $dietitianImage = getValidImage($row['profile_picture']);
    }
}

// Retrieve the full name and profile picture of the patient's assigned doctor
$doctorQuery = "SELECT u.full_name, u.profile_picture 
                FROM doctor_patient dp 
                JOIN users u ON dp.doctor_id = u.id 
                WHERE dp.patient_id = ?";
$stmt = $conn->prepare($doctorQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $row = $result->fetch_assoc()) {
    $doctorName = $row['full_name'];
    if (!empty($row['profile_picture'])) {
        $doctorImage = getValidImage($row['profile_picture']);
    }
}

// Select and display a daily tip based on the current day of the year
$tipText = "";
$tipQuery = "SELECT COUNT(*) as total FROM tips";
$result = $conn->query($tipQuery);
if ($result && $row = $result->fetch_assoc()) {
    $totalTips = $row['total'];
    $dayOfYear = date('z');
    $tipIndex = ($dayOfYear % $totalTips) + 1;

    $tipResult = $conn->query("SELECT tip_text FROM tips WHERE id = $tipIndex");
    if ($tipResult && $tipRow = $tipResult->fetch_assoc()) {
        $tipText = $tipRow['tip_text'];
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>מסך בית - SugarSense</title>
  <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate" />
  <meta http-equiv="Pragma" content="no-cache" />
  <meta http-equiv="Expires" content="0" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
  html, body {
      height: 100%;
    }
    body {
      display: flex;
      flex-direction: column;
    }
    main {
      flex: 1;
    }
  </style>
</head>
<body class="bg-light">

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
              <img src="../Images/logo.jpg" alt="לוגו" width="50" height="50"
                  class="rounded-circle border border-2 border-secondary shadow-sm">
            </a>
          </div>
        </div>
      </div>
    </nav>
 </header> 


<main class="container py-5">
  <div class="text-center mb-4">
    <h2>שלום, <?php echo htmlspecialchars($fullName); ?>! 🤍</h2>
    <p class="text-muted">ברוכה הבאה למערכת לניהול סוכרת ההיריון שלך</p>
  </div>

  <!-- Patient profile card -->
  <div class="card mb-4 mx-auto" style="max-width: 600px;">
    <div class="card-header text-white" style="background-color: #f4b6bd;">כרטיס מטופלת</div>
    <div class="card-body">

      <!-- Patient data -->
      <div class="d-flex align-items-center justify-content-start mb-3 text-end">
        <img src="<?php echo htmlspecialchars($patientImage); ?>" alt="תמונת מטופלת" class="rounded-circle me-3" width="70" height="70">
        <p class="mb-0"><strong>שם מלא:</strong> <?php echo htmlspecialchars($fullName); ?></p>
      </div>

      <!-- Dietititian data -->
      <div class="d-flex align-items-center justify-content-start mb-3 text-end">
        <img src="<?php echo htmlspecialchars($dietitianImage); ?>" alt="תמונת דיאטנית" class="rounded-circle me-3" width="70" height="70">
        <p class="mb-0"><strong>דיאטנית מלווה:</strong> <?php echo htmlspecialchars($dietitianName); ?></p>
      </div>

      <!-- Doctor data -->
      <div class="d-flex align-items-center justify-content-start mb-3 text-end">
        <img src="<?php echo htmlspecialchars($doctorImage); ?>" alt="תמונת רופאה" class="rounded-circle me-3" width="70" height="70">
        <p class="mb-0"><strong>רופאה מלווה:</strong> <?php echo htmlspecialchars($doctorName); ?></p>
      </div>
    </div>
  </div>

  <!-- Daily tip -->
  <?php if (!empty($tipText)): ?>
  <div class="alert alert-info text-center mx-auto" style="max-width: 600px;">
    💡 <strong>טיפ יומי:</strong> <?php echo htmlspecialchars($tipText); ?>
  </div>
  <?php endif; ?>

  <!-- Sweet points -->
  <div class="alert alert-success text-center mx-auto" style="max-width: 600px;">
    🎁 <strong>נקודות מתוקות שצברת:</strong> <?= $sweetPoints ?> נקודות
    <div class="mt-2">
      <a href="rewards.php" class="btn btn-sm btn-outline-success">מימוש נקודות</a>
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

<!-- Force page reload if user navigates back using browser history to prevent showing outdated cached content -->
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
