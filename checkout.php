<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Function to format price in Indian Rupees
function formatIndianPrice($price) {
    return '₹' . number_format($price, 2);
}

// Get user details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Get cart items
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit();
}

// Get UPI settings
$stmt = $conn->prepare("SELECT upi_id FROM settings LIMIT 1");
$stmt->execute();
$settings = $stmt->get_result()->fetch_assoc();

// Calculate totals
$subtotal = 0;
$cart_items = [];

foreach ($_SESSION['cart'] as $product_id => $quantity) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    
    if ($product) {
        $item_total = $product['price'] * $quantity;
        $subtotal += $item_total;
        $cart_items[] = [
            'product' => $product,
            'quantity' => $quantity,
            'total' => $item_total
        ];
    }
}

$tax = $subtotal * 0.18; // 18% GST
$total = $subtotal + $tax;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $pincode = trim($_POST['pincode'] ?? '');
    $payment_method = $_POST['payment_method'] ?? '';

    $errors = [];

    // Validate input
    if (empty($name)) $errors[] = "Name is required";
    if (empty($email)) $errors[] = "Email is required";
    if (empty($phone)) $errors[] = "Phone is required";
    if (empty($address)) $errors[] = "Address is required";
    if (empty($city)) $errors[] = "City is required";
    if (empty($state)) $errors[] = "State is required";
    if (empty($pincode)) $errors[] = "PIN code is required";
    if (empty($payment_method)) $errors[] = "Payment method is required";
    if ($payment_method === 'upi' && empty($settings['upi_id'])) $errors[] = "UPI payment is not available";

    if (empty($errors)) {
        // Start transaction
        $conn->begin_transaction();

        try {
            // Create order
            $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, tax_amount, status, 
                                                      shipping_name, shipping_email, shipping_phone,
                                                      shipping_address, shipping_city, shipping_state,
                                                      shipping_pincode, payment_method) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $status = 'pending';
            $stmt->bind_param("iddsssssssss", 
                $_SESSION['user_id'], $total, $tax, $status,
                $name, $email, $phone, $address, $city, $state, $pincode, $payment_method
            );
            $stmt->execute();
            $order_id = $conn->insert_id;

            // Create order items
            $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) 
                                  VALUES (?, ?, ?, ?)");
            
            foreach ($cart_items as $item) {
                $stmt->bind_param("iiid", 
                    $order_id, 
                    $item['product']['id'], 
                    $item['quantity'], 
                    $item['product']['price']
                );
                $stmt->execute();
            }

            // Update user address if changed
            if ($address !== $user['address'] || $city !== $user['city'] || 
                $state !== $user['state'] || $pincode !== $user['pincode']) {
                
                $stmt = $conn->prepare("UPDATE users SET address = ?, city = ?, state = ?, pincode = ? 
                                      WHERE id = ?");
                $stmt->bind_param("ssssi", $address, $city, $state, $pincode, $_SESSION['user_id']);
                $stmt->execute();
            }

            // Clear cart
            unset($_SESSION['cart']);

            // Commit transaction
            $conn->commit();

            // Redirect to success page
            header("Location: order_success.php?order_id=" . $order_id);
            exit();

        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            $errors[] = "Failed to create order. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Sweet Scoops</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 80px;
        }
        .navbar-brand {
            color: #fd7e14 !important;
            font-weight: bold;
        }
        .section-title {
            color: #fd7e14;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #fd7e14;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }
        .btn-primary {
            background-color: #fd7e14;
            border-color: #fd7e14;
        }
        .btn-primary:hover {
            background-color: #dc6a12;
            border-color: #dc6a12;
        }
        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .summary-total {
            font-size: 1.2rem;
            font-weight: bold;
            border-top: 2px solid #dee2e6;
            padding-top: 10px;
            margin-top: 10px;
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
                        <a class="nav-link" href="cart.php">
                            <i class="fas fa-shopping-cart"></i> Cart
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

    <div class="container mb-5">
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo $error; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="POST" class="needs-validation" novalidate>
                            <div class="mb-4">
                                <div class="section-title">
                                    <i class="fas fa-user me-2"></i>Contact Information
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Name</label>
                                        <input type="text" class="form-control" name="name" 
                                               value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                        <div class="invalid-feedback">Please enter your name.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" name="email" 
                                               value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                        <div class="invalid-feedback">Please enter a valid email.</div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Phone</label>
                                        <input type="tel" class="form-control" name="phone" 
                                               value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>
                                        <div class="invalid-feedback">Please enter your phone number.</div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <div class="section-title">
                                    <i class="fas fa-map-marker-alt me-2"></i>Shipping Address
                                </div>
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label">Address</label>
                                        <textarea class="form-control" name="address" rows="3" required><?php 
                                            echo htmlspecialchars($user['address'] ?? ''); 
                                        ?></textarea>
                                        <div class="invalid-feedback">Please enter your address.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">City</label>
                                        <input type="text" class="form-control" name="city" 
                                               value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>" required>
                                        <div class="invalid-feedback">Please enter your city.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">State</label>
                                        <input type="text" class="form-control" name="state" 
                                               value="<?php echo htmlspecialchars($user['state'] ?? ''); ?>" required>
                                        <div class="invalid-feedback">Please enter your state.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">PIN Code</label>
                                        <input type="text" class="form-control" name="pincode" 
                                               value="<?php echo htmlspecialchars($user['pincode'] ?? ''); ?>" required>
                                        <div class="invalid-feedback">Please enter your PIN code.</div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <div class="section-title">
                                    <i class="fas fa-credit-card me-2"></i>Payment Method
                                </div>
                                <div class="row g-3">
                                    <div class="col-12">
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="radio" name="payment_method" 
                                                   id="cod" value="cod" required>
                                            <label class="form-check-label" for="cod">
                                                <i class="fas fa-money-bill-wave me-2"></i>Cash on Delivery
                                            </label>
                                        </div>
                                        <?php if (!empty($settings['upi_id'])): ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="payment_method" 
                                                       id="upi" value="upi" required>
                                                <label class="form-check-label" for="upi">
                                                    <i class="fas fa-mobile-alt me-2"></i>UPI Payment
                                                </label>
                                            </div>
                                            <div id="upiDetails" class="mt-3 d-none">
                                                <div class="alert alert-info">
                                                    <i class="fas fa-info-circle me-2"></i>
                                                    Please make the payment to UPI ID: 
                                                    <strong><?php echo htmlspecialchars($settings['upi_id']); ?></strong>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        <div class="invalid-feedback">Please select a payment method.</div>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg w-100">
                                <i class="fas fa-lock me-2"></i>Place Order
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Order Summary</h5>
                        <?php foreach ($cart_items as $item): ?>
                            <div class="d-flex align-items-center mb-3">
                                <img src="<?php echo htmlspecialchars($item['product']['image_url']); ?>" 
                                     class="product-image me-3" alt="<?php echo htmlspecialchars($item['product']['name']); ?>">
                                <div class="flex-grow-1">
                                    <h6 class="mb-0"><?php echo htmlspecialchars($item['product']['name']); ?></h6>
                                    <small class="text-muted">
                                        <?php echo $item['quantity']; ?> × <?php echo formatIndianPrice($item['product']['price']); ?>
                                    </small>
                                </div>
                                <div class="ms-3">
                                    <?php echo formatIndianPrice($item['total']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <hr>

                        <div class="summary-item">
                            <span>Subtotal</span>
                            <span><?php echo formatIndianPrice($subtotal); ?></span>
                        </div>
                        <div class="summary-item">
                            <span>GST (18%)</span>
                            <span><?php echo formatIndianPrice($tax); ?></span>
                        </div>
                        <div class="summary-item summary-total">
                            <span>Total</span>
                            <span><?php echo formatIndianPrice($total); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        (function() {
            'use strict';
            var forms = document.querySelectorAll('.needs-validation');
            Array.from(forms).forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        })();

        // Auto-fill address fields from profile
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('address').value = <?php echo json_encode($user['address'] ?? ''); ?>;
            document.getElementById('city').value = <?php echo json_encode($user['city'] ?? ''); ?>;
            document.getElementById('state').value = <?php echo json_encode($user['state'] ?? ''); ?>;
            document.getElementById('pincode').value = <?php echo json_encode($user['pincode'] ?? ''); ?>;
        });

        // Show/hide UPI details
        document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const upiDetails = document.getElementById('upiDetails');
                if (this.value === 'upi') {
                    upiDetails.classList.remove('d-none');
                } else {
                    upiDetails.classList.add('d-none');
                }
            });
        });
    </script>
</body>
</html>