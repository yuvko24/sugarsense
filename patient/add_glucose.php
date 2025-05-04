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

$timeSlots = [
  "בצום (לפני ארוחת בוקר)",
  "שעתיים אחרי ארוחת בוקר",
  "לפני ארוחת צהריים",
  "שעתיים אחרי ארוחת צהריים",
  "לפני ארוחת ערב",
  "שעתיים אחרי ארוחת ערב",
  "לפני שינה"
];
?>

<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>הוספת מדידת סוכר</title>
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

<main class="container py-5">
  <h2 class="mb-4 text-center">➕ הוספת מדידות סוכר</h2>

  <!-- Success message for adding a glucose measurement -->
  <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
  <div class="alert alert-success text-center" role="alert">
    ✅ נוספו <?= htmlspecialchars($_GET['count']) ?> מדידות סוכר בהצלחה!
  </div>
  <?php endif; ?>

  <!-- Error message for failed glucose measurement addition -->
  <?php if (isset($_GET['skipped']) && $_GET['skipped'] > 0): ?>
  <div class="alert alert-warning text-center" role="alert">
    ⚠️ <?= htmlspecialchars($_GET['skipped']) ?> מדידות לא נשמרו – כבר קיימות לאותו תאריך ומועד. תוכלי לעדכן אותן ביומן המדידות.
  </div>
  <?php endif; ?>


  <!-- Desired glucose ranges -->
  <div class="alert alert-info mx-auto" dir="rtl" style="max-width: 600px; text-align: right;">
    <strong>💡 טווח רצוי:</strong><br>
    בצום/לפני ארוחה: עד 95 מ"ג<br>
    שעתיים מהביס הראשון: עד 120 מ"ג<br>
    שעה מהביס הראשון: עד 140 מ"ג
  </div>

  <!-- Glucose measurement submission form -->
  <form action="save_glucose.php" method="POST" class="bg-white p-4 rounded shadow-sm mx-auto" style="max-width: 700px;">
    <div id="glucose-entries">
      <div class="glucose-entry row g-2 align-items-end mb-3">
        <div class="col-md-4">
          <label class="form-label">📅 תאריך:</label>
          <?php $today = date('Y-m-d'); ?>
          <input type="date" name="reading_date[]" class="form-control" required max="<?= $today ?>">
        </div>
        <div class="col-md-5">
          <label class="form-label">⏰ מועד מדידה:</label>
          <select name="time_slot[]" class="form-select" required>
            <option value="">בחרי</option>
            <?php foreach ($timeSlots as $slot): ?>
              <option value="<?= htmlspecialchars($slot); ?>"><?= htmlspecialchars($slot); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">🩸 רמת סוכר (מ"ג):</label>
          <input type="number" step="0.1" min="0" name="glucose_level[]" class="form-control" required>
        </div>
      </div>
    </div>

    <button type="button" class="btn btn-secondary mb-3" onclick="addGlucoseEntry()">➕ הוסיפי מדידה נוספת</button>
    <br>
    <button type="submit" class="btn btn-primary">📂 שמרי מדידות</button>
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

<script>
function addGlucoseEntry() {
  const container = document.getElementById('glucose-entries');
  const item = container.querySelector('.glucose-entry');
  const clone = item.cloneNode(true);
  clone.querySelectorAll('select, input').forEach(el => el.value = '');
  container.appendChild(clone);
}

window.addEventListener("pageshow", function (event) {
  if (event.persisted || (window.performance && window.performance.getEntriesByType("navigation")[0]?.type === "back_forward")) {
    window.location.reload();
  }
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
