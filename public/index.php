<?php
if (PHP_SAPI == 'cli-server') {
    // To help the built-in PHP dev server, check if the request was actually for
    // something which should probably be served as a static file
    $url  = parse_url($_SERVER['REQUEST_URI']);
    $file = __DIR__ . $url['path'];
    if (is_file($file)) {
        return false;
    }
}

require __DIR__ . '/../vendor/autoload.php';

session_start();

// Instantiate the app
$settings = require __DIR__ . '/../src/settings.php';
$app = new \Slim\App($settings);

// Set up dependencies
require __DIR__ . '/../src/dependencies.php';

// Register middleware
require __DIR__ . '/../src/middleware.php';

// post calls
require __DIR__ . '/../src/postroutes.php';

// Login
require __DIR__ . '/../src/login.php';

// JWT
require __DIR__ . '/../src/JWT.php';

//Mailgun
require __DIR__ . '/../src/mailgun.php';

// Register account creation
require __DIR__ . '/../src/register.php';

// Register routes
require __DIR__ . '/../src/routes.php';

// Run app
$app->run();
