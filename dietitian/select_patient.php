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

// Connect to the SugarSense database and stop execution if connection fails
require_once '../general/config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

require_once '../general/functions.php';

$dietitianId = $_SESSION['user_id'];

// Fetching dietitian's personal details
$dietitianImage = "Images/default.jpeg";
$dietitianName = "לא ידוע";
$stmt = $conn->prepare("SELECT full_name, profile_picture FROM users WHERE id = ?");
$stmt->bind_param("i", $dietitianId);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $row = $result->fetch_assoc()) {
    $dietitianName = $row['full_name'];
    if (!empty($row['profile_picture'])) {
        $dietitianImage = getValidImage($row['profile_picture']);
    }
}

// Retrieving patients
$patients = [];
$stmt = $conn->prepare("SELECT u.id, u.id_number, u.full_name, u.profile_picture FROM dietitian_patient dp JOIN users u ON dp.patient_id = u.id WHERE dp.dietitian_id = ?");
$stmt->bind_param("i", $dietitianId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $row['profile_picture'] = getValidImage($row['profile_picture']);
    $patients[] = $row;
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>בחירת מטופלת</title>
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
            <li class="nav-item"><a class="nav-link text-black" href="select_patient.php">🔁 החלפת מטופלת</a></li>
          </ul>

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
 <main class="container py-5">
  <div class="text-center mb-4">
    <img src="<?= htmlspecialchars($dietitianImage); ?>" alt="תמונת דיאטנית" width="100" height="100" class="rounded-circle border border-2 border-secondary shadow-sm mb-2">
    <h2>שלום, <?= htmlspecialchars($dietitianName); ?>! 🌸</h2>
  </div>

  <h4 class="text-center mb-4">בחרי את המטופלת שלך</h4>

  <div class="mb-4 text-center">
    <input type="text" id="searchInput" class="form-control w-50 mx-auto" placeholder="🔍 חיפוש לפי שם מטופלת...">
  </div>

  <div class="row justify-content-center" id="patientsContainer">
    <p id="noResultsMessage" class="text-center text-muted mt-4" style="display: none;">לא נמצאו מטופלות בשם שחיפשת 😕</p>
    <?php foreach ($patients as $patient): ?>
      <div class="col-md-4 mb-4 patient-card">
        <div class="card shadow-sm text-center p-3">
          <img src="<?= htmlspecialchars($patient['profile_picture']) ?>" alt="תמונת מטופלת" width="120" height="120" class="rounded-circle border border-2 border-secondary shadow-sm mx-auto mb-3">
          <h5 class="card-title mb-3">👤 <?= htmlspecialchars($patient['full_name']) ?></h5>
          <a href="dashboard_dietitian.php?patient_id=<?= $patient['id'] ?>" class="btn text-white" style="background-color: #f4b6bd;">כניסה לפרופיל</a>
        </div>
      </div>
    <?php endforeach; ?>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('searchInput').addEventListener('keyup', function() {
    const searchTerm = this.value.toLowerCase();
    const patientCards = document.querySelectorAll('.patient-card');
    let found = false;

    patientCards.forEach(card => {
        const name = card.querySelector('.card-title').textContent.toLowerCase();
        if (name.includes(searchTerm)) {
            card.style.display = '';
            found = true;
        } else {
            card.style.display = 'none';
        }
    });

    document.getElementById('noResultsMessage').style.display = found ? 'none' : 'block';
});
</script>
</body>
</html>
