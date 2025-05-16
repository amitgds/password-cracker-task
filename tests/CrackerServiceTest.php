<?php

use PHPUnit\Framework\TestCase;
use PasswordCracker\Services\CrackerService;
use PasswordCracker\Services\DatabaseService;
use PasswordCracker\Config\AwsConfig;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class CrackerServiceTest extends TestCase
{
    private $dbService;
    private $service;
    private $logger;
    private $salt = 'ThisIs-A-Salt123';

    protected function setUp(): void
    {
        // Initialize config
        $config = new AwsConfig();
        
        // Initialize logger
        $this->logger = new Logger('test');
        $this->logger->pushHandler(new StreamHandler('D:\\wamp64\\www\\password-cracker\\logs\\test.log', Logger::INFO));

        // Initialize DatabaseService
        $this->dbService = new DatabaseService($config, $this->logger);
        
        // Clear tables
        $this->dbService->exec('TRUNCATE TABLE not_so_smart_users');
        $this->dbService->exec('TRUNCATE TABLE password_hashes');

        // Initialize CrackerService
        $this->service = new CrackerService($this->dbService, $config, $this->logger);
    }

    public function testGenerateHash()
    {
        $password = 'EII9';
        $expectedHash = '776081a98bebdeba0f3cf05c3be6d47c';
        $hash = $this->service->generateHash($password, $this->salt);
        $this->assertEquals($expectedHash, $hash, "Hash for $password does not match expected.");
    }

    public function testCrackEasyPasswords()
    {
        // Insert test data
        $this->dbService->exec("INSERT INTO not_so_smart_users (user_id, password) VALUES
            (2500, '140ea3ae479499f321c19f9b05cc84b9'), -- 12345
            (2501, '7dc747525720702cd5759078ca82902e'), -- 67890
            (2502, 'aa0b59e76927e33fad8c1ea5621f2fb3'), -- 54321
            (2503, 'bf4670ddfa20407e45ed3d7a0e64c2ec')"); 

        $this->dbService->exec("INSERT INTO password_hashes (password, hash, type) VALUES
            ('12345', '140ea3ae479499f321c19f9b05cc84b9', 'Easy (Numbers)'),
            ('67890', '7dc747525720702cd5759078ca82902e', 'Easy (Numbers)'),
            ('54321', 'aa0b59e76927e33fad8c1ea5621f2fb3', 'Easy (Numbers)'),
            ('11111', 'bf4670ddfa20407e45ed3d7a0e64c2ec', 'Easy (Numbers)')");

        $results = $this->service->crackPasswords('easy');
        $this->assertCount(4, $results, 'Should find 4 easy passwords.');
        $expected = [
            ['user_id' => 2500, 'password' => '12345', 'type' => 'Easy (Numbers)'],
            ['user_id' => 2501, 'password' => '67890', 'type' => 'Easy (Numbers)'],
            ['user_id' => 2502, 'password' => '54321', 'type' => 'Easy (Numbers)'],
            ['user_id' => 2503, 'password' => '11111', 'type' => 'Easy (Numbers)']
        ];
        $this->assertEquals($expected, $results, 'Easy passwords do not match expected.');
    }

    public function testCrackMediumPasswords()
    {
        // Insert test data
        $this->dbService->exec("INSERT INTO not_so_smart_users (user_id, password) VALUES
            (2995, '776081a98bebdeba0f3cf05c3be6d47c'), -- EII9
            (2832, '4c05f70c6d6ad8c019272ac5ebff3310'), -- FMS8
            (2794, 'fe54e5597c5c1a4ed9f5f0892e88a97a'), -- XCN2
            (2627, 'eda1b6f21e2822494f1eb47eabb6352e'), -- YQI7
            (2666, 'b6f27572752f2fc9b257359d0c6a4ef8'), -- aaaaaa
            (2959, '0f3ae57bb4e32740ed68305c0bd01ad8'), -- bbbbbb
            (2600, 'b699ada355aaf66e5dbfd86ff3c9df81'), -- cccccc
            (2601, '0bb5039d972f4bf52a5b189017321fb0'), -- dddddd
            (2602, 'c86ae40b8df0722c474bed61aa0ec38d'), -- eeeeee
            (2603, '1882aa5b2b6ce7a0921dae5b7208fb16'), -- ffffff
            (2604, '6fef2b3a9f12aed52c4f7bd1eb0c512a'), -- gggggg
            (2605, 'd426e3969bdd7d0b2ef85853afac317e'), -- hhhhhh
            (2606, '3de9ad260fdbf5e8b55df66941f844bd'), -- iiiiii
            (2607, '25ce3e0ebaedb99fb30c1754dd687647'), -- jjjjjj
            (2608, '9237023e486a6cc7fe707fc6f44b0057'), -- kkkkkk
            (2609, '12d6870adcdb93e41094718c252a0bd3')"); 

        $this->dbService->exec("INSERT INTO password_hashes (password, hash, type) VALUES
            ('EII9', '776081a98bebdeba0f3cf05c3be6d47c', 'Medium (Uppercase + Number)'),
            ('FMS8', '4c05f70c6d6ad8c019272ac5ebff3310', 'Medium (Uppercase + Number)'),
            ('XCN2', 'fe54e5597c5c1a4ed9f5f0892e88a97a', 'Medium (Uppercase + Number)'),
            ('YQI7', 'eda1b6f21e2822494f1eb47eabb6352e', 'Medium (Uppercase + Number)'),
            ('aaaaaa', 'b6f27572752f2fc9b257359d0c6a4ef8', 'Medium (Lowercase)'),
            ('bbbbbb', '0f3ae57bb4e32740ed68305c0bd01ad8', 'Medium (Lowercase)'),
            ('cccccc', 'b699ada355aaf66e5dbfd86ff3c9df81', 'Medium (Lowercase)'),
            ('dddddd', '0bb5039d972f4bf52a5b189017321fb0', 'Medium (Lowercase)'),
            ('eeeeee', 'c86ae40b8df0722c474bed61aa0ec38d', 'Medium (Lowercase)'),
            ('ffffff', '1882aa5b2b6ce7a0921dae5b7208fb16', 'Medium (Lowercase)'),
            ('gggggg', '6fef2b3a9f12aed52c4f7bd1eb0c512a', 'Medium (Lowercase)'),
            ('hhhhhh', 'd426e3969bdd7d0b2ef85853afac317e', 'Medium (Lowercase)'),
            ('iiiiii', '3de9ad260fdbf5e8b55df66941f844bd', 'Medium (Lowercase)'),
            ('jjjjjj', '25ce3e0ebaedb99fb30c1754dd687647', 'Medium (Lowercase)'),
            ('kkkkkk', '9237023e486a6cc7fe707fc6f44b0057', 'Medium (Lowercase)'),
            ('llllll', '12d6870adcdb93e41094718c252a0bd3', 'Medium (Lowercase)')");

        $results = $this->service->crackPasswords('medium');
        $this->assertCount(16, $results, 'Should find 16 medium passwords.');
        $this->assertContains(['user_id' => 2995, 'password' => 'EII9', 'type' => 'Medium (Uppercase + Number)'], $results);
        $this->assertContains(['user_id' => 2666, 'password' => 'aaaaaa', 'type' => 'Medium (Lowercase)'], $results);
    }

    public function testCrackHardPasswords()
    {
        // Insert test data
        $this->dbService->exec("INSERT INTO not_so_smart_users (user_id, password) VALUES
            (2700, '045fea5bea7626fb311e0e67602b767a'), -- AbC12z
            (2701, '6dd7aeac53ad699cd26850da4a75e1f6')"); 

        $this->dbService->exec("INSERT INTO password_hashes (password, hash, type) VALUES
            ('AbC12z', '045fea5bea7626fb311e0e67602b767a', 'Hard (Mixed)'),
            ('XyZ78w', '6dd7aeac53ad699cd26850da4a75e1f6', 'Hard (Mixed)')");

        $results = $this->service->crackPasswords('hard');
        $this->assertCount(2, $results, 'Should find 2 hard passwords.');
        $expected = [
            ['user_id' => 2700, 'password' => 'AbC12z', 'type' => 'Hard (Mixed)'],
            ['user_id' => 2701, 'password' => 'XyZ78w', 'type' => 'Hard (Mixed)']
        ];
        $this->assertEquals($expected, $results);
    }

    public function testEmptyNotSoSmartUsersTable()
    {
        $results = $this->service->crackPasswords('medium');
        $this->assertEmpty($results, 'Should return empty array for empty not_so_smart_users table.');
    }

    public function testEmptyPasswordHashesTable()
    {
        $this->dbService->exec("INSERT INTO not_so_smart_users (user_id, password) VALUES
            (2995, '776081a98bebdeba0f3cf05c3be6d47c')");
        $results = $this->service->crackPasswords('medium');
        $this->assertEmpty($results, 'Should return empty array for empty password_hashes table.');
    }

    public function testInvalidHashInNotSoSmartUsers()
    {
        $this->dbService->exec("INSERT INTO not_so_smart_users (user_id, password) VALUES
            (2995, 'invalid_hash')");
        $this->dbService->exec("INSERT INTO password_hashes (password, hash, type) VALUES
            ('EII9', '776081a98bebdeba0f3cf05c3be6d47c', 'Medium (Uppercase + Number)')");
        $results = $this->service->crackPasswords('medium');
        $this->assertEmpty($results, 'Should return empty array for invalid hash.');
    }

    public function testLogOutput()
    {
        $this->dbService->exec("INSERT INTO not_so_smart_users (user_id, password) VALUES
            (2995, '776081a98bebdeba0f3cf05c3be6d47c')");
        $this->dbService->exec("INSERT INTO password_hashes (password, hash, type) VALUES
            ('EII9', '776081a98bebdeba0f3cf05c3be6d47c', 'Medium (Uppercase + Number)')");

        $results = $this->service->crackPasswords('medium');
        $logContent = file_get_contents('D:\\wamp64\\www\\password-cracker\\logs\\test.log');
        $this->assertStringContainsString('Match found for user_id 2995, password EII9', $logContent);
        $this->assertStringContainsString('Cracking completed', $logContent);
    }

    public function testLargeDatasetPerformance()
    {
        // Insert 1000 rows
        $stmt = $this->dbService->prepare("INSERT INTO not_so_smart_users (user_id, password) VALUES (?, ?)");
        for ($i = 2500; $i < 3500; $i++) {
            $hash = md5("test$i" . $this->salt);
            $stmt->execute([$i, $hash]);
        }
        $start = microtime(true);
        $results = $this->service->crackPasswords('medium');
        $duration = microtime(true) - $start;
        $this->assertLessThan(15, $duration, 'Cracking large dataset should take less than 15 seconds.');
        $this->assertEmpty($results, 'No matches expected for test passwords.');
    }
}
?>