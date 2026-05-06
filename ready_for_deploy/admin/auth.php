<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../src/Core/Database.php';

function isLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function redirect($path) {
    header("Location: $path");
    exit;
}

if (isset($_POST['login'])) {
    $password = $_POST['password'] ?? '';
    try {
        $db = \Kodan\Core\Database::getInstance();
        $stmt = $db->query("SELECT value FROM settings WHERE key = 'admin_password'");
        $hashedPassword = $stmt->fetchColumn();
        
        if (password_verify($password, $hashedPassword)) {
            $_SESSION['admin_logged_in'] = true;
            redirect('index.php');
        } else {
            $error = "Contraseña incorrecta";
        }
    } catch (\Exception $e) {
        $error = "Error de conexión a base de datos: " . $e->getMessage();
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    redirect('login.php');
}
