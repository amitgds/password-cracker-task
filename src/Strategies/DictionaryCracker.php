<?php
namespace PasswordCracker\Strategies;

class DictionaryCracker implements CrackerStrategy {
    private $dictionaryPath;

    public function __construct(string $dictionaryPath) {
        $this->dictionaryPath = $dictionaryPath;
    }

    public function crack(array $passwords, string $salt): array {
        $results = [];
        if (!file_exists($this->dictionaryPath)) {
            return $results;
        }

        $words = file($this->dictionaryPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($words as $word) {
            if (strlen($word) <= 6 && ctype_lower($word)) {
                $hash = md5($word . $salt);
                foreach ($passwords as $row) {
                    if ($row['password'] === $hash) {
                        $results[] = [
                            'user_id' => $row['user_id'],
                            'password' => $word,
                            'type' => 'Medium (Dictionary)'
                        ];
                    }
                }
            }
        }
        return $results;
    }
}
?>