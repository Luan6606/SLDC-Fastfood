<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Xử lý cập nhật số lượng từ POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    foreach ($_POST['quantity'] as $product_id => $qty) {
        $qty = max(1, intval($qty));
        $update_sql = "UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("iii", $qty, $user_id, $product_id);
        $update_stmt->execute();
    }
    header("Location: cart.php");
    exit();
}

// Xử lý xóa sản phẩm
if (isset($_GET['remove'])) {
    $remove_id = intval($_GET['remove']);
    $delete_sql = "DELETE FROM cart WHERE user_id = ? AND product_id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("ii", $user_id, $remove_id);
    $delete_stmt->execute();
    header("Location: cart.php");
    exit();
}

// Xử lý cập nhật số lượng qua GET (tăng/giảm)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $product_id = intval($_GET['id']);
    $action = $_GET['action'];
    
    if ($action == 'increase') {
        $update_sql = "UPDATE cart SET quantity = quantity + 1 WHERE user_id = ? AND product_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ii", $user_id, $product_id);
        $update_stmt->execute();
    } elseif ($action == 'decrease') {
        $update_sql = "UPDATE cart SET quantity = quantity - 1 WHERE user_id = ? AND product_id = ? AND quantity > 1";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ii", $user_id, $product_id);
        $update_stmt->execute();
    } elseif ($action == 'remove') {
        $delete_sql = "DELETE FROM cart WHERE user_id = ? AND product_id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("ii", $user_id, $product_id);
        $delete_stmt->execute();
    }
    header("Location: cart.php");
    exit();
}

// Lấy giỏ hàng
$sql = "SELECT c.*, p.name, p.price, p.image, p.description 
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_items = $stmt->get_result();

// Tính tổng và lưu vào mảng
$total = 0;
$items = [];
while ($row = $cart_items->fetch_assoc()) {
    $total += $row['price'] * $row['quantity'];
    $items[] = $row;
}

