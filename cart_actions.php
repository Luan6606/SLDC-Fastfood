<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    switch ($action) {
        case 'add':
            $quantity = isset($_GET['qty']) ? intval($_GET['qty']) : 1;
            
            // Kiểm tra sản phẩm đã có trong giỏ chưa
            $check_sql = "SELECT quantity FROM cart WHERE user_id = ? AND product_id = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("ii", $user_id, $product_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                // Cập nhật số lượng
                $update_sql = "UPDATE cart SET quantity = quantity + ? WHERE user_id = ? AND product_id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("iii", $quantity, $user_id, $product_id);
                $update_stmt->execute();
            } else {
                // Thêm mới
                $insert_sql = "INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)";
                $insert_stmt = $conn->prepare($insert_sql);
                $insert_stmt->bind_param("iii", $user_id, $product_id, $quantity);
                $insert_stmt->execute();
            }
            break;
            
        case 'update':
            $quantity = isset($_GET['qty']) ? max(1, intval($_GET['qty'])) : 1;
            $update_sql = "UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("iii", $quantity, $user_id, $product_id);
            $update_stmt->execute();
            break;
            
        case 'remove':
            $delete_sql = "DELETE FROM cart WHERE user_id = ? AND product_id = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("ii", $user_id, $product_id);
            $delete_stmt->execute();
            break;
            
        case 'clear':
            $delete_sql = "DELETE FROM cart WHERE user_id = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("i", $user_id);
            $delete_stmt->execute();
            break;
            
        case 'checkout':
            // Chuyển hướng đến trang thanh toán
            header("Location: checkout.php");
            exit();
    }
    
    // Redirect back to cart page
    header("Location: cart.php");
    exit();
}
?>