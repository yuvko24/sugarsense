<?php
// Start or resume the session to access user-specific data
session_start();

// Prevent browser from caching the page (ensures fresh data is always loaded)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Ensure the user is logged in as a doctor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'doctor') {
    header("Location: ../general/index.html");
    exit;
}

$patientId = $_SESSION['current_patient'] ?? null;
if (!$patientId) {
    header("Location: select_patient_doctor.php");
    exit;
}

// Connect to the SugarSense database and stop execution if connection fails
require_once '../general/config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$patientName = "לא ידוע";
$patientImage = "../Images/default.jpeg";

require_once '../general/functions.php';

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

$fromDate = $_GET['from_date'] ?? '';
$toDate = $_GET['to_date'] ?? '';
$timeGroup = $_GET['time_group'] ?? '';

$query = "SELECT reading_date, time_slot, glucose_level, is_normal FROM glucose_readings WHERE patient_id = ?";
$params = [$patientId];
$types = "i";

if (!empty($fromDate)) {
    $query .= " AND reading_date >= ?";
    $types .= "s";
    $params[] = $fromDate;
}

if (!empty($toDate)) {
    $query .= " AND reading_date <= ?";
    $types .= "s";
    $params[] = $toDate;
}

$timeGroups = [
    'morning' => ["בצום (לפני ארוחת בוקר)", "שעתיים אחרי ארוחת בוקר"],
    'noon'    => ["לפני ארוחת צהריים", "שעתיים אחרי ארוחת צהריים"],
    'evening' => ["לפני ארוחת ערב", "שעתיים אחרי ארוחת ערב", "לפני שינה"]
];

if (!empty($timeGroup) && isset($timeGroups[$timeGroup])) {
    $placeholders = implode(',', array_fill(0, count($timeGroups[$timeGroup]), '?'));
    $query .= " AND time_slot IN ($placeholders)";
    $types .= str_repeat('s', count($timeGroups[$timeGroup]));
    $params = array_merge($params, $timeGroups[$timeGroup]);
}

$query .= " ORDER BY reading_date ASC";
$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>📈 מגמות סוכר</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-light d-flex flex-column min-vh-100">

<header>
<nav class="navbar navbar-expand-lg navbar-light position-relative" style="background-color: #d3d3d3;" dir="rtl">
  <div class="container-fluid">
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
      aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse justify-content-between" id="navbarNav">
      <ul class="navbar-nav me-auto d-flex flex-row-reverse gap-3">
        <li class="nav-item"><a class="nav-link text-black" href="select_patient_doctor.php">🔁 החלפת מטופלת</a></li>
      </ul>

      <div class="position-absolute top-50 start-50 translate-middle d-none d-lg-flex flex-column align-items-center">
        <img src="<?= htmlspecialchars($patientImage) ?>" alt="תמונת מטופלת" width="40" height="40"
             class="rounded-circle border border-secondary shadow-sm mb-1">
        <span class="fw-bold small"><?= htmlspecialchars($patientName) ?></span>
      </div>

      <div class="d-flex align-items-center gap-3">
        <a class="btn btn-outline-dark" href="../general/logout.php">🚪 התנתקות</a>
        <a class="navbar-brand me-3" href="select_patient_doctor.php">
          <img src="../Images/logo.jpg" alt="לוגו" width="50" height="50"
               class="rounded-circle border border-2 border-secondary shadow-sm">
        </a>
      </div>
    </div>
  </div>
</nav>
</header>

