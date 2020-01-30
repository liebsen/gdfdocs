<?php 

/*
 * This file is part of the puntoweberplast project
 *
 * Copyright (c) 2019 Oxean
 * Author: Martin Frith
 * Email: telemagico@gmail.com
 *
 * Licensed under the MIT license:
 *   http://www.opensource.org/licenses/mit-license.php
 *
 * Project home:
 *   https://bitbucket.com/martin_fly/weber
 *
 */

date_default_timezone_set('America/Argentina/Buenos_Aires'); 

require __DIR__ . "/vendor/autoload.php";

$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();

$app = new \Slim\App([
    "settings" => [
        "displayErrorDetails" => true
    ]
]);


//require __DIR__ . "/routes/functions.php";
require __DIR__ . "/routes/dependencies.php";
require __DIR__ . "/routes/handlers.php";
require __DIR__ . "/routes/middleware.php";
require __DIR__ . "/routes/routes.php";	

$app->run();