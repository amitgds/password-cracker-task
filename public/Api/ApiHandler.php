<?php
namespace PasswordCracker\Api;

use Monolog\Logger;
use PasswordCracker\Services\CrackerService;

// Suppress warnings to ensure JSON output
error_reporting(E_ALL & ~E_WARNING);

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
            $rawInput = file_get_contents('php://input');
            $this->logger->debug('Raw input received: ' . $rawInput);
            $input = json_decode($rawInput, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \InvalidArgumentException('Invalid JSON input: ' . json_last_error_msg());
            }
            if (!isset($input['difficulty'])) {
                throw new \InvalidArgumentException('Missing difficulty parameter');
            }

            $difficulty = $input['difficulty'];
            $this->logger->info('Processing crack request for difficulty: ' . $difficulty);
            $results = $this->crackerService->crackPasswords($difficulty);
            echo json_encode($results);
        } catch (\InvalidArgumentException $e) {
            $this->logger->error('Invalid request: ' . $e->getMessage());
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            $this->logger->error('Server error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error: ' . $e->getMessage()]);
        }
    }
}

// Initialize and run
try {
    $logger = new Logger('password_cracker');
    $logger->pushHandler(new \Monolog\Handler\StreamHandler('D:/wamp64/www/password-cracker/logs/cracker.log', Logger::DEBUG));

    $config = new \PasswordCracker\Config\AwsConfig();
    $dbService = new \PasswordCracker\Services\DatabaseService($config, $logger);
    $crackerService = new \PasswordCracker\Services\CrackerService($dbService, $config, $logger);
    $apiHandler = new ApiHandler($crackerService, $logger);

    $apiHandler->handleRequest();
} catch (\Exception $e) {
    $logger = new Logger('password_cracker');
    $logger->pushHandler(new \Monolog\Handler\StreamHandler('D:/wamp64/www/password-cracker/logs/cracker.log', Logger::DEBUG));
    $logger->error('Application error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error: ' . $e->getMessage()]);
}
?>