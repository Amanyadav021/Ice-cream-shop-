<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';

// Function to format price in Indian Rupees
function formatIndianPrice($price) {
    return '₹' . number_format($price, 2);
}

// Get all products
$stmt = $conn->prepare("SELECT * FROM products ORDER BY name");
$stmt->execute();
$products = $stmt->get_result();

// Get user session info
$logged_in = isset($_SESSION['user_id']);
$is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'];
$user_name = isset($_SESSION['name']) ? $_SESSION['name'] : '';

// Get cart count
$cart_count = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sweet Scoops - Premium Ice Cream Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <style>
        body {
            padding-top: 80px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .navbar-brand {
            font-size: 1.5rem;
            font-weight: bold;
            color: #fd7e14 !important;
        }
        .hero-section {
            background: linear-gradient(135deg, #ffa07a, #20b2aa);
            color: white;
            padding: 100px 0;
            margin-top: -80px;
            margin-bottom: 50px;
        }
        .hero-content {
            text-align: center;
            max-width: 800px;
            margin: 0 auto;
        }
        .hero-content h1 {
            font-size: 3.5rem;
            margin-bottom: 20px;
        }
        .hero-btn {
            padding: 12px 30px;
            font-size: 1.2rem;
            border-radius: 30px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 20px;
        }
        .hero-btn-primary {
            background-color: white;
            color: #fd7e14;
            border: none;
        }
        .hero-btn-primary:hover {
            background-color: #f8f9fa;
            color: #dc6a12;
        }
        .section-title {
            text-align: center;
            margin-bottom: 50px;
        }
        .section-title h2 {
            color: #fd7e14;
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
            margin-bottom: 30px;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .card-img-wrapper {
            height: 200px;
            overflow: hidden;
            border-radius: 15px 15px 0 0;
        }
        .card-img-top {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .card-body {
            padding: 20px;
        }
        .card-title {
            color: #fd7e14;
            font-size: 1.25rem;
            margin-bottom: 10px;
        }
        .price-tag {
            font-size: 1.5rem;
            color: #28a745;
            margin: 15px 0;
            font-weight: bold;
        }
        .btn-primary {
            background-color: #fd7e14;
            border-color: #fd7e14;
        }
        .btn-primary:hover {
            background-color: #dc6a12;
            border-color: #dc6a12;
        }
        #cartCount {
            position: relative;
            top: -10px;
            right: 5px;
            background-color: #dc3545;
            color: white;
            border-radius: 50%;
            padding: 3px 6px;
            font-size: 0.7rem;
        }
        .toast-success {
            background: #28a745;
            color: white;
        }
        .toast-error {
            background: #dc3545;
            color: white;
        }
        .user-welcome {
            color: #6c757d;
            margin-right: 15px;
        }
        footer {
            background-color: #343a40;
            color: white;
            padding: 20px 0;
            margin-top: auto;
        }
        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .social-links a {
            color: white;
            margin-left: 15px;
            font-size: 1.2rem;
        }
        .social-links a:hover {
            color: #fd7e14;
        }
        main {
            flex: 1;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-ice-cream"></i> Sweet Scoops
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if ($logged_in): ?>
                        <?php if ($is_admin): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="admin/dashboard.php">
                                    <i class="fas fa-cog"></i> Admin Dashboard
                                </a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link" href="profile.php">
                                <i class="fas fa-user"></i> Profile
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="cart.php">
                                <i class="fas fa-shopping-cart"></i> Cart
                                <?php if (!empty($_SESSION['cart'])): ?>
                                    <span class="badge bg-danger"><?php echo count($_SESSION['cart']); ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">
                                <i class="fas fa-user-plus"></i> Register
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <main>
        <div class="hero-section">
            <div class="container">
                <div class="hero-content">
                    <h1>Indulge in Pure Delight</h1>
                    <p class="lead">Discover our handcrafted ice creams made with the finest ingredients</p>
                    <a href="#menu" class="btn hero-btn hero-btn-primary">Explore Menu</a>
                </div>
            </div>
        </div>

        <div class="container" id="menu">
            <div class="section-title">
                <h2>Our Premium Selection</h2>
                <p class="text-muted">Handcrafted with love and tradition</p>
            </div>

            <div class="row">
                <?php while ($product = $products->fetch_assoc()): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-img-wrapper">
                                <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                     class="card-img-top" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>">
                            </div>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                <p class="card-text"><?php echo htmlspecialchars($product['description']); ?></p>
                                <p class="price-tag"><?php echo formatIndianPrice($product['price']); ?></p>
                                <?php if ($logged_in): ?>
                                    <button class="btn btn-primary w-100" onclick="addToCart(<?php echo $product['id']; ?>)">
                                        <i class="fas fa-cart-plus"></i> Add to Cart
                                    </button>
                                <?php else: ?>
                                    <a href="login.php" class="btn btn-primary w-100">
                                        <i class="fas fa-sign-in-alt"></i> Login to Order
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div>
                    <p class="mb-0">&copy; 2025 Sweet Scoops. All rights reserved.</p>
                </div>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script>
        function addToCart(productId) {
            fetch('add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'product_id=' + productId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update cart count
                    const cartCount = document.getElementById('cartCount');
                    if (cartCount) {
                        cartCount.textContent = data.cart_count;
                    }

                    // Show success message
                    Toastify({
                        text: "Product added to cart!",
                        duration: 3000,
                        gravity: "top",
                        position: "right",
                        className: "toast-success",
                    }).showToast();
                } else {
                    // Show error message
                    Toastify({
                        text: data.message || "Failed to add product to cart",
                        duration: 3000,
                        gravity: "top",
                        position: "right",
                        className: "toast-error",
                    }).showToast();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Toastify({
                    text: "An error occurred",
                    duration: 3000,
                    gravity: "top",
                    position: "right",
                    className: "toast-error",
                }).showToast();
            });
        }
    </script>
</body>
</html>