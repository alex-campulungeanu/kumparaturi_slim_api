<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

//Register test middleware
require __DIR__ . './../src/middleware/TestMiddleware.php';
$tst = new TestMiddleware($app->getContainer());

//Register ipfilter middleware
require __DIR__ . './../src/middleware/Mode.php';
require __DIR__ . './../src/middleware/IpFilterMiddleware.php';
$ipflt = new IpFilterMiddleware();

//Register token middleware
require __DIR__ . './../src/middleware/CheckTokenMiddleware.php';
$chkTkM = new CheckTokenMiddleware($app->getContainer());

// Register routes
require __DIR__ . '/../src/routes.php';

// Run app
$app->run();
