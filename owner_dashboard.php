<?php
// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Sanitize and set defaults for session variables
$name = isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'Owner';
$email = isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : '';
$owner_id = isset($_SESSION['owner_id']) ? (int) $_SESSION['owner_id'] : 0;

// Include database configuration
require_once('db_config.php');

// Initialize variables
$requestStatus = null;
$two_wheeler_slots = 0;
$four_wheeler_slots = 0;
$floors = 1; // default

// Fetch floor status data
$floor_status = [];
$floor_status_query = $conn->prepare("SELECT floor_number, status FROM floor_status WHERE owner_id = ?");
if ($floor_status_query) {
    $floor_status_query->bind_param("i", $owner_id);
    $floor_status_query->execute();
    $floor_status_result = $floor_status_query->get_result();
    while ($row = $floor_status_result->fetch_assoc()) {
        $floor_status[$row['floor_number']] = $row['status'];
    }
    $floor_status_query->close();
}

// Fetch parking request details
$stmt = $conn->prepare("
    SELECT id, status, slot_2w, slot_4w, COALESCE(floors,1) AS floors 
    FROM parking_requests 
    WHERE owner_id = ?
");
$parking_id = 0;
if ($stmt) {
    $stmt->bind_param("i", $owner_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $parking_id = $row['id'];
        $requestStatus = $row['status'];
        $two_wheeler_slots = (int) $row['slot_2w'];
        $four_wheeler_slots = (int) $row['slot_4w'];
        $floors = (int) $row['floors'];
    }
    $stmt->close();
}

// Fetch all active bookings (NOT expired yet)
$bookings = [];
if ($parking_id) {
  $sql = "SELECT slot_number, membership, booked_by, end_time 
          FROM bookings 
          WHERE parking_id=? AND status='active' AND end_time > NOW()";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $parking_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $bookings[$row['slot_number']] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Owner Dashboard - Auto Park</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <style>
    body { background-color: #f8f9fa; }
    .sidebar { height: 210vh; background: #343a40; color: white; }
    .sidebar a { color: white; padding: 15px; display: block; text-decoration: none; }
    .sidebar a:hover { background-color: #495057; }
    #map { height: 300px; border-radius: 10px; }
    .slot-box { padding: 15px; border-radius: 8px; text-align: center; transition: transform 0.2s;
                font-weight: bold; }
    .slot-box:hover { transform: scale(1.05); }
    .autocomplete-suggestion {
      padding: 8px;
      cursor: pointer;
    }
    .autocomplete-suggestion:hover { background-color: #f1f1f1; }
    .password-wrapper { position: relative; }
    .password-wrapper input { padding-right: 40px; }
    .password-wrapper .toggle-password {
      position: absolute;
      top: 50%;
      right: 10px;
      transform: translateY(-50%);
      cursor: pointer;
      color: #666;
    }
    .slot {
    width: 60px;
    height: 60px;
    margin: 5px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    border-radius: 8px;
    cursor: pointer;
}

.available {
    background-color: blue;
    color: white;
}

.booked {
    background-color: red;
    color: white;
}
    .available { background-color: blue; color: white; cursor:pointer; }
    .booked { background-color: red; color: white; cursor:not-allowed; }
    .membership { background-color: gold; color: black; cursor:not-allowed; }
    .offline { background-color: orange; color: black; cursor:not-allowed; }
    .floor-status {
        display: inline-block;
        padding: 5px 10px;
        border-radius: 4px;
        margin-left: 10px;
        cursor: pointer;
    }
    .floor-status.open {
        background-color: #28a745;
        color: white;
    }
    .floor-status.closed {
        background-color: #dc3545;
        color: white;
    }
    .floor-header {
        display: flex;
        align-items: center;
        margin-bottom: 15px;
    }
  </style>
</head>
<body>
<div class="d-flex">
  <div class="sidebar p-3">
    <h4 class="text-center">Auto Park</h4>
    <a href="homepage.html">Home</a>
    <a href="owner_records.php">Records</a>
</div>

  <div class="flex-fill p-4">
    <nav class="navbar navbar-light bg-white rounded shadow-sm p-3 mb-4">
      <span><strong><?= $name; ?></strong> (<?= $email; ?>)</span>
      <a href="logout.php" class="btn btn-danger">Logout</a>
    </nav>

    <?php if ($requestStatus === 'approved'): ?>
      <h5>Parking Structure:</h5>
      <?php for ($floor = 1; $floor <= $floors; $floor++): ?>
        <div class="mb-4">
          <div class="floor-header">
            <h6 class="text-primary">Floor <?= $floor; ?></h6>
            <span class="floor-status <?= isset($floor_status[$floor]) && $floor_status[$floor] === 'closed' ? 'closed' : 'open'; ?>" onclick="toggleFloorStatus(<?= $floor; ?>)">
              <?= isset($floor_status[$floor]) && $floor_status[$floor] === 'closed' ? 'Closed' : 'Open'; ?>
            </span>
          </div>

          <!-- 2W slots -->
          <div class="mb-2">2-Wheeler Slots:</div>
          <div class="row mb-4">
            <?php for ($i = 1; $i <= $two_wheeler_slots; $i++): 
              $slot_id = "F{$floor}-2W-{$i}";
              $class = "available";
              if (isset($bookings[$slot_id])) {
                $b = $bookings[$slot_id];
                if ($b['booked_by'] === 'owner') $class = "offline";
                elseif ($b['membership'] !== "none") $class = "membership";
                else $class = "booked";
              }
            ?>
              <div class="col-6 col-sm-4 col-md-3 col-lg-2 mb-2">
                <div class="slot-box <?= $class ?>"
                     <?php if ($class === "available") { ?> 
                        onclick="bookOffline('<?= $slot_id ?>')" 
                     <?php } ?>>
                  <?= $slot_id ?>
                </div>
              </div>
            <?php endfor; ?>
          </div>

          <!-- 4W slots -->
          <div class="mb-2">4-Wheeler Slots:</div>
          <div class="row">
            <?php for ($i = 1; $i <= $four_wheeler_slots; $i++): 
              $slot_id = "F{$floor}-4W-{$i}";
              $class = "available";
              if (isset($bookings[$slot_id])) {
                  $b = $bookings[$slot_id];
                  if ($b['booked_by'] === 'owner') $class = "offline";
                  elseif ($b['membership'] !== "none") $class = "membership";
                  else $class = "booked";
              }
            ?>
              <div class="col-6 col-sm-4 col-md-3 col-lg-2 mb-2">
                <div class="slot-box <?= $class ?>"
                     <?php if ($class === "available") { ?> 
                        onclick="bookOffline('<?= $slot_id ?>')" 
                     <?php } ?>>
                  <?= $slot_id ?>
                </div>
              </div>
            <?php endfor; ?>
          </div>
        </div>
      <?php endfor; ?>

    <?php elseif ($requestStatus === 'waiting'): ?>
      <div class="alert alert-warning">Your request has been sent successfully. Waiting for admin approval.</div>
    <?php elseif ($requestStatus === 'rejected'): ?>
      <div class="alert alert-danger">Your parking request has been rejected by the admin. Please review and submit again.</div>
    <?php else: ?>
      <h5>Register Your Parking Area</h5>
      <form method="POST" action="send_request.php">
      <div class="card p-4 shadow-sm">
        <h5 class="mb-3">Submit Parking Request</h5>
        <div class="mb-3">
          <input type="text" name="parking_name" class="form-control" placeholder="Parking Name" required>
        </div>
        <div class="mb-3">
          <input type="text" name="address" class="form-control" placeholder="Enter Address" required>
        </div>
        <div class="mb-3">
        <input type="text" name="mobile" id="mobile" class="form-control"
          placeholder="Mobile Number"
          pattern="[0-9]{10}"
          maxlength="10"
          title="Enter exactly 10 digits"
          required>
        </div>
        <div class="position-relative mb-3">
          <input type="text" id="location_search" class="form-control" placeholder="Search Location">
          <div id="suggestions" class="autocomplete-suggestions"></div>
          <button type="button" class="btn btn-success mt-2" onclick="geocodeAddress()">Locate</button>
          <button type="button" class="btn btn-info mt-2" onclick="getCurrentLocation()">Use My Location</button>
        </div>
        <div id="map"></div>
        <input type="hidden" name="latitude" id="latitude">
        <input type="hidden" name="longitude" id="longitude">

        <div class="mb-3">
          <label>EV Charging:</label>
          <select name="ev" class="form-select">
            <option value="yes">Available</option>
            <option value="no">Not Available</option>
          </select>
        </div>

        <label><strong>Supported Vehicles:</strong></label><br>
        <div class="form-check form-check-inline">
          <input class="form-check-input" type="checkbox" name="vehicle_types[]" value="Truck" id="truck">
          <label class="form-check-label" for="truck">Truck</label>
        </div>
        <div class="form-check form-check-inline">
          <input class="form-check-input" type="checkbox" name="vehicle_types[]" value="Car" id="car">
          <label class="form-check-label" for="car">Car</label>
        </div>
        <div class="form-check form-check-inline">
          <input class="form-check-input" type="checkbox" name="vehicle_types[]" value="Bike" id="bike">
          <label class="form-check-label" for="bike">Bike</label>
        </div>
        <div class="form-check form-check-inline">
          <input class="form-check-input" type="checkbox" name="vehicle_types[]" value="Other" id="other">
          <label class="form-check-label" for="other">Three Wheeler</label>
        </div>

        <div class="mb-3">
          <input type="number" name="slot2" class="form-control" placeholder="No. of 2-Wheeler Slots" required>
        </div>
        <div class="mb-3">
          <input type="number" name="slot4" class="form-control" placeholder="No. of 4-Wheeler Slots" required>
        </div>
        <div class="mb-3">
          <input type="number" name="floors" class="form-control" placeholder="Number of Floors" min="1" required>
        </div>
        <button type="submit" class="btn btn-primary" onclick="return confirm('Request submitted successfully. Please wait for admin approval.')">Send Request to Admin</button>
      </div>
    </form>
    <?php endif; ?>
  </div>
</div>

<script>
function bookOffline(slot_id) {
  const hours = prompt("Enter duration in hours for this offline booking:", "1");
  if (!hours || isNaN(hours) || hours <= 0) return;

  fetch("owner_offline_booking.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: "slot_number=" + encodeURIComponent(slot_id) +
          "&parking_id=<?= $parking_id ?>" +
          "&duration=" + encodeURIComponent(hours)
  })
  .then(r => r.json())
  .then(res => {
    if (res.success) {
      const slotBox = Array.from(document.querySelectorAll(".slot-box"))
        .find(el => el.textContent.trim() === slot_id);
      if (slotBox) {
        slotBox.classList.remove("available");
        slotBox.classList.add("offline");
        slotBox.removeAttribute("onclick"); // lock slot until expire
      }
      alert("Slot booked for " + hours + " hour(s).");
    } else {
      alert(res.message);
    }
  });
}
function toggleFloorStatus(floor) {
    fetch('update_floor_status.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'floor=' + encodeURIComponent(floor)
    })
    .then(r => r.json())
    .then(res => {
      if (res && res.success) {
        window.location.reload();
      } else {
        alert(res && res.message ? res.message : 'Failed to update floor status.');
      }
    })
    .catch(() => alert('Network error while updating floor status.'));
  }

  const mapEl = document.getElementById('map');
  if (mapEl) {
    let map = L.map('map').setView([19.0760, 72.8777], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    const customIcon = L.icon({
      iconUrl: 'https://cdn-icons-png.flaticon.com/512/684/684908.png',
      iconSize: [30, 40],
      iconAnchor: [15, 40],
      popupAnchor: [0, -40]
    });

    let marker;
    function setMapLocation(lat, lon) {
      document.getElementById('latitude').value = lat;
      document.getElementById('longitude').value = lon;
      map.setView([lat, lon], 15);
      if (marker) marker.setLatLng([lat, lon]);
      else marker = L.marker([lat, lon], { icon: customIcon }).addTo(map);
      marker.bindPopup("Parking Location").openPopup();
    }

    window.geocodeAddress = function () {
      const input = document.getElementById('location_search');
      if (!input || !input.value.trim()) return;
      fetch('https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(input.value))
        .then(r => r.json())
        .then(data => {
          if (!data.length) return alert("Location not found");
          setMapLocation(data[0].lat, data[0].lon);
        });
    };

    window.getCurrentLocation = function () {
      if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(pos => {
          setMapLocation(pos.coords.latitude, pos.coords.longitude);
        }, () => alert("Unable to fetch location"));
      } else {
        alert("Geolocation is not supported by this browser.");
      }
    };
  }

  const locationInput = document.getElementById('location_search');
  const suggestionsEl = document.getElementById('suggestions');

  if (locationInput && suggestionsEl) {
    locationInput.addEventListener('input', function () {
      const query = this.value;
      if (query.length < 3) {
        suggestionsEl.style.display = 'none';
        suggestionsEl.innerHTML = '';
        return;
      }
      fetch('https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(query))
        .then(response => response.json())
        .then(data => {
          suggestionsEl.innerHTML = '';
          data.slice(0, 5).forEach(place => {
            const div = document.createElement('div');
            div.textContent = place.display_name;
            div.className = 'autocomplete-suggestion';
            div.onclick = function () {
              locationInput.value = place.display_name;
              suggestionsEl.innerHTML = '';
              suggestionsEl.style.display = 'none';
            };
            suggestionsEl.appendChild(div);
          });
          suggestionsEl.style.display = 'block';
        });
    });
  }
  const mobileInput = document.getElementById("mobile");

mobileInput.addEventListener("input", function () {
  // Remove any non-numeric characters
  this.value = this.value.replace(/\D/g, '');

  // Limit to 10 digits
  if (this.value.length > 10) {
    this.value = this.value.slice(0, 10);
  }
});

document.querySelector("form").addEventListener("submit", function (e) {
  const mobile = mobileInput.value.trim();
  const pattern = /^[0-9]{10}$/;

  if (!pattern.test(mobile)) {
    alert("📱 Please enter a valid 10-digit mobile number.");
    e.preventDefault();
  }
});

</script>
</body>
</html>
