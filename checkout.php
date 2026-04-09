<?php
require_once 'config.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Lấy thông tin giỏ hàng
$sql = "SELECT c.*, p.name, p.price, p.image 
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_items = $stmt->get_result();

// Tính tổng tiền
$total = 0;
$items = [];
while ($row = $cart_items->fetch_assoc()) {
    $total += $row['price'] * $row['quantity'];
    $items[] = $row;
}

// Kiểm tra giỏ hàng có trống không
if (empty($items)) {
    header("Location: cart.php");
    exit();
}

// Xử lý đặt hàng
$order_success = false;
$order_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $payment_method = $_POST['payment_method'] ?? 'cod';
    $notes = trim($_POST['notes'] ?? '');
    
    // Validate dữ liệu
    $errors = [];
    if (empty($fullname)) {
        $errors[] = "Full name is required";
    }
    if (empty($address)) {
        $errors[] = "Address is required";
    }
    if (empty($phone)) {
        $errors[] = "Phone number is required";
    } elseif (!preg_match('/^[0-9]{10,11}$/', $phone)) {
        $errors[] = "Invalid phone number (10-11 digits)";
    }
    
    if (empty($errors)) {
        // Bắt đầu transaction
        $conn->begin_transaction();
        
        try {
            // 1. Tạo đơn hàng mới
            $order_sql = "INSERT INTO orders (user_id, fullname, address, phone, payment_method, notes, total_amount, status, created_at) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
            $order_stmt = $conn->prepare($order_sql);
            $order_stmt->bind_param("isssssd", $user_id, $fullname, $address, $phone, $payment_method, $notes, $total);
            $order_stmt->execute();
            $order_id = $conn->insert_id;
            
            // 2. Thêm chi tiết đơn hàng
            $detail_sql = "INSERT INTO order_details (order_id, product_id, product_name, price, quantity, subtotal) 
                           VALUES (?, ?, ?, ?, ?, ?)";
            $detail_stmt = $conn->prepare($detail_sql);
            
            foreach ($items as $item) {
                $subtotal = $item['price'] * $item['quantity'];
                $detail_stmt->bind_param("iisddd", $order_id, $item['product_id'], $item['name'], $item['price'], $item['quantity'], $subtotal);
                $detail_stmt->execute();
            }
            
            // 3. Xóa giỏ hàng
            $clear_sql = "DELETE FROM cart WHERE user_id = ?";
            $clear_stmt = $conn->prepare($clear_sql);
            $clear_stmt->bind_param("i", $user_id);
            $clear_stmt->execute();
            
            // Commit transaction
            $conn->commit();
            
            // Lưu order_id vào session để hiển thị
            $_SESSION['last_order_id'] = $order_id;
            $order_success = true;
            
        } catch (Exception $e) {
            $conn->rollback();
            $order_error = "Order failed: " . $e->getMessage();
        }
    } else {
        $order_error = implode(", ", $errors);
    }
}

