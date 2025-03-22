
# Ice Cream Shop Management System

A PHP-based web application for managing an ice cream shop with user and admin functionalities.

## Features

- User Authentication System
  - User registration and login
  - Admin and regular user roles
  - Session management

- Product Management (Admin)
  - Add, edit, and delete ice cream products
  - Manage product categories
  - Update stock levels
  - View order history

- Shopping Features (Users)
  - Browse ice cream products
  - Add items to cart
  - Update cart quantities
  - Checkout process
  - View order history

- Responsive Design
  - Modern and clean interface
  - Mobile-friendly layout
  - Bootstrap 5 framework

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Modern web browser

## Installation

1. Clone the repository to your web server directory:
   ```bash
   git clone [repository-url]
   ```

2. Create a MySQL database named 'icecream_shop'

3. Update database configuration in `config/database.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'your_username');
   define('DB_PASS', 'your_password');
   define('DB_NAME', 'icecream_shop');
   ```

4. Import the database schema:
   - The schema will be automatically created when you first access the application
   - Two default admin users will be created:
     - Username: admin1, Password: admin123
     - Username: admin2, Password: admin123

5. Set up your web server to point to the project directory

6. Access the application through your web browser

## Directory Structure

```
ice-cream-shop/
├── admin/
│   ├── dashboard.php
│   ├── add_product.php
│   └── edit_product.php
├── config/
│   └── database.php
├── includes/
│   └── auth.php
├── index.php
├── login.php
├── register.php
├── cart.php
├── checkout.php
└── README.md
```

## Security Features

- Password hashing using PHP's password_hash()
- Prepared statements for database queries
- Input validation and sanitization
- Session-based authentication
- CSRF protection
- XSS prevention

## Default Admin Credentials

```
Username: admin1
Password: admin123

Username: admin2
Password: admin123
```

## Contributing

1. Fork the repository
2. Create your feature branch
3. Commit your changes
4. Push to the branch
5. Create a new Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support, please open an issue in the repository or contact the development team. 
