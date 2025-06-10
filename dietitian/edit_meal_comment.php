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

$mealId = $_GET['meal_id'] ?? null;
if (!$mealId) {
    header("Location: meal_log_dietitian.php");
    exit;
}

// Connect to the SugarSense database and stop execution if connection fails
require_once '../general/config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$existingComment = '';
$patientId = null;

$stmt = $conn->prepare("
    SELECT mc.comment_text, m.patient_id
    FROM meal_comments mc
    JOIN meals m ON mc.meal_id = m.id
    WHERE mc.meal_id = ?
");
$stmt->bind_param("i", $mealId);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $existingComment = $row['comment_text'];
    $patientId = $row['patient_id'];
}
$stmt->close();
$conn->close();

$patientName = "לא ידוע";
$patientImage = "Images/default.jpeg";

if (!empty($patientId)) {
    require_once '../general/functions.php';
    $conn = new mysqli($host, $username, $password, $dbname);
    if (!$conn->connect_error) {
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
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>✏️ עריכת הערה</title>
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

<!-- Main -->
<main class="container py-5 flex-grow-1">
  <div class="position-relative text-center mb-4">
    <a href="meal_log_dietitian.php" class="btn btn-outline-secondary btn-sm position-absolute top-0 start-0 mt-1 ms-2">🔙 חזרה</a>
    <h2 class="mb-0">✏️ עריכת הערה</h2>
  </div>

  <form action="save_meal_comment.php" method="POST" class="mx-auto" style="max-width: 600px;">
    <div class="mb-3">
      <label for="comment_text" class="form-label">📝 ערכי את ההערה:</label>
      <textarea name="comment_text" id="comment_text" rows="3" class="form-control" required><?= htmlspecialchars($existingComment) ?></textarea>
    </div>
    <input type="hidden" name="meal_id" value="<?= htmlspecialchars($mealId) ?>">
    <input type="hidden" name="patient_id_number" value="<?= htmlspecialchars($patientId) ?>">
    <div class="d-grid">
      <button type="submit" class="btn btn-primary">💾 שמירת שינויים</button>
    </div>
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