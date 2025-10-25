<?php
require_once('db_config.php');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { die("Invalid"); }

// Collect posted data
$parking_id   = (int)($_POST['parking_id'] ?? 0);
$vehicle_no   = trim($_POST['vehicle_no'] ?? '');
$vehicle_type = $_POST['vehicle_type'] ?? '2w';
$ev           = $_POST['ev'] ?? 'no';
$membership   = $_POST['membership'] ?? 'none';
$start_time   = $_POST['start_time'] ?? '';
$end_time     = $_POST['end_time'] ?? '';
$slot_number  = $_POST['slot_number'] ?? '';
$price        = (int)($_POST['price'] ?? 0);

if ($parking_id<=0 || !$vehicle_no || !$start_time || !$end_time || !$slot_number) {
  die("Invalid input");
}

// Get owner_id first
$owner_query = $conn->prepare("SELECT owner_id FROM parking_requests WHERE id = ?");
$owner_query->bind_param("i", $parking_id);
$owner_query->execute();
$owner_result = $owner_query->get_result();
$owner_data = $owner_result->fetch_assoc();
$owner_id = $owner_data['owner_id'];
$owner_query->close();

// Check if floor is still open
$floor = (int)substr($slot_number, 1, 1);
$floor_check = $conn->prepare("SELECT status FROM floor_status WHERE owner_id = ? AND floor_number = ?");
$floor_check->bind_param("ii", $owner_id, $floor);
$floor_check->execute();
$floor_result = $floor_check->get_result();

if ($floor_result->num_rows > 0) {
  $floor_status = $floor_result->fetch_assoc()['status'];
  if ($floor_status === 'closed') {
    die("Sorry, this floor is now closed for maintenance. Please choose another floor.");
  }
}
$floor_check->close();

// RE-CHECK availability just before final booking (avoid race)
$sql = "SELECT id FROM bookings 
        WHERE parking_id=? AND slot_number=? AND status='active'
          AND start_time < ? AND end_time > ?";
$st = $conn->prepare($sql);
$st->bind_param("isss", $parking_id, $slot_number, $end_time, $start_time);
$st->execute();
$st->store_result();
if ($st->num_rows > 0) {
  $st->close();
  die("Sorry, this slot just got booked by someone else. Please try another slot/time.");
}
$st->close();

// Simulate successful payment here (integrate gateway as needed)

// Insert booking as ONLINE
$booked_by = 'online';
$user_id = 0; // if you have user login, place the real user_id here

$ins = $conn->prepare("
  INSERT INTO bookings 
  (user_id, parking_id, slot_number, vehicle_no, vehicle_type, ev, start_time, end_time, membership, price, status, booked_by)
  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?)
");
$ins->bind_param(
  "iissssssiss",
  $user_id,
  $parking_id,
  $slot_number,
  $vehicle_no,
  $vehicle_type,
  $ev,
  $start_time,
  $end_time,
  $membership,
  $price,
  $booked_by
);
$ok = $ins->execute();
$err = $ins->error;
$ins->close();
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Payment Result</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
</head>
<body class="bg-light">
<div class="container py-5">
  <?php if ($ok): ?>
    <div class="alert alert-success">
      ✅ Slot booked successfully!
    </div>
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="mb-3">Your Booking Details</h5>
        <p class="mb-1"><strong>Parking ID:</strong> <?= (int)$parking_id ?></p>
        <p class="mb-1"><strong>Slot:</strong> <?= htmlspecialchars($slot_number) ?></p>
        <p class="mb-1"><strong>Time:</strong> <?= htmlspecialchars($start_time) ?> → <?= htmlspecialchars($end_time) ?></p>
        <p class="mb-1"><strong>Vehicle:</strong> <?= $vehicle_type==='2w'?'2-Wheeler':'4-Wheeler' ?> (EV: <?= $ev==='yes'?'Yes':'No' ?>)</p>
        <p class="mb-1"><strong>Membership:</strong> <?= htmlspecialchars($membership) ?></p>
        <p class="mb-1"><strong>Paid:</strong> ₹<?= (int)$price ?></p>
      </div>
    </div>
    <a href="user_dashboard.php" class="btn btn-primary mt-3">Done</a>
  <?php else: ?>
    <div class="alert alert-danger">
      Booking failed: <?= htmlspecialchars($err) ?>
    </div>
    <a href="user_dashboard.php" class="btn btn-secondary mt-2">Back</a>
  <?php endif; ?>
</div>
</body>
</html>
