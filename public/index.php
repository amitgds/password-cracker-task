<?php
// Check for autoload file
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    die('Autoloader not found. Please run "composer install" to set up dependencies.');
}

require_once $autoloadPath;

use Admin\NewCracker\Core\Config;
use Admin\NewCracker\Core\Logger;
use Admin\NewCracker\Core\Database;
use Admin\NewCracker\Api\PasswordCrackerApi;

// Check for PasswordCrackerApi class
$apiPath = __DIR__ . '/../src/Api/PasswordCrackerApi.php';
if (!class_exists('\Admin\NewCracker\Api\PasswordCrackerApi')) {
    if (!file_exists($apiPath)) {
        die('PasswordCrackerApi class not found. Please ensure the file exists at src/Api/PasswordCrackerApi.php.');
    }
    require_once $apiPath;
}

try {
    $config = new Config();
    $logFile = $config->get('LOG_FILE', 'logs/password_cracker.log');
    if (!is_writable(dirname($logFile))) {
        $logFile = 'logs/default_password_cracker.log'; // Fallback log file
    }
    $logger = new Logger($logFile);
    $database = new Database($config, $logger);
    $api = new PasswordCrackerApi($database, $logger, $config);

    if (isset($_GET['action'])) {
        $validActions = ['crack_easy', 'crack_medium', 'crack_hard'];
        $action = $_GET['action'];
        if (!in_array($action, $validActions)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Invalid action'], JSON_FORCE_OBJECT);
            exit;
        }

        header('Content-Type: application/json');
        $response = $api->handleRequest();
        echo json_encode($response, JSON_FORCE_OBJECT);
        exit;
    }
} catch (Exception $e) {
    $logger = new Logger('logs/default_password_cracker.log');
    $logger->log("Initialization error: An unexpected error occurred");
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Initialization failed'], JSON_FORCE_OBJECT);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Cracker</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .progress-bar {
            transition: width 0.3s ease-in-out;
        }
        .table-container {
            max-height: 400px;
            overflow-y: auto;
        }
        #results-chart {
            max-width: 400px;
            max-height: 400px;
            width: 100%;
            height: auto;
            margin: 0 auto;
        }
        @media (max-width: 640px) {
            #results-chart {
                max-width: 300px;
                max-height: 300px;
            }
        }
        .animate-pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        .button-transition {
            transition: all 0.2s ease-in-out;
        }
        .fade-in {
            opacity: 0;
            animation: fadeIn 0.5s ease-in forwards;
        }
        @keyframes fadeIn {
            to { opacity: 1; }
        }
        .animate-spin {
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="bg-gray-900 text-white min-h-screen flex flex-col">
    <header class="bg-gray-800 py-4 shadow-md">
        <div class="container mx-auto px-6">
            <h1 class="text-2xl font-bold text-blue-400 text-center">Password Cracker</h1>
        </div>
    </header>

    <main class="container mx-auto p-6 max-w-4xl flex-grow">
       
        <div class="flex justify-center space-x-4 mb-6">
            <button onclick="crack('easy')" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold px-6 py-3 rounded-lg shadow-md button-transition">Crack Easy</button>
            <button onclick="crack('medium')" class="bg-purple-500 hover:bg-purple-600 text-white font-semibold px-6 py-3 rounded-lg shadow-md button-transition">Crack Medium</button>
            <button onclick="crack('hard')" class="bg-red-500 hover:bg-red-600 text-white font-semibold px-6 py-3 rounded-lg shadow-md button-transition">Crack Hard</button>
        </div>
        <div class="mb-6">
            <div id="progress" class="w-full bg-gray-700 rounded-full h-4">
                <div id="progress-bar" class="progress-bar bg-blue-500 h-4 rounded-full" style="width: 0%"></div>
            </div>
            <p id="progress-text" class="text-center mt-2 text-gray-300"></p>
            <div id="loading" class="hidden text-center mt-4">
                <svg class="animate-spin h-8 w-8 text-blue-400 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                </svg>
            </div>
        </div>
        <div id="results" class="bg-gray-800 p-6 rounded-lg shadow-lg mb-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-semibold">Results</h2>
                <button id="export-csv" class="bg-green-500 hover:bg-green-600 text-white font-semibold px-4 py-2 rounded-lg button-transition hidden">Export as CSV</button>
            </div>
            <div class="table-container">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-gray-700">
                            <th class="p-3">User ID</th>
                            <th class="p-3">Password</th>
                            <th class="p-3">Type</th>
                        </tr>
                    </thead>
                    <tbody id="results-table" class="text-gray-300"></tbody>
                </table>
            </div>
        </div>
        <div class="flex justify-center">
            <canvas id="results-chart" class="mt-6"></canvas>
        </div>
    </main>

    <footer class="bg-gray-800 py-4">
        <div class="container mx-auto px-6 text-center">
            <p class="text-gray-300">&copy; 2025 Password Cracker. All rights reserved.</p>
            <p class="text-gray-300">Contact: 
                <a href="mailto:info@passwordcracker.com" class="hover:text-blue-400 transition">
                    info@passwordcracker.com
                </a>
            </p>
        </div>
    </footer>

    <script src="js/app.js"></script>
</body>
</html>