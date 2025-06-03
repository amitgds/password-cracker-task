<?php

namespace Admin\NewCracker\Tests;

use Admin\NewCracker\Core\Database;
use Admin\NewCracker\Core\Logger;
use Admin\NewCracker\Core\Config;
use Admin\NewCracker\Strategies\HardCracker;
use PHPUnit\Framework\TestCase;

class HardCrackerTest extends TestCase
{
    private $database;
    private $logger;
    private $config;

    protected function setUp(): void
    {
        $this->config = $this->createMock(Config::class);
        $this->config->method('get')->willReturnMap([
            ['DICTIONARY_FILE', 'data/dictionary.txt'],
            ['PASSWORD_SALT', 'ThisIs-A-Salt123']
        ]);
        $this->logger = $this->createMock(Logger::class);
        $this->database = $this->createMock(Database::class);
    }

    public function testCrackReturnsExpectedResults()
    {
        $this->database->method('query')->willReturn([
            ['user_id' => 1, 'password' => md5('12345' . 'ThisIs-A-Salt123')],
            ['user_id' => 2, 'password' => md5('ABC1' . 'ThisIs-A-Salt123')],
            ['user_id' => 3, 'password' => md5('london' . 'ThisIs-A-Salt123')],
            ['user_id' => 4, 'password' => md5('AbC12z' . 'ThisIs-A-Salt123')]
        ]);

        $cracker = new HardCracker($this->database, $this->logger, $this->config);
        $results = $cracker->crack();

        $this->assertCount(4, $results);
        $this->assertEquals('12345', $results[1]);
        $this->assertEquals('ABC1', $results[2]);
        $this->assertEquals('london', $results[3]);
        $this->assertEquals('AbC12z', $results[4]);
    }
}