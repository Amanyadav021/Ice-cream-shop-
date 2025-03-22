<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Sweet Scoops Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-size: .875rem;
            background-color: #f8f9fa;
        }

        .navbar {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,.1);
        }

        .navbar-brand {
            color: #fd7e14 !important;
            font-weight: bold;
            font-size: 1.5rem;
            padding-top: .75rem;
            padding-bottom: .75rem;
        }

        .navbar-brand i {
            margin-right: 8px;
        }

        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            padding: 48px 0 0;
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
        }

        .sidebar .nav-link {
            font-weight: 500;
            color: #333;
            padding: .5rem 1rem;
            border-radius: 0;
            margin: 0;
        }

        .sidebar .nav-link:hover {
            color: #fd7e14;
            background: rgba(253, 126, 20, 0.1);
        }

        .sidebar .nav-link.active {
            color: #fd7e14;
            background: rgba(253, 126, 20, 0.1);
        }

        .sidebar-heading {
            font-size: .75rem;
            text-transform: uppercase;
        }

        .navbar .navbar-toggler {
            top: .25rem;
            right: 1rem;
        }

        .navbar-nav .nav-link {
            color: #495057;
            padding: 8px 16px;
            margin: 0 4px;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .navbar-nav .nav-link:hover {
            background: rgba(253, 126, 20, 0.1);
            color: #fd7e14;
        }

        .btn-view-site {
            background: #e9ecef;
            color: #495057;
            border: none;
            margin-right: 8px;
        }

        .btn-logout {
            background: #dc3545;
            color: white;
            border: none;
        }

        main {
            padding-top: 1.5rem;
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
        }

        .table td {
            vertical-align: middle;
            border-color: #f1f3f5;
        }

        .btn-primary {
            background: #fd7e14;
            border-color: #fd7e14;
        }

        .btn-primary:hover {
            background: #e96b02;
            border-color: #e96b02;
        }

        .alert {
            border-radius: 12px;
            border: none;
            margin-bottom: 20px;
        }

        @media (max-width: 767.98px) {
            .sidebar {
                top: 5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container-fluid px-4">
            <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" 
                    data-bs-toggle="collapse" data-bs-target="#sidebarMenu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-ice-cream"></i>Sweet Scoops
            </a>
            <div class="d-flex">
                <a href="../index.php" class="btn btn-view-site d-none d-md-inline-block">
                    <i class="fas fa-store me-2"></i>View Site
                </a>
                <a href="../logout.php" class="btn btn-logout">
                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                </a>
            </div>
        </div>
    </nav>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebarMenu">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>" href="dashboard.php">
                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'products' ? 'active' : ''; ?>" href="products.php">
                    <i class="fas fa-ice-cream me-2"></i>Products
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'orders' ? 'active' : ''; ?>" href="orders.php">
                    <i class="fas fa-shopping-bag me-2"></i>Orders
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'users' ? 'active' : ''; ?>" href="users.php">
                    <i class="fas fa-users me-2"></i>Users
                </a>
            </li>
        </ul>
    </div>
    <main>
        <!-- Page content -->
    </main>
