<?php
// register.php - Fixed Layout for exam_timetable database

require_once 'auth.php';
ensureUsersTableExists();

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($username) || empty($email) || empty($password)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        $conn = getDB();
        
        // Check if username or email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = 'Username or email already exists.';
        } else {
            // Insert new user
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $email, $password_hash);
            
            if ($stmt->execute()) {
                $success = 'Registration successful! You can now <a href="login.php">login</a>.';
            } else {
                $error = 'Registration failed. Please try again.';
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - ADSU Timetable System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ============================================
           RESET & BASE
           ============================================ */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0a0e1a 0%, #1a1a3e 50%, #2d1b4e 100%);
            color: #ffffff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        /* ============================================
           REGISTER CONTAINER
           ============================================ */
        .register-container {
            width: 100%;
            max-width: 440px;
        }

        .register-box {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            padding: 45px 40px;
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.5);
            animation: fadeInUp 0.6s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ============================================
           LOGO / HEADER
           ============================================ */
        .register-logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .register-logo .icon-wrapper {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #6c63ff, #00d4aa);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            color: white;
            box-shadow: 0 8px 30px rgba(108, 99, 255, 0.3);
            margin-bottom: 15px;
        }

        .register-logo h1 {
            font-size: 28px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .register-logo h1 span {
            color: #6c63ff;
        }

        .register-logo .subtitle {
            color: rgba(255, 255, 255, 0.5);
            font-size: 14px;
            margin-top: 4px;
        }

        /* ============================================
           ALERTS
           ============================================ */
        .alert {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
        }

        .alert-danger {
            background: rgba(255, 107, 107, 0.12);
            border: 1px solid rgba(255, 107, 107, 0.2);
            color: #ff6b6b;
        }

        .alert-success {
            background: rgba(0, 212, 170, 0.12);
            border: 1px solid rgba(0, 212, 170, 0.2);
            color: #00d4aa;
        }

        .alert i {
            font-size: 18px;
        }

        /* ============================================
           FORM ELEMENTS - FIXED ALIGNMENT
           ============================================ */
        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            font-size: 13px;
            color: rgba(255, 255, 255, 0.6);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-group .input-wrapper {
            position: relative;
            width: 100%;
        }

        .form-group .input-wrapper .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.25);
            font-size: 15px;
            z-index: 1;
            pointer-events: none;
            transition: color 0.3s;
        }

        .form-group input {
            width: 100%;
            padding: 13px 16px 13px 44px;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            color: #ffffff;
            font-size: 15px;
            font-family: 'Inter', sans-serif;
            outline: none;
            transition: all 0.3s;
        }

        .form-group input::placeholder {
            color: rgba(255, 255, 255, 0.25);
        }

        .form-group input:focus {
            border-color: #6c63ff;
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 0 0 3px rgba(108, 99, 255, 0.05);
        }

        .form-group input:focus + .input-icon {
            color: #6c63ff;
        }

        .form-group input:focus ~ .input-icon {
            color: #6c63ff;
        }

        /* ============================================
           REGISTER BUTTON
           ============================================ */
        .btn-register {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, #6c63ff, #5a52d5);
            color: white;
            font-weight: 600;
            font-size: 16px;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 5px;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(108, 99, 255, 0.4);
        }

        /* ============================================
           FOOTER
           ============================================ */
        .register-footer {
            text-align: center;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }

        .register-footer p {
            color: rgba(255, 255, 255, 0.4);
            font-size: 14px;
        }

        .register-footer a {
            color: #6c63ff;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }

        .register-footer a:hover {
            color: #8a7fff;
            text-decoration: underline;
        }

        /* ============================================
           RESPONSIVE
           ============================================ */
        @media (max-width: 480px) {
            .register-box {
                padding: 30px 20px;
            }

            .register-logo h1 {
                font-size: 22px;
            }

            .register-logo .icon-wrapper {
                width: 64px;
                height: 64px;
                font-size: 28px;
            }

            .form-group input {
                padding: 12px 14px 12px 40px;
                font-size: 14px;
            }

            .btn-register {
                padding: 12px;
                font-size: 15px;
            }
        }

        /* ============================================
           SCROLLBAR
           ============================================ */
        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: #0a0e1a;
        }

        ::-webkit-scrollbar-thumb {
            background: #6c63ff;
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #5a52d5;
        }

        /* ============================================
           ANIMATIONS
           ============================================ */
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert {
            animation: slideDown 0.3s ease;
        }
    </style>
</head>
<body>

<div class="register-container">
    <div class="register-box">

        <!-- Logo -->
        <div class="register-logo">
            <div class="icon-wrapper">
                <i class="fas fa-user-plus"></i>
            </div>
            <h1>ADSU <span>Timetable</span></h1>
            <div class="subtitle">Create Your Account</div>
        </div>

        <!-- Alerts -->
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <!-- Registration Form -->
        <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            
            <!-- Username Field -->
            <div class="form-group">
                <label for="username">
                    <i class="fas fa-user" style="margin-right: 6px;"></i> Username
                </label>
                <div class="input-wrapper">
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" id="username" name="username" placeholder="Choose a username" required autofocus>
                </div>
            </div>

            <!-- Email Field -->
            <div class="form-group">
                <label for="email">
                    <i class="fas fa-envelope" style="margin-right: 6px;"></i> Email Address
                </label>
                <div class="input-wrapper">
                    <i class="fas fa-envelope input-icon"></i>
                    <input type="email" id="email" name="email" placeholder="Enter your email address" required>
                </div>
            </div>

            <!-- Password Field -->
            <div class="form-group">
                <label for="password">
                    <i class="fas fa-lock" style="margin-right: 6px;"></i> Password
                </label>
                <div class="input-wrapper">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" id="password" name="password" placeholder="Minimum 6 characters" required>
                </div>
            </div>

            <!-- Confirm Password Field -->
            <div class="form-group">
                <label for="confirm_password">
                    <i class="fas fa-check-circle" style="margin-right: 6px;"></i> Confirm Password
                </label>
                <div class="input-wrapper">
                    <i class="fas fa-check input-icon"></i>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
                </div>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn-register">
                <i class="fas fa-user-plus"></i> Create Account
            </button>
        </form>

        <!-- Footer -->
        <div class="register-footer">
            <p>Already have an account? <a href="login.php">Login here</a></p>
        </div>

    </div>
</div>

<!-- ============================================
     SCRIPTS
     ============================================ -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('📝 ADSU Timetable System - Register Page');
        
        // Auto-focus username field
        const usernameField = document.getElementById('username');
        if (usernameField) {
            usernameField.focus();
        }
        
        // Password strength indicator (optional)
        const passwordField = document.getElementById('password');
        const confirmField = document.getElementById('confirm_password');
        
        if (passwordField && confirmField) {
            confirmField.addEventListener('input', function() {
                if (this.value.length > 0 && this.value !== passwordField.value) {
                    this.style.borderColor = '#ff6b6b';
                } else if (this.value.length > 0 && this.value === passwordField.value) {
                    this.style.borderColor = '#00d4aa';
                } else {
                    this.style.borderColor = '';
                }
            });
            
            passwordField.addEventListener('input', function() {
                if (confirmField.value.length > 0 && confirmField.value !== this.value) {
                    confirmField.style.borderColor = '#ff6b6b';
                } else if (confirmField.value.length > 0 && confirmField.value === this.value) {
                    confirmField.style.borderColor = '#00d4aa';
                }
            });
        }
    });
</script>

</body>
</html>