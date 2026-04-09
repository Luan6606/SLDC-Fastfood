<?php
require_once '../config.php';

// Check admin access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// Get revenue statistics
$monthly_revenue = $conn->query("
    SELECT 
        DATE_FORMAT(order_date, '%Y-%m') as month,
        COUNT(*) as order_count,
        SUM(total_amount) as revenue
    FROM orders 
    WHERE status = 'completed'
    GROUP BY DATE_FORMAT(order_date, '%Y-%m')
    ORDER BY month DESC
    LIMIT 12
");

// Get all orders with user info
$orders = $conn->query("
    SELECT o.*, u.username, u.full_name 
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    ORDER BY o.order_date DESC 
    LIMIT 50
");

// Calculate totals
$total_revenue = 0;
$total_orders = 0;
$result = $conn->query("SELECT SUM(total_amount) as total, COUNT(*) as count FROM orders WHERE status='completed'");
if ($row = $result->fetch_assoc()) {
    $total_revenue = $row['total'] ?? 0;
    $total_orders = $row['count'] ?? 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revenue - FastFood Admin</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>FastFood Admin</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="index.php"><i class="fas fa-dashboard"></i> Dashboard</a>
                <a href="users.php"><i class="fas fa-users"></i> Users</a>
                <a href="products.php"><i class="fas fa-box"></i> Products</a>
                <a href="revenue.php" class="active"><i class="fas fa-chart-line"></i> Revenue</a>
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </aside>
        
        <main class="admin-main">
            <header class="admin-header">
                <h1>Revenue Management</h1>
                <div class="admin-user">
                    Welcome, <?php echo $_SESSION['username']; ?>
                </div>
            </header>
            
            <!-- Revenue Summary -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Revenue</h3>
                    <p class="stat-number">$<?php echo number_format($total_revenue, 2); ?></p>
                </div>
                
                <div class="stat-card">
                    <h3>Total Orders</h3>
                    <p class="stat-number"><?php echo $total_orders; ?></p>
                </div>
                
                <div class="stat-card">
                    <h3>Average Order</h3>
                    <p class="stat-number">
                        $<?php echo $total_orders > 0 ? number_format($total_revenue / $total_orders, 2) : '0.00'; ?>
                    </p>
                </div>
            </div>
            
            <!-- Monthly Revenue -->
            <div class="revenue-section">
                <h2>Monthly Revenue</h2>
                <table class="revenue-table">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Orders</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($monthly_revenue && $monthly_revenue->num_rows > 0): ?>
                            <?php while($row = $monthly_revenue->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('F Y', strtotime($row['month'] . '-01')); ?></td>
                                <td><?php echo $row['order_count']; ?></td>
                                <td>$<?php echo number_format($row['revenue'], 2); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3">
                                    <div class="empty-state">
                                        <i class="fas fa-chart-line"></i>
                                        <h3>No revenue data</h3>
                                        <p>Complete some orders to see revenue here</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Recent Orders -->
            <div class="recent-orders">
                <h2>Recent Orders</h2>
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($orders && $orders->num_rows > 0): ?>
                            <?php while($order = $orders->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $order['id']; ?></td>
                                <td><?php echo $order['full_name'] ?? $order['username'] ?? 'Guest'; ?></td>
                                <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                <td><?php echo date('m/d/Y H:i', strtotime($order['order_date'])); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $order['status']; ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5">
                                    <div class="empty-state">
                                        <i class="fas fa-shopping-cart"></i>
                                        <h3>No orders found</h3>
                                        <p>Customers haven't placed any orders yet</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>