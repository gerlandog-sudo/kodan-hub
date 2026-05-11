<?php
namespace App\Core;

require_once __DIR__ . '/Medoo.php';

/**
 * Database Wrapper (Singleton)
 */
class Database {
    private static $instance = null;
    private $db;

    private function __construct() {
        // Ruta absoluta a la base de datos real proporcionada por el usuario
        $dbPath = __DIR__ . '/../../data/hub.sqlite';
        
        $this->db = new Medoo([
            'database' => $dbPath
        ]);
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Retorna la instancia de Medoo para consultas fluidas
     */
    public function getDB() {
        return $this->db;
    }

    /**
     * Proxy para queries crudas (Compatibilidad)
     */
    public function query($sql, $params = []) {
        return $this->db->query($sql, $params);
    }
}
