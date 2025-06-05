<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirectTo('login.php');
}

$user_name = $_SESSION['user_name'] ?? 'Utilizator';

session_unset();
session_destroy();

session_start();
$_SESSION['success_message'] = "La revedere, $user_name! Te-ai deconectat cu succes.";

redirectTo('login.php');
?>