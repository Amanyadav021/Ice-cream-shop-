<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

// Ensure user is logged in
requireLogin();

// Get order ID from URL
$order_id = $_GET['order_id'] ?? 0;

// Fetch order details
$stmt = $conn->prepare("
    SELECT o.*, u.username 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    header('Location: index.php');
    exit();
}

// Fetch order items
$stmt = $conn->prepare("
    SELECT oi.*, p.name 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - Ice Cream Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="index.php">🍦 Ice Cream Shop</a>
        </div>
    </nav>

    <div class="container py-5">
        <div class="text-center mb-5">
            <i class="bi bi-check-circle text-success" style="font-size: 4rem;"></i>
            <h1 class="mt-3">Thank You for Your Order!</h1>
            <p class="lead">Order #<?php echo $order_id; ?> has been placed successfully.</p>
        </div>

        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Order Details</h5>
                        <p>
                            <strong>Order Date:</strong> <?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?><br>
                            <strong>Status:</strong> <?php echo ucfirst($order['status']); ?><br>
                        </p>

                        <h6 class="mt-4">Items Ordered:</h6>
                        <?php foreach ($items as $item): ?>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <?php echo htmlspecialchars($item['name']); ?>
                                    <small class="text-muted">(<?php echo $item['quantity']; ?>x)</small>
                                </div>
                                <span>$<?php echo number_format($item['price_at_time'] * $item['quantity'], 2); ?></span>
                            </div>
                        <?php endforeach; ?>

                        <hr>
                        <div class="d-flex justify-content-between">
                            <span>Subtotal:</span>
                            <span>$<?php echo number_format($order['total_amount'], 2); ?></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Tax (10%):</span>
                            <span>$<?php echo number_format($order['total_amount'] * 0.1, 2); ?></span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <strong>Total:</strong>
                            <strong>$<?php echo number_format($order['total_amount'] * 1.1, 2); ?></strong>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <a href="index.php" class="btn btn-primary">Continue Shopping</a>
                    <a href="orders.php" class="btn btn-outline-primary">View All Orders</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 