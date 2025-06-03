<?php

namespace Admin\NewCracker\Strategies;

use Admin\NewCracker\Core\Database;
use Admin\NewCracker\Core\Logger;

class MixedCaseNumberCracker implements CrackerStrategy {
    private $database;
    private $logger;

    public function __construct(Database $database, Logger $logger) {
        $this->database = $database;
        $this->logger = $logger;
    }

    public function crack(): array {
        $this->logger->log("Starting mixed case + number password cracking");
        $results = [];
        $combinations = CombinationGenerator::generateMixedCaseNumber();

        foreach ($combinations as $combo) {
            $hash = $this->hashPassword($combo);
            $users = $this->database->query(
                "SELECT user_id FROM not_so_smart_users WHERE password = :hash",
                ['hash' => $hash]
            );
            foreach ($users as $user) {
                $results[$user['user_id']] = $combo;
                $this->logger->log("Cracked user_id {$user['user_id']}: $combo");
            }
        }

        $this->logger->log("Mixed case + number cracking completed");
        return $results;
    }

    private function hashPassword(string $password): string {
        return md5($password . 'ThisIs-A-Salt123');
    }
}