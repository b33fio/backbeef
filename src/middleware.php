<?php
// Application middleware

// e.g: $app->add(new \Slim\Csrf\Guard);

//middleware for JWT Auth
$app->add(new \Slim\Middleware\JwtAuthentication([
    //SECRET_KEY == env variable within apache2 beef.conf
    "secret" => getenv('SECRET_KEY')
]));