<main class="container py-4 flex-grow-1">
  <div class="row justify-content-between align-items-center mb-4">
    <div class="col-auto">
      <a href="menu_doctor.php?type=glucose" class="btn btn-outline-secondary btn-sm">🔙 חזרה</a>
    </div>
    <div class="col text-center">
      <h2 class="mb-0">📈 גרף מגמות סוכר</h2>
    </div>
    <div class="col-auto"></div>
  </div>

  <div class="alert alert-info mt-4 text-center" dir="rtl">
    <h5 class="fw-bold">📚 הסבר על קבוצות:</h5>
    <strong>🌅 בוקר:</strong> צום, שעתיים אחרי ארוחת בוקר<br>
    <strong>🌞 צהריים:</strong> לפני ארוחת צהריים, שעתיים אחרי ארוחת צהריים<br>
    <strong>🌙 ערב:</strong> לפני ארוחת ערב, שעתיים אחרי ארוחת ערב, לפני שינה
  </div>

  <form method="get" class="row g-3 align-items-end text-end mb-4">
    <div class="col-md-3">
      <label for="from_date" class="form-label text-end d-block">תאריך התחלה</label>
      <input type="date" id="from_date" name="from_date" value="<?= htmlspecialchars($fromDate) ?>" class="form-control">
    </div>
    <div class="col-md-3">
      <label for="to_date" class="form-label text-end d-block">תאריך סיום</label>
      <input type="date" id="to_date" name="to_date" value="<?= htmlspecialchars($toDate ?? '') ?>" class="form-control">
    </div>
    <div class="col-md-3">
      <label for="time_group" class="form-label text-end d-block">קבוצת מדידה</label>
      <select id="time_group" name="time_group" class="form-select">
        <option value="">הצג הכל</option>
        <option value="morning" <?= $timeGroup === 'morning' ? 'selected' : '' ?>>🌅 בוקר</option>
        <option value="noon" <?= $timeGroup === 'noon' ? 'selected' : '' ?>>🌞 צהריים</option>
        <option value="evening" <?= $timeGroup === 'evening' ? 'selected' : '' ?>>🌙 ערב</option>
      </select>
    </div>
    <div class="col-md-3 text-end">
      <label class="form-label invisible d-block">פעולות</label>
      <div class="d-grid gap-2">
        <button type="submit" class="btn btn-primary">🔍 סינון</button>
        <a href="glucose_trends_doctor.php" class="btn btn-outline-secondary">🔄 איפוס סינון</a>
      </div>
    </div>
  </form>

  <div style="overflow-x: auto;">
    <canvas id="glucoseChart" height="300" style="min-width: 600px;"></canvas>
  </div>
</main>

<footer class="text-white text-center py-4 mt-auto" style="background-color: #bcbcbc;">
  <div class="d-flex justify-content-center gap-3 mb-2">
    <a href="#" class="text-black"><i class="bi bi-facebook fs-5"></i></a>
    <a href="#" class="text-black"><i class="bi bi-instagram fs-5"></i></a>
    <a href="#" class="text-black"><i class="bi bi-twitter fs-5"></i></a>
  </div>
  <p class="mb-0 text-black">© 2025 SugarSense. כל הזכויות שמורות.</p>
</footer>

<script>
const ctx = document.getElementById('glucoseChart').getContext('2d');
const chartData = {
  labels: <?= json_encode(array_column($data, 'reading_date')) ?>,
  datasets: [{
    label: 'רמת סוכר',
    data: <?= json_encode(array_map(fn($r) => $r['glucose_level'], $data)) ?>,
    backgroundColor: <?= json_encode(array_map(fn($r) => $r['is_normal'] ? 'green' : 'red', $data)) ?>,
    borderColor: 'black',
    borderWidth: 2,
    pointRadius: 5,
    pointHoverRadius: 7,
    pointBackgroundColor: <?= json_encode(array_map(fn($r) => $r['is_normal'] ? 'green' : 'red', $data)) ?>,
    tension: 0.3,
    fill: false,
  }]
};

const timeSlots = <?= json_encode(array_column($data, 'time_slot')) ?>;

new Chart(ctx, {
  type: 'line',
  data: chartData,
  options: {
    responsive: true,
    plugins: {
      tooltip: {
        callbacks: {
          label: function(context) {
            const idx = context.dataIndex;
            return '🕒 ' + timeSlots[idx] + ' — רמת סוכר: ' + context.formattedValue + ' מ"ג/ד"ל';
          }
        }
      },
      legend: {
        display: false
      }
    },
    scales: {
      y: {
        title: {
          display: true,
          text: 'רמת סוכר (מ"ג)'
        }
      },
      x: {
        title: {
          display: true,
          text: 'תאריך מדידה'
        },
        ticks: {
          callback: function(value, index, ticks) {
            const rawDate = this.getLabelForValue(value);
            const date = new Date(rawDate);
            if (isNaN(date)) return rawDate; 
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = date.getFullYear();
            return `${day}/${month}/${year}`;
          }
        }
      }
    }
  }
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>