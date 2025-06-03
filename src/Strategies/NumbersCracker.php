<?php

namespace Admin\NewCracker\Strategies;

use Admin\NewCracker\Core\Database;
use Admin\NewCracker\Core\Logger;
use Admin\NewCracker\Core\Config;

class NumbersCracker extends BaseCracker {
    public function __construct(Database $database, Logger $logger, Config $config) {
        parent::__construct($database, $logger, $config);
    }

    public function crack(): array {
        $results = [];
        $numbers = CombinationGenerator::generateNumbers(5);

        foreach ($numbers as $number) {
            $hash = $this->hashPassword($number);
            $users = $this->database->query(
                "SELECT user_id FROM not_so_smart_users WHERE password = ?",
                [$hash]
            );

            foreach ($users as $user) {
                $results[$user['user_id']] = $number;
            }
        }
        return $results;
    }
}