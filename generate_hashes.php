<?php
require 'vendor/autoload.php';
use PasswordCracker\Config\AwsConfig;

$config = new AwsConfig();
$salt = $config->getSalt();
$passwords = [
    '12345', '67890', '54321', '11111', // Easy
    'EII9', 'FMS8', 'XCN2', 'YQI7', // Medium (UUUN)
    'aaaaaa', 'bbbbbb', 'cccccc', 'dddddd', 'eeeeee', 'ffffff',
    'gggggg', 'hhhhhh', 'iiiiii', 'jjjjjj', 'kkkkkk', 'llllll', // Medium (LLLLLL)
    'AbC12z', 'XyZ78w' // Hard
];

echo "Salt: $salt\n";
$hashes = [];
foreach ($passwords as $pass) {
    $hash = md5($pass . $salt);
    $hashes[$pass] = $hash;
    echo "('$pass', '$hash'),\n";
}
?>