<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in and is an owner
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'owner') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Validate input
if (!isset($_POST['floor'])) {
    echo json_encode(['success' => false, 'message' => 'Missing floor']);
    exit();
}

$owner_id = (int)($_SESSION['owner_id'] ?? 0);
$floor = (int)$_POST['floor'];

if ($owner_id <= 0 || $floor <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit();
}

require_once 'db_config.php';

// Validate the floor number is within owner's floors
$check = $conn->prepare("SELECT COALESCE(floors,1) AS floors FROM parking_requests WHERE owner_id = ? AND status='approved'");
$check->bind_param("i", $owner_id);
$check->execute();
$res = $check->get_result();

if ($res->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Owner parking not found or not approved']);
    exit();
}

$maxFloors = (int)$res->fetch_assoc()['floors'];
if ($floor > $maxFloors) {
    echo json_encode(['success' => false, 'message' => 'Floor number exceeds maximum floors']);
    exit();
}

// Read current status and toggle it
$q = $conn->prepare("SELECT status FROM floor_status WHERE owner_id = ? AND floor_number = ?");
$q->bind_param("ii", $owner_id, $floor);
$q->execute();
$r = $q->get_result();

if ($r->num_rows > 0) {
    // Update existing status
    $current = $r->fetch_assoc()['status'];
    $newStatus = ($current === 'open') ? 'closed' : 'open';
    
    $u = $conn->prepare("UPDATE floor_status SET status = ? WHERE owner_id = ? AND floor_number = ?");
    $u->bind_param("sii", $newStatus, $owner_id, $floor);
    $u->execute();
} else {
    // Insert new status record (default to closed since we're marking for maintenance)
    $newStatus = 'closed';
    $i = $conn->prepare("INSERT INTO floor_status (owner_id, floor_number, status) VALUES (?, ?, ?)");
    $i->bind_param("iis", $owner_id, $floor, $newStatus);
    $i->execute();
}

echo json_encode(['success' => true, 'newStatus' => $newStatus]);
?>
