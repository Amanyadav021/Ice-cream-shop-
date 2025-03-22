<?php
require_once 'includes/auth.php';
session_start();
session_destroy();
header('Location: index.php');
exit();
?>