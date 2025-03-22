<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

// Handle registration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate input
    if (empty($name) || empty($email) || empty($phone) || empty($password) || empty($confirm_password)) {
        $error = "Please fill in all fields.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Email already registered. Please use a different email.";
        } else {
            // Create new user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $email, $phone, $hashed_password);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Registration successful! Please login.";
                header('Location: login.php');
                exit();
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Sweet Scoops</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #ffa07a, #20b2aa);
            padding: 20px;
        }
        .register-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 40px;
            width: 100%;
            max-width: 500px;
            text-align: center;
        }
        .logo {
            color: #fd7e14;
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        .brand-name {
            color: #fd7e14;
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .welcome-text {
            color: #6c757d;
            margin-bottom: 30px;
        }
        .form-control {
            border-radius: 50px;
            padding: 12px 20px;
            margin-bottom: 20px;
            border: 2px solid #f1f1f1;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #fd7e14;
            box-shadow: 0 0 0 0.25rem rgba(253, 126, 20, 0.25);
        }
        .input-group {
            margin-bottom: 20px;
        }
        .input-group-text {
            border-radius: 50px 0 0 50px;
            border: 2px solid #f1f1f1;
            border-right: none;
            background: white;
            color: #6c757d;
        }
        .input-group .form-control {
            border-radius: 0 50px 50px 0;
            margin-bottom: 0;
        }
        .btn-register {
            background: #fd7e14;
            border: none;
            border-radius: 50px;
            padding: 12px 30px;
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            width: 100%;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        .btn-register:hover {
            background: #dc6a12;
            transform: translateY(-2px);
        }
        .login-link {
            color: #6c757d;
        }
        .login-link a {
            color: #fd7e14;
            text-decoration: none;
            font-weight: 600;
        }
        .login-link a:hover {
            color: #dc6a12;
        }
        .alert {
            border-radius: 15px;
            margin-bottom: 20px;
        }
        .password-requirements {
            font-size: 0.85rem;
            color: #6c757d;
            text-align: left;
            margin-top: -15px;
            margin-bottom: 20px;
            padding-left: 20px;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <i class="fas fa-ice-cream logo"></i>
        <h1 class="brand-name">Sweet Scoops</h1>
        <p class="welcome-text">Create your account to start ordering!</p>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="input-group">
                <span class="input-group-text">
                    <i class="fas fa-user"></i>
                </span>
                <input type="text" class="form-control" name="name" 
                       placeholder="Full Name" required 
                       value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
            </div>

            <div class="input-group">
                <span class="input-group-text">
                    <i class="fas fa-envelope"></i>
                </span>
                <input type="email" class="form-control" name="email" 
                       placeholder="Email address" required 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>

            <div class="input-group">
                <span class="input-group-text">
                    <i class="fas fa-phone"></i>
                </span>
                <input type="tel" class="form-control" name="phone" 
                       placeholder="Phone Number" required 
                       value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
            </div>

            <div class="input-group">
                <span class="input-group-text">
                    <i class="fas fa-lock"></i>
                </span>
                <input type="password" class="form-control" name="password" 
                       placeholder="Password" required>
            </div>

            <div class="input-group">
                <span class="input-group-text">
                    <i class="fas fa-lock"></i>
                </span>
                <input type="password" class="form-control" name="confirm_password" 
                       placeholder="Confirm Password" required>
            </div>

            <p class="password-requirements">
                <i class="fas fa-info-circle me-1"></i>Password must be at least 6 characters long
            </p>

            <button type="submit" class="btn btn-register">
                <i class="fas fa-user-plus me-2"></i>Create Account
            </button>
        </form>

        <p class="login-link">
            Already have an account? <a href="login.php">Login here</a>
        </p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>