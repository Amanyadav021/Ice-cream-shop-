<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

// Ensure admin is logged in
requireAdmin();

// Handle product deletion
if (isset($_POST['delete_product'])) {
    $product_id = $_POST['product_id'];
    
    // Get product image path before deletion
    $stmt = $conn->prepare("SELECT image_url FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    
    // Delete product
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    
    if ($stmt->execute()) {
        // Delete product image if it exists
        if ($product && isset($product['image_url']) && $product['image_url'] && file_exists("../" . $product['image_url'])) {
            unlink("../" . $product['image_url']);
        }
        $_SESSION['success'] = "Product deleted successfully!";
    } else {
        $_SESSION['error'] = "Error deleting product.";
    }
    header("Location: products.php");
    exit;
}

// Get all products
$stmt = $conn->prepare("
    SELECT p.*, 
           CASE WHEN p.image_url IS NOT NULL AND p.image_url != '' 
                THEN p.image_url 
                ELSE 'images/default-ice-cream.jpg' 
           END as image_path,
           CASE WHEN p.stock > 0 THEN 'Active' ELSE 'Inactive' END as status_text,
           CASE WHEN p.stock > 0 THEN 1 ELSE 0 END as status
    FROM products p 
    ORDER BY p.name
");
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Function to format price in Indian Rupees
function formatIndianPrice($price) {
    return '₹' . number_format($price, 2);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - Sweet Scoops Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        .navbar-brand {
            color: #fd7e14 !important;
            font-weight: bold;
            font-size: 1.5rem;
        }
        .nav-link {
            color: #495057 !important;
            padding: 8px 16px;
            margin: 0 4px;
            border-radius: 4px;
            transition: all 0.3s;
        }
        .nav-link:hover, .nav-link.active {
            background: rgba(253, 126, 20, 0.1);
            color: #fd7e14 !important;
        }
        .btn-outline-primary {
            color: #fd7e14;
            border-color: #fd7e14;
        }
        .btn-outline-primary:hover {
            background: #fd7e14;
            color: white;
        }
        .btn-danger {
            background: #dc3545;
            border: none;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            transition: transform 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .page-title {
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn-add-product {
            background: #fd7e14;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        .btn-add-product:hover {
            background: #e96b02;
            color: white;
            transform: translateY(-2px);
        }
        .table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .table th {
            background: #f8f9fa;
            color: #495057;
            font-weight: 600;
            border: none;
            padding: 15px;
        }
        .table td {
            vertical-align: middle;
            border-color: #f1f3f5;
            padding: 15px;
        }
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .status-badge {
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        .action-btn {
            padding: 8px 16px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
            background: white;
            color: #495057;
            transition: all 0.3s;
            margin: 0 2px;
        }
        .action-btn:hover {
            transform: translateY(-2px);
        }
        .action-btn.edit:hover {
            color: #0d6efd;
            border-color: #0d6efd;
            background: #f8f9fa;
        }
        .action-btn.delete:hover {
            color: #dc3545;
            border-color: #dc3545;
            background: #f8f9fa;
        }
        .alert {
            border-radius: 10px;
            border: none;
            padding: 15px 20px;
            margin-bottom: 20px;
        }
        .alert-dismissible .btn-close {
            padding: 20px;
        }
        .product-name {
            font-weight: 500;
            color: #212529;
        }
        .product-description {
            color: #6c757d;
            font-size: 0.875rem;
        }
        .product-price {
            font-weight: 600;
            color: #fd7e14;
        }
        .product-category {
            color: #6c757d;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-ice-cream"></i> Sweet Scoops
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="products.php">
                            <i class="fas fa-ice-cream"></i> Products
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="orders.php">
                            <i class="fas fa-shopping-bag"></i> Orders
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php">
                            <i class="fas fa-users"></i> Users
                        </a>
                    </li>
                </ul>
                <div class="d-flex">
                    <a href="../index.php" class="btn btn-outline-primary me-2">
                        <i class="fas fa-store"></i> View Site
                    </a>
                    <a href="../logout.php" class="btn btn-danger">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="page-title">
            <h2 class="mb-0">Manage Products</h2>
            <a href="add_product.php" class="btn btn-add-product">
                <i class="fas fa-plus me-2"></i>Add New Product
            </a>
        </div>

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Product Details</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td>
                                        <img src="../<?php echo htmlspecialchars($product['image_path']); ?>" 
                                             alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                             class="product-image">
                                    </td>
                                    <td>
                                        <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                                        <div class="product-description"><?php echo htmlspecialchars($product['description']); ?></div>
                                    </td>
                                    <td class="product-category"><?php echo htmlspecialchars($product['category']); ?></td>
                                    <td class="product-price"><?php echo formatIndianPrice($product['price']); ?></td>
                                    <td><?php echo $product['stock']; ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $product['status'] ? 'status-active' : 'status-inactive'; ?>">
                                            <?php echo htmlspecialchars($product['status_text']); ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="edit_product.php?id=<?php echo $product['id']; ?>" 
                                           class="action-btn edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form method="POST" class="d-inline" 
                                              onsubmit="return confirm('Are you sure you want to delete this product?');">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <button type="submit" name="delete_product" class="action-btn delete">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
</body>
</html>
