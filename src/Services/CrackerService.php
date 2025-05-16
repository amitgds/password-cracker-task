<?php
namespace PasswordCracker\Services;

use Directory;
use Monolog\Logger;
use PasswordCracker\Config\ConfigInterface;
use PasswordCracker\Strategies\DictionaryCracker;
use PasswordCracker\Strategies\NumbersCracker;
use PasswordCracker\Strategies\UppercaseNumberCracker;
use PasswordCracker\Strategies\LowercaseCracker;
use PasswordCracker\Strategies\MixedCracker;

class CrackerService {
    private $dbService;
    private $config;
    private $logger;
    private $strategies;

    public function __construct(DatabaseService $dbService, ConfigInterface $config, Logger $logger) {
        $this->dbService = $dbService;
        $this->config = $config;
        $this->logger = $logger;
        $pdo = $dbService->getConnection();
        $this->strategies = [
            'easy' => [new NumbersCracker()],
            'medium' => [
                new UppercaseNumberCracker(),
                new LowercaseCracker($pdo),
                new DictionaryCracker('D:/wamp64/www/password-cracker/data/dictionary.txt')
            ],
            'hard' => [new MixedCracker($pdo)]
        ];
    }

    public function generateHash(string $password, string $salt): string {
        return md5($password . $salt);
    }

    public function crackPasswords(string $category): array {
        // Check if password_hashes is empty
        $countStmt = $this->dbService->prepare('SELECT COUNT(*) as cnt FROM password_hashes WHERE type LIKE :type');
        $countStmt->execute(['type' => "%$category%"]);
        if ($countStmt->fetch()['cnt'] == 0) {
            $this->logger->info('No password hashes found for category', ['type' => $category]);
            return [];
        }

        // Fetch and sort results
        $stmt = $this->dbService->prepare("
            SELECT n.user_id, p.password, p.type
            FROM not_so_smart_users n
            JOIN password_hashes p ON n.password = p.hash
            WHERE p.type LIKE :type
            ORDER BY n.user_id ASC
        ");
        $stmt->execute(['type' => "%$category%"]);
        $results = $stmt->fetchAll();

        foreach ($results as $result) {
            $this->logger->info("Match found for user_id {$result['user_id']}, password {$result['password']}");
        }

        $this->logger->info('Cracking completed', ['type' => $category, 'results_count' => count($results)]);
        return $results;
    }
}
?>