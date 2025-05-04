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

// Load common functions
require_once '../general/functions.php';

// Connect to the SugarSense database and stop execution if connection fails
$host = "localhost";
$dbname = "maiav_sugarSense";
$username = "maiav_sugarSense";
$password = "MaiYuvalMichal!Sugar@";
$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->autocommit(false);

// Initialize variables for Google API and search results
$googleApiKey = 'AIzaSyDjBjuyvLPQcTvrbv1i8cGM7BTArcvXmDw';
$searchResults = [];
$errorMessage = '';
$city = '';

// Send a search request to Google Places API for gyms in the specified city
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['city'])) {
    $city = trim($_POST['city']);

    logAction($conn, $_SESSION['user_id'], 'search_gym', 'Searched gyms in ' . $city);
    $conn->commit();
    
    $query = urlencode("חדר כושר ב" . $city);
    $url = "https://maps.googleapis.com/maps/api/place/textsearch/json?query=$query&key=$googleApiKey&language=iw";
    $response = file_get_contents($url);
    if ($response !== false) {
        $data = json_decode($response, true);
        $status = $data['status'];
        if ($status === 'OK') {
            // Check if any of the results contains the city name
            $cityFound = false;
            foreach ($data['results'] as $gym) {
                if (strpos($gym['formatted_address'], $city) !== false) {
                    $cityFound = true;
                    break;
                }
            }
        
            if ($cityFound) {
                $searchResults = $data['results'];
            } else {
                $errorMessage = 'לא נמצאו חדרי כושר באזור שהוזן.';
            }
        }
        
    } else {
        $errorMessage = 'שגיאה בשליחת הבקשה ל-API של Google.';
    }
}
?>

<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>חיפוש חדרי כושר - SugarSense</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    html, body { height: 100%; }
    body { display: flex; flex-direction: column; }
    main { flex: 1; }
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

<main class="container py-5 flex-grow-1">
  <h2 class="text-center mb-4">🔍 חיפוש חדרי כושר לפי אזור</h2>
  
  <p class="text-center mb-4 fs-5 text-muted" style="max-width: 800px; margin: auto;">
  פעילות גופנית סדירה תורמת רבות לאיזון רמות הסוכר בדם ומשפרת את איכות החיים.  
  בכל יום שבו ביצעת פעילות גופנית – תוכלי לדווח עליה ולקבל <strong>2 נקודות מתוקות</strong> 💪🍬.  
  חשוב לנו לדעת האם הפעילות בוצעה באחד מחדרי הכושר שהופיעו בתוצאות החיפוש,  
  כדי שנוכל לעקוב ולתגמל אותך בהתאם 🙌
  </p>

  <form method="POST" class="text-center mb-5">
    <div class="input-group mx-auto" style="max-width: 400px;">
      <input type="text" name="city" class="form-control" placeholder="הכניסי עיר או אזור (לדוגמה: תל אביב)" required>
      <button type="submit" class="btn btn-primary">חיפוש</button>
    </div>
  </form>

  <?php if (!empty($errorMessage)): ?>
    <div class="alert alert-danger text-center">😔 לצערנו לא נמצאו חדרי כושר באזור שהוזן.</div>
  <?php endif; ?>

  <?php if (!empty($searchResults)): ?>
    <h4 class="text-center mb-4 text-success">🏋️‍♀️ אלו חדרי הכושר שמצאנו עבורך באזור <?= htmlspecialchars($city) ?></h4>
    <div class="row row-cols-1 row-cols-md-2 g-4">
      <?php foreach ($searchResults as $gym): ?>
        <div class="col">
          <div class="card h-100 shadow-sm">
            <div class="card-body">
              <h5 class="card-title">🏋️ <?= htmlspecialchars($gym['name']) ?></h5>
              <p class="card-text">📍 <?= htmlspecialchars($gym['formatted_address']) ?></p>
              <?php if (isset($gym['rating'])): ?>
                <p class="card-text">⭐ דירוג: <?= $gym['rating'] ?>/5</p>
              <?php endif; ?>
              <a href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($gym['name']) ?>" class="btn btn-outline-secondary" target="_blank">📌 פתחי בגוגל מפות</a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
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
<script>
  window.addEventListener("pageshow", function (event) {
    if (event.persisted || (window.performance && window.performance.getEntriesByType("navigation")[0]?.type === "back_forward")) {
      window.location.reload();
    }
  });
</script>

</body>
</html>
