<?php
// Start or resume the session to access user-specific data
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'doctor') {
    header("Location: ../general/index.html");
    exit;
}

$patientId = $_SESSION['current_patient'] ?? null;
if (!$patientId) {
    header("Location: select_patient_doctor.php");
    exit;
}

$host = "localhost";
$dbname = "maiav_sugarSense";
$username = "maiav_sugarSense";
$password = "MaiYuvalMichal!Sugar@";
$conn = new mysqli($host, $username, $password, $dbname);
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

$timeGroups = [
    'morning' => ["בצום (לפני ארוחת בוקר)", "שעתיים אחרי ארוחת בוקר"],
    'noon' => ["לפני ארוחת צהריים", "שעתיים אחרי ארוחת צהריים"],
    'evening' => ["לפני ארוחת ערב", "שעתיים אחרי ארוחת ערב", "לפני שינה"]
];

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
$grouped = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
    $grouped[$row['time_slot']][] = $row;
}
$stmt->close();
$conn->close();

$canForecast = count($data) >= 3;
$numForecastDays = 3;

if ($canForecast) {
    foreach ($grouped as $slot => $readings) {
        if (count($readings) < 3) continue;

        $totalDelta = 0;
        for ($i = 1; $i < count($readings); $i++) {
            $totalDelta += $readings[$i]['glucose_level'] - $readings[$i - 1]['glucose_level'];
        }
        $avgDelta = $totalDelta / (count($readings) - 1);

        $lastDate = new DateTime(end($readings)['reading_date']);
        $lastValue = end($readings)['glucose_level'];

        for ($i = 1; $i <= $numForecastDays; $i++) {
            $forecastDate = $lastDate->modify('+1 day')->format('Y-m-d');
            $lastValue += $avgDelta;
            $data[] = [
                'reading_date' => $forecastDate,
                'glucose_level' => round($lastValue, 1),
                'is_forecast' => true,
                'is_normal' => null,
                'time_slot' => "$slot"
            ];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>🧠 תחזית לרמות סוכר</title>
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
        <img src="<?= htmlspecialchars($patientImage) ?>" alt="תמונת מטופלת" width="40" height="40" class="rounded-circle border border-secondary shadow-sm mb-1">
        <span class="fw-bold small"><?= htmlspecialchars($patientName) ?></span>
      </div>

      <div class="d-flex align-items-center gap-3">
        <a class="btn btn-outline-dark" href="../general/logout.php">🚪 התנתקות</a>
        <a class="navbar-brand me-3" href="select_patient_doctor.php">
          <img src="../Images/logo.jpg" alt="לוגו" width="50" height="50" class="rounded-circle border border-2 border-secondary shadow-sm">
        </a>
      </div>
    </div>
  </div>
</nav>
</header>

<main class="container py-4 flex-grow-1">
  <div class="row justify-content-between align-items-center mb-4">
    <div class="col-auto">
      <a href="dashboard_doctor.php?patient_id=<?= $patientId ?>" class="btn btn-outline-secondary btn-sm">🔙 חזרה</a>
    </div>
    <div class="col text-center">
      <h2 class="mb-0">🧠 תחזית רמות סוכר</h2>
    </div>
    <div class="col-auto"></div>
  </div>

  <div class="alert alert-primary text-center" dir="rtl">
    <h5 class="fw-bold">🧠 איך פועלת התחזית?</h5>
    התחזית מבוססת על ממוצע שינוי יומי ברמות הסוכר האחרונות של המטופלת.
    המערכת מחשבת את קצב העלייה או הירידה ומשליכה אותו קדימה לימים הבאים.
    <br>
    ⚠️ שים.י לב: מדובר באומדן מגמתי בלבד ולא בחיזוי רפואי מוסמך.
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
        <option value="noon" <?= $timeGroup === 'noon' ? 'selected' : '' ?>>☀️ צהריים</option>
        <option value="evening" <?= $timeGroup === 'evening' ? 'selected' : '' ?>>🌙 ערב</option>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label invisible d-block">כפתור סינון</label>
      <div class="d-grid gap-2">
        <button type="submit" class="btn btn-primary">🔍 סינון</button>
        <a href="glucose_forecast_doctor.php" class="btn btn-outline-secondary">🔄 איפוס סינון</a>
      </div>
    </div>
  </form>

  <?php if ($canForecast): ?>
    <div style="overflow-x: auto;">
      <canvas id="forecastChart" height="300" style="min-width: 600px;"></canvas>
    </div>
  <?php else: ?>
    <div class="alert alert-warning text-center">
      ⚠️ אין מספיק נתוני ניטור כדי לחשב תחזית. יש צורך לפחות ב־3 מדידות קודמות.
    </div>
  <?php endif; ?>
</main>

<footer class="text-white text-center py-4 mt-auto" style="background-color: #bcbcbc;">
  <div class="d-flex justify-content-center gap-3 mb-2">
    <a href="#" class="text-black"><i class="bi bi-facebook fs-5"></i></a>
    <a href="#" class="text-black"><i class="bi bi-instagram fs-5"></i></a>
    <a href="#" class="text-black"><i class="bi bi-twitter fs-5"></i></a>
  </div>
  <p class="mb-0 text-black">© 2025 SugarSense. כל הזכויות שמורות.</p>
</footer>

<?php if ($canForecast): ?>
<script>
const ctx = document.getElementById('forecastChart').getContext('2d');
const labels = <?= json_encode(array_column($data, 'reading_date')) ?>;
const levels = <?= json_encode(array_map(fn($r) => $r['glucose_level'], $data)) ?>;
const timeSlots = <?= json_encode(array_column($data, 'time_slot')) ?>;
const isForecast = <?= json_encode(array_map(fn($r) => $r['is_forecast'] ?? false, $data)) ?>;
const isNormal = <?= json_encode(array_map(fn($r) => $r['is_normal'] ?? null, $data)) ?>;

const pointColors = isForecast.map((forecast, idx) => {
  if (forecast) return 'blue';
  return isNormal[idx] === true ? 'green' : 'red';
});

const chartData = {
  labels: labels,
  datasets: [{
    label: 'רמת סוכר (כולל תחזית)',
    data: levels,
    backgroundColor: pointColors,
    borderColor: 'black',
    borderWidth: 2,
    pointRadius: 5,
    pointHoverRadius: 7,
    pointBackgroundColor: pointColors,
    tension: 0.3,
    fill: false,
  }]
};

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
            const value = context.formattedValue;
            const slot = timeSlots[idx] || 'לא ידוע';
            return isForecast[idx]
              ? `🧠 תחזית – ${slot}: ${value} מ"ג/ד"ל`
              : `🕒 ${slot}: ${value} מ"ג/ד"ל`;
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
          text: 'תאריך'
        }
      }
    }
  }
});
</script>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>