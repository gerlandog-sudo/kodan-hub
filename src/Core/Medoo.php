<?php
namespace Medoo;
use PDO;
use Exception;

/**
 * Medoo Core (Simplified for Antigravity)
 */
class Medoo {
    public $pdo;

    public function __construct($options) {
        try {
            $this->pdo = new PDO("sqlite:" . $options['database'], null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
        } catch (Exception $e) {
            throw new Exception("Error de conexión: " . $e->getMessage());
        }
    }

    public function select($table, $columns, $where = null) {
        $sql = "SELECT " . (is_array($columns) ? implode(', ', $columns) : $columns) . " FROM " . $table;
        if ($where) { $sql .= " WHERE " . $this->whereClause($where); }
        $query = $this->pdo->prepare($sql);
        $query->execute();
        return $query->fetchAll();
    }

    public function insert($table, $data) {
        $keys = implode(", ", array_keys($data));
        $values = ":" . implode(", :", array_keys($data));
        $sql = "INSERT INTO $table ($keys) VALUES ($values)";
        $query = $this->pdo->prepare($sql);
        $query->execute($data);
        return $this->pdo->lastInsertId();
    }

    public function update($table, $data, $where) {
        $fields = "";
        foreach ($data as $key => $value) { $fields .= "$key = :$key, "; }
        $fields = rtrim($fields, ", ");
        $sql = "UPDATE $table SET $fields WHERE " . $this->whereClause($where);
        $query = $this->pdo->prepare($sql);
        $query->execute($data);
        return $query->rowCount();
    }

    public function delete($table, $where) {
        $sql = "DELETE FROM $table WHERE " . $this->whereClause($where);
        $query = $this->pdo->prepare($sql);
        $query->execute();
        return $query->rowCount();
    }

    protected function whereClause($where) {
        $clauses = [];
        foreach ($where as $key => $value) {
            $clauses[] = "$key = '$value'";
        }
        return implode(" AND ", $clauses);
    }
}
