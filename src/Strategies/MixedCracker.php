<?php
namespace PasswordCracker\Strategies;

use PDO;

class MixedCracker implements CrackerStrategy {
    private $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function crack(array $passwords, string $salt): array {
        $results = [];
        $query = "SELECT password, hash FROM password_hashes WHERE type = 'hard_mixed'";
        $stmt = $this->db->query($query);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $hash = $row['hash'];
            foreach ($passwords as $user) {
                if ($user['password'] === $hash) {
                    $results[] = [
                        'user_id' => $user['user_id'],
                        'password' => $row['password'],
                        'type' => 'Hard (Mixed)'
                    ];
                }
            }
        }

        return $results;
    }
}
?>