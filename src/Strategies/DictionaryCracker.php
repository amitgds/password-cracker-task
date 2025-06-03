<?php

namespace Admin\NewCracker\Strategies;

use Admin\NewCracker\Core\Database;
use Admin\NewCracker\Core\Logger;
use Admin\NewCracker\Core\Config;

class DictionaryCracker extends BaseCracker {
    private $dictionaryFile;

    public function __construct(Database $database, Logger $logger, Config $config) {
        parent::__construct($database, $logger, $config);
        $this->dictionaryFile = $config->get('DICTIONARY_FILE');
        if (!file_exists($this->dictionaryFile) || !is_readable($this->dictionaryFile)) {
            throw new \Exception("Dictionary file not found or not readable: {$this->dictionaryFile}");
        }
    }

    public function crack(): array {
        $results = [];
        $words = file($this->dictionaryFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($words as $word) {
            $word = trim($word);
            if (strlen($word) <= 6 && ctype_lower($word)) {
                $hash = $this->hashPassword($word);
                $users = $this->database->query(
                    "SELECT user_id FROM not_so_smart_users WHERE password = ?",
                    [$hash]
                );
                foreach ($users as $user) {
                    $results[$user['user_id']] = $word;
                }
            }
        }
        return $results;
    }
}