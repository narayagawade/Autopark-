<?php
require_once('db_config.php');

// Read & validate inputs
$parking_id   = isset($_POST['parking_id']) ? (int)$_POST['parking_id'] : 0;
$vehicle_no   = isset($_POST['vehicle_no']) ? trim($_POST['vehicle_no']) : '';
$vehicle_type = isset($_POST['vehicle_type']) ? strtoupper($_POST['vehicle_type']) : '2W';
$ev_support   = isset($_POST['ev']) ? $_POST['ev'] : 'no';
$membership   = isset($_POST['membership']) ? $_POST['membership'] : 'none';
$start_time   = isset($_POST['start_time']) ? $_POST['start_time'] : '';
$end_time     = isset($_POST['end_time']) ? $_POST['end_time'] : '';

if ($parking_id <= 0 || !$vehicle_no || !$start_time || !$end_time) {
  die("Invalid input");
}
if (!in_array($vehicle_type, ['2W','4W'])) die("Invalid vehicle type");
if (!in_array($ev_support, ['yes','no'])) die("Invalid EV option");
if (!in_array($membership, ['none','7days','15days','30days'])) die("Invalid membership");

// Check floor availability
function isFloorAvailable($conn, $owner_id, $floor) {
  $sql = "SELECT status FROM floor_status WHERE owner_id = ? AND floor_number = ?";
  $st = $conn->prepare($sql);
  $st->bind_param("ii", $owner_id, $floor);
  $st->execute();
  $result = $st->get_result();
  if ($result->num_rows > 0) {
    $status = $result->fetch_assoc()['status'];
    return $status === 'open';
  }
  return true; // If no status set, consider floor open
}

