<?php
namespace PasswordCracker;

use Monolog\Logger;
use PasswordCracker\Services\CrackerService;
use PasswordCracker\Config\AwsConfig;
use PasswordCracker\Services\DatabaseService;

require dirname(__DIR__) . '/vendor/autoload.php';

try {
    $logger = new Logger('password_cracker');
    $logger->pushHandler(new \Monolog\Handler\StreamHandler('D:/wamp64/www/password-cracker/logs/cracker.log', Logger::DEBUG));

    $difficulty = isset($argv[1]) ? $argv[1] : 'hard';
    $taskId = isset($argv[2]) ? $argv[2] : uniqid();

    if (!in_array($difficulty, ['easy', 'medium', 'hard'], true)) {
        $logger->error('Invalid difficulty: ' . $difficulty);
        exit(1);
    }

    $config = new AwsConfig();
    $dbService = new DatabaseService($config, $logger);
    $crackerService = new CrackerService($dbService, $config, $logger);

    // Store task status
    $statusFile = "D:/wamp64/www/password-cracker/storage/task_$taskId.json";
    file_put_contents($statusFile, json_encode(['status' => 'running', 'progress' => 0, 'results' => []]));

    $results = $crackerService->crackPasswords($difficulty);

    // Update task status
    file_put_contents($statusFile, json_encode([
        'status' => 'completed',
        'progress' => 100,
        'results' => $results
    ]));

    $logger->info('Background cracking completed', ['task_id' => $taskId, 'difficulty' => $difficulty]);
} catch (\Exception $e) {
    $logger->error('Background cracking failed: ' . $e->getMessage());
    file_put_contents($statusFile, json_encode([
        'status' => 'failed',
        'progress' => 0,
        'error' => $e->getMessage()
    ]));
    exit(1);
}
?>