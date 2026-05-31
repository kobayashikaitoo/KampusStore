<?php
session_start();
require_once __DIR__ . '/../functions/auth.php';
require_once __DIR__ . '/../config/db.php';
logoutUser();
header('Location: ' . BASE_URL . 'auth/login.php');
exit;
