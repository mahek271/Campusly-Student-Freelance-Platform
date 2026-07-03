<?php
// ── DATABASE CONFIG ──────────────────────────────────────────
$host     = "localhost";
$db_user  = "root";
$db_pass  = "";
$database = "campusly";

$conn = mysqli_connect($host, $db_user, $db_pass, $database);

if (!$conn) {
    error_log("DB Connection Failed: " . mysqli_connect_error());
    die("<div style='font-family:sans-serif;padding:40px;background:#08070e;color:#f43f7a;text-align:center;min-height:100vh;display:flex;align-items:center;justify-content:center;flex-direction:column'>
        <h2>⚠️ Database connection failed</h2>
        <p style='margin-top:10px;color:#9d7ffe'>Make sure XAMPP is running and you imported campusly.sql into phpMyAdmin</p>
        <code style='margin-top:20px;background:rgba(255,255,255,0.05);padding:12px 20px;border-radius:8px;font-size:13px'>" . mysqli_connect_error() . "</code>
    </div>");
}

mysqli_set_charset($conn, "utf8mb4");

// ── Helper: escape ──
function esc($conn, $val) {
    return mysqli_real_escape_string($conn, trim($val));
}

// ── Helper: current logged user ──
function currentUser() {
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
}

function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php?next=" . urlencode($_SERVER['REQUEST_URI']));
        exit();
    }
    // Prevent browser from caching authenticated pages — stops back-button privacy leak
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    header("Expires: Sat, 01 Jan 2000 00:00:00 GMT");
}

function requireRole($role) {
    requireLogin();
    if ($_SESSION['user_role'] !== $role) {
        header("Location: index.php");
        exit();
    }
}
?>
