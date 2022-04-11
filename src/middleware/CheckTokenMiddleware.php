<?php 

use \Psr\Http\Message\RequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Signature\Algorithm\HS256;
use Jose\Component\Signature\JWSVerifier;

use Jose\Component\Core\Converter\StandardConverter;
use Jose\Component\Core\JWK;
use Jose\Component\Signature\Serializer\JWSSerializerManager;
use Jose\Component\Signature\Serializer\CompactSerializer;

class CheckTokenMiddleware
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
        $valid_token = true;
        $token_error = '';
        $header_data = $request->getHeaders();
        // var_dump($header_data);
        // die('stop');
        if (isset($header_data['HTTP_TOKENAUTH'][0])) {
            $token = $header_data['HTTP_TOKENAUTH'][0];
        } else {
            $valid_token = false;
            $token_error = 'token inexistent';
        }

        /*if (isset($token)) {
            $handler = $this->handler;
            return $handler($request, $response);
        }
        $resp = $next($request, $response);
        return $resp;*/

        //TODO: token cu librarie

        if ($valid_token) {
            if (verifyToken($token)) {
                $sql_token_ver = 'select count(distinct ut.id) as token_cnt, sum(case when u.active = 1 then 1 else 0 end) as active_user from users_token ut left join users u on u.id = ut.user_id where token = :token';
                $stmt = $this->container->db->prepare($sql_token_ver);
                $stmt->execute(['token' => $token]);
                $res = $stmt->fetchAll();
                if ($res[0]['token_cnt'] > 1) {
                    $valid_token = false;
                    $token_error = 'Token duplicat';
                } else if ($res[0]['token_cnt'] === '0') {
                    $valid_token = false;
                    $token_error = 'Token nu e in bd: ' . $token;
                } else if ($res[0]['active_user'] === '0') {
                    $valid_token = false;
                    $token_error = 'Contul nu este activat';                    
                }
            } else {
                $valid_token = false;
                $error_message = 'token invalid';
            }
        }

        if ($valid_token) {
            $newResponse = $next($request, $response);
        } else {
            $arr_response = [];
            $arr_response['status'] = "token_error";
            $arr_response['message'] = "check token failed";
            $arr_response['payload'] =  $token_error;
            $newResponse = $response->withJson($arr_response);
        }

        return $newResponse;
    }
}

function verifyToken($appToken) {
    // The algorithm manager with the HS256 algorithm.
    $algorithmManager = AlgorithmManager::create([
        new HS256(),
    ]);

    // We instantiate our JWS Verifier.
    $jwsVerifier = new JWSVerifier(
        $algorithmManager
    );
    $encodedKey = base64_encode('cheiasupersecreta');
    // Our key.
    $jwk = JWK::create([
        'kty' => 'oct',
        'k' => $encodedKey,
    ]);

    // The JSON Converter.
    $jsonConverter = new StandardConverter();

    // The serializer manager. We only use the JWS Compact Serialization Mode.
    $serializerManager = JWSSerializerManager::create([
        new CompactSerializer($jsonConverter),
    ]);

    // The input we want to check
    $token = $appToken;

    // We try to load the token.
    $jws = $serializerManager->unserialize($token);

    // We verify the signature. This method does NOT check the header.
    // The arguments are:
    // - The JWS object,
    // - The key,
    // - The index of the signature to check. See 
    $isVerified = $jwsVerifier->verifyWithKey($jws, $jwk, 0);

    return $isVerified;
}

 ?>