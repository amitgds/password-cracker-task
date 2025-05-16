<?php
$dsn = 'mysql:host=localhost;dbname=cracker';
$username = 'root';
$password = ''; // Update if custom MySQL password
$salt = 'ThisIs-A-Salt123';

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Clear existing hashes
    $pdo->exec('TRUNCATE TABLE password_hashes');
    echo "Cleared password_hashes table.\n";

    // Medium lowercase passwords (12, all 6-char lowercase)
    $mediumPasswords = [
        'aaaaaa' => 'b6f27572752f2fc9b257359d0c6a4ef8', // Your hash
        'bbbbbb' => '0f3ae57bb4e32740ed68305c0bd01ad8', // Replace with hash from hashes.txt
        'cccccc' => 'b699ada355aaf66e5dbfd86ff3c9df81', // Replace
        'dddddd' => '0bb5039d972f4bf52a5b189017321fb0', // Replace
        'eeeeee' => 'c86ae40b8df0722c474bed61aa0ec38d', // Replace
        'ffffff' => '1882aa5b2b6ce7a0921dae5b7208fb16', // Replace
        'gggggg' => '6fef2b3a9f12aed52c4f7bd1eb0c512a', // Replace
        'hhhhhh' => 'd426e3969bdd7d0b2ef85853afac317e', // Replace
        'iiiiii' => '3de9ad260fdbf5e8b55df66941f844bd', // Replace
        'jjjjjj' => '25ce3e0ebaedb99fb30c1754dd687647', // Replace
        'kkkkkk' => '9237023e486a6cc7fe707fc6f44b0057', // Replace
        'llllll' => '12d6870adcdb93e41094718c252a0bd3'  // Replace
    ];

    // Hard mixed passwords (2)
    $hardPasswords = [
        'AbC12z' => '045fea5bea7626fb311e0e67602b767a', // Your hash
        'XyZ78w' => '6dd7aeac53ad699cd26850da4a75e1f6' // Replace
    ];

    // Insert Medium lowercase
    $stmt = $pdo->prepare('INSERT INTO password_hashes (password, hash, type) VALUES (:password, :hash, "medium_lowercase")');
    foreach ($mediumPasswords as $pass => $hash) {
        $computedHash = md5($pass . $salt);
        echo "Medium: $pass, Expected: $hash, Computed: $computedHash\n";
        $stmt->execute(['password' => $pass, 'hash' => $hash]);
        echo "Inserted medium_lowercase: $pass, hash: $hash\n";
    }

    // Insert Hard mixed
    $stmt = $pdo->prepare('INSERT INTO password_hashes (password, hash, type) VALUES (:password, :hash, "hard_mixed")');
    foreach ($hardPasswords as $pass => $hash) {
        $computedHash = md5($pass . $salt);
        echo "Hard: $pass, Expected: $hash, Computed: $computedHash\n";
        $stmt->execute(['password' => $pass, 'hash' => $hash]);
        echo "Inserted hard_mixed: $pass, hash: $hash\n";
    }

    echo "Password hashes populated successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>