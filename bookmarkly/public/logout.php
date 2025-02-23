<?php
session_start();

// Verwijder de sessie variabelen
$_SESSION = array();

// Verwijder de sessie cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Verwijder de remember me cookie
if (isset($_COOKIE['remember_me'])) {
    setcookie('remember_me', '', time() - 3600, '/');
}

// Vernietig de sessie
session_destroy();

// Redirect naar de login pagina
header('Location: login.php');
exit(); 