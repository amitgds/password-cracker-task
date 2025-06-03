<?php

spl_autoload_register(function ($class) {
    $prefix = 'Admin\\NewCracker\\';
    $baseDir = __DIR__ . '/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

function salter($string) {
    return md5($string . SALT);
}
?>