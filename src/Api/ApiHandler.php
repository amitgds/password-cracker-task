<?php
namespace PasswordCracker\Api;

use Monolog\Logger;
use PasswordCracker\Services\CrackerService;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

class ApiHandler {
    private $crackerService;
    private $logger;

    public function __construct(CrackerService $crackerService, Logger $logger) {
        $this->crackerService = $crackerService;
        $this->logger = $logger;
    }

    public function handleRequest(): void {
        header('Content-Type: application/json');

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \InvalidArgumentException('Invalid JSON input');
            }

            $type = $input['type'] ?? 'easy';
            $results = $this->crackerService->crackPasswords($type);
            echo json_encode($results);
        } catch (\Exception $e) {
            $this->logger->error('API error: ' . $e->getMessage());
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}

// Initialize and run
try {
    $logger = new Logger('password_cracker');
    $logger->pushHandler(new \Monolog\Handler\StreamHandler('C:/wamp64/www/password-cracker/logs/cracker.log', Logger::INFO));

    $config = new \PasswordCracker\Config\AwsConfig();
    $dbService = new \PasswordCracker\Services\DatabaseService($config, $logger);
    $crackerService = new \PasswordCracker\Services\CrackerService($dbService, $config, $logger);
    $apiHandler = new ApiHandler($crackerService, $logger);

    $apiHandler->handleRequest();
} catch (\Exception $e) {
    $logger->error('Application error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>