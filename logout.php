<?php
require __DIR__.'/lib.php';
start_session();
$wasCustomer = !empty($_SESSION['customer_slug']) && empty($_SESSION['admin']);
session_destroy();
header('Location: '.($wasCustomer ? 'customer_login.php' : 'login.php'));
