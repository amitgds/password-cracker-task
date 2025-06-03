<?php

namespace Admin\NewCracker\Core;

class Database
{
    private $conn;
    private $logger;

    public function __construct(Config $config, Logger $logger)
    {
        $this->logger = $logger;
        $host = $config->get('DB_HOST', 'localhost');
        $dbName = $config->get('DB_NAME', 'cracker');
        $user = $config->get('DB_USER', 'root');
        $pass = $config->get('DB_PASS', '');

        $this->conn = new \mysqli($host, $user, $pass, $dbName);
        if ($this->conn->connect_error) {
            throw new \Exception("Database connection failed: " . $this->conn->connect_error);
        }
    }

    public function query(string $sql, array $params = []): array
    {
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new \Exception("Prepare failed: " . $this->conn->error);
        }

        if (!empty($params)) {
            $types = str_repeat('s', count($params));
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
        return $rows;
    }

    public function __destruct()
    {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}