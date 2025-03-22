<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';

// Ensure user is logged in
requireLogin();

// Get user details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Get user's orders with items count
$stmt = $conn->prepare("
    SELECT o.*, COUNT(oi.id) as items_count,
           GROUP_CONCAT(CONCAT(p.name, ' (', oi.quantity, ')') SEPARATOR ', ') as items_list
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE o.user_id = ?
    GROUP BY o.id
    ORDER BY o.created_at DESC
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Function to format price in Indian Rupees
function formatIndianPrice($price) {
    return '₹' . number_format($price, 2);
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $state = trim($_POST['state']);
    $pincode = trim($_POST['pincode']);
    
    $stmt = $conn->prepare("UPDATE users SET name = ?, phone = ?, address = ?, city = ?, state = ?, pincode = ? WHERE id = ?");
    $stmt->bind_param("ssssssi", $name, $phone, $address, $city, $state, $pincode, $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        $success_message = "Profile updated successfully!";
        // Refresh user data
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
    } else {
        $error_message = "Failed to update profile. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Sweet Scoops</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
        .profile-section {
            background: white;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 30px;
            transition: transform 0.3s ease;
        }
        .profile-section:hover {
            transform: translateY(-5px);
        }
        .section-title {
            color: #fd7e14;
            margin-bottom: 20px;
            font-weight: 600;
        }
        .form-control:focus {
            border-color: #fd7e14;
            box-shadow: 0 0 0 0.25rem rgba(253, 126, 20, 0.25);
        }
        .btn-primary {
            background-color: #fd7e14;
            border-color: #fd7e14;
        }
        .btn-primary:hover {
            background-color: #e96b02;
            border-color: #e96b02;
        }
        .order-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        .order-card:hover {
            transform: translateY(-5px);
        }
        .order-header {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px 10px 0 0;
        }
        .order-body {
            padding: 20px;
        }
        .order-items {
            margin: 15px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            font-size: 0.9rem;
        }
        .status-badge {
            padding: 8px 15px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        .status-processing {
            background: #cce5ff;
            color: #004085;
        }
        .status-completed {
            background: #d4edda;
            color: #155724;
        }
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        .nav-link {
            color: #495057;
            transition: all 0.3s ease;
        }
        .nav-link:hover, .nav-link.active {
            color: #fd7e14;
        }
        .nav-link i {
            margin-right: 8px;
        }
        .alert {
            border-radius: 10px;
            border: none;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .form-label {
            font-weight: 500;
            color: #495057;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <div class="container py-5">
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-4">
                <div class="profile-section">
                    <h5 class="section-title">
                        <i class="fas fa-user me-2"></i>Personal Information
                    </h5>
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" name="name" 
                                   value="<?php echo htmlspecialchars($user['name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" 
                                   readonly disabled>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="tel" class="form-control" name="phone" 
                                   value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" name="address" rows="3"><?php echo htmlspecialchars($user['address']); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">City</label>
                            <input type="text" class="form-control" name="city" 
                                   value="<?php echo htmlspecialchars($user['city']); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">State</label>
                            <input type="text" class="form-control" name="state" 
                                   value="<?php echo htmlspecialchars($user['state']); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Pincode</label>
                            <input type="text" class="form-control" name="pincode" 
                                   value="<?php echo htmlspecialchars($user['pincode']); ?>">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-save me-2"></i>Update Profile
                        </button>
                    </form>
                </div>
            </div>

            <div class="col-md-8">
                <div class="profile-section">
                    <h5 class="section-title">
                        <i class="fas fa-shopping-bag me-2"></i>Order History
                    </h5>
                    <?php if (empty($orders)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No orders found. Start shopping now!</p>
                            <a href="index.php" class="btn btn-primary">
                                <i class="fas fa-ice-cream me-2"></i>Browse Products
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                            <div class="order-card">
                                <div class="order-header d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0">Order #<?php echo $order['id']; ?></h6>
                                        <small class="text-muted">
                                            <?php echo date('d M Y h:i A', strtotime($order['created_at'])); ?>
                                        </small>
                                    </div>
                                    <span class="status-badge status-<?php echo $order['status']; ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </div>
                                <div class="order-body">
                                    <div class="d-flex justify-content-between mb-3">
                                        <div>
                                            <strong>Total Amount:</strong>
                                            <?php echo formatIndianPrice($order['total_amount']); ?>
                                        </div>
                                        <div>
                                            <strong>Payment Method:</strong>
                                            <?php echo strtoupper($order['payment_method']); ?>
                                        </div>
                                    </div>
                                    <div class="order-items">
                                        <strong>Items:</strong> <?php echo $order['items_list']; ?>
                                    </div>
                                    <div class="shipping-info">
                                        <strong>Shipping Address:</strong><br>
                                        <?php echo htmlspecialchars($order['shipping_address']); ?>,<br>
                                        <?php echo htmlspecialchars($order['shipping_city']); ?>,
                                        <?php echo htmlspecialchars($order['shipping_state']); ?> - 
                                        <?php echo htmlspecialchars($order['shipping_pincode']); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
