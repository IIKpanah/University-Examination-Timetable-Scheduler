<?php
// login.php
// ADSU Mubi Timetable System - Professional Login Page

require_once 'auth.php';
ensureUsersTableExists();

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (login($username, $password)) {
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Invalid username/email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ADSU Mubi Timetable System</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
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
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #0a0e1a 0%, #1a1a3e 50%, #2d1b4e 100%);
            color: #ffffff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        /* Animated Background */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(ellipse at 20% 50%, rgba(108, 99, 255, 0.08) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 50%, rgba(0, 212, 170, 0.08) 0%, transparent 50%);
            pointer-events: none;
            z-index: 0;
        }

        /* Floating Particles */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
        }

        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(108, 99, 255, 0.3);
            border-radius: 50%;
            animation: float 20s infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) translateX(0); opacity: 0.3; }
            25% { transform: translateY(-120px) translateX(60px); opacity: 0.6; }
            50% { transform: translateY(-240px) translateX(-60px); opacity: 0.3; }
            75% { transform: translateY(-120px) translateX(60px); opacity: 0.6; }
        }

        /* ============================================
           LOGIN CONTAINER
           ============================================ */
        .login-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 440px;
        }

        .login-box {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            padding: 45px 40px;
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.5);
            animation: fadeInUp 0.6s ease;
            position: relative;
            overflow: hidden;
        }

        .login-box::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at 30% 30%, rgba(108, 99, 255, 0.05), transparent 70%);
            pointer-events: none;
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
        .login-logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-logo .icon-wrapper {
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

        .login-logo h1 {
            font-size: 28px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .login-logo h1 span {
            color: #6c63ff;
        }

        .login-logo .subtitle {
            color: rgba(255, 255, 255, 0.5);
            font-size: 14px;
            margin-top: 4px;
        }

        .login-logo .badge {
            display: inline-block;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            background: rgba(0, 212, 170, 0.1);
            color: #00d4aa;
            margin-top: 8px;
        }

        .login-logo .badge i {
            margin-right: 4px;
        }

        /* ============================================
           ALERT / ERROR MESSAGES
           ============================================ */
        .alert {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            animation: fadeInUp 0.3s ease;
        }

        .alert-danger {
            background: rgba(255, 107, 107, 0.12);
            border: 1px solid rgba(255, 107, 107, 0.2);
            color: #ff6b6b;
        }

        .alert-danger i {
            font-size: 18px;
        }

        .alert-success {
            background: rgba(0, 212, 170, 0.12);
            border: 1px solid rgba(0, 212, 170, 0.2);
            color: #00d4aa;
        }

        /* ============================================
           FORM ELEMENTS
           ============================================ */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            font-size: 14px;
            color: rgba(255, 255, 255, 0.7);
        }

        .form-group .input-wrapper {
            position: relative;
        }

        .form-group .input-wrapper .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.3);
            font-size: 16px;
            transition: color 0.3s;
        }

        .form-group input {
            width: 100%;
            padding: 14px 18px 14px 46px;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 14px;
            color: #ffffff;
            font-size: 15px;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s;
            outline: none;
        }

        .form-group input::placeholder {
            color: rgba(255, 255, 255, 0.25);
        }

        .form-group input:focus {
            border-color: #6c63ff;
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 0 30px rgba(108, 99, 255, 0.05);
        }

        .form-group input:focus + .input-icon,
        .form-group input:focus ~ .input-icon {
            color: #6c63ff;
        }

        /* ============================================
           BUTTONS
           ============================================ */
        .btn-login {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 14px;
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
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(108, 99, 255, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        /* ============================================
           FOOTER
           ============================================ */
        .login-footer {
            text-align: center;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }

        .login-footer p {
            color: rgba(255, 255, 255, 0.4);
            font-size: 14px;
        }

        .login-footer a {
            color: #6c63ff;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }

        .login-footer a:hover {
            color: #8a7fff;
            text-decoration: underline;
        }

        .login-footer .version {
            font-size: 11px;
            color: rgba(255, 255, 255, 0.15);
            margin-top: 10px;
        }

        /* ============================================
           RESPONSIVE
           ============================================ */
        @media (max-width: 480px) {
            .login-box {
                padding: 30px 20px;
            }

            .login-logo h1 {
                font-size: 22px;
            }

            .login-logo .icon-wrapper {
                width: 64px;
                height: 64px;
                font-size: 28px;
            }

            .form-group input {
                padding: 12px 14px 12px 40px;
                font-size: 14px;
            }

            .btn-login {
                padding: 12px;
                font-size: 15px;
            }
        }

        @media (max-width: 360px) {
            .login-box {
                padding: 24px 16px;
                border-radius: 18px;
            }

            .login-logo h1 {
                font-size: 19px;
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
    </style>
</head>
<body>

<!-- ============================================
     PARTICLES BACKGROUND
     ============================================ -->
<div class="particles">
    <?php for ($i = 0; $i < 30; $i++): ?>
        <div class="particle" style="top: <?php echo rand(0, 100); ?>%; left: <?php echo rand(0, 100); ?>%; animation-delay: <?php echo rand(0, 20); ?>s; animation-duration: <?php echo 15 + rand(0, 10); ?>s;"></div>
    <?php endfor; ?>
</div>

<!-- ============================================
     LOGIN CONTAINER
     ============================================ -->
<div class="login-container">
    <div class="login-box">

        <!-- Logo -->
        <div class="login-logo">
            <div class="icon-wrapper">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <h1>ADSU <span>Timetable</span></h1>
            <div class="subtitle">Examination & Lecture Scheduling System</div>
            <div class="badge">
                <i class="fas fa-shield-alt"></i> Secure Access
            </div>
        </div>

        <!-- Error Message -->
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <div class="form-group">
                <label for="username">Username or Email</label>
                <div class="input-wrapper">
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" id="username" name="username" placeholder="Enter your username or email" required autofocus>
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>
            </div>

            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
        </form>

        <!-- Footer -->
        <div class="login-footer">
            <p>Don't have an account? <a href="register.php">Register here</a></p>
            <div class="version">ADSU Timetable System v1.0 &bull; Boni Haruna Campus</div>
        </div>

    </div>
</div>

<!-- ============================================
     SCRIPTS
     ============================================ -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🔐 ADSU Timetable System - Login Page loaded successfully!');
        
        // Auto-focus the username field
        const usernameField = document.getElementById('username');
        if (usernameField) {
            usernameField.focus();
        }
        
        // Add Enter key support for form submission
        const passwordField = document.getElementById('password');
        if (passwordField) {
            passwordField.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    this.form.submit();
                }
            });
        }
    });
</script>

</body>
</html>