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

// Get filter parameters
$status = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$query = "SELECT o.*, u.name as customer_name, u.phone as customer_phone 
          FROM orders o 
          JOIN users u ON o.user_id = u.id 
          WHERE 1=1";

$params = [];
$types = "";

if ($status !== 'all') {
    $query .= " AND o.status = ?";
    $params[] = $status;
    $types .= "s";
}

if ($search) {
    $search = "%$search%";
    $query .= " AND (o.id LIKE ? OR u.name LIKE ? OR u.phone LIKE ? OR o.shipping_email LIKE ?)";
    $params = array_merge($params, [$search, $search, $search, $search]);
    $types .= "ssss";
}

$query .= " ORDER BY o.created_at DESC";

// Prepare and execute query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$orders_result = $stmt->get_result();

// Get order counts by status
$status_counts = [
    'pending' => $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'")->fetch_assoc()['count'],
    'processing' => $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'processing'")->fetch_assoc()['count'],
    'completed' => $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'completed'")->fetch_assoc()['count'],
    'cancelled' => $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'cancelled'")->fetch_assoc()['count']
];
$total_orders = array_sum($status_counts);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - Sweet Scoops Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .navbar {
            padding: 1rem;
            margin-bottom: 2rem;
        }
        .navbar-brand {
            color: #fd7e14 !important;
            font-weight: bold;
            font-size: 1.5rem;
        }
        .nav-link {
            color: #495057;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
        }
        .nav-link:hover, .nav-link.active {
            color: #fd7e14;
            background-color: #fff3cd;
        }
        .nav-link i {
            margin-right: 0.5rem;
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
        .status-filter {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            overflow-x: auto;
            padding-bottom: 10px;
        }
        .status-badge {
            padding: 10px 20px;
            border-radius: 50px;
            color: #fff;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        .status-badge:hover {
            transform: translateY(-2px);
            color: #fff;
            opacity: 0.9;
        }
        .status-badge.all {
            background: #6c757d;
        }
        .status-badge.pending {
            background: #ffc107;
        }
        .status-badge.processing {
            background: #0d6efd;
        }
        .status-badge.completed {
            background: #198754;
        }
        .status-badge.cancelled {
            background: #dc3545;
        }
        .status-badge.active {
            box-shadow: 0 0 0 3px rgba(255,255,255,0.9), 0 0 0 6px currentColor;
        }
        .search-box {
            position: relative;
            max-width: 300px;
        }
        .search-box .form-control {
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            border-radius: 50px;
            border: 2px solid #e9ecef;
            font-size: 0.95rem;
            box-shadow: none;
            transition: all 0.3s ease;
        }
        .search-box .form-control:focus {
            border-color: #fd7e14;
            box-shadow: 0 0 0 0.2rem rgba(253, 126, 20, 0.25);
        }
        .search-box .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        .btn-action {
            padding: 8px 15px;
            border-radius: 10px;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        .btn-action:hover {
            transform: translateY(-2px);
        }
        .table {
            margin-bottom: 0;
        }
        .table th {
            font-weight: 600;
            color: #495057;
            border-bottom: 2px solid #dee2e6;
            padding: 1rem;
        }
        .table td {
            vertical-align: middle;
            padding: 1rem;
            color: #6c757d;
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
        .modal-content {
            border: none;
            border-radius: 15px;
        }
        .modal-header {
            border-bottom: 1px solid #dee2e6;
            padding: 1.5rem;
        }
        .modal-body {
            padding: 1.5rem;
        }
        .modal-footer {
            border-top: 1px solid #dee2e6;
            padding: 1.5rem;
        }
        @keyframes highlight {
            0% { background-color: #fff3cd; }
            50% { background-color: #fff; }
            100% { background-color: #fff3cd; }
        }
        @media (max-width: 768px) {
            .status-filter {
                flex-wrap: nowrap;
                overflow-x: auto;
                padding-bottom: 10px;
                -webkit-overflow-scrolling: touch;
            }
            .status-badge {
                white-space: nowrap;
            }
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
                        <a class="nav-link" href="products.php">
                            <i class="fas fa-ice-cream"></i> Products
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="orders.php">
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
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
            <h2 class="mb-0">Manage Orders</h2>
            <div class="search-box">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="searchInput" class="form-control" placeholder="Search orders..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
        </div>

        <div class="status-filter">
            <a href="?status=all<?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
               class="status-badge all <?php echo $status === 'all' ? 'active' : ''; ?>">
                <i class="fas fa-list"></i>
                All Orders
                <span class="badge bg-white text-dark"><?php echo $total_orders; ?></span>
            </a>
            <a href="?status=pending<?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
               class="status-badge pending <?php echo $status === 'pending' ? 'active' : ''; ?>">
                <i class="fas fa-clock"></i>
                Pending
                <span class="badge bg-white text-dark"><?php echo $status_counts['pending']; ?></span>
            </a>
            <a href="?status=processing<?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
               class="status-badge processing <?php echo $status === 'processing' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i>
                Processing
                <span class="badge bg-white text-dark"><?php echo $status_counts['processing']; ?></span>
            </a>
            <a href="?status=completed<?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
               class="status-badge completed <?php echo $status === 'completed' ? 'active' : ''; ?>">
                <i class="fas fa-check"></i>
                Completed
                <span class="badge bg-white text-dark"><?php echo $status_counts['completed']; ?></span>
            </a>
            <a href="?status=cancelled<?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
               class="status-badge cancelled <?php echo $status === 'cancelled' ? 'active' : ''; ?>">
                <i class="fas fa-times"></i>
                Cancelled
                <span class="badge bg-white text-dark"><?php echo $status_counts['cancelled']; ?></span>
            </a>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Payment</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($order = $orders_result->fetch_assoc()):
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
                                    <td>
                                        <?php echo strtoupper($order['payment_method']); ?>
                                        <?php if ($order['payment_method'] === 'upi'): ?>
                                            <i class="fas fa-mobile-alt ms-1"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo date('d M Y', strtotime($order['created_at'])); ?>
                                        <br>
                                        <small class="text-muted">
                                            <?php echo date('h:i A', strtotime($order['created_at'])); ?>
                                        </small>
                                    </td>
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
        // Search functionality
        let searchTimeout;
        document.getElementById('searchInput').addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const currentUrl = new URL(window.location.href);
                if (this.value) {
                    currentUrl.searchParams.set('search', this.value);
                } else {
                    currentUrl.searchParams.delete('search');
                }
                window.location.href = currentUrl.toString();
            }, 500);
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
