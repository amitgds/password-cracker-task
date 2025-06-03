<?php

namespace Admin\NewCracker\Strategies;

use Admin\NewCracker\Core\Database;
use Admin\NewCracker\Core\Logger;
use Admin\NewCracker\Core\Config;

class UppercaseNumberCracker extends BaseCracker {
    public function __construct(Database $database, Logger $logger, Config $config) {
        parent::__construct($database, $logger, $config);
    }

    public function crack(): array {
        $this->logger->log("Starting uppercase + number password cracking");
        $results = [];

        $users = $this->database->query(
            "SELECT u.user_id, h.password 
             FROM not_so_smart_users u
             JOIN uppercase_hashes h ON u.password = h.hash"
        );

        foreach ($users as $user) {
            $results[$user['user_id']] = $user['password'];
        }

        $this->logger->log("Uppercase + number cracking completed with " . count($results) . " results");
        return $results;
    }
}