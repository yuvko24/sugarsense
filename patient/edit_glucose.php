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

// Connect to the SugarSense database and stop execution if connection fails
$host = "localhost";
$dbname = "maiav_sugarSense";
$username = "maiav_sugarSense";
$password = "MaiYuvalMichal!Sugar@";
$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$timeSlots = [
  "בצום (לפני ארוחת בוקר)",
  "שעתיים אחרי ארוחת בוקר",
  "לפני ארוחת צהריים",
  "שעתיים אחרי ארוחת צהריים",
  "לפני ארוחת ערב",
  "שעתיים אחרי ארוחת ערב",
  "לפני שינה"
];

// Check if glucose level is normal for given time of day
function isGlucoseNormal($slot, $value) {
    $slot = mb_strtolower($slot);
    if (strpos($slot, 'בצום') !== false || strpos($slot, 'לפני') !== false) {
        return $value <= 95;
    } elseif (strpos($slot, 'שעתיים') !== false) {
        return $value <= 120;
    } elseif (strpos($slot, 'שעה') !== false) {
        return $value <= 140;
    }
    return null;
}

// Check if the user has submitted the form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $date = $_POST['reading_date'];
    $slot = $_POST['time_slot'];
    $level = floatval($_POST['glucose_level']);
    $isNormal = isGlucoseNormal($slot, $level);

    $updateStmt = $conn->prepare("UPDATE glucose_readings SET reading_date = ?, time_slot = ?, glucose_level = ?, is_normal = ? WHERE id = ? AND patient_id = ?");
    $updateStmt->bind_param("ssddii", $date, $slot, $level, $isNormal, $id, $_SESSION['user_id']);
    $updateStmt->execute();
    $updateStmt->close();

    $conn->close();
    header("Location: glucose_history.php?updated=1");
    exit;
}

// Loading existing measurement data
$id = $_GET['id'] ?? null;
$reading = null;

if ($id) {
    $stmt = $conn->prepare("SELECT * FROM glucose_readings WHERE id = ? AND patient_id = ?");
    $stmt->bind_param("ii", $id, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $reading = $result->fetch_assoc();
    }
    $stmt->close();
}

$conn->close();

if (!$reading) {
    echo "<p style='text-align: center; margin-top: 50px;'>😕 לא נמצאה מדידה.</p>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>עריכת מדידת סוכר</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light d-flex flex-column min-vh-100">

<!-- Header -->
<header>
  <nav class="navbar navbar-expand-lg navbar-light" style="background-color: #d3d3d3;" dir="rtl">
    <div class="container-fluid">

      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
          aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse justify-content-between" id="navbarNav">
        <ul class="navbar-nav me-auto d-flex flex-row-reverse gap-3">
          <li class="nav-item"><a class="nav-link text-black" href="menu_patient.php?type=glucose">🩸 חזרה לניטורי סוכר</a></li>
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

<main class="container py-5">
  <h2 class="mb-4 text-center">✏️ עריכת מדידת סוכר</h2>

  <form method="POST" class="bg-white p-4 rounded shadow-sm mx-auto" style="max-width: 700px;">
    <input type="hidden" name="id" value="<?= htmlspecialchars($reading['id']); ?>">

    <div class="mb-3">
      <label class="form-label">📅 תאריך:</label>
      <input type="date" name="reading_date" class="form-control" required value="<?= htmlspecialchars($reading['reading_date']); ?>">
    </div>

    <div class="mb-3">
      <label class="form-label">⏰ מועד מדידה:</label>
      <select name="time_slot" class="form-select" required>
        <option value="">בחרי</option>
        <?php foreach ($timeSlots as $slot): ?>
          <option value="<?= $slot ?>" <?= $slot == $reading['time_slot'] ? 'selected' : '' ?>><?= $slot ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="mb-3">
      <label class="form-label">🩸 רמת סוכר (מ"ג):</label>
      <input type="number" name="glucose_level" step="0.1" min="0" class="form-control" required value="<?= htmlspecialchars($reading['glucose_level']); ?>">
    </div>

    <button type="submit" class="btn btn-primary">💾 שמרי שינויים</button>
    <a href="glucose_history.php" class="btn btn-secondary">🔙 חזרה</a>
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
