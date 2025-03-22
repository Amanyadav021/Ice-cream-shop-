<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

// Ensure user is admin
requireAdmin();

// Function to format price in Indian Rupees
function formatIndianPrice($price) {
    return '₹' . number_format($price, 2);
}

// Get UPI settings
$stmt = $conn->prepare("SELECT upi_id FROM settings LIMIT 1");
$stmt->execute();
$settings = $stmt->get_result()->fetch_assoc();

// Get order statistics
$new_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'")->fetch_assoc()['count'];
$processing = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'processing'")->fetch_assoc()['count'];
$completed = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'completed'")->fetch_assoc()['count'];
$cancelled = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'cancelled'")->fetch_assoc()['count'];

// Get total users and products
$total_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE is_admin = 0")->fetch_assoc()['count'];
$total_products = $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Sweet Scoops</title>
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
        .card-header {
            background-color: #fff;
            border-bottom: 2px solid #f1f1f1;
            padding: 20px;
            border-radius: 15px 15px 0 0 !important;
        }
        .stat-card {
            padding: 25px;
            text-align: center;
            border-radius: 15px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-card i {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        .stat-card .number {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 10px 0;
        }
        .stat-card .label {
            font-size: 1.1rem;
            font-weight: 500;
        }
        .stat-card.primary {
            background: linear-gradient(45deg, #007bff, #0056b3);
            color: white;
        }
        .stat-card.success {
            background: linear-gradient(45deg, #28a745, #1e7e34);
            color: white;
        }
        .stat-card.warning {
            background: linear-gradient(45deg, #ffc107, #d39e00);
            color: white;
        }
        .stat-card.danger {
            background: linear-gradient(45deg, #dc3545, #bd2130);
            color: white;
        }
        .notification-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            padding: 4px 8px;
            border-radius: 50%;
            background: #dc3545;
            color: white;
            font-size: 12px;
            font-weight: bold;
        }
        .order-card {
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .order-card:hover {
            background-color: #f8f9fa;
        }
        .new-order {
            background-color: #fff3cd;
            animation: highlight 2s infinite;
        }
        @keyframes highlight {
            0% { background-color: #fff3cd; }
            50% { background-color: #fff; }
            100% { background-color: #fff3cd; }
        }
        .btn-action {
            padding: 8px 15px;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        .btn-action:hover {
            transform: translateY(-2px);
        }
        .table th {
            font-weight: 600;
            color: #495057;
        }
        .table td {
            vertical-align: middle;
        }
        .modal-content {
            border-radius: 15px;
            border: none;
        }
        .modal-header {
            border-bottom: 2px solid #f1f1f1;
            padding: 20px;
        }
        .modal-footer {
            border-top: 2px solid #f1f1f1;
            padding: 20px;
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
                        <a class="nav-link active" href="dashboard.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php">
                            <i class="fas fa-ice-cream"></i> Products
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="orders.php">
                            <i class="fas fa-shopping-bag"></i> Orders
                            <?php if ($new_orders > 0): ?>
                                <span class="badge bg-danger"><?php echo $new_orders; ?></span>
                            <?php endif; ?>
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
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card primary">
                    <i class="fas fa-users"></i>
                    <div class="number"><?php echo $total_users; ?></div>
                    <div class="label">Total Users</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card success">
                    <i class="fas fa-ice-cream"></i>
                    <div class="number"><?php echo $total_products; ?></div>
                    <div class="label">Total Products</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card warning">
                    <i class="fas fa-shopping-cart"></i>
                    <div class="number"><?php echo $new_orders; ?></div>
                    <div class="label">New Orders</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card danger">
                    <i class="fas fa-chart-line"></i>
                    <div class="number"><?php echo $processing; ?></div>
                    <div class="label">Processing Orders</div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-cog me-2"></i>Payment Settings</h5>
                    </div>
                    <div class="card-body">
                        <form id="upiForm">
                            <div class="mb-3">
                                <label class="form-label">UPI ID</label>
                                <input type="text" class="form-control" name="upi_id" 
                                       value="<?php echo htmlspecialchars($settings['upi_id'] ?? ''); ?>" 
                                       placeholder="username@bank">
                                <div class="form-text">Enter your UPI ID to accept UPI payments</div>
                            </div>
                            <button type="submit" class="btn btn-primary btn-action">
                                <i class="fas fa-save me-2"></i>Save UPI ID
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-shopping-bag me-2"></i>Recent Orders
                            <?php if ($new_orders > 0): ?>
                                <span class="badge bg-danger ms-2"><?php echo $new_orders; ?> New</span>
                            <?php endif; ?>
                        </h5>
                        <a href="orders.php" class="btn btn-primary btn-sm btn-action">
                            <i class="fas fa-list me-1"></i>View All
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $orders_query = "SELECT o.*, u.name as customer_name, u.phone as customer_phone 
                                                   FROM orders o 
                                                   JOIN users u ON o.user_id = u.id 
                                                   ORDER BY o.created_at DESC LIMIT 5";
                                    $orders_result = $conn->query($orders_query);
                                    
                                    while ($order = $orders_result->fetch_assoc()):
                                        $is_new = $order['status'] === 'pending';
                                        $status_class = [
                                            'pending' => 'warning',
                                            'processing' => 'primary',
                                            'completed' => 'success',
                                            'cancelled' => 'danger'
                                        ][$order['status']];
                                    ?>
                                    <tr class="order-card <?php echo $is_new ? 'new-order' : ''; ?>" 
                                        data-order-id="<?php echo $order['id']; ?>">
                                        <td>
                                            #<?php echo $order['id']; ?>
                                            <?php if ($is_new): ?>
                                                <span class="badge bg-danger">New</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div><?php echo htmlspecialchars($order['customer_name']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($order['customer_phone']); ?></small>
                                        </td>
                                        <td><?php echo formatIndianPrice($order['total_amount']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $status_class; ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d M Y', strtotime($order['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" 
                                                        data-bs-toggle="dropdown">
                                                    Actions
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li>
                                                        <a class="dropdown-item view-order" href="#" 
                                                           data-order-id="<?php echo $order['id']; ?>">
                                                            <i class="fas fa-eye me-2"></i>View Details
                                                        </a>
                                                    </li>
                                                    <?php if ($order['status'] !== 'completed' && $order['status'] !== 'cancelled'): ?>
                                                        <li>
                                                            <a class="dropdown-item update-status" href="#" 
                                                               data-order-id="<?php echo $order['id']; ?>" 
                                                               data-status="processing">
                                                                <i class="fas fa-clock me-2"></i>Mark Processing
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item update-status" href="#" 
                                                               data-order-id="<?php echo $order['id']; ?>" 
                                                               data-status="completed">
                                                                <i class="fas fa-check me-2"></i>Mark Completed
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item update-status" href="#" 
                                                               data-order-id="<?php echo $order['id']; ?>" 
                                                               data-status="cancelled">
                                                                <i class="fas fa-times me-2"></i>Cancel Order
                                                            </a>
                                                        </li>
                                                    <?php endif; ?>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Details Modal -->
    <div class="modal fade" id="orderModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Order Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="orderDetails">
                    <!-- Order details will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script>
        // Save UPI ID
        document.getElementById('upiForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('../admin_actions.php?action=save_upi', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Toastify({
                        text: "UPI ID saved successfully!",
                        duration: 3000,
                        gravity: "top",
                        position: "right",
                        backgroundColor: "#28a745"
                    }).showToast();
                } else {
                    throw new Error(data.message || 'Failed to save UPI ID');
                }
            })
            .catch(error => {
                Toastify({
                    text: error.message,
                    duration: 3000,
                    gravity: "top",
                    position: "right",
                    backgroundColor: "#dc3545"
                }).showToast();
            });
        });

        // Update Order Status
        document.querySelectorAll('.update-status').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const orderId = this.dataset.orderId;
                const status = this.dataset.status;
                
                fetch('../admin_actions.php?action=update_order_status', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `order_id=${orderId}&status=${status}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Toastify({
                            text: "Order status updated successfully!",
                            duration: 3000,
                            gravity: "top",
                            position: "right",
                            backgroundColor: "#28a745"
                        }).showToast();
                        
                        // Reload page to refresh order list
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        throw new Error(data.message || 'Failed to update order status');
                    }
                })
                .catch(error => {
                    Toastify({
                        text: error.message,
                        duration: 3000,
                        gravity: "top",
                        position: "right",
                        backgroundColor: "#dc3545"
                    }).showToast();
                });
            });
        });

        // View Order Details
        document.querySelectorAll('.view-order').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const orderId = this.dataset.orderId;
                const modal = new bootstrap.Modal(document.getElementById('orderModal'));
                
                fetch(`../admin_actions.php?action=get_order_details&order_id=${orderId}`)
                    .then(response => response.text())
                    .then(html => {
                        document.getElementById('orderDetails').innerHTML = html;
                        modal.show();
                    })
                    .catch(error => {
                        Toastify({
                            text: "Failed to load order details",
                            duration: 3000,
                            gravity: "top",
                            position: "right",
                            backgroundColor: "#dc3545"
                        }).showToast();
                    });
            });
        });

        // Check for new orders periodically
        setInterval(() => {
            fetch('../admin_actions.php?action=check_new_orders')
                .then(response => response.json())
                .then(data => {
                    if (data.new_orders > 0) {
                        Toastify({
                            text: `You have ${data.new_orders} new order(s)!`,
                            duration: 5000,
                            gravity: "top",
                            position: "right",
                            backgroundColor: "#ffc107",
                            stopOnFocus: true,
                            onClick: () => location.reload()
                        }).showToast();
                    }
                });
        }, 30000); // Check every 30 seconds
    </script>
</body>
</html>