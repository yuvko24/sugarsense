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
require_once '../general/config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->autocommit(false);

function calculateDistanceKm($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2) * sin($dLat/2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return round($earthRadius * $c, 1);
}

// Initialize variables for Google API and search results
$googleApiKey = GOOGLE_API_KEY;
$searchResults = [];
$errorMessage = '';
$city = '';
$activity = '';
$address = '';
$searchMode = 'city';
$lat = null;
$lng = null;

// Send a search request to Google Places API for gyms in the specified city
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $searchMode = $_POST['search_mode'] ?? 'city';
    $activity = trim($_POST['activity'] ?? '');

    if ($searchMode === 'city' && !empty($_POST['city'])) {
        $city = trim($_POST['city']);
        logAction($conn, $_SESSION['user_id'], 'search_gym', 'Searched gyms in ' . $city . ' with activity: ' . $activity);
        $conn->commit();

        $query = urlencode("חדר כושר ב" . $city . " " . $activity);
        $url = "https://maps.googleapis.com/maps/api/place/textsearch/json?query=$query&key=$googleApiKey&language=iw";
        $response = file_get_contents($url);
        if ($response !== false) {
            $data = json_decode($response, true);
            if ($data['status'] === 'OK') {
                $searchResults = $data['results'];
            } else {
                $errorMessage = 'לא נמצאו חדרי כושר באזור שהוזן.';
            }
        } else {
            $errorMessage = 'שגיאה בשליחת הבקשה ל-API של Google.';
        }
    }

    if ($searchMode === 'address' && !empty($_POST['address'])) {
        $address = trim($_POST['address']);
        logAction($conn, $_SESSION['user_id'], 'search_gym', 'Searched gyms near address: ' . $address . ' with activity: ' . $activity);
        $conn->commit();

        $geoUrl = "https://maps.googleapis.com/maps/api/geocode/json?address=" . urlencode($address) . "&key=$googleApiKey";
        $geoResponse = file_get_contents($geoUrl);
        if ($geoResponse !== false) {
            $geoData = json_decode($geoResponse, true);
            if ($geoData['status'] === 'OK') {
                $location = $geoData['results'][0]['geometry']['location'];
                $lat = $location['lat'];
                $lng = $location['lng'];

                $nearbyUrl = "https://maps.googleapis.com/maps/api/place/nearbysearch/json?location=$lat,$lng&radius=2000&type=gym&keyword=" . urlencode($activity) . "&key=$googleApiKey&language=iw";
                $nearbyResponse = file_get_contents($nearbyUrl);
                if ($nearbyResponse !== false) {
                    $nearbyData = json_decode($nearbyResponse, true);
                    if ($nearbyData['status'] === 'OK') {
                        $searchResults = $nearbyData['results'];
                    } else {
                        $errorMessage = 'לא נמצאו חדרי כושר סמוכים לכתובת שהוזנה.';
                    }
                }
            } else {
                $errorMessage = 'הכתובת לא זוהתה. אנא בדקי ונסי שוב.';
            }
        }
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

  <form method="POST" class="text-center mb-5">
    <div class="form-check form-check-inline">
      <input class="form-check-input" type="radio" name="search_mode" id="modeCity" value="city" <?= $searchMode === 'city' ? 'checked' : '' ?>>
      <label class="form-check-label" for="modeCity">חיפוש לפי עיר</label>
    </div>
    <div class="form-check form-check-inline">
      <input class="form-check-input" type="radio" name="search_mode" id="modeAddress" value="address" <?= $searchMode === 'address' ? 'checked' : '' ?>>
      <label class="form-check-label" for="modeAddress">חיפוש חדר כושר בקרבת כתובת</label>
    </div>

    <div class="input-group mx-auto mt-3 mb-2" style="max-width: 400px;">
      <input type="text" name="city" class="form-control" placeholder="הכניסי עיר (למשל: תל אביב)" value="<?= htmlspecialchars($city) ?>">
    </div>

    <div class="input-group mx-auto mb-2" style="max-width: 400px;">
      <input type="text" name="address" class="form-control" placeholder="הכניסי כתובת מדויקת (למשל: הלוחמים 4 הוד השרון)" value="<?= htmlspecialchars($address) ?>">
    </div>

    <div class="input-group mx-auto mb-3" style="max-width: 400px;">
      <input type="text" name="activity" class="form-control" placeholder="סוג פעילות (יוגה, פילאטיס וכו׳)" value="<?= htmlspecialchars($activity) ?>">
    </div>

    <button type="submit" class="btn btn-primary">חיפוש</button>
  </form>

  <?php if (!empty($errorMessage)): ?>
    <div class="alert alert-danger text-center">😔 <?= htmlspecialchars($errorMessage) ?></div>
  <?php endif; ?>

  <?php if (!empty($searchResults)): ?>
    <h4 class="text-center mb-4 text-success">🏋️‍♀️ אלו חדרי הכושר שמצאנו עבורך:</h4>
    <div class="row row-cols-1 row-cols-md-2 g-4">
      <?php foreach ($searchResults as $gym): ?>
      <?php
        $gymLat = $gym['geometry']['location']['lat'] ?? null;
        $gymLng = $gym['geometry']['location']['lng'] ?? null;
        $distanceKm = ($searchMode === 'address' && $lat && $lng && $gymLat && $gymLng)
            ? calculateDistanceKm($lat, $lng, $gymLat, $gymLng)
            : null;
      ?>
        <div class="col">
          <div class="card h-100 shadow-sm">
            <div class="card-body">
              <h5 class="card-title">🏋️ <?= htmlspecialchars($gym['name']) ?></h5>
              <p class="card-text">📍 <?= htmlspecialchars($gym['vicinity'] ?? $gym['formatted_address']) ?></p>
              <?php if ($distanceKm !== null): ?>
                <p class="card-text">📏 מרחק מהכתובת: כ־<?= $distanceKm ?> ק"מ</p>
              <?php endif; ?>
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

<script>
  function toggleFields() {
    const mode = document.querySelector('input[name="search_mode"]:checked').value;
    const cityInput = document.querySelector('input[name="city"]');
    const addressInput = document.querySelector('input[name="address"]');

    if (mode === 'city') {
      cityInput.disabled = false;
      addressInput.disabled = true;
    } else {
      cityInput.disabled = true;
      addressInput.disabled = false;
    }
  }

  document.querySelectorAll('input[name="search_mode"]').forEach(radio => {
    radio.addEventListener('change', toggleFields);
  });

  window.addEventListener('DOMContentLoaded', toggleFields);
</script>


</body>
</html>
