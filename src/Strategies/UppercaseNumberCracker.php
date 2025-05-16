<?php
namespace PasswordCracker\Strategies;

class UppercaseNumberCracker implements CrackerStrategy {
    public function crack(array $passwords, string $salt): array {
        $results = [];
        $letters = range('A', 'Z');
        foreach ($letters as $l1) {
            foreach ($letters as $l2) {
                foreach ($letters as $l3) {
                    for ($i = 0; $i <= 9; $i++) {
                        $test = "$l1$l2$l3$i";
                        $hash = md5($test . $salt);
                        foreach ($passwords as $row) {
                            if ($row['password'] === $hash) {
                                $results[] = [
                                    'user_id' => $row['user_id'],
                                    'password' => $test,
                                    'type' => 'Medium (Uppercase + Number)'
                                ];
                            }
                        }
                    }
                }
            }
        }
        return $results;
    }
}
?>