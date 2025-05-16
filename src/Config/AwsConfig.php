<?php
namespace PasswordCracker\Config;

class AwsConfig implements ConfigInterface {
    public function getDbConfig(): array {
        return [
            'host' => 'localhost',
            'name' => 'cracker',
            'user' => 'root', // or 'cracker_user' if created
            'pass' => '' // or 'secure_password' if user created
        ];
    }

    public function getSalt(): string {
        return 'ThisIs-A-Salt123';
    }
}
?>