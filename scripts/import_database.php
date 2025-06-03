<?php
/**
 * PHP CLI script to import a MySQL database from a .sql file.
 * Usage: php import_database.php --host=<host> --user=<user> --password=<password> --database=<database> --file=<sql_file>
 * Example: php import_database.php --host=localhost --user=root --password=secret --database=myapp_db --file=backup.sql
 */

// Function to display usage instructions
function displayUsage() {
    echo "Usage: php " . basename(__FILE__) . " --host=<host> --user=<user> --password=<password> --database=<database> --file=<sql_file>\n";
    echo "Example: php " . basename(__FILE__) . " --host=localhost --user=root --password=secret --database=myapp_db --file=backup.sql\n";
    exit(1);
}

// Parse command-line arguments
$options = getopt('', ['host:', 'user:', 'password:', 'database:', 'file:']);
$required = ['host', 'user', 'password', 'database', 'file'];

foreach ($required as $arg) {
    if (!isset($options[$arg])) {
        echo "Error: Missing argument --$arg\n";
        displayUsage();
    }
}

$host = $options['host'];
$user = $options['user'];
$password = $options['password'];
$database = $options['database'];
$sqlFile = $options['file'];

// Validate SQL file existence
if (!file_exists($sqlFile) || !is_readable($sqlFile)) {
    echo "Error: SQL file '$sqlFile' does not exist or is not readable.\n";
    exit(1);
}

// Connect to MySQL server (without selecting a database)
$mysqli = new mysqli($host, $user, $password);

if ($mysqli->connect_error) {
    echo "Error: Failed to connect to MySQL server: " . $mysqli->connect_error . "\n";
    exit(1);
}

// Create database if it doesn't exist
$createDbQuery = "CREATE DATABASE IF NOT EXISTS `$database`";
if (!$mysqli->query($createDbQuery)) {
    echo "Error: Failed to create database '$database': " . $mysqli->error . "\n";
    $mysqli->close();
    exit(1);
}

// Select the database
if (!$mysqli->select_db($database)) {
    echo "Error: Failed to select database '$database': " . $mysqli->error . "\n";
    $mysqli->close();
    exit(1);
}

// Read SQL file
$sql = file_get_contents($sqlFile);
if ($sql === false) {
    echo "Error: Failed to read SQL file '$sqlFile'.\n";
    $mysqli->close();
    exit(1);
}

echo "Importing database '$database' from '$sqlFile'...\n";

// Execute multi-query (handles multiple SQL statements)
$mysqli->multi_query($sql);

// Process results and check for errors
$success = true;
do {
    if ($result = $mysqli->store_result()) {
        $result->free();
    }
    if ($mysqli->error) {
        echo "Error: Query failed: " . $mysqli->error . "\n";
        $success = false;
        break;
    }
} while ($mysqli->more_results() && $mysqli->next_result());

if ($success) {
    echo "Database '$database' imported successfully.\n";
} else {
    echo "Database import completed with errors.\n";
}

// Close connection
$mysqli->close();
exit($success ? 0 : 1);
?>
