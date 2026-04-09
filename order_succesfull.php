<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$order_id = $_GET['order_id'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Success - FastFood</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="home-page">
    <nav class="home-navbar">
        <div class="nav-wrapper">
            <a href="index.php" class="logo">🍔 FastFood</a>
            <div class="nav-links">
                <a href="index.php">Home</a>
                <a href="index.php#menu">Menu</a>
                <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="home-container">
        <div class="no-products" style="background: white; padding: 60px;">
            <i class="fas fa-check-circle" style="color: #27ae60; font-size: 5rem;"></i>
            <h3>Order Successful!</h3>
            <p>Your order #<?php echo $order_id; ?> has been placed successfully.</p>
            <p>Thank you for shopping with us!</p>
            <a href="index.php" class="btn-order" style="display: inline-block; margin-top: 20px; text-decoration: none;">Continue Shopping</a>
        </div>
    </div>
</body>
</html>