<?php
// Show all errors
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'C:/xampp/php/logs/php_error.log');
error_reporting(E_ALL);

// Test error logging
error_log("This is a test message");
echo "Test complete - check the error log";
?> 