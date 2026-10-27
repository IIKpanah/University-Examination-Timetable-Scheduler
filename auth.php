<?php
// auth.php - Updated to use exam_timetable database

session_start();

// Database connection
function getDB() {
    static $conn = null;
    
    if ($conn === null) {
        $conn = new mysqli('localhost', 'root', '', 'exam_timetable');
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
    }
    return $conn;
}

// Ensure users table exists (with your schema)
function ensureUsersTableExists() {
    $conn = getDB();
    
    // Check if users table exists, if not create it
    $conn->query("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Check if admin user exists, if not create one
    $result = $conn->query("SELECT id FROM users WHERE username = 'admin'");
    if ($result->num_rows == 0) {
        $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
        $conn->query("
            INSERT INTO users (username, email, password_hash) 
            VALUES ('admin', 'admin@adsu.edu.ng', '$password_hash')
        ");
        echo "<!-- Admin user created: username=admin, password=admin123 -->";
    }
}

// Login function
function login($username, $password) {
    $conn = getDB();
    
    $stmt = $conn->prepare("SELECT id, username, email, password_hash FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        return false;
    }
    
    $user = $result->fetch_assoc();
    if (password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        return true;
    }
    return false;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function logout() {
    session_destroy();
}
?>