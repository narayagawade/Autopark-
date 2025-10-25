<?php
require_once('db_config.php');

$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($booking_id <= 0) die("Invalid booking ID");

// Auto-expire booking
$stmt = $conn->prepare("UPDATE bookings SET status='expired' WHERE id=? AND status='active'");
$stmt->bind_param("i", $booking_id);
if ($stmt->execute()) {
    echo "Booking marked as expired.";
} else {
    echo "Error updating booking.";
}
$stmt->close();
?>
