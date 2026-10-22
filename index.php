<?php


$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($path !== '/' && is_file(__DIR__ . $path)) {
    return false;
}


// Autoload
spl_autoload_register(function ($class) {

    $file = __DIR__ . '/'
        . str_replace(['App\\', '\\'], ['', '/'], $class)
        . '.php';

    if (is_file($file)) {
        require $file;
    }
});

use Controller\HomeController;

$controller = new HomeController();

if ($path == '/') {

    $controller->index();

} elseif (str_starts_with($path, '/sport/')) {

    $slug = str_replace('/sport/', '', $path);

    $controller->sport($slug);

}