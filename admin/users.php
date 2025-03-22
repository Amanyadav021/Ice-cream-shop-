<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

// Ensure user is admin
requireAdmin();

// Get filter parameters
$search = $_GET['search'] ?? '';

// Build query
$query = "SELECT * FROM users WHERE is_admin = 0";
$params = [];
$types = "";

if ($search) {
    $search = "%$search%";
    $query .= " AND (name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $params = [$search, $search, $search];
    $types = "sss";
}

$query .= " ORDER BY name ASC";

// Prepare and execute query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$users_result = $stmt->get_result();

// Get total users and orders
$total_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE is_admin = 0")->fetch_assoc()['count'];
$total_orders = $conn->query("SELECT COUNT(DISTINCT user_id) as count FROM orders")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - Sweet Scoops Admin</title>
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
        .stats-card {
            background: linear-gradient(45deg, #fd7e14, #ffc107);
            color: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
        }
        .stats-card i {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        .stats-card .number {
            font-size: 2rem;
            font-weight: bold;
        }
        .search-box {
            position: relative;
        }
        .search-box .form-control {
            padding-left: 40px;
            border-radius: 50px;
            border: 2px solid #e9ecef;
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
        .user-card {
            transition: all 0.3s ease;
        }
        .user-card:hover {
            background-color: #f8f9fa;
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
                        <a class="nav-link" href="orders.php">
                            <i class="fas fa-shopping-bag"></i> Orders
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="users.php">
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Users</h2>
            <div class="search-box">
                <i class="fas fa-search search-icon"></i>
                <input type="text" class="form-control" id="searchInput" 
                       placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-6">
                <div class="stats-card">
                    <i class="fas fa-users"></i>
                    <div class="number"><?php echo $total_users; ?></div>
                    <div>Total Users</div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="stats-card">
                    <i class="fas fa-shopping-cart"></i>
                    <div class="number"><?php echo $total_orders; ?></div>
                    <div>Users with Orders</div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Address</th>
                                <th>Orders</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($user = $users_result->fetch_assoc()):
                                // Get user's orders count
                                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = ?");
                                $stmt->bind_param("i", $user['id']);
                                $stmt->execute();
                                $orders_count = $stmt->get_result()->fetch_assoc()['count'];
                            ?>
                                <tr class="user-card">
                                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                    <td>
                                        <?php if ($user['address']): ?>
                                            <?php echo htmlspecialchars($user['address']); ?>,
                                            <?php echo htmlspecialchars($user['city']); ?>,
                                            <?php echo htmlspecialchars($user['state']); ?> - 
                                            <?php echo htmlspecialchars($user['pincode']); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Not provided</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="orders.php?search=<?php echo urlencode($user['email']); ?>" 
                                           class="badge bg-primary text-decoration-none">
                                            <?php echo $orders_count; ?> orders
                                        </a>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary btn-action view-user"
                                                data-id="<?php echo $user['id']; ?>">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- User Details Modal -->
    <div class="modal fade" id="userModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">User Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="userDetails">
                    <!-- User details will be loaded here -->
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

        // View User Details
        document.querySelectorAll('.view-user').forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.dataset.id;
                const modal = new bootstrap.Modal(document.getElementById('userModal'));
                
                fetch(`../admin_actions.php?action=get_user_details&user_id=${userId}`)
                    .then(response => response.text())
                    .then(html => {
                        document.getElementById('userDetails').innerHTML = html;
                        modal.show();
                    })
                    .catch(error => {
                        Toastify({
                            text: "Failed to load user details",
                            duration: 3000,
                            gravity: "top",
                            position: "right",
                            backgroundColor: "#dc3545"
                        }).showToast();
                    });
            });
        });
    </script>
</body>
</html>
