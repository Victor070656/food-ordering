<?php
/**
 * Database Connection Class
 * Singleton pattern for database connection
 */

class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        try {
            $this->connection = new mysqli(
                DB_HOST,
                DB_USER,
                DB_PASS,
                DB_NAME
            );

            if ($this->connection->connect_error) {
                throw new Exception("Connection failed: " . $this->connection->connect_error);
            }

            $this->connection->set_charset(DB_CHARSET);

        } catch (Exception $e) {
            throw new Exception("Database connection error: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }

    /**
     * Execute a SELECT query and return all rows
     */
    public function select($query, $params = []) {
        $stmt = $this->prepare($query, $params);
        $result = $stmt->get_result();
        $rows = [];

        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }

        $stmt->close();
        return $rows;
    }

    /**
     * Execute a SELECT query and return a single row
     */
    public function selectOne($query, $params = []) {
        $stmt = $this->prepare($query, $params);
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        return $row ?: null;
    }

    /**
     * Insert a record and return the insert ID
     */
    public function insert($query, $params = []) {
        $stmt = $this->prepare($query, $params);
        $insertId = $stmt->insert_id;
        $stmt->close();

        return $insertId;
    }

    /**
     * Update a record and return affected rows
     */
    public function update($query, $params = []) {
        $stmt = $this->prepare($query, $params);
        $affectedRows = $stmt->affected_rows;
        $stmt->close();

        return $affectedRows;
    }

    /**
     * Delete a record and return affected rows
     */
    public function delete($query, $params = []) {
        return $this->update($query, $params);
    }

    /**
     * Execute any query and return affected rows
     */
    public function execute($query, $params = []) {
        $stmt = $this->prepare($query, $params);
        $affectedRows = $stmt->affected_rows;
        $stmt->close();

        return $affectedRows;
    }

    /**
     * Prepare and execute a statement
     */
    private function prepare($query, $params = []) {
        $stmt = $this->connection->prepare($query);

        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->connection->error);
        }

        if (!empty($params)) {
            $types = '';
            $values = [];

            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                    $values[] = $param;
                } elseif (is_float($param)) {
                    $types .= 'd';
                    $values[] = $param;
                } elseif (is_null($param)) {
                    $types .= 's';
                    $values[] = null;
                } else {
                    $types .= 's';
                    $values[] = $param;
                }
            }

            if (!empty($types)) {
                array_unshift($values, $types);
                call_user_func_array([$stmt, 'bind_param'], $this->refValues($values));
            }
        }

        $result = $stmt->execute();

        if (!$result) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        return $stmt;
    }

    /**
     * Helper for bind_param with references
     */
    private function refValues($arr) {
        $refs = [];
        foreach ($arr as $key => $value) {
            $refs[$key] = &$arr[$key];
        }
        return $refs;
    }

    /**
     * Escape a string
     */
    public function escape($string) {
        return $this->connection->real_escape_string($string);
    }

    /**
     * Get last insert ID
     */
    public function lastId() {
        return $this->connection->insert_id;
    }

    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->connection->begin_transaction();
    }

    /**
     * Commit transaction
     */
    public function commit() {
        return $this->connection->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->connection->rollback();
    }

    // Prevent cloning
    private function __clone() {}

    // Prevent unserialization
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
