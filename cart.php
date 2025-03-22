<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';

// Ensure user is logged in
requireLogin();

// Function to format price in Indian Rupees
function formatIndianPrice($price) {
    return '₹' . number_format($price, 2);
}

// Get cart items
$cart_items = [];
$total = 0;

if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $placeholders = str_repeat('?,', count($_SESSION['cart']) - 1) . '?';
    $stmt = $conn->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->bind_param(str_repeat('i', count($_SESSION['cart'])), ...array_keys($_SESSION['cart']));
    $stmt->execute();
    $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    foreach ($products as $product) {
        $quantity = $_SESSION['cart'][$product['id']];
        $cart_items[] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'price' => $product['price'],
            'image_url' => $product['image_url'],
            'quantity' => $quantity,
            'subtotal' => $product['price'] * $quantity
        ];
        $total += $product['price'] * $quantity;
    }
}

// Handle quantity updates via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $response = ['success' => false];

    if ($_POST['action'] === 'update_quantity') {
        $product_id = $_POST['product_id'] ?? 0;
        $quantity = $_POST['quantity'] ?? 0;

        if ($product_id > 0 && $quantity >= 0) {
            if ($quantity === 0) {
                unset($_SESSION['cart'][$product_id]);
            } else {
                $_SESSION['cart'][$product_id] = $quantity;
            }
            $response['success'] = true;
        }
    }

    echo json_encode($response);
    exit;
}

$tax_rate = 0.18; // 18% GST
$tax_amount = $total * $tax_rate;
$total_with_tax = $total + $tax_amount;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Sweet Scoops</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <style>
        body {
            background-color: #f8f9fa;
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
        .cart-container {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 10px;
        }
        .btn-primary {
            background-color: #fd7e14;
            border-color: #fd7e14;
        }
        .btn-primary:hover {
            background-color: #dc6a12;
            border-color: #dc6a12;
        }
        .quantity-control {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .quantity-btn {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            border: 1px solid #dee2e6;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        .quantity-btn:hover {
            background: #f8f9fa;
        }
        .quantity-input {
            width: 60px;
            text-align: center;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 5px;
        }
        .quantity-input::-webkit-inner-spin-button,
        .quantity-input::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        .price {
            color: #fd7e14;
            font-weight: bold;
        }
        .remove-item {
            color: #dc3545;
            cursor: pointer;
            transition: all 0.2s;
        }
        .remove-item:hover {
            color: #bb2d3b;
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
        .empty-cart {
            text-align: center;
            padding: 40px 0;
        }
        .empty-cart i {
            font-size: 4rem;
            color: #dee2e6;
            margin-bottom: 20px;
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
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-home"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">
                            <i class="fas fa-user"></i> Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <main>
        <div class="container py-4">
            <div class="cart-container">
                <h1 class="mb-4">
                    <i class="fas fa-shopping-cart me-2"></i>Shopping Cart
                </h1>

                <?php if (empty($cart_items)): ?>
                    <div class="empty-cart">
                        <i class="fas fa-shopping-cart mb-3"></i>
                        <h3>Your cart is empty</h3>
                        <p class="text-muted">Add some delicious ice cream to your cart!</p>
                        <a href="index.php" class="btn btn-primary mt-3">
                            <i class="fas fa-ice-cream me-2"></i>Continue Shopping
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th class="text-end">Subtotal</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cart_items as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if ($item['image_url']): ?>
                                                    <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                                         class="product-image me-3" 
                                                         alt="<?php echo htmlspecialchars($item['name']); ?>">
                                                <?php else: ?>
                                                    <img src="images/default-ice-cream.jpg" 
                                                         class="product-image me-3" 
                                                         alt="Default product image">
                                                <?php endif; ?>
                                                <span><?php echo htmlspecialchars($item['name']); ?></span>
                                            </div>
                                        </td>
                                        <td class="price"><?php echo formatIndianPrice($item['price']); ?></td>
                                        <td>
                                            <div class="quantity-control" data-product-id="<?php echo $item['id']; ?>">
                                                <button class="quantity-btn decrease">
                                                    <i class="fas fa-minus"></i>
                                                </button>
                                                <input type="number" class="quantity-input" 
                                                       value="<?php echo $item['quantity']; ?>" 
                                                       min="1" max="99">
                                                <button class="quantity-btn increase">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            </div>
                                        </td>
                                        <td class="text-end price item-subtotal">
                                            <?php echo formatIndianPrice($item['subtotal']); ?>
                                        </td>
                                        <td class="text-end">
                                            <i class="fas fa-trash remove-item" 
                                               data-product-id="<?php echo $item['id']; ?>"></i>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-end">Subtotal:</td>
                                    <td class="text-end price"><?php echo formatIndianPrice($total); ?></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end">GST (18%):</td>
                                    <td class="text-end price"><?php echo formatIndianPrice($tax_amount); ?></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                    <td class="text-end price"><strong><?php echo formatIndianPrice($total_with_tax); ?></strong></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <a href="index.php" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left me-2"></i>Continue Shopping
                        </a>
                        <a href="checkout.php" class="btn btn-primary">
                            Proceed to Checkout<i class="fas fa-arrow-right ms-2"></i>
                        </a>
                    </div>
                <?php endif; ?>
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
    document.addEventListener('DOMContentLoaded', function() {
        // Handle quantity changes
        document.querySelectorAll('.quantity-control').forEach(control => {
            const input = control.querySelector('.quantity-input');
            const decrease = control.querySelector('.decrease');
            const increase = control.querySelector('.increase');
            const productId = control.dataset.productId;

            decrease.addEventListener('click', () => {
                const currentValue = parseInt(input.value);
                if (currentValue > 1) {
                    input.value = currentValue - 1;
                    updateQuantity(productId, currentValue - 1);
                }
            });

            increase.addEventListener('click', () => {
                const currentValue = parseInt(input.value);
                if (currentValue < 99) {
                    input.value = currentValue + 1;
                    updateQuantity(productId, currentValue + 1);
                }
            });

            input.addEventListener('change', () => {
                let value = parseInt(input.value);
                if (isNaN(value) || value < 1) value = 1;
                if (value > 99) value = 99;
                input.value = value;
                updateQuantity(productId, value);
            });
        });

        // Handle remove item
        document.querySelectorAll('.remove-item').forEach(button => {
            button.addEventListener('click', () => {
                const productId = button.dataset.productId;
                updateQuantity(productId, 0);
            });
        });

        function updateQuantity(productId, quantity) {
            const formData = new FormData();
            formData.append('action', 'update_quantity');
            formData.append('product_id', productId);
            formData.append('quantity', quantity);

            fetch('cart.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            })
            .catch(error => {
                Toastify({
                    text: "Error updating cart",
                    duration: 3000,
                    close: true,
                    gravity: "top",
                    position: "right",
                    backgroundColor: "#dc3545"
                }).showToast();
            });
        }
    });
    </script>
</body>
</html>