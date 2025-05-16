<?php
namespace PasswordCracker\Strategies;

class NumbersCracker implements CrackerStrategy {
    public function crack(array $passwords, string $salt): array {
        $results = [];
        for ($i = 10000; $i <= 99999; $i++) {
            $test = str_pad($i, 5, '0', STR_PAD_LEFT);
            $hash = md5($test . $salt);
            foreach ($passwords as $row) {
                if ($row['password'] === $hash) {
                    $results[] = [
                        'user_id' => $row['user_id'],
                        'password' => $test,
                        'type' => 'Easy (Numbers)'
                    ];
                }
            }
        }
        return $results;
    }
}
?>