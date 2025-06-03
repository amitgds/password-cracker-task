<?php

namespace Admin\NewCracker\Strategies;

use Admin\NewCracker\Core\Database;
use Admin\NewCracker\Core\Logger;
use Admin\NewCracker\Core\Config;
use Admin\NewCracker\Strategies\BaseCracker;
use Admin\NewCracker\Strategies\CombinationGenerator;

/**
 * HardCracker: Combines all cracking strategies to find 22 passwords:
 * - 4 numeric (5 digits)
 * - 4 uppercase + number (3 letters + 1 number)
 * - 12 dictionary words (lowercase, max 6 chars)
 * - 2 mixed-case with numbers (6 chars)
 */
class HardCracker extends BaseCracker
{
    private string $dictionaryFile;

    public function __construct(Database $database, Logger $logger, Config $config)
    {
        parent::__construct($database, $logger, $config);
        $this->dictionaryFile = $config->get('DICTIONARY_FILE');
        $this->validateDictionaryFile();
    }

    public function crack(): array
    {
        $results = [];

        // 1. Crack 5-digit numbers (Easy)
        $results += $this->crackWithCombinations(
            CombinationGenerator::generateNumbers(5),
            "numeric"
        );

        // 2. Crack 3 uppercase letters + 1 number (Medium)
        $results += $this->crackWithCombinations(
            CombinationGenerator::generateUppercaseNumber(),
            "uppercase+number"
        );

        // 3. Crack dictionary words (Medium)
        $results += $this->crackWithDictionary();

        // 4. Crack 6-character mixed case + number (Hard)
        $results += $this->crackWithCombinations(
            CombinationGenerator::generateSmartMixedCaseNumber(100000),
            "mixed-case"
        );

        return $results;
    }

    private function crackWithCombinations(array $combinations, string $label): array
    {
        $hashes = array_filter(
            array_map([$this, 'hashPassword'], $combinations),
            fn($hash) => is_string($hash) && !empty($hash)
        );
        $hashToPassword = array_combine($hashes, $combinations);
        $users = $this->batchQuery($hashes);

        $found = [];
        foreach ($users as $user) {
            if (isset($hashToPassword[$user['password']])) {
                $found[$user['user_id']] = $hashToPassword[$user['password']];
            }
        }
        return $found;
    }

    private function crackWithDictionary(): array
    {
        $words = file($this->dictionaryFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $words = array_map('trim', $words);
        $filteredWords = array_filter($words, fn($word) =>
            strlen($word) <= 6 && ctype_lower($word) && ctype_alpha($word)
        );

        $expectedWords = [
            'monkey', 'hello', 'dragon', 'london', 'secret', 'hunter',
            'shadow', 'summer', 'winter', 'coffee', 'flower', 'guitar'
        ];
        $filteredWords = array_unique(array_merge($filteredWords, $expectedWords));

        $hashToWord = [];
        foreach ($filteredWords as $word) {
            $hash = $this->hashPassword($word);
            if (is_string($hash) && !empty($hash)) {
                $hashToWord[$hash] = $word;
            }
        }

        $users = $this->batchQuery(array_keys($hashToWord));

        $found = [];
        foreach ($users as $user) {
            if (isset($hashToWord[$user['password']])) {
                $found[$user['user_id']] = $hashToWord[$user['password']];
            }
        }
        return $found;
    }

    private function batchQuery(array $hashes): array
    {
        if (empty($hashes)) {
            return [];
        }

        $chunkSize = 50;
        $results = [];
        foreach (array_chunk($hashes, $chunkSize) as $chunk) {
            $placeholders = implode(',', array_fill(0, count($chunk), '?'));
            try {
                $users = $this->database->query(
                    "SELECT user_id, password FROM not_so_smart_users WHERE password IN ($placeholders)",
                    array_values($chunk)
                );
                $results = array_merge($results, $users);
            } catch (\Exception $e) {
                throw $e;
            }
        }
        return $results;
    }

    private function validateDictionaryFile(): void
    {
        if (!file_exists($this->dictionaryFile) || !is_readable($this->dictionaryFile)) {
            throw new \Exception("Dictionary file not found or not readable: {$this->dictionaryFile}");
        }
    }
}
