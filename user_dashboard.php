<?php
session_start();
require_once('db_config.php');

// Very strong no-cache headers so browsers won't reuse a cached copy
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// Block access if not logged in (replace role check if needed)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit();
}

// ✅ Expired booking check & extend option
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $check = $conn->prepare("SELECT * FROM bookings WHERE user_id=? AND end_time <= NOW() AND status='active' LIMIT 1");
    $check->bind_param("i", $user_id);
    $check->execute();
    $res = $check->get_result();
    if ($res->num_rows > 0) {
        $booking = $res->fetch_assoc();
        echo "
<script>
  window.onload = function() {
    let confirmed = confirm('⏰ Your booking has ended. Do you want to extend?');
    if (confirmed) {
      window.location.href='extend_booking.php?booking_id={$booking['id']}';
    } else {
      // auto-expire after 5 minutes if no action
      setTimeout(function(){
        fetch('mark_expired.php?id={$booking['id']}');
      }, 300000); // 5 minutes = 300,000 ms
    }
  }
</script>
";

    }
    $check->close();
}

// ✅ Search Parking
$q = isset($_GET['q']) ? trim($_GET['q']) : '';

$sql = "
  SELECT id, parking_name, address, ev_support, slot_2w, slot_4w, COALESCE(floors,1) AS floors
  FROM parking_requests
  WHERE status='approved'
";
$params = [];
$types  = "";
if ($q !== "") {
  $sql .= " AND (parking_name LIKE ? OR address LIKE ?)";
  $like = "%$q%";
  $params[] = &$like;
  $params[] = &$like;
  $types .= "ss";
}

$stmt = $conn->prepare($sql);
if ($types) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$res = $stmt->get_result();
$parkings = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ✅ Count available slots
function availableCount($conn, $parking_id, $slot2, $slot4, $floors) {
  $active = [];
  $sql = "SELECT slot_number FROM bookings WHERE parking_id=? AND status='active' AND end_time > NOW()";
  $st = $conn->prepare($sql);
  $st->bind_param("i", $parking_id);
  $st->execute();
  $r = $st->get_result();
  while ($row = $r->fetch_assoc()) { $active[$row['slot_number']] = true; }
  $st->close();

  $free2 = 0; $free4 = 0;
  for ($f=1; $f <= (int)$floors; $f++) {
    for ($i=1; $i <= (int)$slot2; $i++) {
      $id = "F{$f}-2W-{$i}";
      if (!isset($active[$id])) $free2++;
    }
    for ($i=1; $i <= (int)$slot4; $i++) {
      $id = "F{$f}-4W-{$i}";
      if (!isset($active[$id])) $free4++;
    }
  }
  return [$free2, $free4];
}
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>User – Find Parking</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <style>
    body { background-color: #f8f9fa; }
    .sidebar { height: 100vh; background: #343a40; color: white; }
    .sidebar a { color: white; padding: 15px; display: block; text-decoration: none; }
    .sidebar a:hover { background-color: #495057; }
  </style>
  <script>
/*
  If the page is restored from the bfcache (event.persisted === true)
  or if the navigation type is back_forward, force a redirect so the
  server-side session check runs.
*/
window.addEventListener('pageshow', function(event) {
  try {
    var navEntries = performance.getEntriesByType && performance.getEntriesByType('navigation');
    var navType = (navEntries && navEntries[0] && navEntries[0].type) || (performance.navigation && performance.navigation.type);

    var isBackForward = (event.persisted) || (navType === 'back_forward') || (navType === 2);

    if (isBackForward) {
      // Use replace so there is no extra history entry
      window.location.replace('login.php');
    }
  } catch (e) {
    // fallback
    if (event.persisted) window.location.replace('login.php');
  }
});
</script>
</head>
<body class="bg-light">
<div class="d-flex">
  <!-- Sidebar -->
  <div class="sidebar p-3">
    <h4 class="text-center">Auto Park</h4>
    <a href="cancel_booking.php">Cancel Booking</a>
    <a href="logout.php" 
     style="display:block; margin-top:20px; padding:10px; background:#ff4c4c; color:#fff; text-align:center; border-radius:6px; text-decoration:none;">
    Logout
  </a>
  </div>

  <!-- Main Content -->
  <div class="container py-4">
    <h3 class="mb-3">Find Parking</h3>
    <form class="mb-4" method="get">
      <div class="input-group">
        <input type="text" name="q" class="form-control" placeholder="Search by location / address / name" value="<?= htmlspecialchars($q) ?>">
        <button class="btn btn-primary" type="submit">Search</button>
      </div>
    </form>

    <?php if (!$parkings): ?>
      <div class="alert alert-info">No parking found. Try another search.</div>
    <?php endif; ?>

    <div class="row g-3">
      <?php foreach ($parkings as $p):
        [$free2, $free4] = availableCount($conn, $p['id'], $p['slot_2w'], $p['slot_4w'], $p['floors']);
        $totalAvailable = $free2 + $free4;
      ?>
        <div class="col-12 col-md-6 col-lg-4">
          <div class="card shadow-sm">
            <div class="card-body">
              <h5 class="card-title mb-1"><?= htmlspecialchars($p['parking_name']) ?></h5>
              <div class="text-muted mb-2"><?= htmlspecialchars($p['address']) ?></div>
              <div class="mb-2"><strong>EV:</strong> <?= $p['ev_support']==='yes' ? 'Available' : 'Not Available' ?></div>
              <div class="mb-2">
                <span class="badge bg-primary">2W Free: <?= $free2 ?></span>
                <span class="badge bg-success ms-1">4W Free: <?= $free4 ?></span>
              </div>
              <?php if ($totalAvailable > 0): ?>
                <a class="btn btn-outline-primary" href="user_book.php?parking_id=<?= (int)$p['id'] ?>">Book Slot</a>
              <?php else: ?>
                <button class="btn btn-secondary" disabled>No slots available</button>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
</body>
</html>
