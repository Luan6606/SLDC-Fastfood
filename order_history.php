<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Lấy danh sách đơn hàng
$sql = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$orders = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History - FastFood</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .orders-container {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-top: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        
        .order-card {
            border: 1px solid #ecf0f1;
            border-radius: 15px;
            margin-bottom: 20px;
            overflow: hidden;
            transition: all 0.3s;
        }
        
        .order-card:hover {
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .order-header {
            background: #f8f9fa;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .order-id {
            font-weight: bold;
            color: #2c3e50;
        }
        
        .order-status {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .status-pending { background: #f39c12; color: white; }
        .status-processing { background: #3498db; color: white; }
        .status-delivering { background: #9b59b6; color: white; }
        .status-completed { background: #27ae60; color: white; }
        .status-cancelled { background: #e74c3c; color: white; }
        
        .order-body {
            padding: 20px;
        }
        
        .order-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .order-total {
            text-align: right;
            font-size: 1.2rem;
            font-weight: bold;
            color: #e67e22;
        }
        
        .btn-view-details {
            display: inline-block;
            padding: 8px 20px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 0.9rem;
        }
    </style>
</head>
<body class="home-page">
    <nav class="home-navbar">
        <div class="nav-wrapper">
            <a href="index.php" class="logo">🍔 FastFood</a>
            <div class="nav-links">
                <a href="index.php">Home</a>
                <a href="index.php#menu">Menu</a>
                <span class="welcome-text">Welcome, <?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?></span>
                <a href="cart.php" class="cart-icon">
                    <i class="fas fa-shopping-cart"></i>
                </a>
                <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="home-container">
        <h1 class="section-title">Order History</h1>
        
        <div class="orders-container">
            <?php if ($orders->num_rows > 0): ?>
                <?php while ($order = $orders->fetch_assoc()): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <span class="order-id">Order #<?php echo $order['id']; ?></span>
                            <span style="margin-left: 15px; color: #7f8c8d;">
                                <i class="far fa-calendar-alt"></i> 
                                <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?>
                            </span>
                        </div>
                        <div>
                            <span class="order-status status-<?php echo $order['status']; ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="order-body">
                        <div class="order-info">
                            <div>
                                <i class="fas fa-map-marker-alt"></i> 
                                <?php echo htmlspecialchars($order['address']); ?>
                            </div>
                            <div>
                                <i class="fas fa-phone"></i> 
                                <?php echo htmlspecialchars($order['phone']); ?>
                            </div>
                            <div>
                                <i class="fas fa-credit-card"></i> 
                                <?php echo $order['payment_method'] == 'cod' ? 'Cash on Delivery' : ($order['payment_method'] == 'banking' ? 'Bank Transfer' : 'MoMo Wallet'); ?>
                            </div>
                        </div>
                        <div class="order-total">
                            Total: $<?php echo number_format($order['total_amount'], 2); ?>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-shopping-bag" style="font-size: 4rem; color: #bdc3c7;"></i>
                    <h3 style="margin-top: 20px;">No orders yet</h3>
                    <p style="color: #7f8c8d;">Start shopping to see your orders here</p>
                    <a href="index.php#menu" class="btn-order" style="display: inline-block; margin-top: 20px;">Browse Menu</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>