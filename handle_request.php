<?php
session_start();
include("db_config.php");

if (!isset($_SESSION['email']) || $_SESSION['email'] !== 'aaditaygawade01@gmail.com') {
    echo "<script>alert('Unauthorized access!'); window.location.href='login.php';</script>";
    exit();
}

if (isset($_GET['id']) && isset($_GET['action'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'] === 'approve' ? 'approved' : 'rejected';

    $stmt = $conn->prepare("UPDATE parkings SET status=? WHERE id=?");
    $stmt->bind_param("si", $action, $id);
    $stmt->execute();
    $stmt->close();

    echo "<script>alert('Request $action'); window.location.href='admin_dashboard.php';</script>";
} else {
    echo "<script>alert('Invalid request'); window.location.href='admin_dashboard.php';</script>";
}
?>
parking_request.php
<?php
include("db_config.php");
session_start();

$name = $_POST['parking_name'];
$lat = $_POST['latitude'];
$lng = $_POST['longitude'];
$ev = $_POST['ev_charging'];
$bike = $_POST['bike_slots'];
$car = $_POST['car_slots'];
$truck = $_POST['truck_slots'];
$owner = $_SESSION['owner_email'];

$sql = "INSERT INTO parking_requests (owner_email, parking_name, latitude, longitude, ev_charging, bike_slots, car_slots, truck_slots, status)
        VALUES ('$owner', '$name', '$lat', '$lng', '$ev', '$bike', '$car', '$truck', 'pending')";

if (mysqli_query($conn, $sql)) {
    echo "Request sent to Admin!";
} else {
    echo "Error: " . mysqli_error($conn);
}
?>
