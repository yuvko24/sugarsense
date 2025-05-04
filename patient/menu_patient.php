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

// Get the 'type' parameter from the URL, or use 'default' if it's not set
$type = $_GET['type'] ?? 'default';
?>

<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>בחירת פעולה - SugarSense</title>
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

<main class="container text-center py-5">
  <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
    <div class="alert alert-success text-center" role="alert">
      ✅ הארוחה נוספה בהצלחה!
    </div>
  <?php endif; ?>
  <?php if ($type === 'nutrition'): ?>
    <h2 class="mb-4">🍽️ תזונה יומית</h2>
    <div class="d-grid gap-3 col-md-6 mx-auto">
      <a href="add_meal.php" class="btn btn-lg text-white" style="background-color: #f4b6bd;">➕ הוספת ארוחה</a>
      <a href="meal_log.php" class="btn btn-lg text-white" style="background-color: #f4b6bd;">📚 צפייה ביומן ארוחות</a>
      <a href="personal_menu.php" class="btn btn-lg text-white" style="background-color: #f4b6bd;">📝 הצגת תפריט אישי</a>
    </div>

  <?php elseif ($type === 'glucose'): ?>
    <h2 class="mb-4">🩸 ניטורי סוכר</h2>
    <div class="d-grid gap-3 col-md-6 mx-auto">
      <a href="add_glucose.php" class="btn btn-lg text-white" style="background-color: #f4b6bd;">➕ הוספת מדידת סוכר</a>
      <a href="glucose_history.php" class="btn btn-lg text-white" style="background-color: #f4b6bd;">📊 היסטוריית מדידות</a>
    </div>

  <?php elseif ($type === 'gamification'): ?>
    <h2 class="mb-4">🍬 נקודות מתוקות</h2>
    <div class="d-grid gap-3 col-md-6 mx-auto">
      <a href="sweet_points_history.php" class="btn btn-lg text-white" style="background-color: #f4b6bd;">📅 יומן צבירת נקודות מתוקות</a>
      <a href="rewards.php" class="btn btn-lg text-white" style="background-color: #f4b6bd;">🎁 מימוש נקודות מתוקות</a>
    </div>

    <?php elseif ($type === 'activity'): ?>
    <h2 class="mb-4">🏃 פעילות גופנית</h2>
    <div class="d-grid gap-3 col-md-6 mx-auto">
      <a href="search_gyms.php" class="btn btn-lg text-white" style="background-color: #f4b6bd;">🔍 חיפוש חדרי כושר</a>
      <a href="report_activity.php" class="btn btn-lg text-white" style="background-color: #f4b6bd;">🏃 דיווח על קיום פעילות גופנית</a>
      <a href="activity_log.php" class="btn btn-lg text-white" style="background-color: #f4b6bd;">📚 צפייה ביומן פעילות גופנית</a>
    </div>

  <?php else: ?>
    <p class="text-center text-muted">😕 לא נבחרה קטגוריה תקינה</p>
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
