<?php
namespace PasswordCracker\Strategies;

use PDO;

class LowercaseCracker implements CrackerStrategy {
    private $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function crack(array $passwords, string $salt): array {
        $results = [];
        error_log("LowercaseCracker: Starting crack, passwords count: " . count($passwords));
        
        $query = "SELECT password, hash FROM password_hashes WHERE type = 'medium_lowercase'";
        try {
            $stmt = $this->db->query($query);
            if ($stmt === false) {
                error_log("LowercaseCracker: Query failed: " . print_r($this->db->errorInfo(), true));
                return $results;
            }

            $rowCount = $stmt->rowCount();
            error_log("LowercaseCracker: Fetched $rowCount rows from password_hashes");

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $hash = $row['hash'];
                $password = $row['password'];
                error_log("LowercaseCracker: Checking hash $hash for password $password");
                foreach ($passwords as $user) {
                    if ($user['password'] === $hash) {
                        $results[] = [
                            'user_id' => $user['user_id'],
                            'password' => $password,
                            'type' => 'Medium (Lowercase)'
                        ];
                        error_log("LowercaseCracker: Match found for user_id {$user['user_id']}, password $password");
                    }
                }
            }
        } catch (\Exception $e) {
            error_log("LowercaseCracker: Exception: " . $e->getMessage());
        }

        error_log("LowercaseCracker: Completed, results count: " . count($results));
        return $results;
    }
}
?>