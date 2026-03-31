<?php
// src/logout.php
require_once __DIR__ . '/Includes/auth.php';
Auth::logout();
header('Location: login.php');
exit;