// Fetch parking info
$st = $conn->prepare("SELECT p.id, p.parking_name, p.address, p.ev_support, p.slot_2w, p.slot_4w, 
                             COALESCE(p.floors,1) AS floors, p.owner_id 
                      FROM parking_requests p 
                      WHERE p.id=? AND p.status='approved'");
$st->bind_param("i", $parking_id);
$st->execute();
$res = $st->get_result();
$parking = $res->fetch_assoc();
$st->close();
if (!$parking) { die("Parking not found"); }

// Hours calc
function hoursDiffCeil($start, $end) {
  $s = strtotime($start);
  $e = strtotime($end);
  if ($e <= $s) return 0;
  $mins = ($e - $s) / 60;
  return (int)ceil($mins / 60.0);
}
// Price calc with membership discount
$hours = hoursDiffCeil($start_time, $end_time);
if ($hours <= 0) die("End time must be after start time");

$base   = ($vehicle_type === '2w') ? 20 : 50;  // ₹/hr
$ev_add = ($ev_support === 'yes') ? 10 : 0;    // ₹/hr
$price  = ($base + $ev_add) * $hours;

// Membership discount
$discount = 0;
if ($membership === '15days') {
    $discount = 0.10 * $price; // 10% off
} elseif ($membership === '30days') {
    $discount = 0.20 * $price; // 20% off
}
$final_price = $price - $discount;

// Modified slot checking to consider floor status
function isSlotFree($conn, $parking_id, $owner_id, $slot_id, $start, $end) {
  // Extract floor number from slot ID (format: F1-2W-1)
  $floor = (int)substr($slot_id, 1, 1);
  
  // First check if floor is available
  if (!isFloorAvailable($conn, $owner_id, $floor)) {
    return false;
  }
  
  // Then check if slot is free
  $sql = "SELECT id FROM bookings 
          WHERE parking_id=? AND slot_number=? AND status='active'
            AND start_time < ? AND end_time > ?";
  $st = $conn->prepare($sql);
  $st->bind_param("isss", $parking_id, $slot_id, $end, $start);
  $st->execute();
  $st->store_result();
  $busy = $st->num_rows > 0;
  $st->close();
  return !$busy;
}

function pickFreeSlot($conn, $parking, $vehicle_type, $start, $end) {
  $floors = (int)$parking['floors'];
  $slot2  = (int)$parking['slot_2w'];
  $slot4  = (int)$parking['slot_4w'];
  
  if ($vehicle_type === '2W') {
    for ($f=1; $f <= $floors; $f++) {
      for ($i=1; $i <= $slot2; $i++) {
        $id = "F{$f}-2W-{$i}";
        if (isSlotFree($conn, $parking['id'], $parking['owner_id'], $id, $start, $end)) {
          return [$id, $f];
        }
      }
    }
  } else {
    for ($f=1; $f <= $floors; $f++) {
      for ($i=1; $i <= $slot4; $i++) {
        $id = "F{$f}-4W-{$i}";
        if (isSlotFree($conn, $parking['id'], $parking['owner_id'], $id, $start, $end)) {
          return [$id, $f];
        }
      }
    }
  }
  return [null, null];
}

[$slot_number, $floor] = pickFreeSlot($conn, $parking, $vehicle_type, $start_time, $end_time);
$hasAvailable = $slot_number !== null;
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Confirm Booking</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
</head>
<body class="bg-light">
<div class="container py-4">
  <h4 class="mb-3">Booking Confirmation</h4>

  <?php if (!$hasAvailable): ?>
    <div class="alert alert-danger">
      Sorry, no slot is available for the selected time window.
    </div>
    <a class="btn btn-secondary" href="user_book.php?parking_id=<?= (int)$parking_id ?>">Back</a>
  <?php else: ?>
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="mb-1"><?= htmlspecialchars($parking['parking_name']) ?></h5>
        <div class="text-muted mb-2"><?= htmlspecialchars($parking['address']) ?></div>

        <div class="row">
          <div class="col-md-6">
            <p class="mb-1"><strong>Slot:</strong> <?= htmlspecialchars($slot_number) ?> (Floor <?= (int)$floor ?>)</p>
            <p class="mb-1"><strong>Vehicle:</strong> <?= $vehicle_type === '2W' ? '2-Wheeler' : '4-Wheeler' ?></p>
            <p class="mb-1"><strong>EV:</strong> <?= $ev_support === 'yes' ? 'Yes (+₹10/hr)' : 'No' ?></p>
            <p class="mb-1"><strong>Membership:</strong> <?= htmlspecialchars($membership) ?></p>
          </div>
          <div class="col-md-6">
            <p class="mb-1"><strong>Start:</strong> <?= htmlspecialchars($start_time) ?></p>
            <p class="mb-1"><strong>End:</strong> <?= htmlspecialchars($end_time) ?></p>
            <p class="mb-1"><strong>Hours (rounded up):</strong> <?= (int)$hours ?></p>
            <p class="mb-1"><strong>Price:</strong> ₹<?= (int)$price ?></p>
          </div>
        </div>

        <form class="mt-3" method="post" action="user_payment.php">
          <input type="hidden" name="parking_id" value="<?= (int)$parking_id ?>">
          <input type="hidden" name="vehicle_no" value="<?= htmlspecialchars($vehicle_no) ?>">
          <input type="hidden" name="vehicle_type" value="<?= htmlspecialchars($vehicle_type) ?>">
          <input type="hidden" name="ev" value="<?= htmlspecialchars($ev_support) ?>">
          <input type="hidden" name="membership" value="<?= htmlspecialchars($membership) ?>">
          <input type="hidden" name="start_time" value="<?= htmlspecialchars($start_time) ?>">
          <input type="hidden" name="end_time" value="<?= htmlspecialchars($end_time) ?>">
          <input type="hidden" name="slot_number" value="<?= htmlspecialchars($slot_number) ?>">
          <input type="hidden" name="price" value="<?= (int)$price ?>">

          <button class="btn btn-success" type="submit">Pay & Book</button>
          <a class="btn btn-secondary ms-2" href="user_book.php?parking_id=<?= (int)$parking_id ?>">Back</a>
        </form>
      </div>
    </div>
  <?php endif; ?>
</div>
</body>
</html>
