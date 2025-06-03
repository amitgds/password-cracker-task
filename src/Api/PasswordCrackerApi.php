<?php

namespace Admin\NewCracker\Api;

use Admin\NewCracker\Core\Database;
use Admin\NewCracker\Core\Logger;
use Admin\NewCracker\Core\Config;
use Admin\NewCracker\Strategies\NumbersCracker;
use Admin\NewCracker\Strategies\UppercaseNumberCracker;
use Admin\NewCracker\Strategies\DictionaryCracker;
use Admin\NewCracker\Strategies\HardCracker;

class PasswordCrackerApi {
    private $database;
    private $logger;
    private $config;

    public function __construct(Database $database, Logger $logger, Config $config) {
        $this->database = $database;
        $this->logger = $logger;
        $this->config = $config;
    }

    public function handleRequest(): void {
        $action = $_GET['action'] ?? '';
        $this->logger->log("Handling request for action: $action");

        try {
            $results = [];
            switch ($action) {
                case 'crack_easy':
                    $cracker = new NumbersCracker($this->database, $this->logger, $this->config);
                    $results = $cracker->crack();
                    break;
                case 'crack_medium':
                    $uppercaseCracker = new UppercaseNumberCracker($this->database, $this->logger, $this->config);
                    $dictionaryCracker = new DictionaryCracker($this->database, $this->logger, $this->config);
                    $uppercaseResults = $uppercaseCracker->crack();
                    $dictionaryResults = $dictionaryCracker->crack();
                    $results = $uppercaseResults + $dictionaryResults; // Preserve keys
                    break;
                case 'crack_hard':
                    $cracker = new HardCracker($this->database, $this->logger, $this->config);
                    $results = $cracker->crack();
                    break;
                default:
                    throw new \Exception("Invalid action: $action");
            }

            $this->sendResponse(['status' => 'success', 'data' => $results, 'count' => count($results)]);
        } catch (\Exception $e) {
            $this->logger->log("Error: " . $e->getMessage());
            $this->sendResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    private function sendResponse(array $data, int $statusCode = 200): void {
        header('Content-Type: application/json', true, $statusCode);
        echo json_encode($data);
        exit;
    }
}