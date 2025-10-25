<?php
session_start();
require_once('db_config.php');

$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
if ($booking_id <= 0) die("Invalid booking ID");

// Fetch booking
$stmt = $conn->prepare("SELECT * FROM bookings WHERE id=? AND status='active'");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$res = $stmt->get_result();
$booking = $res->fetch_assoc();
$stmt->close();

if (!$booking) {
    die("Booking not found or already expired");
}

// If user submits new end time
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $extra_hours = (int)$_POST['extra_hours'];
    if ($extra_hours <= 0) die("Invalid time extension");

    $old_end = strtotime($booking['end_time']);
    $new_end = date("Y-m-d H:i:s", $old_end + ($extra_hours * 3600));

    // Calculate extra cost
    $rate = ($booking['vehicle_type'] === '2W') ? 30 : 60;
    if ($booking['ev'] === 'yes') $rate += 10;
    $extra_price = $rate * $extra_hours;

    // Update booking
    $stmt = $conn->prepare("UPDATE bookings SET end_time=?, price=price+? WHERE id=?");
    $stmt->bind_param("sii", $new_end, $extra_price, $booking_id);
    if ($stmt->execute()) {
        echo "<script>alert('Booking extended successfully! Extra ₹$extra_price charged.');window.location.href='user_dashboard.php';</script>";
    } else {
        echo "Error extending booking.";
    }
    $stmt->close();
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Extend Booking</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
</head>
<body class="bg-light">
<div class="container py-4">
  <h4 class="mb-3">Extend Booking</h4>
  <div class="card p-3 shadow-sm">
    <p><strong>Slot:</strong> <?= htmlspecialchars($booking['slot_number']) ?></p>
    <p><strong>Current End Time:</strong> <?= htmlspecialchars($booking['end_time']) ?></p>
    <form method="post">
      <label class="form-label">Add Extra Hours</label>
      <input type="number" name="extra_hours" min="1" max="24" class="form-control" required>
      <button class="btn btn-primary mt-3" type="submit">Extend</button>
      <a href="user_dashboard.php" class="btn btn-secondary mt-3">Cancel</a>
    </form>
  </div>
</div>
</body>
</html>