// Lấy số lượng items trong giỏ
$cart_count = count($items);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart - FastFood</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Cart Page Specific Styles */
        .cart-container {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-top: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        
        .cart-table-wrapper {
            overflow-x: auto;
        }
        
        .cart-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .cart-table th {
            background: #2c3e50;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }
        
        .cart-table td {
            padding: 20px 15px;
            border-bottom: 1px solid #ecf0f1;
            vertical-align: middle;
        }
        
        .cart-table tr:hover {
            background: #f8f9fa;
        }
        
        .product-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .product-image {
            width: 70px;
            height: 70px;
            border-radius: 12px;
            overflow: hidden;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .product-name {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .quantity-control {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .quantity-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            background: #ecf0f1;
            border-radius: 8px;
            color: #2c3e50;
            font-weight: bold;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .quantity-btn:hover {
            background: #3498db;
            color: white;
            transform: translateY(-2px);
        }
        
        .quantity-number {
            min-width: 40px;
            text-align: center;
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .price {
            color: #27ae60;
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .total-price {
            color: #e67e22;
            font-weight: bold;
            font-size: 1.1rem;
        }
        
        .btn-delete {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 8px 15px;
            background: #e74c3c;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s;
            font-size: 0.9rem;
        }
        
        .btn-delete:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }
        
        .cart-summary {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #ecf0f1;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .total-amount {
            font-size: 1.5rem;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .total-amount span {
            color: #27ae60;
            font-size: 1.8rem;
        }
        
        .cart-actions {
            display: flex;
            gap: 15px;
        }
        
        .btn-continue {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 25px;
            background: #95a5a6;
            color: white;
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s;
            font-weight: 600;
        }
        
        .btn-continue:hover {
            background: #7f8c8d;
            transform: translateY(-2px);
        }
        
        .btn-checkout-custom {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 30px;
            background: #27ae60;
            color: white;
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s;
            font-weight: 600;
            font-size: 1rem;
        }
        
        .btn-checkout-custom:hover {
            background: #2ecc71;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
        }
        
        .btn-update-cart {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 25px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
            font-size: 1rem;
        }
        
        .btn-update-cart:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }
        
        .empty-cart-content {
            text-align: center;
            padding: 60px 20px;
        }
        
        .empty-cart-content i {
            font-size: 5rem;
            color: #bdc3c7;
            margin-bottom: 20px;
        }
        
        .empty-cart-content h3 {
            font-size: 1.8rem;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .empty-cart-content p {
            color: #7f8c8d;
            margin-bottom: 30px;
        }
        
        .alert-message {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        @media (max-width: 768px) {
            .cart-container {
                padding: 20px;
            }
            
            .product-info {
                flex-direction: column;
                text-align: center;
            }
            
            .cart-summary {
                flex-direction: column;
                text-align: center;
            }
            
            .cart-actions {
                flex-direction: column;
                width: 100%;
            }
            
            .btn-continue,
            .btn-checkout-custom,
            .btn-update-cart {
                width: 100%;
                justify-content: center;
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
                    <span class="cart-count"><?php echo $cart_count; ?></span>
                </a>
                <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="home-container">
        <h1 class="section-title">Your Shopping Cart</h1>
        
        <?php if (isset($_GET['error']) && $_GET['error'] == 'checkout_failed'): ?>
            <div class="alert-message alert-error">
                <i class="fas fa-exclamation-circle"></i>
                Checkout failed. Please try again.
            </div>
        <?php endif; ?>
        
        <?php if (count($items) > 0): ?>
            <div class="cart-container">
                <form method="POST" action="cart.php" id="cart-form">
                    <div class="cart-table-wrapper">
                        <table class="cart-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Total</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                <tr>
                                    <td>
                                        <div class="product-info">
                                            <div class="product-image">
                                                <?php 
                                                $imagePath = "images/" . $item['image'];
                                                if(file_exists($imagePath) && !empty($item['image']) && $item['image'] != 'default-product.jpg'): 
                                                ?>
                                                    <img src="<?php echo $imagePath; ?>" alt="<?php echo $item['name']; ?>">
                                                <?php else: ?>
                                                    <i class="fas fa-utensils" style="font-size: 2rem; color: #95a5a6;"></i>
                                                <?php endif; ?>
                                            </div>
                                            <div class="product-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                        </div>
                                    </td>
                                    <td class="price">$<?php echo number_format($item['price'], 2); ?></td>
                                    <td>
                                        <div class="quantity-control">
                                            <a href="cart.php?action=decrease&id=<?php echo $item['product_id']; ?>" class="quantity-btn">-</a>
                                            <input type="number" 
                                                   name="quantity[<?php echo $item['product_id']; ?>]" 
                                                   value="<?php echo $item['quantity']; ?>" 
                                                   min="1" 
                                                   style="width: 60px; text-align: center; padding: 5px; border: 1px solid #ddd; border-radius: 5px;">
                                            <a href="cart.php?action=increase&id=<?php echo $item['product_id']; ?>" class="quantity-btn">+</a>
                                        </div>
                                    </td>
                                    <td class="total-price">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                    <td>
                                        <a href="cart.php?action=remove&id=<?php echo $item['product_id']; ?>" class="btn-delete" onclick="return confirm('Remove this item?')">
                                            <i class="fas fa-trash"></i> Remove
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="cart-summary">
                        <div class="total-amount">
                            Total: <span>$<?php echo number_format($total, 2); ?></span>
                        </div>
                        <div class="cart-actions">
                            <button type="submit" name="update_cart" class="btn-update-cart">
                                <i class="fas fa-sync-alt"></i> Update Cart
                            </button>
                            <a href="index.php#menu" class="btn-continue">
                                <i class="fas fa-arrow-left"></i> Continue Shopping
                            </a>
                            <a href="checkout.php" class="btn-checkout-custom" onclick="return confirm('Proceed to checkout?')">
                                <i class="fas fa-credit-card"></i> Proceed to Checkout
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div class="cart-container">
                <div class="empty-cart-content">
                    <i class="fas fa-shopping-cart"></i>
                    <h3>Your cart is empty</h3>
                    <p>Looks like you haven't added anything to your cart yet.</p>
                    <a href="index.php#menu" class="btn-order" style="display: inline-block; margin-top: 20px; text-decoration: none;">
                        <i class="fas fa-utensils"></i> Browse Menu
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>