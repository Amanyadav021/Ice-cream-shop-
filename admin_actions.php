<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';

// Ensure user is admin
requireAdmin();

// Get the action
$action = $_GET['action'] ?? '';

// Function to format price in Indian Rupees
function formatIndianPrice($price) {
    return '₹' . number_format($price, 2);
}

// Handle different actions
switch ($action) {
    case 'save_upi':
        $upi_id = $_POST['upi_id'] ?? '';
        if (empty($upi_id)) {
            echo json_encode(['success' => false, 'message' => 'UPI ID is required']);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO settings (upi_id) VALUES (?) ON DUPLICATE KEY UPDATE upi_id = ?");
        $stmt->bind_param("ss", $upi_id, $upi_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save UPI ID']);
        }
        break;

    case 'update_order_status':
        $order_id = $_POST['order_id'] ?? '';
        $status = $_POST['status'] ?? '';
        
        if (empty($order_id) || empty($status)) {
            echo json_encode(['success' => false, 'message' => 'Order ID and status are required']);
            exit;
        }

        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $order_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update order status']);
        }
        break;

    case 'get_order_details':
        $order_id = $_GET['order_id'] ?? '';
        
        if (empty($order_id)) {
            echo 'Order ID is required';
            exit;
        }

        // Get order details
        $stmt = $conn->prepare("
            SELECT o.*, u.name as customer_name, u.email as customer_email, 
                   u.phone as customer_phone
            FROM orders o
            JOIN users u ON o.user_id = u.id
            WHERE o.id = ?
        ");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();

        if (!$order) {
            echo 'Order not found';
            exit;
        }

        // Get order items
        $stmt = $conn->prepare("
            SELECT oi.*, p.name as product_name, p.image as product_image
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
        ");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Output order details
        ?>
        <div class="order-details">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h6 class="mb-3">Customer Details</h6>
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($order['customer_email']); ?></p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['customer_phone']); ?></p>
                </div>
                <div class="col-md-6">
                    <h6 class="mb-3">Shipping Details</h6>
                    <p><strong>Address:</strong> <?php echo htmlspecialchars($order['shipping_address']); ?></p>
                    <p><strong>City:</strong> <?php echo htmlspecialchars($order['shipping_city']); ?></p>
                    <p><strong>State:</strong> <?php echo htmlspecialchars($order['shipping_state']); ?></p>
                    <p><strong>Pincode:</strong> <?php echo htmlspecialchars($order['shipping_pincode']); ?></p>
                </div>
            </div>

            <h6 class="mb-3">Order Items</h6>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="../<?php echo htmlspecialchars($item['product_image']); ?>" 
                                             alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                             class="me-2" style="width: 50px; height: 50px; object-fit: cover;">
                                        <?php echo htmlspecialchars($item['product_name']); ?>
                                    </div>
                                </td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td><?php echo formatIndianPrice($item['price']); ?></td>
                                <td><?php echo formatIndianPrice($item['price'] * $item['quantity']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-end"><strong>Tax (5%):</strong></td>
                            <td><?php echo formatIndianPrice($order['tax_amount']); ?></td>
                        </tr>
                        <tr>
                            <td colspan="3" class="text-end"><strong>Total Amount:</strong></td>
                            <td><strong><?php echo formatIndianPrice($order['total_amount']); ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="row mt-4">
                <div class="col-md-6">
                    <h6 class="mb-3">Payment Details</h6>
                    <p><strong>Method:</strong> <?php echo strtoupper($order['payment_method']); ?></p>
                    <p><strong>Status:</strong> 
                        <span class="badge bg-<?php 
                            echo $order['status'] === 'completed' ? 'success' : 
                                ($order['status'] === 'pending' ? 'warning' : 
                                    ($order['status'] === 'processing' ? 'primary' : 'danger')); 
                        ?>">
                            <?php echo ucfirst($order['status']); ?>
                        </span>
                    </p>
                </div>
                <div class="col-md-6">
                    <h6 class="mb-3">Order Information</h6>
                    <p><strong>Order ID:</strong> #<?php echo $order['id']; ?></p>
                    <p><strong>Date:</strong> <?php echo date('d M Y h:i A', strtotime($order['created_at'])); ?></p>
                </div>
            </div>
        </div>
        <?php
        break;

    case 'check_new_orders':
        $new_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'")->fetch_assoc()['count'];
        echo json_encode(['new_orders' => $new_orders]);
        break;

    case 'delete_product':
        $product_id = $_POST['product_id'] ?? '';
        
        if (empty($product_id)) {
            echo json_encode(['success' => false, 'message' => 'Product ID is required']);
            exit;
        }

        // Get product image path
        $stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();

        // Delete product
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        
        if ($stmt->execute()) {
            // Delete product image if exists
            if ($product && $product['image'] && file_exists($product['image'])) {
                unlink($product['image']);
            }
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete product']);
        }
        break;

    case 'get_user_details':
        $user_id = $_GET['user_id'] ?? '';
        
        if (empty($user_id)) {
            echo 'User ID is required';
            exit;
        }

        // Get user details
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND is_admin = 0");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if (!$user) {
            echo 'User not found';
            exit;
        }

        // Get user's orders
        $stmt = $conn->prepare("
            SELECT o.*, COUNT(oi.id) as items_count
            FROM orders o
            LEFT JOIN order_items oi ON o.id = oi.order_id
            WHERE o.user_id = ?
            GROUP BY o.id
            ORDER BY o.created_at DESC
            LIMIT 5
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Output user details
        ?>
        <div class="user-details">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h6 class="mb-3">Personal Information</h6>
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($user['name']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($user['phone']); ?></p>
                </div>
                <div class="col-md-6">
                    <h6 class="mb-3">Address Information</h6>
                    <?php if ($user['address']): ?>
                        <p><strong>Address:</strong> <?php echo htmlspecialchars($user['address']); ?></p>
                        <p><strong>City:</strong> <?php echo htmlspecialchars($user['city']); ?></p>
                        <p><strong>State:</strong> <?php echo htmlspecialchars($user['state']); ?></p>
                        <p><strong>Pincode:</strong> <?php echo htmlspecialchars($user['pincode']); ?></p>
                    <?php else: ?>
                        <p class="text-muted">No address information provided</p>
                    <?php endif; ?>
                </div>
            </div>

            <h6 class="mb-3">Recent Orders</h6>
            <?php if ($orders): ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Date</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>#<?php echo $order['id']; ?></td>
                                    <td><?php echo date('d M Y', strtotime($order['created_at'])); ?></td>
                                    <td><?php echo $order['items_count']; ?> items</td>
                                    <td><?php echo formatIndianPrice($order['total_amount']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $order['status'] === 'completed' ? 'success' : 
                                                ($order['status'] === 'pending' ? 'warning' : 
                                                    ($order['status'] === 'processing' ? 'primary' : 'danger')); 
                                        ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted">No orders found</p>
            <?php endif; ?>
        </div>
        <?php
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
?>
