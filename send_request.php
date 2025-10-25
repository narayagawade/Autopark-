<?php
session_start();
include("db_config.php");

// Check if owner is logged in
if (!isset($_SESSION['owner_id']) || !isset($_SESSION['email'])) {
    die("Unauthorized access.");
}

$owner_id = $_SESSION['owner_id'];
$owner_email = $_SESSION['email'];

// Sanitize POST data
$parking_name = $_POST['parking_name'] ?? '';
$address = $_POST['address'] ?? '';
$mobile = $_POST['mobile'] ?? '';
$location = $_POST['location_search'] ?? '';
$latitude = $_POST['latitude'] ?? '';
$longitude = $_POST['longitude'] ?? '';
$ev_support = $_POST['ev'] ?? 'no';

// ✅ Collect number of floors
$floors = isset($_POST['floors']) ? (int)$_POST['floors'] : 1;

// ✅ Corrected vehicle checkbox handling
$supported_vehicles = isset($_POST['vehicle_types']) ? implode(",", $_POST['vehicle_types']) : '';

$slot_2w = $_POST['slot2'] ?? 0;
$slot_4w = $_POST['slot4'] ?? 0;

// Optional: Validate required fields
if (empty($parking_name) || empty($address) || empty($latitude) || empty($longitude)) {
    die("Please fill all required fields.");
}

// Check if request already exists for this owner
$check = $conn->prepare("SELECT id FROM parking_requests WHERE owner_id = ?");
$check->bind_param("i", $owner_id);
$check->execute();
$res = $check->get_result();

if ($res->num_rows > 0) {
    header("Location: owner_dashboard.php?already_requested=1");
    exit();
}

// ✅ Insert into database with floors included
$stmt = $conn->prepare("INSERT INTO parking_requests 
    (owner_id, owner_email, status, parking_name, address, mobile, location, latitude, longitude, ev_support, supported_vehicles, slot_2w, slot_4w, floors) 
    VALUES (?, ?, 'waiting', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$stmt->bind_param("isssssssssiii", 
    $owner_id, 
    $owner_email, 
    $parking_name, 
    $address, 
    $mobile, 
    $location, 
    $latitude, 
    $longitude, 
    $ev_support, 
    $supported_vehicles, 
    $slot_2w, 
    $slot_4w,
    $floors
);

if ($stmt->execute()) {
    header("Location: owner_dashboard.php?success=1");
    exit();
} else {
    echo "Failed to send request: " . $stmt->error;
}
?>
