<?php
namespace PasswordCracker\Strategies;

interface CrackerStrategy {
    public function crack(array $passwords, string $salt): array;
}
?>