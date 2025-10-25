<?php
session_start();
if ($_SESSION['role'] != 'user') {
    header("Location: ../login.html");
    exit;
}
include '../db_config.php';

// Handle booking form
if (isset($_POST['confirm_booking'])) {
    $user_email = $_SESSION['email'];
    $parking_id = $_POST['parking_id'];
    $vehicle_name = $_POST['vehicle_name'];
    $vehicle_type = $_POST['vehicle_type']; // 2W or 4W
    $ev_required = isset($_POST['ev_required']) ? "yes" : "no";
    $start_time = $_POST['start_time'];
    $duration = intval($_POST['duration']);

    // Fetch parking details
    $stmt = $conn->prepare("SELECT * FROM parking WHERE id = ?");
    $stmt->bind_param("i", $parking_id);
    $stmt->execute();
    $parking = $stmt->get_result()->fetch_assoc();

    if (!$parking) {
        die("<div class='alert alert-danger'>Invalid Parking ID</div>");
    }

    // Check slot availability
    $slot_field = ($vehicle_type == "2W") ? "slot_2w" : "slot_4w";
    if ($parking[$slot_field] <= 0) {
        echo "<div class='alert alert-danger'>No slots available for $vehicle_type</div>";
    } else {
        // Deduct slot
        $new_slots = $parking[$slot_field] - 1;
        $update = $conn->prepare("UPDATE parking SET $slot_field = ? WHERE id = ?");
        $update->bind_param("ii", $new_slots, $parking_id);
        $update->execute();

        // Save booking
        $end_time = date("Y-m-d H:i:s", strtotime($start_time . " +$duration hours"));
        $book = $conn->prepare("INSERT INTO bookings 
            (user_email, parking_id, vehicle_name, vehicle_type, ev_required, start_time, end_time, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $book->bind_param("sisssss", $user_email, $parking_id, $vehicle_name, $vehicle_type, $ev_required, $start_time, $end_time);
        $book->execute();

        echo "<div class='alert alert-success'>✅ Your slot has been booked successfully!</div>";

        // Show receipt
        echo "<div class='card mt-3'>
                <div class='card-body'>
                    <h4 class='card-title'>Booking Receipt</h4>
                    <p><b>Parking Name:</b> ".$parking['parking_name']."</p>
                    <p><b>Address:</b> ".$parking['address']."</p>
                    <p><b>Vehicle:</b> $vehicle_name ($vehicle_type)</p>
                    <p><b>EV Required:</b> $ev_support </p>
                    <p><b>Start Time:</b> $start_time</p>
                    <p><b>End Time:</b> $end_time</p>
                </div>
              </div>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Book Slot</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="container mt-4">

<h3>Book Parking Slot</h3>
<hr>

<?php if (!isset($_POST['confirm_booking'])): ?>
<form method="POST">
    <input type="hidden" name="parking_id" value="<?php echo $_POST['parking_id']; ?>">

    <div class="mb-3">
        <label class="form-label">Vehicle Name</label>
        <input type="text" name="vehicle_name" class="form-control" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Vehicle Type</label>
        <select name="vehicle_type" class="form-select" required>
            <option value="2W">Two Wheeler</option>
            <option value="4W">Four Wheeler</option>
        </select>
    </div>

    <div class="mb-3 form-check">
        <input type="checkbox" name="ev_required" class="form-check-input">
        <label class="form-check-label">Require EV Charging</label>
    </div>

    <div class="mb-3">
        <label class="form-label">Start Time</label>
        <input type="datetime-local" name="start_time" class="form-control" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Duration (hours)</label>
        <input type="number" name="duration" class="form-control" min="1" required>
    </div>

    <button type="submit" name="confirm_booking" class="btn btn-success">Confirm Booking</button>
</form>
<?php endif; ?>

</body>
</html>
