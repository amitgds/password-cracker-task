# Password Cracker

Password Cracker is a PHP-based application designed to identify weak passwords by matching hashed user passwords against a database of known password hashes. It leverages a MySQL database for data storage, Monolog for logging, and PHPUnit for testing. The project runs in a Windows WAMP environment and is optimized for performance with indexed database queries.

## Purpose
The application simulates password cracking by comparing MD5-hashed passwords from a user table (`not_so_smart_users`) against a precomputed hash table (`password_hashes`). It categorizes passwords as `Easy (Numbers)`, `Medium (Uppercase + Number)`, `Medium (Lowercase)`, or `Hard (Mixed)` and logs cracking results for analysis.

## Features
- **Hash Generation**: Creates MD5 hashes using a configurable salt (`ThisIs-A-Salt123`).
- **Password Cracking**: Matches user hashes against known hashes for specified categories (`easy`, `medium`, `hard`).
- **Database Integration**: Uses MySQL (`cracker_test`) with indexed tables for efficient queries.
- **Logging**: Records database operations and cracking results via Monolog.
- **Testing**: Includes unit tests to ensure reliability and performance.
- **Performance**: Optimized for large datasets with SQL JOINs and indexes.

## Requirements
- **PHP**: 8.3.6 or higher
- **MySQL**: 5.7 or higher
- **WAMP**: Configured at `D:\wamp64`
- **Composer**: For dependency management
- **Dependencies** (via `composer.json`):
  - `monolog/monolog`: Logging
  - `phpunit/phpunit`: Testing (11.5.20)

## Installation

1. **Clone the Repository**:
   ```bash
   git clone <repository-url> D:\wamp64\www\password-cracker
   cd D:\wamp64\www\password-cracker
   ```

2. **Install Dependencies**:
   ```bash
   composer install
   ```

3. **Set Up MySQL Database**:
   - Open phpMyAdmin (`http://localhost/phpmyadmin`).
   - Create database:
     ```sql
     CREATE DATABASE cracker_test;
     ```
   - Create tables:
     ```sql
     CREATE TABLE not_so_smart_users (
         user_id INT PRIMARY KEY,
         password VARCHAR(32) NOT NULL
     );
     CREATE TABLE password_hashes (
         password VARCHAR(255) NOT NULL,
         hash VARCHAR(32) NOT NULL,
         type VARCHAR(50) NOT NULL
     );
     ```
   - Add indexes:
     ```sql
     ALTER TABLE password_hashes ADD INDEX idx_hash (hash);
     ALTER TABLE not_so_smart_users ADD INDEX idx_password (password);
     ```

4. **Configure Logging**:
   - Ensure `logs` directory is writable:
     ```bash
     icacls D:\wamp64\www\password-cracker\logs /grant Everyone:F
     ```

5. **Verify WAMP**:
   - Confirm Apache and MySQL are running via WAMP.
   - Place project in `D:\wamp64\www\password-cracker`.

## Project Structure
```
password-cracker/
├── src/
│   └── Services/
│       ├── CrackerService.php  # Core cracking logic
│       ├── DatabaseService.php # Database operations
│       └── AwsConfig.php      # Configuration (salt)
├── tests/
│   └── CrackerServiceTest.php # Unit tests
├── logs/
│   └── test.log              # Log file
├── vendor/                   # Composer dependencies
├── composer.json             # Dependency configuration
└── README.md                 # Project documentation
```

## Usage

### Cracking Passwords
1. **Prepare Data**:
   - Populate `not_so_smart_users` with user IDs and hashed passwords.
   - Populate `password_hashes` with known passwords, their hashes, and types (e.g., `Easy (Numbers)`).
   Example:
   ```sql
   INSERT INTO not_so_smart_users (user_id, password) VALUES
       (2995, '776081a98bebdeba0f3cf05c3be6d47c'); -- EII9
   INSERT INTO password_hashes (password, hash, type) VALUES
       ('EII9', '776081a98bebdeba0f3cf05c3be6d47c', 'Medium (Uppercase + Number)');
   ```