// Lấy thông tin user
$user_sql = "SELECT full_name, email, phone FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_info = $user_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - FastFood</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Checkout Page Styles */
        .checkout-container {
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            gap: 30px;
            margin-top: 30px;
        }
        
        .checkout-form-section,
        .order-summary-section {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        
        .section-header {
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f1c40f;
        }
        
        .section-header h2 {
            font-size: 1.5rem;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-header h2 i {
            color: #f1c40f;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 500;
        }
        
        .form-group label i {
            margin-right: 8px;
            color: #f1c40f;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ecf0f1;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s;
            font-family: inherit;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #f1c40f;
            box-shadow: 0 0 0 3px rgba(241, 196, 15, 0.1);
        }
        
        .payment-methods {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .payment-method {
            flex: 1;
            min-width: 120px;
        }
        
        .payment-method input[type="radio"] {
            display: none;
        }
        
        .payment-method label {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            padding: 15px;
            border: 2px solid #ecf0f1;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 0;
            text-align: center;
        }
        
        .payment-method input[type="radio"]:checked + label {
            border-color: #f1c40f;
            background: #fff9e6;
            color: #f1c40f;
        }
        
        .payment-method label i {
            font-size: 2rem;
            color: #7f8c8d;
        }
        
        .payment-method input[type="radio"]:checked + label i {
            color: #f1c40f;
        }
        
        /* Order Summary */
        .order-items {
            max-height: 400px;
            overflow-y: auto;
            margin-bottom: 20px;
        }
        
        .order-item {
            display: flex;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .order-item-image {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .order-item-image i {
            font-size: 1.5rem;
            color: #95a5a6;
        }
        
        .order-item-details {
            flex: 1;
        }
        
        .order-item-name {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .order-item-meta {
            display: flex;
            justify-content: space-between;
            color: #7f8c8d;
            font-size: 0.9rem;
        }
        
        .order-item-price {
            color: #27ae60;
            font-weight: 600;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .summary-total {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            font-size: 1.2rem;
            font-weight: bold;
            color: #2c3e50;
            border-top: 2px solid #f1c40f;
            margin-top: 10px;
        }
        
        .summary-total span:last-child {
            color: #e67e22;
            font-size: 1.5rem;
        }
        
        /* Buttons */
        .btn-place-order {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 20px;
        }
        
        .btn-place-order:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(39, 174, 96, 0.3);
        }
        
        .btn-back-cart {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 25px;
            background: #95a5a6;
            color: white;
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s;
            margin-top: 15px;
        }
        
        .btn-back-cart:hover {
            background: #7f8c8d;
            transform: translateY(-2px);
        }
        
        /* Success Message */
        .success-message {
            background: white;
            border-radius: 20px;
            padding: 50px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            margin-top: 30px;
        }
        
        .success-message i {
            font-size: 5rem;
            color: #27ae60;
            margin-bottom: 20px;
        }
        
        .success-message h2 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .success-message p {
            color: #7f8c8d;
            margin-bottom: 30px;
        }
        
        .order-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        
        .btn-order-history {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 30px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 10px;
            margin: 0 10px;
        }
        
        .btn-order-history:hover {
            background: #2980b9;
        }
        
        /* Error Message */
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .checkout-container {
                grid-template-columns: 1fr;
            }
            
            .payment-methods {
                flex-direction: column;
            }
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
                    <span class="cart-count"><?php echo count($items); ?></span>
                </a>
                <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="home-container">
        <h1 class="section-title">Checkout</h1>
        
        <?php if ($order_success): ?>
            <!-- Success Message -->
            <div class="success-message">
                <i class="fas fa-check-circle"></i>
                <h2>Order Placed Successfully!</h2>
                <p>Thank you for your order. We'll deliver it to you soon.</p>
                <div class="order-info">
                    <p><strong>Order ID:</strong> #<?php echo $_SESSION['last_order_id']; ?></p>
                    <p><strong>Total Amount:</strong> $<?php echo number_format($total, 2); ?></p>
                    <p><strong>Estimated Delivery:</strong> 30-45 minutes</p>
                </div>
                <div>
                    <a href="index.php" class="btn-order-history">
                        <i class="fas fa-home"></i> Continue Shopping
                    </a>
                    <a href="order_history.php" class="btn-order-history">
                        <i class="fas fa-list"></i> View Orders
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="checkout-container">
                <!-- Checkout Form -->
                <div class="checkout-form-section">
                    <div class="section-header">
                        <h2><i class="fas fa-address-card"></i> Delivery Information</h2>
                    </div>
                    
                    <?php if ($order_error): ?>
                        <div class="error-message">
                            <i class="fas fa-exclamation-triangle"></i>
                            <?php echo htmlspecialchars($order_error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="checkout.php" id="checkout-form">
                        <div class="form-group">
                            <label><i class="fas fa-user"></i> Full Name</label>
                            <input type="text" name="fullname" required 
                                   value="<?php echo htmlspecialchars($user_info['full_name'] ?? ''); ?>"
                                   placeholder="Enter your full name">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-map-marker-alt"></i> Delivery Address</label>
                            <input type="text" name="address" required 
                                   placeholder="Street, City, District">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-phone"></i> Phone Number</label>
                            <input type="tel" name="phone" required 
                                   value="<?php echo htmlspecialchars($user_info['phone'] ?? ''); ?>"
                                   placeholder="Enter your phone number">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-credit-card"></i> Payment Method</label>
                            <div class="payment-methods">
                                <div class="payment-method">
                                    <input type="radio" name="payment_method" value="cod" id="cod" checked>
                                    <label for="cod">
                                        <i class="fas fa-money-bill-wave"></i>
                                        <span>Cash on Delivery</span>
                                    </label>
                                </div>
                                <div class="payment-method">
                                    <input type="radio" name="payment_method" value="banking" id="banking">
                                    <label for="banking">
                                        <i class="fas fa-university"></i>
                                        <span>Bank Transfer</span>
                                    </label>
                                </div>
                                <div class="payment-method">
                                    <input type="radio" name="payment_method" value="momo" id="momo">
                                    <label for="momo">
                                        <i class="fas fa-mobile-alt"></i>
                                        <span>MoMo Wallet</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-sticky-note"></i> Order Notes (Optional)</label>
                            <textarea name="notes" rows="3" placeholder="Special instructions, delivery time, etc."></textarea>
                        </div>
                        
                        <button type="submit" class="btn-place-order">
                            <i class="fas fa-check"></i> Place Order
                        </button>
                        
                        <div style="text-align: center; margin-top: 15px;">
                            <a href="cart.php" class="btn-back-cart">
                                <i class="fas fa-arrow-left"></i> Back to Cart
                            </a>
                        </div>
                    </form>
                </div>
                
                <!-- Order Summary -->
                <div class="order-summary-section">
                    <div class="section-header">
                        <h2><i class="fas fa-shopping-bag"></i> Order Summary</h2>
                    </div>
                    
                    <div class="order-items">
                        <?php foreach ($items as $item): ?>
                        <div class="order-item">
                            <div class="order-item-image">
                                <?php 
                                $imagePath = "images/" . $item['image'];
                                if(file_exists($imagePath) && !empty($item['image']) && $item['image'] != 'default-product.jpg'): 
                                ?>
                                    <img src="<?php echo $imagePath; ?>" alt="<?php echo $item['name']; ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                <?php else: ?>
                                    <i class="fas fa-utensils"></i>
                                <?php endif; ?>
                            </div>
                            <div class="order-item-details">
                                <div class="order-item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                <div class="order-item-meta">
                                    <span>Quantity: <?php echo $item['quantity']; ?></span>
                                    <span class="order-item-price">$<?php echo number_format($item['price'], 2); ?> each</span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span>$<?php echo number_format($total, 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Delivery Fee:</span>
                        <span>Free</span>
                    </div>
                    <div class="summary-total">
                        <span>Total:</span>
                        <span>$<?php echo number_format($total, 2); ?></span>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Auto format phone number
        document.querySelector('input[name="phone"]')?.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11);
        });
        
        // Form validation before submit
        document.getElementById('checkout-form')?.addEventListener('submit', function(e) {
            const phone = document.querySelector('input[name="phone"]').value;
            if (phone.length < 10 || phone.length > 11) {
                e.preventDefault();
                alert('Please enter a valid phone number (10-11 digits)');
                return false;
            }
        });
    </script>
</body>
</html>