<?php
include("db_config.php");

$id = $_GET['id'] ?? '';
$status = $_GET['status'] ?? '';

if (!in_array($status, ['approved', 'rejected'], true) || !ctype_digit($id)) {
    die("❌ Invalid status or ID");
}

$stmt = $conn->prepare("UPDATE parking_requests SET status = ? WHERE id = ?");
$stmt->bind_param("si", $status, $id);

if ($stmt->execute()) {
    header("Location: admin_dashboard.php?updated=1");
    exit;
} else {
    echo "⚠️ Error updating request.";
}

$stmt->close();
$conn->close();
?>
