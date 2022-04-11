<?php
// DIC configuration

$container = $app->getContainer();

// view renderer
$container['renderer'] = function ($c) {
    $settings = $c->get('settings')['renderer'];
    return new Slim\Views\PhpRenderer($settings['template_path']);
};

// monolog
$container['logger'] = function ($c) {    $settings = $c->get('settings')['logger'];
    $logger = new Monolog\Logger($settings['name']);
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
    $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
    return $logger;
};

//database connection
$container['db'] = function ($c) {
    $db = $c['settings']['db'];
    $pdo = new PDO('mysql:host=' . $db['host'] . ';dbname=' . $db['dbname'] , $db['user'], $db['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
};

$container['profile_images_path'] = 'assets/profile_images';

$container['paths'] = function($c) {
    $paths = [];
    $serverURL = "http://$_SERVER[HTTP_HOST]";
    $publicPath = $c['request']->getUri()->getBasePath() . '/';
    $public_path_disk = $c->get('settings')['public_path_disk']['directory'];
    $images_directory = $c->get('settings')['profile_images']['directory'];
    $profileBasePath = $serverURL . $publicPath . $images_directory;
    $paths['profile_images'] = $profileBasePath;
    $paths['public_path'] = $serverURL . $publicPath;
    $paths['public_path_disk'] = $public_path_disk;
    $paths['server_url'] = $serverURL;
    return $paths;
};

$container['mailer'] = function($c) {
    // $public_path_disk = $c->get('settings')['mail']['hostname'];
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    //Server settings
    $mail->SMTPDebug = 0;                                       // Enable verbose debug output
    $mail->isSMTP();                                            // Set mailer to use SMTP
    $mail->Host       = $c->get('settings')['mail']['hostname'];  // Specify main and backup SMTP servers
    $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
    $mail->Username   = $c->get('settings')['mail']['username'];                     // SMTP username
    $mail->Password   = $c->get('settings')['mail']['password'];                               // SMTP password
    $mail->SMTPSecure = 'ssl';                                  // Enable TLS encryption, `ssl` also accepted
    $mail->Port       = 465;                                    // TCP port to connect to
    $mail->setFrom('', 'Kumparaturi applicatie');
    $mail->isHTML(true);                                  // Set email format to HTML

    return $mail;
};

// $container['view'] = function ($c) {
//     $view = new \Slim\Views\Twig('templates');

//     // Instantiate and add Slim specific extension
//     $basePath = rtrim(str_ireplace('index.php', '', $c['request']->getUri()->getBasePath()), '/');
//     $view->addExtension(new Slim\Views\TwigExtension($c['router'], $basePath));
//     $view['baseUrl'] = $c['request']->getUri()->getBaseUrl();

//     return $view;
// };


/*
    Custom error handler 
*/
/*$container['errorHandler'] = function ($c) {
    return function ($request, $response, $exception) use ($c) {
        // log error here
        return $c['response']->withStatus(500)
                             ->withHeader('Content-Type', 'text/html')
                             ->write('Something went wrong on our side!');
    };
};
$container['phpErrorHandler'] = function ($c) {
    return function ($request, $response, $error) use ($c) {
        // log error here
        return $c['response']->withStatus(500)
                             ->withHeader('Content-Type', 'text/html')
                             ->write('Something went wrong on our side (again)!');
    };
};*/