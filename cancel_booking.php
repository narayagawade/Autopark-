<?php
session_start();
require_once("db_config.php");

// ✅ Check login (at least email should be in session to confirm login)
if (!isset($_SESSION['email'])) {
    die("<p style='color:red;font-weight:bold;'>Access denied. Please login first.</p>");
}

// If cancel request is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehicle_no = trim($_POST['vehicle_no']);

    if (empty($vehicle_no)) {
        die("<p style='color:red;font-weight:bold;'>Vehicle number is required.</p>");
    }

    // Fetch active booking by vehicle number
    $sql = "SELECT * FROM bookings WHERE vehicle_no=? AND status='active' LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $vehicle_no);
    $stmt->execute();
    $res = $stmt->get_result();
    $booking = $res->fetch_assoc();
    $stmt->close();

    if (!$booking) {
        echo "<p style='color:red;font-weight:bold;'>No active booking found for this vehicle number.</p>";
    } else {
        // Penalty refund logic: return 50%
        $refund = $booking['price'] * 0.5;

        // Cancel booking
        $sql = "UPDATE bookings SET status='cancelled' WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $booking['id']);
        $stmt->execute();
        $stmt->close();

        echo "<p style='color:green;font-weight:bold;'>Booking cancelled successfully. Refund: ₹{$refund}</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Cancel Booking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
</head>
<body class="bg-light">
<div class="container py-4">
    <h3 class="mb-3">Cancel Booking</h3>
    <form method="post" class="card p-3 shadow-sm">
        <div class="mb-3">
            <label class="form-label">Enter Vehicle Number</label>
            <input type="text" name="vehicle_no" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-danger">Cancel Booking</button>
    </form>
</div>
</body>
</html>
