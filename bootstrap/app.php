<?php

use Respect\Validation\Validator as v;

use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

session_start();

require  __DIR__ . '\..\vendor\autoload.php';

// set up cloudinary is needed

\Cloudinary::config(array( 
    "cloud_name" => "", 
    "api_key" => "", 
    "api_secret" => "" 
  ));

  
// create new app with changaes to configuration settings
$app = new \Slim\App([
    'setting' => [

        'displayErrorDetails'=>true,
        // database connection
        'db' => [
            'driver' => 'mysql',
            'host' => 'localhost',
            'database' => '',
            'username' => '',
            'password' => '',
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
            'logger' => [
                'name' => '',
                'level' => Monolog\Logger::DEBUG,
                'path' => __DIR__ . '/../logs/app.log',
            ],
        ]
    ]
]);

// creating a container to fetch dependencies
$container = $app->getContainer();

$app->add(function (Request $request, Response $response, callable $next) {
    $uri = $request->getUri();
    $path = $uri->getPath();
    if ($path != '/' && substr($path, -1) == '/') {
        // permanently redirect paths with a trailing slash
        // to their non-trailing counterpart
        $uri = $uri->withPath(substr($path, 0, -1));
        
        if($request->getMethod() == 'GET') {
            return $response->withRedirect((string)$uri, 301);
        }
        else {
            return $next($request->withUri($uri), $response);
        }
    }

    return $next($request, $response);
});


// adding flashmessages
$container['flash'] = function () {
    return new \Slim\Flash\Messages();
};


//bind view to slims container, fetching view dependency
$container['view'] =  function ($container) {
$view = new \Slim\Views\Twig(__DIR__ . '/../resources/',[
        'cache' => false,]);

//extension generates urls to different routes within out view
    $view->addExtension(new \Slim\Views\TwigExtension(
        // router to generate url for links within our views
        $container->router,
        // pull in the currrent url
        $container->request->getUri()

    ));
    $view->getEnvironment()->addGlobal('auth',[

'date' => date('Y')
]);
    // add flash to global to be used on our twig
    $view->getEnvironment()->addGlobal('flash',$container->flash);
    return $view;
  };


  $container['notAllowedHandler'] = function ($container) {
    return function ($request, $response, $methods) use ($container) {
         $response->withStatus(405)
            ->withHeader('Allow', implode(', ', $methods))
            ->withHeader('Content-type', 'text/html');
            // ->write('Method must be one of: ' . implode(', ', $methods))
            return $container['view']->render($response, '405.twig', [
                'method' => $methods
            ]);
        };   
};

$container['notFoundHandler'] = function ($container) {
    return function ($request, $response) use ($container) {
         $response->withStatus(404)
            ->withHeader('Content-Type', 'text/html');
            return $container['view']->render($response, '404.twig');
    };
};

$container['logger'] = function($c) {
    $logger = new \Monolog\Logger('LodgerMan');
    $file_handler = new \Monolog\Handler\StreamHandler('../logs/app.log');
    $logger->pushHandler($file_handler);
    return $logger;
};
// start eloquent

$capsule = new \Illuminate\Database\Capsule\Manager;
$capsule->addConnection($container['setting']['db']);
$capsule->setAsGlobal();
$capsule->bootEloquent();

$container['db'] = function($container) use ($capsule){
    return $capsule;
};

// Respect/validator dependency made available to other dependencies

$container['validator'] = function($container){
    return new Lodgerman\Validation\Validator;
};


// setting our personal validation rules
// v::with('Lodgerman\\Validation\\Rules\\');

require __DIR__ . '\..\app\routes.php';
