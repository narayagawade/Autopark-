<?php
require_once('db_config.php');

$parking_id = isset($_GET['parking_id']) ? (int)$_GET['parking_id'] : 0;
if ($parking_id <= 0) { die("Invalid parking"); }

// Fetch parking basic info
$st = $conn->prepare("SELECT id, parking_name, address, ev_support, slot_2w, slot_4w, COALESCE(floors,1) AS floors 
                      FROM parking_requests WHERE id=? AND status='approved'");
$st->bind_param("i", $parking_id);
$st->execute();
$res = $st->get_result();
$parking = $res->fetch_assoc();
$st->close();

if (!$parking) { die("Parking not found or not approved"); }
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Book – <?= htmlspecialchars($parking['parking_name']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
</head>
<body class="bg-light">
<div class="container py-4">
  <h4 class="mb-3">Book Slot – <?= htmlspecialchars($parking['parking_name']) ?></h4>
  <div class="mb-2 text-muted"><?= htmlspecialchars($parking['address']) ?></div>

  <form class="card p-3 shadow-sm" method="post" action="user_confirm.php" onsubmit="return validateTimes()">
    <input type="hidden" name="parking_id" value="<?= (int)$parking['id'] ?>">

    <div class="row g-3">
    <div class="col-md-6">
  <label class="form-label">Vehicle Number</label>
  <input type="text" name="vehicle_no" id="vehicle_no" class="form-control"
         pattern="[A-Z]{2}[ -]?[0-9]{1,2}[A-Z]{1,3}[0-9]{1,4}"
         title="Enter valid vehicle number like MH12AB1234"
         placeholder="e.g. MH12AB1234"
         required>
    </div>

      <div class="col-md-6">
        <label class="form-label">Vehicle Type</label>
        <select name="vehicle_type" class="form-select" required>
          <option value="2W">2-Wheeler</option>
          <option value="4W">4-Wheeler</option>
        </select>
      </div>

      <div class="col-md-6">
        <label class="form-label">EV Option</label>
        <select name="ev" class="form-select" required>
          <option value="no">No</option>
          <option value="yes">Yes</option>
        </select>
        <div class="form-text">If EV is chosen, ₹10/hr will be added.</div>
      </div>

      <div class="col-md-6">
        <label class="form-label">Membership</label>
        <select name="membership" class="form-select">
          <option value="none" selected>None</option>
          <option value="7days">7 Days</option>
          <option value="15days">15 Days</option>
          <option value="30days">30 Days</option>
        </select>
      </div>

      <div class="col-md-6">
        <label class="form-label">Start Time</label>
        <input type="datetime-local" name="start_time" id="start_time" class="form-control" required>
      </div>

      <div class="col-md-6">
        <label class="form-label">End Time</label>
        <input type="datetime-local" name="end_time" id="end_time" class="form-control" required>
      </div>
    </div>

    <div class="mt-3 d-flex gap-2">
      <a href="user_dashboard.php" class="btn btn-secondary">Back</a>
      <button class="btn btn-primary" type="submit">Proceed to Confirmation</button>
    </div>
  </form>
</div>

<script>
  // ⛔ Block past date selection
  const now = new Date();
  const isoNow = now.toISOString().slice(0,16); // format yyyy-MM-ddTHH:mm
  document.getElementById('start_time').min = isoNow;
  document.getElementById('end_time').min = isoNow;
  document.querySelector("form").addEventListener("submit", function(e){
  const vehicleNo = document.getElementById("vehicle_no").value.trim().toUpperCase();
  const pattern = /^[A-Z]{2}[ -]?[0-9]{1,2}[A-Z]{1,3}[0-9]{1,4}$/;

  if (!pattern.test(vehicleNo)) {
    alert("⛔ Please enter a valid vehicle number (e.g. MH12AB1234)");
    e.preventDefault();
  }
});


  function validateTimes() {
    const start = new Date(document.getElementById('start_time').value);
    const end = new Date(document.getElementById('end_time').value);
    const current = new Date();

    if (start < current) {
      alert("⛔ Start time cannot be in the past.");
      return false;
    }
    if (end <= start) {
      alert("⛔ End time must be after start time.");
      return false;
    }
    return true;
  }
</script>

</body>
</html>
