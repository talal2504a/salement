<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }   // session shuru — nama lene ke liye

if (!function_exists('log_activity')) {
    function log_activity($conn, $action, $details = '') {
        $user = $_SESSION['user_name'] ?? 'System';               // logged-in user ka naam
        $stmt = $conn->prepare("INSERT INTO activity_log (user_name, action, details) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $user, $action, $details);
        $stmt->execute();
        $stmt->close();
    }
}
?>