<?php
// Start or resume the session to access user-specific data
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'patient') {
    header("Location: ../general/index.html");
    exit;
}


// Check reward id, if not exists back to rewards page
if (!isset($_GET['id'])) {
    header("Location: rewards.php");
    exit;
}

$rewardId = intval($_GET['id']);
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

// Retrieving voucher details
$rewardQuery = "SELECT * FROM rewards WHERE id = ?";
$stmt = $conn->prepare($rewardQuery);
$stmt->bind_param("i", $rewardId);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || !$result->num_rows) {
    $conn->close();
    header("Location: rewards.php");
    exit;
}

$reward = $result->fetch_assoc();
$pointsRequired = $reward['points_required'];
$rewardTitle = $reward['title'];
$baseCode = $reward['code'];
// Generate a unique redemption code for the reward
$uniqueCode = $baseCode . '-' . strtoupper(bin2hex(random_bytes(3)));


// Retrieving current points
$pointsQuery = "SELECT sweet_points FROM users WHERE id = ?";
$stmt = $conn->prepare($pointsQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$currentPoints = $user['sweet_points'];

// Check if there are enough points
if ($currentPoints < $pointsRequired) {
    $conn->close();
    header("Location: rewards.php?error=notenough");
    exit;
}

$conn->begin_transaction();

try {
    $updateQuery = "UPDATE users SET sweet_points = sweet_points - ? WHERE id = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("ii", $pointsRequired, $userId);
    $stmt->execute();

    $insertQuery = "INSERT INTO reward_redemptions (patient_id, reward_id, redemption_time, code_given)
                    VALUES (?, ?, NOW(), ?)";
    $stmt = $conn->prepare($insertQuery);
    $stmt->bind_param("iis", $userId, $rewardId, $uniqueCode);
    $stmt->execute();

    $conn->commit();

} catch (Exception $e) {
    $conn->rollback();
    die("שגיאה בביצוע המימוש: " . $e->getMessage());
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>שובר מומש - SugarSense</title>
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
            <li class="nav-item"><a class="nav-link text-black" href="menu_patient.php?type=gamifiaction">🍬 נקודות מתוקות</a></li>
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

<main class="container py-5 text-center flex-grow-1">
  <h2 class="mb-4 text-success">🎉 מימוש מוצלח!</h2>
  <p>מימשת בהצלחה את השובר: <strong><?= htmlspecialchars($rewardTitle) ?></strong></p>
  <p>📦 קוד השובר שלך:</p>
  <div class="alert alert-info d-inline-block px-4 py-2 fs-4"><?= htmlspecialchars($uniqueCode) ?></div>
  <p class="mt-4"><a href="rewards.php" class="btn btn-primary">חזרה לשוברים</a></p>
  <p class="mt-4 text-center">
        <a href="redemption_history.php" class="btn btn-outline-primary">📜 הצגת היסטוריית מימושים</a></p>
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
