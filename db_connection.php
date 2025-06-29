<?php
// Database Configuration
$host = "localhost";
$user = "root";        // Change if your MySQL username is different
$pass = "";            // Change if your MySQL password is not empty
$dbname = "ilos_kit";

// Create connection with error handling
try {
    $conn = new mysqli($host, $user, $pass, $dbname);

    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Set charset to utf8mb4 for full Unicode support
    $conn->set_charset("utf8mb4");

    // Set timezone for SQL operations (adjust as needed)
    $conn->query("SET time_zone = '+02:00'");

} catch (Exception $e) {
    // Log error securely
    error_log("Database connection error: " . $e->getMessage());

    // User-friendly message
    die("We're experiencing technical difficulties. Please try again later.");
}

// Prevent redeclaring if already included
if (!function_exists('generateOrderID')) {
    /**
     * Generates a unique order ID
     * Format: ILO{YYMMDD}{USERCODE}{RANDOM}
     * Example: ILO250622ABC9D4
     */
    function generateOrderID($user_id, $conn) {
        $prefix = 'ILO';
        $date_part = date('ymd');
        $user_code = strtoupper(substr(md5($user_id), 0, 3));
        $random_part = strtoupper(bin2hex(random_bytes(2))); // 4-char random string

        $order_id = $prefix . $date_part . $user_code . $random_part;

        // Ensure it's unique
        $check_stmt = $conn->prepare("SELECT id FROM orders WHERE id = ?");
        $check_stmt->bind_param("s", $order_id);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            // Try again recursively
            return generateOrderID($user_id, $conn);
        }

        return $order_id;
    }
}

if (!function_exists('dbQuery')) {
    /**
     * Simple query helper
     * @param mysqli $conn
     * @param string $sql
     * @param array $params
     * @param string $types
     * @return mysqli_stmt
     */
    function dbQuery($conn, $sql, $params = [], $types = '') {
        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt;
    }
}
?>
