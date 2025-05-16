<?php
namespace PasswordCracker\Services;

use Monolog\Logger;
use PasswordCracker\Config\ConfigInterface;
use PDO;

class DatabaseService {
    private $config;
    private $logger;
    private $pdo;

    public function __construct(ConfigInterface $config, Logger $logger) {
        $this->config = $config;
        $this->logger = $logger;
        $this->connect();
    }

    private function connect(): void {
        try {
            $dbConfig = $this->config->getDbConfig();
            $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset=utf8mb4";
            $this->pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            $this->logger->info('Database connection established');
        } catch (\PDOException $e) {
            $this->logger->error('Database connection failed: ' . $e->getMessage());
            throw new \RuntimeException('Failed to connect to database: ' . $e->getMessage());
        }
    }

    public function getConnection(): PDO {
        return $this->pdo;
    }

    public function getHashedPasswords(): array {
        try {
            $stmt = $this->pdo->query('SELECT user_id, password FROM not_so_smart_users');
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            $this->logger->error('Failed to fetch hashed passwords: ' . $e->getMessage());
            throw new \RuntimeException('Failed to fetch hashed passwords: ' . $e->getMessage());
        }
    }

     public function exec(string $statement): int {
        try {
            $result = $this->pdo->exec($statement);
            $this->logger->info("Executed query: $statement");
            return $result;
        } catch (\PDOException $e) {
            $this->logger->error("Failed to execute query: $statement, Error: " . $e->getMessage());
            throw new \RuntimeException("Failed to execute query: " . $e->getMessage());
        }
    }

    public function prepare(string $statement): \PDOStatement {
        try {
            $stmt = $this->pdo->prepare($statement);
            $this->logger->info("Prepared statement: $statement");
            return $stmt;
        } catch (\PDOException $e) {
            $this->logger->error("Failed to prepare statement: $statement, Error: " . $e->getMessage());
            throw new \RuntimeException("Failed to prepare statement: " . $e->getMessage());
        }
    }
}
?>