2. **Run CrackerService**:
   Create a script (e.g., `crack.php`):
   ```php
   <?php
   require 'vendor/autoload.php';
   use PasswordCracker\Services\CrackerService;
   use PasswordCracker\Services\DatabaseService;
   use PasswordCracker\Config\AwsConfig;
   use Monolog\Logger;
   use Monolog\Handler\StreamHandler;

   $config = new AwsConfig();
   $logger = new Logger('test');
   $logger->pushHandler(new StreamHandler('D:\\wamp64\\www\\password-cracker\\logs\\test.log', Logger::INFO));
   $dbService = new DatabaseService($config, $logger);
   $crackerService = new CrackerService($dbService, $config, $logger);

   $results = $crackerService->crackPasswords('medium');
   print_r($results);
   ?>
   ```
   Run:
   ```bash
   php crack.php
   ```

3. **View Logs**:
   Check `D:\wamp64\www\password-cracker\logs\test.log` for results (e.g., `Match found for user_id 2995, password EII9`).

### Generating Hashes
To generate hashes for new passwords:
1. Create `generate_hashes.php`:
   ```php
   <?php
   require 'vendor/autoload.php';
   use PasswordCracker\Config\AwsConfig;

   $config = new AwsConfig();
   $salt = $config->getSalt();
   $passwords = ['mmmmmm', 'nnnnnn'];

   echo "Salt: '$salt' (length: " . strlen($salt) . ")\n";
   foreach ($passwords as $pass) {
       $input = $pass . $salt;
       $hash = md5($input);
       echo "('$pass', '$hash'),\n";
   }
   ?>
   ```
2. Run:
   ```bash
   php generate_hashes.php > hashes.txt
   ```

### Running Tests
1. Clear log:
   ```bash
   echo. > D:\wamp64\www\password-cracker\logs\test.log
   ```
2. Run tests:
   ```bash
   vendor\bin\phpunit tests\CrackerServiceTest.php
   ```

## Architecture
- **CrackerService**:
  - Generates MD5 hashes (`generateHash`).
  - Cracks passwords (`crackPasswords`) using SQL JOIN to match `not_so_smart_users.password` with `password_hashes.hash`.
  - Logs matches and completion.
- **DatabaseService**:
  - Handles MySQL connections and queries (`exec`, `prepare`).
  - Configured via `AwsConfig` (salt, database credentials).
- **AwsConfig**:
  - Provides salt (`ThisIs-A-Salt123`) and database settings.
- **Logging**:
  - Monolog writes to `logs/test.log`.
- **Testing**:
  - `CrackerServiceTest.php` tests hash generation, cracking, edge cases, and performance.

## Performance
- Optimized with SQL JOIN and indexes.
- Handles 1000 rows in <15s (tested on WAMP, PHP 8.3.6).
- To profile:
  ```php
  $start = microtime(true);
  $results = $crackerService->crackPasswords('medium');
  echo "Time: " . (microtime(true) - $start) . "\n";
  ```

## Troubleshooting
- **Database Errors**:
  - Verify `cracker_test` exists and tables are indexed:
    ```bash
    mysql -u root -e "USE cracker_test; SHOW TABLES"
    ```
  - Test connection:
    ```php
    <?php
    require 'vendor/autoload.php';
    use PasswordCracker\Services\DatabaseService;
    use PasswordCracker\Config\AwsConfig;
    use Monolog\Logger;
    use Monolog\Handler\StreamHandler;

    $config = new AwsConfig();
    $logger = new Logger('test');
    $logger->pushHandler(new StreamHandler('D:\\wamp64\\www\\password-cracker\\logs\\test.log', Logger::INFO));
    $dbService = new DatabaseService($config, $logger);
    $dbService->exec('SELECT 1');
    echo "Connected successfully\n";
    ?>
    ```
- **Test Failures**:
  - Check `logs/test.log` for query errors.
  - Ensure `password_hashes` types match (`Easy (Numbers)`, `Medium (Uppercase + Number)`, etc.).
- **Logging Issues**:
  - Verify `logs/test.log` is writable.
  - Clear log:
    ```bash
    echo. > D:\wamp64\www\password-cracker\logs\test.log
    ```

## Contributing
- Fork the repository.
- Create a feature branch (`git checkout -b feature/your-feature`).
- Commit changes (`git commit -m "Add your feature"`).
- Push to branch (`git push origin feature/your-feature`).
- Open a pull request.

## License
MIT License. See `LICENSE` file (if available).