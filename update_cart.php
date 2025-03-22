<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login to update cart']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$product_id = $_POST['product_id'] ?? '';
$change = intval($_POST['change'] ?? 0);

if (empty($product_id) || $change === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid request parameters']);
    exit();
}

// Check if product exists and has stock
$stmt = $conn->prepare("SELECT stock FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit();
}

// Initialize cart if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$current_quantity = $_SESSION['cart'][$product_id] ?? 0;
$new_quantity = $current_quantity + $change;

// Validate new quantity
if ($new_quantity < 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid quantity']);
    exit();
}

if ($new_quantity > $product['stock']) {
    echo json_encode(['success' => false, 'message' => 'Not enough stock available']);
    exit();
}

// Update cart
if ($new_quantity === 0) {
    unset($_SESSION['cart'][$product_id]);
} else {
    $_SESSION['cart'][$product_id] = $new_quantity;
}

echo json_encode(['success' => true, 'message' => 'Cart updated successfully']);
?>
