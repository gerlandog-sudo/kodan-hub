<?php
namespace App\Core;

use PDO;
use Exception;

/**
 * Medoo Core (Secured & Optimized for KODAN-HUB)
 */
class Medoo {
    public $pdo;

    public function __construct($options) {
        try {
            $this->pdo = new PDO("sqlite:" . $options['database'], null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
            
            // Habilitar integridad referencial
            $this->pdo->exec("PRAGMA foreign_keys = ON;");
            
        } catch (Exception $e) {
            throw new Exception("Error de conexión KODAN-DB: " . $e->getMessage());
        }
    }

    public function select($table, $columns, $where = []) {
        $cols = is_array($columns) ? implode(', ', $columns) : $columns;
        $sql = "SELECT $cols FROM $table";
        
        $params = [];
        if (!empty($where)) {
            $sql .= " WHERE " . $this->buildWhere($where, $params);
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function insert($table, $data) {
        $keys = implode(", ", array_keys($data));
        $placeholders = ":" . implode(", :", array_keys($data));
        
        $sql = "INSERT INTO $table ($keys) VALUES ($placeholders)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        return $this->pdo->lastInsertId();
    }

    public function update($table, $data, $where) {
        $fields = "";
        $params = [];
        
        foreach ($data as $key => $value) {
            $fields .= "$key = :upd_$key, ";
            $params["upd_$key"] = $value;
        }
        $fields = rtrim($fields, ", ");
        
        $sql = "UPDATE $table SET $fields";
        if (!empty($where)) {
            $sql .= " WHERE " . $this->buildWhere($where, $params);
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public function delete($table, $where) {
        $params = [];
        $sql = "DELETE FROM $table WHERE " . $this->buildWhere($where, $params);
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * Construye la cláusula WHERE usando placeholders para seguridad
     */
    protected function buildWhere($where, &$params) {
        $clauses = [];
        foreach ($where as $key => $value) {
            $placeholder = "whr_" . str_replace(['.', ' '], '_', $key);
            $clauses[] = "$key = :$placeholder";
            $params[$placeholder] = $value;
        }
        return implode(" AND ", $clauses);
    }
    
    public function query($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}
