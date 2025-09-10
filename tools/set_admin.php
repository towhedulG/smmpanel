<?php
// One-time helper to set admin email and password.
// Run this once in the browser, then DELETE this file immediately.

// Load DB credentials
require_once __DIR__ . '/../app/config.php';

header('Content-Type: text/plain; charset=utf-8');

// New credentials (from user request)
$newEmail = 'towhedulauradm@gmail.co';
$newPasswordPlain = 'TowhedulDM1@';

// Hash password with bcrypt, cost 8 to match seed style
$bcryptOptions = ['cost' => 8];
$newPasswordHash = password_hash($newPasswordPlain, PASSWORD_BCRYPT, $bcryptOptions);
if ($newPasswordHash === false) {
    http_response_code(500);
    echo "Failed to hash password";
    exit;
}

// Connect to MySQL
$mysqli = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($mysqli->connect_errno) {
    http_response_code(500);
    echo "DB connection failed: " . $mysqli->connect_error;
    exit;
}
$mysqli->set_charset('utf8mb4');

// Try update by known seed admin id = 10 first
$sqlById = "UPDATE general_staffs SET email = ?, password = ? WHERE id = 10";
$stmt = $mysqli->prepare($sqlById);
if (!$stmt) {
    http_response_code(500);
    echo "Prepare failed: " . $mysqli->error;
    exit;
}
$stmt->bind_param('ss', $newEmail, $newPasswordHash);
$stmt->execute();

if ($stmt->errno) {
    http_response_code(500);
    echo "Update by id failed: " . $stmt->error;
    $stmt->close();
    $mysqli->close();
    exit;
}

$affected = $stmt->affected_rows;
$stmt->close();

// If nothing updated (e.g., different id), fall back to the first admin row
if ($affected === 0) {
    $sqlByAdmin = "UPDATE general_staffs SET email = ?, password = ? WHERE admin = 1 LIMIT 1";
    $stmt2 = $mysqli->prepare($sqlByAdmin);
    if (!$stmt2) {
        http_response_code(500);
        echo "Prepare fallback failed: " . $mysqli->error;
        $mysqli->close();
        exit;
    }
    $stmt2->bind_param('ss', $newEmail, $newPasswordHash);
    $stmt2->execute();
    if ($stmt2->errno) {
        http_response_code(500);
        echo "Fallback update failed: " . $stmt2->error;
        $stmt2->close();
        $mysqli->close();
        exit;
    }
    $affected = $stmt2->affected_rows;
    $stmt2->close();
}

$mysqli->close();

if ($affected > 0) {
    echo "Success: Admin credentials updated.\n";
    echo "Email: " . $newEmail . "\n";
    echo "Password: " . $newPasswordPlain . "\n\n";
    echo "IMPORTANT: Delete tools/set_admin.php now.";
    exit;
}

http_response_code(404);
echo "No admin row updated. Please ensure the database is imported and try again.";
exit;


