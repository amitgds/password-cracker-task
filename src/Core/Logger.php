<?php

namespace Admin\NewCracker\Core;

use Monolog\Logger as MonologLogger;
use Monolog\Handler\StreamHandler;

class Logger {
    private $logger;

    public function __construct(string $logFile) {
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
        $this->logger = new MonologLogger('password_cracker');
        $this->logger->pushHandler(new StreamHandler($logFile, MonologLogger::INFO));
    }

    public function log(string $message): void {
        $this->logger->info($message);
    }
}