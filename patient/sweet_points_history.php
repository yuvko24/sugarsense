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

// Define today's date
$today = date('Y-m-d');

// Update today's points only for activities that haven't been credited yet
if (isset($_GET['recalculate']) && $_GET['recalculate'] == 1) {
    $recalcMessage = "";
    
    // Retrieving existing values from the diary
    $logQuery = "SELECT * FROM daily_points_log WHERE patient_id = ? AND logged_date = ?";
    $stmt = $conn->prepare($logQuery);
    $stmt->bind_param("is", $userId, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    $logRow = $result ? $result->fetch_assoc() : null;

    // Glucose monitoring
    $glucoseQuery = "SELECT COUNT(*) as total FROM glucose_readings WHERE patient_id = ? AND reading_date = ?";
    $stmt = $conn->prepare($glucoseQuery);
    $stmt->bind_param("is", $userId, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    $glucoseCount = $result ? $result->fetch_assoc()['total'] : 0;
    $glucosePoints = ($glucoseCount >= 5) ? 5 : 0;

    // Meals
    $mealsQuery = "SELECT COUNT(DISTINCT meal_type) as total FROM meals WHERE patient_id = ? AND meal_date = ?";
    $stmt = $conn->prepare($mealsQuery);
    $stmt->bind_param("is", $userId, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    $mealsCount = $result ? $result->fetch_assoc()['total'] : 0;
    $mealsPoints = ($mealsCount >= 3) ? 3 : 0;

    // Activity
    $activityQuery = "SELECT COUNT(*) as total FROM activity_reports WHERE patient_id = ? AND report_date = ?";
    $stmt = $conn->prepare($activityQuery);
    $stmt->bind_param("is", $userId, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    $activityCount = $result ? $result->fetch_assoc()['total'] : 0;
    $activityPoints = ($activityCount > 0) ? 2 : 0;

    if ($logRow) {
        // Only entries that haven't been counted yet
        $addGlucose = ($logRow['points_from_glucose'] == 0) ? $glucosePoints : 0;
        $addMeals = ($logRow['points_from_meals'] == 0) ? $mealsPoints : 0;
        $addActivity = ($logRow['points_from_activity'] == 0) ? $activityPoints : 0;
        $additionalPoints = $addGlucose + $addMeals + $addActivity;

        if ($additionalPoints > 0) {
            $updateLog = "UPDATE daily_points_log SET 
                            points_from_glucose = IF(points_from_glucose = 0, ?, points_from_glucose),
                            points_from_meals = IF(points_from_meals = 0, ?, points_from_meals),
                            points_from_activity = IF(points_from_activity = 0, ?, points_from_activity),
                            total_points = total_points + ?
                          WHERE patient_id = ? AND logged_date = ?";
            $stmt = $conn->prepare($updateLog);
            $stmt->bind_param("iiiiis", $glucosePoints, $mealsPoints, $activityPoints, $additionalPoints, $userId, $today);
            $stmt->execute();

            $updateUser = "UPDATE users SET sweet_points = sweet_points + ? WHERE id = ?";
            $stmt = $conn->prepare($updateUser);
            $stmt->bind_param("ii", $additionalPoints, $userId);
            $stmt->execute();

            $recalcMessage = "✨ הניקוד עודכן בהצלחה! נוספו $additionalPoints נקודות.";
        } else {
            $recalcMessage = "🔁 לא נוספו נקודות חדשות – ייתכן שכבר צברת את כל הנקודות האפשריות להיום.";
        }
    // Insert a new points record for today and update the user's total sweet points
    } else {
        $totalPoints = $glucosePoints + $mealsPoints + $activityPoints;
        $insertQuery = "INSERT INTO daily_points_log 
            (patient_id, logged_date, points_from_glucose, points_from_meals, points_from_activity, total_points) 
            VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("isiiii", $userId, $today, $glucosePoints, $mealsPoints, $activityPoints, $totalPoints);
        $stmt->execute();

        $updateUser = "UPDATE users SET sweet_points = sweet_points + ? WHERE id = ?";
        $stmt = $conn->prepare($updateUser);
        $stmt->bind_param("ii", $totalPoints, $userId);
        $stmt->execute();

        $recalcMessage = "✨ הניקוד חושב ונשמר! צברת $totalPoints נקודות.";
    }
}

// Check if points have already been calculated for today
$checkQuery = "SELECT * FROM daily_points_log WHERE patient_id = ? AND logged_date = ?";
$stmt = $conn->prepare($checkQuery);
if (!$stmt) die("❌ שגיאה בהכנת השאילתה לבדיקה: " . $conn->error);
$stmt->bind_param("is", $userId, $today);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Glucose monitoring
    $glucoseQuery = "SELECT COUNT(*) as total FROM glucose_readings WHERE patient_id = ? AND reading_date = ?";
    $stmt = $conn->prepare($glucoseQuery);
    if (!$stmt) die("❌ glucoseQuery prepare: " . $conn->error);
    $stmt->bind_param("is", $userId, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $glucoseCount = $row ? $row['total'] : 0;

    // Meals
    $mealsQuery = "SELECT COUNT(DISTINCT meal_type) as total FROM meals WHERE patient_id = ? AND meal_date = ?";
    $stmt = $conn->prepare($mealsQuery);
    if (!$stmt) die("❌ mealsQuery prepare: " . $conn->error);
    $stmt->bind_param("is", $userId, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $mealsCount = $row ? $row['total'] : 0;

    // Activity
    $activityQuery = "SELECT COUNT(*) as total FROM activity_reports WHERE patient_id = ? AND report_date = ?";
    $stmt = $conn->prepare($activityQuery);
    if (!$stmt) die("❌ activityQuery prepare: " . $conn->error);
    $stmt->bind_param("is", $userId, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $activityCount = $row ? $row['total'] : 0;

    $glucosePoints = ($glucoseCount >= 5) ? 5 : 0;
    $mealsPoints = ($mealsCount >= 3) ? 3 : 0;
    $activityPoints = ($activityCount > 0) ? 2 : 0;
    $totalPoints = $glucosePoints + $mealsPoints + $activityPoints;

    // Saving
    $insertQuery = "INSERT INTO daily_points_log 
        (patient_id, logged_date, points_from_glucose, points_from_meals, points_from_activity, total_points) 
        VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insertQuery);
    if (!$stmt) die("❌ insertQuery prepare: " . $conn->error);
    $stmt->bind_param("isiiii", $userId, $today, $glucosePoints, $mealsPoints, $activityPoints, $totalPoints);
    $stmt->execute();

    // Updating points
    $updateUser = "UPDATE users SET sweet_points = sweet_points + ? WHERE id = ?";
    $stmt = $conn->prepare($updateUser);
    if (!$stmt) die("❌ updateUser prepare: " . $conn->error);
    $stmt->bind_param("ii", $totalPoints, $userId);
    $stmt->execute();
}

// History
$history = [];
$select = "SELECT * FROM daily_points_log WHERE patient_id = ? ORDER BY logged_date DESC";
$stmt = $conn->prepare($select);
if (!$stmt) die("❌ select history prepare: " . $conn->error);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $history[] = $row;
}

$conn->close();

function formatHebrewDate($date) {
    return date("d/m/Y", strtotime($date));
}
?>

<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>📅 יומן צבירת נקודות מתוקות</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    html, body { height: 100%; }
    body { display: flex; flex-direction: column; }
    main { flex: 1; }
  </style>
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
  <h2 class="mb-4 text-center">📅 יומן צבירת נקודות</h2>

  <?php if (!empty($recalcMessage)): ?>
    <div class="alert alert-success text-center">
        <?= $recalcMessage ?>
    </div>
  <?php endif; ?>

  <div class="alert alert-info text-center">
    🧮 <strong>כך תוכלי לצבור נקודות מתוקות:</strong><br>
    ✅ לפחות 5 ניטורי סוכר ביום = 5 נקודות (רק אם דווחו ביום המדידה ולא באוחר)<br>
    ✅ לפחות 3 סוגי ארוחות שונים ביום = 3 נקודות (רק אם דווחו ביום האכילה ולא באוחר)<br>
    ✅ דיווח על פעילות גופנית = 2 נקודות (רק אם דווחה ביום הביצוע ולא באוחר)<br>
    לחצי על כפתור הריענון מטה למקרה שעדכנת פעילות שעשויה לזכות אותך בנקודות מתוקות עבור היום 🔄
  </div>

  <?php if (empty($history)): ?>
    <p class="text-center text-muted">אין עדיין נתוני צבירת נקודות.</p>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-striped text-center">
        <thead class="table-secondary">
          <tr>
            <th>📅 תאריך</th>
            <th>🩸 ניקוד סוכר</th>
            <th>🍽️ ניקוד ארוחות</th>
            <th>🏃 ניקוד פעילות גופנית</th>
            <th>🍬 סה"כ</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($history as $row): ?>
            <tr>
              <td><?= formatHebrewDate($row['logged_date']) ?></td>
              <td><?= $row['points_from_glucose'] ?></td>
              <td><?= $row['points_from_meals'] ?></td>
              <td><?= $row['points_from_activity'] ?></td>
              <td><strong><?= $row['total_points'] ?></strong></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="text-center mb-4">
        <a href="sweet_points_history.php?recalculate=1" class="btn btn-outline-primary">
            🔄 חשבי מחדש את הנקודות שצברת היום
        </a>
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
