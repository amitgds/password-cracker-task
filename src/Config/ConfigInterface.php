<?php
namespace PasswordCracker\Config;

interface ConfigInterface {
    public function getDbConfig(): array;
    public function getSalt(): string;
}
?>