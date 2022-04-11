<?php 

use \Psr\Http\Message\RequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

class TestMiddleware
{
    protected $handler = null;
    private $container = null;

    public function __construct($container)
    {
        $this->container = $container;
        $this->handler = function(Request $request, Response $response) {
            $response = $response->withStatus(401);
            $response->getBody()->write('Access denied!');
            return $response;
        };
    }

    public function __invoke(Request $request, Response $response, callable $next)
    {
        $header_data = $request->getHeaders();
        if (!isset($header_data['HTTP_TESTHEADER'][0])) {
            $handler = $this->handler;
            return $handler($request, $response);
        }

        $resp = $next($request, $response);
        return $resp;

        // var_dump($header_data['HTTP_TESTHEADER'][0]);
        // echo "<pre>";
        // print_r($header_data);
        // echo "</pre>";
        // die('stop');
    }
}

 ?>