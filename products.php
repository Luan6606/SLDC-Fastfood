<?php
require_once '../config.php';

// Check admin access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$message = '';
$message_type = '';

// ===== HANDLE ADD PRODUCT =====
if (isset($_POST['add_product'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $price = floatval($_POST['price']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $available = isset($_POST['is_available']) ? 1 : 0;
    
    // Handle image upload
    $image = 'default-product.jpg';
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
        $target_dir = "../images/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $image_name = time() . '_' . basename($_FILES["product_image"]["name"]);
        $target_file = $target_dir . $image_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        
        // Allow certain file formats
        if(in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
            if (move_uploaded_file($_FILES["product_image"]["tmp_name"], $target_file)) {
                $image = $image_name;
            }
        }
    }
    
    $sql = "INSERT INTO products (name, description, price, category, image, is_available) 
            VALUES ('$name', '$description', $price, '$category', '$image', $available)";
    
    if (mysqli_query($conn, $sql)) {
        $message = "Product added successfully!";
        $message_type = "success";
    } else {
        $message = "Error: " . mysqli_error($conn);
        $message_type = "error";
    }
}

// ===== HANDLE UPDATE PRODUCT =====
if (isset($_POST['update_product'])) {
    $id = intval($_POST['product_id']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $price = floatval($_POST['price']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $available = isset($_POST['is_available']) ? 1 : 0;
    
    // Get current image
    $result = mysqli_query($conn, "SELECT image FROM products WHERE id = $id");
    $row = mysqli_fetch_assoc($result);
    $image = $row['image'];
    
    // Handle new image upload
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
        $target_dir = "../images/";
        $image_name = time() . '_' . basename($_FILES["product_image"]["name"]);
        $target_file = $target_dir . $image_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        
        if(in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
            if (move_uploaded_file($_FILES["product_image"]["tmp_name"], $target_file)) {
                // Delete old image if not default
                if ($image != 'default-product.jpg' && file_exists($target_dir . $image)) {
                    unlink($target_dir . $image);
                }
                $image = $image_name;
            }
        }
    }
    
    $sql = "UPDATE products SET 
            name = '$name',
            description = '$description',
            price = $price,
            category = '$category',
            image = '$image',
            is_available = $available
            WHERE id = $id";
    
    if (mysqli_query($conn, $sql)) {
        $message = "Product updated successfully!";
        $message_type = "success";
    } else {
        $message = "Error: " . mysqli_error($conn);
        $message_type = "error";
    }
}

// ===== HANDLE DELETE PRODUCT =====
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // Get image to delete
    $result = mysqli_query($conn, "SELECT image FROM products WHERE id = $id");
    $row = mysqli_fetch_assoc($result);
    
    if ($row && $row['image'] != 'default-product.jpg') {
        $image_path = "../images/" . $row['image'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }
    
    $sql = "DELETE FROM products WHERE id = $id";
    if (mysqli_query($conn, $sql)) {
        $message = "Product deleted successfully!";
        $message_type = "success";
    } else {
        $message = "Error: " . mysqli_error($conn);
        $message_type = "error";
    }
}

// ===== GET PRODUCT FOR EDIT =====
$edit_product = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $result = mysqli_query($conn, "SELECT * FROM products WHERE id = $id");
    $edit_product = mysqli_fetch_assoc($result);
}

// ===== GET ALL PRODUCTS =====
$products = mysqli_query($conn, "SELECT * FROM products ORDER BY category, name");

// ===== GET CATEGORIES =====
$categories = mysqli_query($conn, "SELECT DISTINCT category FROM products ORDER BY category");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management - FastFood Admin</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>FastFood Admin</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="index.php"><i class="fas fa-dashboard"></i> Dashboard</a>
                <a href="users.php"><i class="fas fa-users"></i> Users</a>
                <a href="products.php" class="active"><i class="fas fa-box"></i> Products</a>
                <a href="revenue.php"><i class="fas fa-chart-line"></i> Revenue</a>
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="admin-main">
            <header class="admin-header">
                <h1><i class="fas fa-box"></i> Product Management</h1>
                <div class="admin-user">
                    <i class="fas fa-user-circle"></i>
                    <span>Welcome, <?php echo $_SESSION['username']; ?></span>
                </div>
            </header>
            
            <?php if ($message): ?>
                <div class="message <?php echo $message_type; ?>">
                    <i class="fas <?php echo $message_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <!-- Add/Edit Product Form -->
            <div class="product-form-container">
                <h2><?php echo $edit_product ? 'Edit Product' : 'Add New Product'; ?></h2>
                
                <form method="POST" enctype="multipart/form-data" class="product-form">
                    <?php if ($edit_product): ?>
                        <input type="hidden" name="product_id" value="<?php echo $edit_product['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-tag"></i> Product Name</label>
                            <input type="text" name="name" value="<?php echo $edit_product['name'] ?? ''; ?>" placeholder="e.g., Chicken Nugget" required>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-list"></i> Category</label>
                            <select name="category" required>
                                <option value="">Select Category</option>
                                <?php 
                                $cat_list = ['Burgers', 'Pizza', 'Sides', 'Drinks', 'Desserts', 'Salads'];
                                $current_cat = $edit_product['category'] ?? '';
                                foreach($cat_list as $cat): 
                                ?>
                                    <option value="<?php echo $cat; ?>" <?php echo $current_cat == $cat ? 'selected' : ''; ?>>
                                        <?php echo $cat; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-align-left"></i> Description</label>
                        <textarea name="description" rows="3" placeholder="Product description..." required><?php echo $edit_product['description'] ?? ''; ?></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-dollar-sign"></i> Price ($)</label>
                            <input type="number" name="price" step="0.01" min="0" value="<?php echo $edit_product['price'] ?? ''; ?>" placeholder="0.00" required>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-image"></i> Product Image</label>
                            <input type="file" name="product_image" accept="image/*">
                            <?php if ($edit_product && $edit_product['image'] != 'default-product.jpg'): ?>
                                <small>Current: <?php echo $edit_product['image']; ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if ($edit_product && $edit_product['image'] != 'default-product.jpg'): ?>
                        <div class="current-image">
                            <img src="../images/<?php echo $edit_product['image']; ?>" alt="Current" style="max-width: 100px; max-height: 100px;">
                        </div>
                    <?php endif; ?>
                    
                    <div class="form-group checkbox-group">
                        <label>
                            <input type="checkbox" name="is_available" <?php echo (!isset($edit_product) || $edit_product['is_available']) ? 'checked' : ''; ?>>
                            <i class="fas fa-check-circle"></i> Product is available
                        </label>
                    </div>
                    
                    <div class="form-actions">
                        <?php if ($edit_product): ?>
                            <a href="products.php" class="btn-cancel">Cancel</a>
                            <button type="submit" name="update_product" class="btn-save">Update Product</button>
                        <?php else: ?>
                            <button type="reset" class="btn-cancel">Reset</button>
                            <button type="submit" name="add_product" class="btn-save">Save Product</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <!-- Products Table -->
            <div class="table-container">
                <h2><i class="fas fa-list"></i> All Products</h2>
                
                <table class="products-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($products) > 0): ?>
                            <?php while($product = mysqli_fetch_assoc($products)): ?>
                            <tr>
                                <td>#<?php echo $product['id']; ?></td>
                                <td>
                                    <?php 
                                    $imagePath = "../images/" . $product['image'];
                                    if(file_exists($imagePath) && $product['image'] != 'default-product.jpg'): 
                                    ?>
                                        <img src="<?php echo $imagePath; ?>" alt="<?php echo $product['name']; ?>" class="product-thumb">
                                    <?php else: ?>
                                        <div class="product-thumb-placeholder">
                                            <i class="fas fa-utensils"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo $product['name']; ?></strong></td>
                                <td><span class="category-badge"><?php echo $product['category']; ?></span></td>
                                <td><strong>$<?php echo number_format($product['price'], 2); ?></strong></td>
                                <td>
                                    <span class="status-badge <?php echo $product['is_available'] ? 'available' : 'unavailable'; ?>">
                                        <?php echo $product['is_available'] ? 'Available' : 'Unavailable'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="products.php?edit=<?php echo $product['id']; ?>" class="btn-edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="products.php?delete=<?php echo $product['id']; ?>" class="btn-delete" onclick="return confirm('Are you sure?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7">
                                    <div class="empty-state">
                                        <i class="fas fa-box-open"></i>
                                        <h3>No Products Found</h3>
                                        <p>Add your first product using the form above</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <style>
    /* Additional styles for product form */
    .product-form-container {
        background: white;
        padding: 25px;
        border-radius: 10px;
        margin-bottom: 30px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .product-form-container h2 {
        color: #2c3e50;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #ecf0f1;
    }
    
    .product-form {
        max-width: 800px;
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 15px;
    }
    
    .form-group {
        margin-bottom: 15px;
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
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 10px 12px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 0.95rem;
    }
    
    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        border-color: #3498db;
        outline: none;
    }
    
    .form-group small {
        display: block;
        margin-top: 5px;
        color: #7f8c8d;
        font-size: 0.85rem;
    }
    
    .checkbox-group {
        margin: 20px 0;
    }
    
    .checkbox-group label {
        display: flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
    }
    
    .checkbox-group input[type="checkbox"] {
        width: auto;
        margin-right: 5px;
    }
    
    .checkbox-group i {
        color: #27ae60;
    }
    
    .form-actions {
        display: flex;
        gap: 15px;
        justify-content: flex-end;
        margin-top: 20px;
    }
    
    .btn-save {
        background: #2ecc71;
        color: white;
        padding: 12px 30px;
        border: none;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .btn-save:hover {
        background: #27ae60;
        transform: translateY(-2px);
    }
    
    .btn-cancel {
        background: #95a5a6;
        color: white;
        padding: 12px 30px;
        border: none;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
    }
    
    .btn-cancel:hover {
        background: #7f8c8d;
    }
    
    .current-image {
        margin: 10px 0;
        padding: 10px;
        background: #f8f9fa;
        border-radius: 8px;
        display: inline-block;
    }
    
    .product-thumb {
        width: 50px;
        height: 50px;
        border-radius: 5px;
        object-fit: cover;
    }
    
    .product-thumb-placeholder {
        width: 50px;
        height: 50px;
        background: #ecf0f1;
        border-radius: 5px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #95a5a6;
    }
    
    .category-badge {
        background: #ecf0f1;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.85rem;
    }
    
    .table-container {
        background: white;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .table-container h2 {
        color: #2c3e50;
        margin-bottom: 20px;
    }
    
    .products-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .products-table th {
        background: #34495e;
        color: white;
        padding: 12px;
        text-align: left;
    }
    
    .products-table td {
        padding: 12px;
        border-bottom: 1px solid #ecf0f1;
    }
    
    .products-table tr:hover {
        background: #f8f9fa;
    }
    
    .action-buttons {
        display: flex;
        gap: 5px;
    }
    
    .btn-edit, .btn-delete {
        padding: 6px 12px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
    }
    
    .btn-edit {
        background: #3498db;
        color: white;
    }
    
    .btn-delete {
        background: #e74c3c;
        color: white;
    }
    
    .empty-state {
        text-align: center;
        padding: 40px;
        color: #7f8c8d;
    }
    
    .empty-state i {
        font-size: 3rem;
        margin-bottom: 15px;
    }
    
    @media (max-width: 768px) {
        .form-row {
            grid-template-columns: 1fr;
            gap: 0;
        }
    }
    </style>
</body>
</html>