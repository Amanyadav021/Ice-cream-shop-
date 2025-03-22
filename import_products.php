<?php
require_once 'config/database.php';

// First, disable foreign key checks
$conn->query("SET FOREIGN_KEY_CHECKS = 0");

// Clear existing products
$conn->query("TRUNCATE TABLE products");

// Re-enable foreign key checks
$conn->query("SET FOREIGN_KEY_CHECKS = 1");

// Insert products
$products = [
    ['Malai Kulfi', 'Traditional Indian ice cream made with thickened milk, cardamom, and pistachios', 89.00, 'Classic', 'images/products/malai-kulfi.jpg', 50],
    ['Mango Lassi Ice Cream', 'Creamy mango ice cream inspired by the classic Indian drink', 99.00, 'Premium', 'images/products/mango-lassi.jpg', 45],
    ['Paan Ice Cream', 'Unique blend of betel leaves, rose petals, and gulkand in creamy ice cream', 109.00, 'Special', 'images/products/paan.jpg', 40],
    ['Kesar Pista', 'Premium ice cream flavored with saffron and garnished with pistachios', 129.00, 'Premium', 'images/products/kesar-pista.jpg', 35],
    ['Rose Falooda', 'Rose-flavored ice cream with vermicelli, basil seeds, and nuts', 119.00, 'Special', 'images/products/rose-falooda.jpg', 30],
    ['Tender Coconut', 'Fresh coconut ice cream with real coconut pieces', 99.00, 'Classic', 'images/products/tender-coconut.jpg', 40],
    ['Gulab Jamun', 'Ice cream inspired by the classic Indian dessert', 119.00, 'Special', 'images/products/gulab-jamun.jpg', 35],
    ['Chikoo Ice Cream', 'Sapota fruit ice cream with a natural caramel-like taste', 89.00, 'Classic', 'images/products/chikoo.jpg', 45],
    ['Anjeer Honey', 'Fig and honey ice cream with roasted almonds', 139.00, 'Premium', 'images/products/anjeer-honey.jpg', 30],
    ['Pista Badam', 'Rich ice cream loaded with pistachios and almonds', 129.00, 'Premium', 'images/products/pista-badam.jpg', 40],
    ['Filter Coffee', 'South Indian filter coffee flavored ice cream', 109.00, 'Special', 'images/products/filter-coffee.jpg', 35],
    ['Pan Masala Special', 'Premium paan ice cream with silver warq and dry fruits', 149.00, 'Premium', 'images/products/pan-masala.jpg', 25],
    ['Gajar Ka Halwa', 'Ice cream version of the beloved carrot dessert', 119.00, 'Special', 'images/products/gajar-halwa.jpg', 30],
    ['Rasgulla Sundae', 'Bengali rasgulla pieces in creamy vanilla ice cream', 129.00, 'Special', 'images/products/rasgulla.jpg', 35],
    ['Jackfruit Delight', 'Tropical jackfruit ice cream with caramelized bits', 109.00, 'Classic', 'images/products/jackfruit.jpg', 40]
];

$stmt = $conn->prepare("INSERT INTO products (name, description, price, category, image_url, stock) VALUES (?, ?, ?, ?, ?, ?)");

foreach ($products as $product) {
    $stmt->bind_param("ssdssi", $product[0], $product[1], $product[2], $product[3], $product[4], $product[5]);
    if (!$stmt->execute()) {
        echo "Error adding product {$product[0]}: " . $conn->error . "\n";
    }
}

echo "✅ Products imported successfully!\n";
echo "Total products added: " . count($products) . "\n";

// Verify the products
$result = $conn->query("SELECT COUNT(*) as count FROM products");
$row = $result->fetch_assoc();
echo "Products in database: " . $row['count'] . "\n";
?>
