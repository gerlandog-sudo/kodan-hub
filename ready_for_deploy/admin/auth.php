<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Carga manual del core
require_once __DIR__ . '/../src/Core/Medoo.php';
require_once __DIR__ . '/../src/Core/Database.php';

use App\Core\Database;

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
        $db = Database::getInstance()->getDB();
        
        // Medoo: select(table, columns, where)
        $settings = $db->select('settings', ['value'], ['key' => 'admin_password']);
        $hashedPassword = !empty($settings) ? $settings[0]['value'] : null;
        
        if ($hashedPassword && password_verify($password, $hashedPassword)) {
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
