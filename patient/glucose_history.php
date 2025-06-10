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

$sortOrder = ($_GET['sort_order'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

// Connect to the SugarSense database and stop execution if connection fails
require_once '../general/config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the logged-in patient's user ID from the session
$userId = $_SESSION['user_id'];

// Fetching measurement history sorted by date (descending)
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';

$sql = "
    SELECT id, reading_date, time_slot, glucose_level, is_normal, dietitian_note, doctor_note
    FROM glucose_readings
    WHERE patient_id = ?
";

$params = [$userId];
$types = "i";

if (!empty($startDate) && !empty($endDate)) {
    $sql .= " AND reading_date BETWEEN ? AND ?";
    $params[] = $startDate;
    $params[] = $endDate;
    $types .= "ss";
}

$sql .= " ORDER BY reading_date $sortOrder,
  FIELD(time_slot, 
    " . ($sortOrder === 'ASC' ? "
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
    'בצום (לפני ארוחת בוקר)'") . "
)";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$readings = [];
while ($row = $result->fetch_assoc()) {
    $readings[] = $row;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>היסטוריית מדידות סוכר</title>
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
  <h2 class="mb-4 text-center">📊 היסטוריית מדידות סוכר</h2>

  <form method="GET" class="row gy-2 gx-3 align-items-center justify-content-center mb-5" id="filterForm">
    <div class="col-auto">
      <label for="start_date" class="form-label mb-0 small">מתאריך:</label>
      <input type="date" id="start_date" name="start_date" class="form-control"
            value="<?= htmlspecialchars($startDate ?? '') ?>">
    </div>
    <div class="col-auto">
      <label for="end_date" class="form-label mb-0 small">עד תאריך:</label>
      <input type="date" id="end_date" name="end_date" class="form-control"
            value="<?= htmlspecialchars($endDate ?? '') ?>">
    </div>
    <div class="col-auto">
      <label for="sort_order" class="form-label mb-0 small">סדר מיון:</label>
      <select name="sort_order" id="sort_order" class="form-select"
              onchange="document.getElementById('filterForm').submit();">
        <option value="desc" <?= ($_GET['sort_order'] ?? '') === 'desc' ? 'selected' : '' ?>>מהחדש לישן</option>
        <option value="asc" <?= ($_GET['sort_order'] ?? '') === 'asc' ? 'selected' : '' ?>>מהישן לחדש</option>
      </select>
    </div>
    <div class="col-auto">
      <label class="form-label mb-0 invisible">פעולה</label>
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">🔎 סינון</button>
        <a href="glucose_history.php" class="btn btn-secondary">🔄 איפוס</a>
      </div>
    </div>
  </form>

  <?php if (isset($_GET['updated']) && $_GET['updated'] == 1): ?>
    <div class="alert alert-success text-center">
        ✅ המדידה עודכנה בהצלחה!
    </div>
  <?php endif; ?>

  <?php if (isset($_GET['deleted']) && $_GET['deleted'] == 1): ?>
    <div class="alert alert-success text-center">
        🗑️ המדידה נמחקה בהצלחה.
    </div>
  <?php endif; ?>

  <?php if (empty($readings)): ?>
    <p class="text-center">לא הוזנו עדיין מדידות.</p>
  <?php else: ?>
    <?php foreach ($readings as $reading): ?>
        <div class="card mb-3 shadow-sm">
            <div class="card-header text-white fw-bold py-3" style="background-color: <?= $reading['is_normal'] ? '#a2d5ab' : '#e57373' ?>;">
                <div class="row align-items-center">
                    <div class="col-md-9 col-12">
                      <?php
                        $date = new DateTime($reading['reading_date']);
                        $formattedDate = $date->format('d/m/Y');
                        ?>
                        📅 <?= $formattedDate; ?>
                        ⏰ <?= htmlspecialchars($reading['time_slot']); ?> |
                        🩸 <?= htmlspecialchars($reading['glucose_level']); ?> מ"ג
                        <?= $reading['is_normal'] ? '✅ תקין' : '❌ חריגה' ?>
                    </div>

                    <div class="col-md-3 col-12 mt-2 mt-md-0 text-md-end text-center">
                        <a href="edit_glucose.php?id=<?= $reading['id']; ?>" class="btn btn-sm btn-outline-light ms-2">✏️ ערכי</a>
                        <a href="delete_glucose.php?id=<?= $reading['id']; ?>" class="btn btn-sm btn-outline-light"
                            onclick="return confirm('האם את בטוחה שברצונך למחוק את המדידה הזו?')">🗑️ מחקי</a>
                    </div>
                </div>
            </div>

            <?php if (!empty($reading['dietitian_note'])): ?>
              <div class="alert alert-info persistent mt-3 mx-3">
                🗨️ <strong>הערת דיאטנית:</strong><br>
                <?= nl2br(htmlspecialchars($reading['dietitian_note'])); ?>
              </div>
            <?php endif; ?>
            <?php if (!empty($reading['doctor_note'])): ?>
              <div class="alert alert-warning persistent mt-3 mx-3">
                💬 <strong>הערת רופאה:</strong><br>
                <?= nl2br(htmlspecialchars($reading['doctor_note'])); ?>
              </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
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
<script>
  window.addEventListener("pageshow", function (event) {
    if (event.persisted || (window.performance && window.performance.getEntriesByType("navigation")[0]?.type === "back_forward")) {
      window.location.reload();
    }
  });
</script>

</body>
</html>
