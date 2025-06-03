<?php

namespace Admin\NewCracker\Strategies;

use Admin\NewCracker\Core\Database;
use Admin\NewCracker\Core\Logger;
use Admin\NewCracker\Core\Config;

abstract class BaseCracker implements CrackerStrategy {
    protected $database;
    protected $logger;
    protected $salt;

    public function __construct(Database $database, Logger $logger, Config $config) {
        $this->database = $database;
        $this->logger = $logger;
        $this->salt = $config->get('PASSWORD_SALT', 'ThisIs-A-Salt123');
    }

    protected function hashPassword(string $password): string {
        return md5($password . $this->salt);
    }

    abstract public function crack(): array;
}