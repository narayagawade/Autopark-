<?php
require_once("db_config.php");
session_start();

header("Content-Type: application/json");

// Check data
if (!isset($_POST['slot_number']) || !isset($_POST['parking_id']) || !isset($_POST['duration'])) {
    echo json_encode(["success" => false, "message" => "Invalid data"]);
    exit;
}

$slot_number = $_POST['slot_number'];
$parking_id = (int) $_POST['parking_id'];
$duration   = (int) $_POST['duration'];

// validate
if (empty($slot_number) || $parking_id <= 0 || $duration <= 0) {
    echo json_encode(["success" => false, "message" => "Invalid input"]);
    exit;
}

// Insert offline booking
$sql = "INSERT INTO bookings 
        (user_id, parking_id, slot_number, vehicle_no, vehicle_type, ev, start_time, end_time, membership, price, status, booked_by) 
        VALUES (0, ?, ?, 'OFFLINE', 'unknown', 'no', NOW(), DATE_ADD(NOW(), INTERVAL ? HOUR), 'none', 0, 'active', 'owner')";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(["success" => false, "message" => "Database prepare error: " . $conn->error]);
    exit;
}

$stmt->bind_param("isi", $parking_id, $slot_number, $duration);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Offline slot booked for {$duration} hour(s)."]);
} else {
    echo json_encode(["success" => false, "message" => "Database error: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
