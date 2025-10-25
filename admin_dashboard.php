<?php
session_start();
include("db_config.php");

// Count stats
$totalRequests = $conn->query("SELECT COUNT(*) AS total FROM parking_requests")->fetch_assoc()['total'];
$approvedRequests = $conn->query("SELECT COUNT(*) AS total FROM parking_requests WHERE status='approved'")->fetch_assoc()['total'];
$pendingRequests = $conn->query("SELECT COUNT(*) AS total FROM parking_requests WHERE status='waiting'")->fetch_assoc()['total'];

// Fetch parking requests
$requests = $conn->query("SELECT * FROM parking_requests ORDER BY created_at DESC");

// Fetch issues/messages
$user_issues = $conn->query("SELECT * FROM issues WHERE sender_role='user' ORDER BY created_at DESC");
$owner_issues = $conn->query("SELECT * FROM issues WHERE sender_role='owner' ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard - Auto Park</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container py-4">

    <!-- ✅ Added Logout Button -->
    <div class="d-flex justify-content-end mb-3">
        <a href="logout.php" class="btn btn-danger">Logout</a>
    </div>

    <h2 class="mb-4 text-center">Admin Dashboard</h2>

    <!-- Stats -->
    <div class="row mb-4 text-white">
        <div class="col-md-4">
            <div class="bg-primary p-3 rounded text-center">Total Requests<br><strong><?= $totalRequests ?></strong></div>
        </div>
        <div class="col-md-4">
            <div class="bg-success p-3 rounded text-center">Approved<br><strong><?= $approvedRequests ?></strong></div>
        </div>
        <div class="col-md-4">
            <div class="bg-warning p-3 rounded text-center">Pending<br><strong><?= $pendingRequests ?></strong></div>
        </div>
    </div>

    <!-- Requests List -->
    <h4>Owner Parking Requests</h4>
    <div class="table-responsive mb-5">
        <table class="table table-bordered table-hover">
            <thead class="table-dark">
                <tr>
                    <th>Owner Email</th>
                    <th>Parking Name</th>
                    <th>Location</th>
                    <th>Vehicles</th>
                    <th>EV</th>
                    <th>2W Slots</th>
                    <th>4W Slots</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $requests->fetch_assoc()) { ?>
                <tr>
                    <td><?= $row['owner_email'] ?></td>
                    <td><?= $row['parking_name'] ?></td>
                    <td><?= $row['location'] ?></td>
                    <td><?= $row['supported_vehicles'] ?></td>
                    <td><?= $row['ev_support'] ?></td>
                    <td><?= $row['slot_2w'] ?></td>
                    <td><?= $row['slot_4w'] ?></td>
                    <td><span class="badge bg-<?= $row['status'] == 'approved' ? 'success' : ($row['status'] == 'waiting' ? 'warning' : 'danger') ?>">
                        <?= ucfirst($row['status']) ?></span>
                    </td>
                    <td>
                        <?php if ($row['status'] == 'waiting') { ?>
                            <a href="update_status.php?id=<?= $row['id'] ?>&status=approved" class="btn btn-sm btn-success">Approve</a>
                            <a href="update_status.php?id=<?= $row['id'] ?>&status=rejected" class="btn btn-sm btn-danger">Reject</a>
                        <?php } else { echo "-"; } ?>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <!-- Owner Details Section -->
    <h4>Owner Details (Special Block)</h4>
    <div class="mb-5">
        <?php mysqli_data_seek($requests, 0); while ($row = $requests->fetch_assoc()) { ?>
            <div class="card mb-3">
                <div class="card-body">
                    <h5><?= $row['parking_name'] ?> (<?= $row['owner_email'] ?>)</h5>
                    <p><strong>Address:</strong> <?= $row['address'] ?><br>
                    <strong>Mobile:</strong> <?= $row['mobile'] ?><br>
                    <strong>Location:</strong> <?= $row['location'] ?><br>
                    <strong>Latitude:</strong> <?= $row['latitude'] ?> | <strong>Longitude:</strong> <?= $row['longitude'] ?><br>
                    <strong>EV Support:</strong> <?= ucfirst($row['ev_support']) ?><br>
                    <strong>Vehicles Supported:</strong> <?= $row['supported_vehicles'] ?><br>
                    <strong>2W Slots:</strong> <?= $row['slot_2w'] ?> | <strong>4W Slots:</strong> <?= $row['slot_4w'] ?></p>
                </div>
            </div>
        <?php } ?>
    </div>

    <!-- Issues Section -->
    <h4>Reported Issues</h4>
    <div class="row">
        <div class="col-md-6">
            <h5>User Issues</h5>
            <ul class="list-group">
                <?php while ($issue = $user_issues->fetch_assoc()) { ?>
                    <li class="list-group-item">
                        <strong><?= $issue['email'] ?></strong><br><?= $issue['message'] ?>
                        <br><small class="text-muted"><?= $issue['created_at'] ?></small>
                    </li>
                <?php } ?>
            </ul>
        </div>
        <div class="col-md-6">
            <h5>Owner Issues</h5>
            <ul class="list-group">
                <?php while ($issue = $owner_issues->fetch_assoc()) { ?>
                    <li class="list-group-item">
                        <strong><?= $issue['email'] ?></strong><br><?= $issue['message'] ?>
                        <br><small class="text-muted"><?= $issue['created_at'] ?></small>
                    </li>
                <?php } ?>
            </ul>
        </div>
    </div>
</div>
</body>
</html>
