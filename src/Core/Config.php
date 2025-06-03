<?php
       namespace Admin\NewCracker\Core;
       use Dotenv\Dotenv;
       class Config {
           public function __construct() {
               $dotenv = Dotenv::createImmutable(__DIR__ . '/../../config');
               $dotenv->load();
           }
           public function get(string $key, $default = null) {
               return $_ENV[$key] ?? $default;
           }
       }