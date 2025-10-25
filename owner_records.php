<?php
session_start();
require_once("db_config.php");

if (!isset($_SESSION['owner_id'])) {
    header("Location: login.php");
    exit;
}
$owner_id = (int)$_SESSION['owner_id'];

// Export to Excel
if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=owner_bookings.xls");

    echo "Booking ID\tParking Name\tUser Name\tVehicle No\tVehicle Type\tSlot\tStart Time\tEnd Time\tPrice\n";

    $sql = "SELECT b.id, p.parking_name, COALESCE(u.name,'(Owner/Offline)') AS user_name,
                   b.vehicle_no, b.vehicle_type, b.slot_number, b.start_time, b.end_time, b.price
            FROM bookings b
            JOIN parking_requests p ON b.parking_id = p.id
            LEFT JOIN users u ON b.user_id = u.id
            WHERE p.owner_id = ?
            ORDER BY b.start_time DESC";
    $st = $conn->prepare($sql);
    $st->bind_param("i", $owner_id);
    $st->execute();
    $result = $st->get_result();

    while ($row = $result->fetch_assoc()) {
        echo $row['id']."\t".$row['parking_name']."\t".$row['user_name']."\t".$row['vehicle_no']."\t".$row['vehicle_type']."\t".
             $row['slot_number']."\t".$row['start_time']."\t".$row['end_time']."\t".$row['price']."\n";
    }
    exit;
}

// Fetch for table view
$sql = "SELECT b.id, p.parking_name, COALESCE(u.name,'(Owner/Offline)') AS user_name,
               b.vehicle_no, b.vehicle_type, b.slot_number, b.start_time, b.end_time, b.price
        FROM bookings b
        JOIN parking_requests p ON b.parking_id = p.id
        LEFT JOIN users u ON b.user_id = u.id
        WHERE p.owner_id = ?
        ORDER BY b.start_time DESC";
$st = $conn->prepare($sql);
$st->bind_param("i", $owner_id);
$st->execute();
$result = $st->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Owner Booking Records</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">📑 Your Parking Booking Records</h3>
    <a href="owner_records.php?export=excel" class="btn btn-success btn-sm">
      <i class="bi bi-download"></i> Export to Excel
    </a>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
          <thead class="table-dark">
            <tr>
              <th>ID</th>
              <th>Parking Name</th>
              <th>User Name</th>
              <th>Vehicle No</th>
              <th>Type</th>
              <th>Slot</th>
              <th>Start Time</th>
              <th>End Time</th>
              <th>Price (₹)</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($result->num_rows > 0): ?>
              <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                  <td><?= htmlspecialchars($row['id']) ?></td>
                  <td><?= htmlspecialchars($row['parking_name']) ?></td>
                  <td><?= htmlspecialchars($row['user_name']) ?></td>
                  <td><?= htmlspecialchars($row['vehicle_no']) ?></td>
                  <td><?= $row['vehicle_type'] == '2w' ? '2-Wheeler' : '4-Wheeler' ?></td>
                  <td><?= htmlspecialchars($row['slot_number']) ?></td>
                  <td><?= htmlspecialchars($row['start_time']) ?></td>
                  <td><?= htmlspecialchars($row['end_time']) ?></td>
                  <td><?= htmlspecialchars($row['price']) ?></td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="9" class="text-center text-muted">No bookings found for your parking yet.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="mt-3">
    <a href="owner_dashboard.php" class="btn btn-secondary">⬅ Back to Dashboard</a>
  </div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</body>
</html>
