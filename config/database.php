<?php
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'icecream_shop';

// Create connection
$conn = new mysqli($host, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS $database";
if ($conn->query($sql) === FALSE) {
    die("Error creating database: " . $conn->error);
}

// Select database
$conn->select_db($database);

// Create users table with address fields
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    city VARCHAR(50),
    state VARCHAR(50),
    pincode VARCHAR(10),
    is_admin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === FALSE) {
    die("Error creating users table: " . $conn->error);
}

// Add address columns if they don't exist
$columns_to_add = [
    'address' => 'TEXT',
    'city' => 'VARCHAR(50)',
    'state' => 'VARCHAR(50)',
    'pincode' => 'VARCHAR(10)'
];

foreach ($columns_to_add as $column => $type) {
    $result = $conn->query("SHOW COLUMNS FROM users LIKE '$column'");
    if ($result->num_rows === 0) {
        $sql = "ALTER TABLE users ADD COLUMN $column $type";
        $conn->query($sql);
    }
}

// Create settings table
$sql = "CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    upi_id VARCHAR(255),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === FALSE) {
    die("Error creating settings table: " . $conn->error);
}

// Insert default settings if not exists
$result = $conn->query("SELECT id FROM settings LIMIT 1");
if ($result->num_rows === 0) {
    $conn->query("INSERT INTO settings (upi_id) VALUES (NULL)");
}

// Create products table
$sql = "CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    category VARCHAR(50),
    stock INT DEFAULT 50,
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === FALSE) {
    die("Error creating products table: " . $conn->error);
}

// Create orders table
$sql = "CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    tax_amount DECIMAL(10,2) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    shipping_name VARCHAR(100) NOT NULL,
    shipping_email VARCHAR(100) NOT NULL,
    shipping_phone VARCHAR(20) NOT NULL,
    shipping_address TEXT NOT NULL,
    shipping_city VARCHAR(50) NOT NULL,
    shipping_state VARCHAR(50) NOT NULL,
    shipping_pincode VARCHAR(10) NOT NULL,
    payment_method VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)";

if ($conn->query($sql) === FALSE) {
    die("Error creating orders table: " . $conn->error);
}

// Create order_items table
$sql = "CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
)";

if ($conn->query($sql) === FALSE) {
    die("Error creating order_items table: " . $conn->error);
}

// Check if admin user exists, if not create one
$stmt = $conn->prepare("SELECT id FROM users WHERE email = 'admin@example.com'");
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $password = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, is_admin) VALUES ('Admin', 'admin@example.com', ?, TRUE)");
    $stmt->bind_param("s", $password);
    $stmt->execute();
}

// Add Indian ice cream products if none exist
$stmt = $conn->prepare("SELECT id FROM products LIMIT 1");
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $indian_products = [
        [
            'name' => 'Malai Kulfi',
            'description' => 'Traditional Indian ice cream made with thickened milk, cardamom, and pistachios',
            'price' => 89.00,
            'category' => 'Traditional',
            'stock' => 50,
            'image_url' => 'images/products/malai-kulfi.jpg'
        ],
        [
            'name' => 'Mango Lassi Ice Cream',
            'description' => 'Creamy mango ice cream inspired by the classic Indian drink',
            'price' => 99.00,
            'category' => 'Fusion',
            'stock' => 50,
            'image_url' => 'images/products/mango-lassi.jpg'
        ],
        [
            'name' => 'Paan Ice Cream',
            'description' => 'Unique blend of betel leaves, rose petals, and gulkand in creamy ice cream',
            'price' => 109.00,
            'category' => 'Special',
            'stock' => 40,
            'image_url' => 'images/products/paan.jpg'
        ],
        [
            'name' => 'Kesar Pista',
            'description' => 'Premium ice cream flavored with saffron and garnished with pistachios',
            'price' => 129.00,
            'category' => 'Premium',
            'stock' => 45,
            'image_url' => 'images/products/kesar-pista.jpg'
        ],
        [
            'name' => 'Rose Falooda',
            'description' => 'Rose-flavored ice cream with vermicelli, basil seeds, and nuts',
            'price' => 119.00,
            'category' => 'Traditional',
            'stock' => 50,
            'image_url' => 'images/products/rose-falooda.jpg'
        ],
        [
            'name' => 'Tender Coconut',
            'description' => 'Fresh coconut ice cream with real coconut pieces',
            'price' => 99.00,
            'category' => 'Classic',
            'stock' => 50,
            'image_url' => 'images/products/tender-coconut.jpg'
        ],
        [
            'name' => 'Gulab Jamun',
            'description' => 'Ice cream inspired by the classic Indian dessert',
            'price' => 119.00,
            'category' => 'Fusion',
            'stock' => 45,
            'image_url' => 'images/products/gulab-jamun.jpg'
        ],
        [
            'name' => 'Chikoo Ice Cream',
            'description' => 'Sapota fruit ice cream with a natural caramel-like taste',
            'price' => 89.00,
            'category' => 'Fruit',
            'stock' => 40,
            'image_url' => 'images/products/chikoo.jpg'
        ],
        [
            'name' => 'Anjeer Honey',
            'description' => 'Fig and honey ice cream with roasted almonds',
            'price' => 139.00,
            'category' => 'Premium',
            'stock' => 35,
            'image_url' => 'images/products/anjeer-honey.jpg'
        ],
        [
            'name' => 'Pista Badam',
            'description' => 'Rich ice cream loaded with pistachios and almonds',
            'price' => 129.00,
            'category' => 'Premium',
            'stock' => 40,
            'image_url' => 'images/products/pista-badam.jpg'
        ],
        [
            'name' => 'Filter Coffee',
            'description' => 'South Indian filter coffee flavored ice cream',
            'price' => 109.00,
            'category' => 'Fusion',
            'stock' => 45,
            'image_url' => 'images/products/filter-coffee.jpg'
        ],
        [
            'name' => 'Pan Masala Special',
            'description' => 'Premium paan ice cream with silver warq and dry fruits',
            'price' => 149.00,
            'category' => 'Special',
            'stock' => 30,
            'image_url' => 'images/products/pan-masala.jpg'
        ],
        [
            'name' => 'Gajar Ka Halwa',
            'description' => 'Ice cream version of the beloved carrot dessert',
            'price' => 119.00,
            'category' => 'Fusion',
            'stock' => 40,
            'image_url' => 'images/products/gajar-halwa.jpg'
        ],
        [
            'name' => 'Rasgulla Sundae',
            'description' => 'Bengali rasgulla pieces in creamy vanilla ice cream',
            'price' => 129.00,
            'category' => 'Fusion',
            'stock' => 45,
            'image_url' => 'images/products/rasgulla.jpg'
        ],
        [
            'name' => 'Jackfruit Delight',
            'description' => 'Tropical jackfruit ice cream with caramelized bits',
            'price' => 109.00,
            'category' => 'Fruit',
            'stock' => 40,
            'image_url' => 'images/products/jackfruit.jpg'
        ]
    ];

    $stmt = $conn->prepare("INSERT INTO products (name, description, price, category, stock, image_url) VALUES (?, ?, ?, ?, ?, ?)");
    
    foreach ($indian_products as $product) {
        $stmt->bind_param("ssdsss", 
            $product['name'], 
            $product['description'], 
            $product['price'], 
            $product['category'],
            $product['stock'],
            $product['image_url']
        );
        $stmt->execute();
    }
}
?>
