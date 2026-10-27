<?php
// setup.php - For existing exam_timetable database

require_once 'auth.php';

echo "<h1>🔧 ADSU Timetable System - Database Status</h1>";

$conn = getDB();

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<p style='color:green;'>✅ Connected to database: exam_timetable</p>";

// Ensure tables exist
ensureUsersTableExists();

// Show users
$result = $conn->query("SELECT id, username, email, created_at FROM users");
echo "<h2>📋 Users in Database:</h2>";
if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='8'>";
    echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Created</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['username'] . "</td>";
        echo "<td>" . $row['email'] . "</td>";
        echo "<td>" . $row['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No users found. Please register or create admin user.</p>";
}

// Show courses
$result = $conn->query("SELECT COUNT(*) as count FROM courses");
$row = $result->fetch_assoc();
echo "<h2>📚 Courses: " . $row['count'] . "</h2>";

// Show venues
$result = $conn->query("SELECT COUNT(*) as count FROM venues");
$row = $result->fetch_assoc();
echo "<h2>🏛️ Venues: " . $row['count'] . "</h2>";

// Show timetable assignments
$result = $conn->query("SELECT COUNT(*) as count FROM timetable_assignments");
$row = $result->fetch_assoc();
echo "<h2>📅 Timetable Assignments: " . $row['count'] . "</h2>";

echo "<hr>";
echo "<h3>🔑 Default Admin Credentials:</h3>";
echo "<ul>";
echo "<li><strong>Username:</strong> admin</li>";
echo "<li><strong>Password:</strong> admin123</li>";
echo "</ul>";

echo "<p><a href='login.php' style='padding:10px 20px; background:#6c63ff; color:white; text-decoration:none; border-radius:6px;'>Go to Login →</a></p>";
?>

<style>
    body { font-family: Arial; margin: 20px; background: #f5f5f5; }
    .container { max-width: 800px; margin: auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
    th { background: #2c3e66; color: white; }
    h1, h2, h3 { color: #2c3e66; }
    ul { list-style: none; padding: 0; }
    ul li { padding: 5px 0; }
    hr { margin: 20px 0; }
</style>