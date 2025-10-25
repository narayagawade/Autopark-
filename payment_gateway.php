<?php
// payment_gateway.php
if (!isset($_GET['amount'])) {
    die("No amount specified!");
}
$amount = htmlspecialchars($_GET['amount']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Auto Park - Payment Gateway</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex align-items-center justify-content-center vh-100 bg-light">
    <div class="card shadow p-4 text-center" style="max-width:400px;">
        <h3 class="mb-3">Auto Park Payment</h3>
        <p>Amount to Pay: <strong>₹<?php echo $amount; ?></strong></p>
        <form method="POST" action="payment_success.php">
            <input type="hidden" name="amount" value="<?php echo $amount; ?>">
            <button type="submit" class="btn btn-success w-100 mb-2">Pay Now</button>
        </form>
        <form method="POST" action="payment_failed.php">
            <button type="submit" class="btn btn-danger w-100">Cancel</button>
        </form>
    </div>
</body>
</html>
