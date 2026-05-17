<?php
session_start();
require_once __DIR__ . '/../functions/auth.php';
logoutUser();
header('Location: /auth/login.php');
exit;
