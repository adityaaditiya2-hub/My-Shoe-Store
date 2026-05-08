<?php
$session_dir = __DIR__ . '/../sessions';
if (!file_exists($session_dir)) {
    mkdir($session_dir, 0777, true);
}
session_name('KICKS_SESSION');
session_set_cookie_params(['path' => '/']);
session_save_path($session_dir);
session_start();

$log_msg = "[" . date('Y-m-d H:i:s') . "] Script: " . $_SERVER['SCRIPT_NAME'] . " | Session ID: " . session_id() . " | Cookies: " . json_encode($_COOKIE) . " | User ID: " . ($_SESSION['user_id'] ?? 'none') . "\n";
file_put_contents(__DIR__ . '/../session.log', $log_msg, FILE_APPEND);
