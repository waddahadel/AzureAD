<?php

require_once "Customizing/global/plugins/Services/Authentication/AuthenticationHook/AzureAD/AzureClient/ADRequest.php";

/**
 * A wrapper around base64_decode which decodes Base64URL-encoded data,
 * which is not the same alphabet as base64.
 * @param string $base64url
 * @return bool|string
 */
function base64url_decode(string $base64url)
{
    return base64_decode(b64url2b64($base64url));
}

/**
 * Per RFC4648, "base64 encoding with URL-safe and filename-safe
 * alphabet".  This just replaces characters 62 and 63.  None of the
 * reference implementations seem to restore the padding if necessary,
 * but we'll do it anyway.
 * @param string $base64url
 * @return string
 */
function b64url2b64(string $base64url): string
{
    // "Shouldn't" be necessary, but why not
    $padding = strlen($base64url) % 4;
    if ($padding > 0) {
        $base64url .= str_repeat('=', 4 - $padding);
    }
    return strtr($base64url, '-_', '+/');
}


/**
 * MinervisAzure Exception Class
 */
class MinervisAzureClientException extends \Exception
{
}

/**
 * Require the CURL and JSON PHP extensions to be installed
 */
if (!function_exists('curl_init')) {
    throw new MinervisAzureClientException('MinervisAzure needs the CURL PHP extension.');
}
if (!function_exists('json_decode')) {
    throw new MinervisAzureClientException('MinervisAzure needs the JSON PHP extension.');
}

/**
 *
 * Please note this class stores nonces by default in $_SESSION['openid_connect_nonce']
 *
 */
class MinervisAzureClient
{
    const CODE_INVALID_API_OR_SECRET = 401;
    const CODE_OK = 200;
    const CODE_NO_CONTENT = 204;
    const CODE_UNAUTHORIZED = 401;
    const CODE_SERVER_ERROR = 500;
    const CODE_UNSUPPORTED_MEDIA = 415;
    const CODE_METHOD_NOT_ALLOWED = 405;
    const CODE_UNKNOWN_ERROR = 600;

    private $providerConfig = array();
    /**
     * @var int
     */
    const LEVEL_INFO = 200;
    /**
     * @var int
     */
    const LEVEL_WARNING = 300;
    /**
     * @var int
     */
    const LEVEL_EXCEPTION = 400;
    /**
     * @var int
     */
    const LEVEL_CRITICAL = 500;
    /**
     * @var array
     */

    /**
     * @var string arbitrary secret value
     */
    private $secret;
    /**
     * @var ilLogger
     */
    private $logger = null;

    /**
     * @var string if we acquire an access token it will be stored here
     */
    protected $accessToken;

    /**
     * @var string if we acquire a refresh token it will be stored here
     */
    private $refreshToken;

    /**
     * @var string if we acquire an id token it will be stored here
     */
    protected $idToken;

    /**
     * @var string stores the token response
     */
    private $tokenResponse;


    /**
     * @var int|null Response code from the server
     */
    private $responseCode;

    /**
     * @var array holds response types
     */
    private $responseTypes = array();

    /**
     * @var array holds a cache of info returned from the user info endpoint
     */
    private $userInfo = array();

    /**
     * @var array holds authentication parameters
     */
    private $authParams = array();

    /**
     * @var array holds additional registration parameters for example post_logout_redirect_uris
     */
    private $registrationParams = array();

    private $login_success = true;


    /**
     * @var int timeout (seconds)
     */
    protected $timeOut = 60;


    /**
     * @var string
     */
    private $redirectURL;

    protected $enc_type = PHP_QUERY_RFC1738;
    /**
     * @var mixed|string
     */
    private $proxy;

    /**
     * @param $provider_url string|null optional
     *
     * @param $api_key string optional
     * @param $secret_key string optional
 */
    public function __construct(string $provider_url = null, $api_key = null, $secret_key = null, $proxy = '')
    {
	global $DIC;
        $this->clientLogger=$DIC->logger()->root();
        $this->setProviderURL($provider_url);
        $this->setApiKey($api_key);
        $this->setSecretKey($secret_key);
        $this->setEndpoints();
        $this->setProxy($proxy);
        $this->logger = ilLoggerFactory::getLogger('MinervisAzureClient');
        //$this->logger->dump($this->providerConfig);
    }
    

    /**
     * @param $provider_url
     */
    public function setProviderURL($provider_url)
    {
        $this->providerConfig['providerUrl'] = $provider_url;
    }

    public function setProxy($proxy)
    {
        $this->providerConfig['proxy'] = $proxy;
    }
    
    /**
     * setSecret
     * @deprecated
     * @param  string  $secret
     * @return void
     */
    public function setSecret($secret)
    {
        $this->providerConfig['secret'] = $secret;
    }

    /**
     * setSecret
     *
     * @param  string  $secret
     * @return void
     */
    public function setSecretKey($secret_key )
    {
        $this->providerConfig['secret_key'] = $secret_key;
    }

    /**
     * setSecret
     *
     * @param  string  $secret
     * @return void
     */
    public function setApiKey($api_key)
    {
        $this->providerConfig['api_key'] = $api_key;
    }

    public function getLoginSuccess()
    {
        return $this->login_success;
    }


    /**
     * @return void
     */
    private function setEndpoints()
    {
        $providerUrl=$this->providerConfig['providerUrl'];
        $this->providerConfig['refresh_endpoint']= $providerUrl . "/v1/ilias/app/azure/refresh";
        $this->providerConfig['token_endpoint']= $providerUrl . "/v1/ilias/app/azure/login";
        $this->providerConfig['verify_endpoint']= $providerUrl . "/v1/ilias/app/azure/verify";
        $this->providerConfig['check_endpoint']= $providerUrl . "/v1/ilias/app/azure/check";
        $this->providerConfig['sync_endpoint']= $providerUrl . "/v1/ilias/app/azure/users";
        $this->providerConfig['single_user_endpoint']= $providerUrl . "/v1/ilias/app/azure/user";
    }

    public function configureInternalProxy()
    {
        $this->getProviderConfigValue['http_proxy']="www-proxy.vpn.minervis.com:3128/";
        $this->getProviderConfigValue['https_proxy'] = "www-proxy.vpn.minervis.com:3128/";
    }


    /**
     * @return bool
     * @throws MinervisAzureClientException
     */
    public function authenticate()
    {
        $this->requestTokens();
       

        // Do a preemptive check to see if the provider has thrown an error from a previous redirect
        if (isset($_REQUEST['error'])) {
            $desc = isset($_REQUEST['error_description']) ? ' Description: ' . $_REQUEST['error_description'] : '';
            throw new MinervisAzureClientException('Error: ' . $_REQUEST['error'] .$desc);
        }

        // var_dump($_REQUEST);
        // If we have an authorization code then proceed to request a token
        if (isset($_REQUEST['code'])) {
            // echo "found request code";
            $code = $_REQUEST['code'];
            $token_json = $this->requestTokens($code);

            // Throw an error if the server returns one
            if (isset($token_json->error)) {
                if (isset($token_json->error_description)) {
                    throw new MinervisAzureClientException($token_json->error_description);
                }
                throw new MinervisAzureClientException('Got response: ' . $token_json->error);
            }

          
            if (!property_exists($token_json, 'jwt')) {
                throw new MinervisAzureClientException('User did not authorize JWT.');
            }

            $claims = $this->decodeJWT($token_json->jwt, 1);



            // Save the id token
            $this->idToken = $token_json->jwt;

            // Save the access token
            $this->accessToken = $token_json->jwt;
        }

        $this->requestAuthorization();
       
        return false;
    }

    /**
     * It calls the end-session endpoint of the Minervis Azure JWT provider to notify the OpenID
     * Connect provider that the end-user has logged out of the relying party site
     * (the client application).
     *
     * @param string $accessToken ID token (obtained at login)
     * @param string|null $redirect URL to which the RP is requesting that the End-User's User Agent
     * be redirected after a logout has been performed. The value MUST have been previously
     * registered with the OP. Value can be null.
     *
     * @throws MinervisAzureClientException
     */
    public function signOut($accessToken, $redirect)
    {
        /*$signout_endpoint = $this->getProviderConfigValue('end_session_endpoint');

        $signout_params = null;
        if($redirect === null){
            $signout_params = array('jwt_hint' => $accessToken);
        } else {
            $signout_params = array(
                'jwt_hint' => $accessToken,
                'post_logout_redirect_uri' => $redirect);
        }

        $signout_endpoint  .= (strpos($signout_endpoint, '?') === false ? '?' : '&') . http_build_query( $signout_params, null, '&', $this->enc_type);
        $this->redirect($signout_endpoint);
        */
        return;
    }



    /**
     * @param array $param - example: prompt=login
     */
    public function addAuthParam($param)
    {
        $this->authParams = array_merge($this->authParams, (array)$param);
    }




    /**
     * Get's anything that we need configuration wise including endpoints, and other values
     *
     * @param string $param
     * @param string $default optional
     * @throws MinervisAzureClientException
     * @return string
     *
     */
    protected function getProviderConfigValue($param, $default = null)
    {
        return $this->providerConfig[$param];
    }


    /**
     * @param string $url Sets redirect URL for auth flow
     */
    public function setRedirectURL($url)
    {
        if (parse_url($url, PHP_URL_HOST) !== false) {
            $this->redirectURL = $url;
        }
    }

    /**
     * Gets the URL of the current page we are on, encodes, and returns it
     *
     * @return string
     */
    public function getRedirectURL()
    {

        // If the redirect URL has been set then return it.
        if (property_exists($this, 'redirectURL') && $this->redirectURL) {
            return $this->redirectURL;
        }

        // Other-wise return the URL of the current page

        /**
         * Thank you
         * http://stackoverflow.com/questions/189113/how-do-i-get-current-page-full-url-in-php-on-a-windows-iis-server
         */

        /*
         * Compatibility with multiple host headers.
         * The problem with SSL over port 80 is resolved and non-SSL over port 443.
         * Support of 'ProxyReverse' configurations.
         */

        if (isset($_SERVER['HTTP_UPGRADE_INSECURE_REQUESTS']) && ($_SERVER['HTTP_UPGRADE_INSECURE_REQUESTS'] === '1')) {
            $protocol = 'https';
        } else {
            $protocol = @$_SERVER['HTTP_X_FORWARDED_PROTO']
                ?: @$_SERVER['REQUEST_SCHEME']
                    ?: ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http');
        }

        $port = @intval($_SERVER['HTTP_X_FORWARDED_PORT'])
            ?: @intval($_SERVER['SERVER_PORT'])
                ?: (($protocol === 'https') ? 443 : 80);

        $host = @explode(':', $_SERVER['HTTP_HOST'])[0]
            ?: @$_SERVER['SERVER_NAME']
                ?: @$_SERVER['SERVER_ADDR'];

        $port = (443 === $port) || (80 === $port) ? '' : ':' . $port;

        return sprintf('%s://%s%s/%s', $protocol, $host, $port, @trim(reset(explode('?', $_SERVER['REQUEST_URI'])), '/'));
    }

    /**
     * Used for arbitrary value generation for nonces and state
     *
     * @return string
     */
    protected function generateRandString()
    {
        return md5(uniqid(rand(), true));
    }

    /**
     * Start Here
     * @return void
     * @throws MinervisAzureClientException
     */
    private function requestAuthorization()
    {
        $auth_endpoint = $this->getProviderConfigValue('token_endpoint');
        
        $response_type = 'code';
        //$this->commitSession();
        //$this->redirect($auth_endpoint);
    }

    /**
     * @throws MinervisAzureClientException
     */
    public  function retrieveUsers($top = 100, $skip_token = '') {
        global $DIC;
        $users_endpoint = $this->getProviderConfigValue('sync_endpoint');
        $body_params = array( 'top' => (string) $top);
        if(!empty($skip_token)){
            $body_params["skiptoken"] = $skip_token;
        }
        $headers=array(
            'Content-Type: application/json',
            'APIKey:  '. $this->getProviderConfigValue('api_key'),
            'APISecret: '. $this->getProviderConfigValue('secret_key')

        );
        $response = $this->fetchURL($users_endpoint, $body_params, $headers, false);
        $this->handleStatus($this->responseCode);
        return json_decode($response);
       /* $request = new ADRequest(
            $users_endpoint,
            $headers,
            $body_params,
            ADRequest::GET_METHOD,
            "www-proxy.vpn.minervis.com:3128"
        );

        $response = $request->send();
        $DIC->logger()->root()->dump($response->getBody());*/
    }

        /**
     * @throws MinervisAzureClientException
     */
    public  function retrieveSingleUser($user) {
        global $DIC;
        $users_endpoint = $this->getProviderConfigValue('single_user_endpoint');
        $body_params = array( 'user' => (string) $user);
        $headers=array(
            'Content-Type: application/json',
            'APIKey:  '. $this->getProviderConfigValue('api_key'),
            'APISecret: '. $this->getProviderConfigValue('secret_key')

        );
        $response = $this->fetchURL($users_endpoint, $body_params, $headers, false);
        $this->handleStatus($this->responseCode);
        return json_decode($response);
    }

    /**
     * @param int $status_code
     * @return void
     * @throws MinervisAzureClientException
     */
    public function handleStatus(int $status_code){
        $message = '';
        switch($status_code){
            case self::CODE_INVALID_API_OR_SECRET:
                if($this->tokenResponse){
                    $message = 'Status Code '.$status_code . ': Invalid  API or Secret';
                }else{
                    $message = 'Status Code '.$status_code . ': Invalid Login credentials';
                    $this->login_success = false;
                }
                break;
            case self::CODE_METHOD_NOT_ALLOWED:
                $message = 'Status Code '.$status_code . ': Method not allowed';
                break;
            case self::CODE_SERVER_ERROR:
                $message = 'Status Code '.$status_code . ': Server error';
                break;
            case self::CODE_UNSUPPORTED_MEDIA:
                $message = 'Status Code '.$status_code. ': Unsupported media type';
                break;

            default:
        }
        if($message){
            $this->logger->info($message);
            throw new MinervisAzureClientException($message);
        }
    }

    /**
     * Requests ID and Access tokens
     *
     * @param string $code
     * @return mixed
     * @throws MinervisAzureClientException
     */
    public function requestTokens()
    {
        $token_endpoint = $this->getProviderConfigValue('token_endpoint');
        $headers = [];
        $token_params=[
            'user'=>str_replace(' ','',$_REQUEST['username']),
            'password'=>$_REQUEST['password']
        ];
        $this->tokenResponse = json_decode($this->fetchURL($token_endpoint, $token_params, array()));
        //var_dump($this->tokenResponse);
        $message = '';
        switch($this->responseCode){
            case self::CODE_INVALID_API_OR_SECRET:
                if($this->tokenResponse){
                    $message = 'Status Code '.$this->responseCode . ': Invalid  API or Secret';
                }else{
                    $message = 'Status Code '.$this->responseCode . ': Invalid Login credentials';
                    $this->login_success = false;
                }
                break;
            case self::CODE_METHOD_NOT_ALLOWED:
                $message = 'Status Code '.$this->responseCode . ': Method not allowed';
                break;
            case self::CODE_SERVER_ERROR:
                $message = 'Status Code '.$this->responseCode . ': Server error';
                break;
            case self::CODE_UNSUPPORTED_MEDIA:
                $message = 'Status Code '.$this->responseCode . ': Unsupported media type';
                break;

            default:
        }
        if($message){
            $this->logger->info($message);
            throw new Exception($message);
        }
        
        $decodedJWT=json_decode(json_encode($this->decodeJWT($this->tokenResponse->jwt, 1)), true);
        $content=json_decode(json_encode($this->tokenResponse->content), true);
        $this->userInfo = (object) array_merge($decodedJWT, $content);
        return $this->tokenResponse;
    }

    /**
     * Requests Access token with refresh token
     *
     * @param string $refreshToken
     * @return mixed
     * @throws MinervisAzureClientException
     */
    public function refreshToken($refreshToken=null)
    {
        $token_endpoint = $this->getProviderConfigValue('refresh_endpoint');

        $grant_type = 'refreshToken';


        $token_params = array(
            'refresh'=>$this->refreshToken
        );
        $headers=[
            'Authorization: Bearer '.$this->accessToken
        ];

        // Convert token params to string format
        //$token_params = http_build_query($token_params, null, '&', $this->enc_type);

        $json = json_decode($this->fetchURL($token_endpoint, $token_params, $headers));

        if (isset($json->jwt)) {
            $this->accessToken = $json->jwt;
        }

        if (isset($json->refreshToken)) {
            $this->refreshToken = $json->refreshToken;
        }

        return $json;
    }


    /**
     * verify the access token
     *
     * @param string $refreshToken
     * @return mixed
     * @throws MinervisAzureClientException
     */
    public function verifyToken()
    {
        $token_endpoint = $this->getProviderConfigValue('verify_endpoint');

        
        $token_params = array(
            'token'=>$this->accessToken
        );


        

        $json = json_decode($this->fetchURL($token_endpoint, $token_params, array()));

        if ($this->getResponseCode()<>400) {
            return false;
        }

        return true;
    }

    public function getUserInfo()
    {
        return $this->userInfo;
    }


    /**
     * @param string $str
     * @return string
     */
    protected function urlEncode($str)
    {
        $enc = base64_encode($str);
        $enc = rtrim($enc, '=');
        $enc = strtr($enc, '+/', '-_');
        return $enc;
    }

    /**
     * @param string $jwt encoded JWT
     * @param int $section the section we would like to decode
     * @return object
     */
    protected function decodeJWT(string $jwt, int $section = 0): object
    {
        $parts = explode('.', $jwt);
        return json_decode(base64url_decode($parts[$section]));
    }

    

    

    /**
     * @param string $url
     * @param array | null $post_body string If this is set the post type will be POST
     * @param array $headers Extra headers to be send with the request. Format as 'NameHeader: ValueHeader'
     * @return mixed
     *@throws MinervisAzureClientException
     */
    protected function fetchURL(string $url, array $post_body = null, array $headers = array(), $method_post = true)
    {
        $proxy = $this->getProviderConfigValue('proxy');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if($method_post){
            curl_setopt($ch, CURLOPT_POST, 1);
        }else{
            curl_setopt($ch, CURLOPT_POST, 0);
            curl_setopt($ch, CURLOPT_HTTPGET, 1);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        }
        if ($post_body!==null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_body));
        }
        
        curl_setopt($ch, CURLOPT_PROXY, $proxy);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if (!isset($headers) || count($headers) == 0) {
            $headers=array(
                'Content-Type: application/json',
                'APIKey:  '. $this->getProviderConfigValue('api_key'),
                'APISecret: '. $this->getProviderConfigValue('secret_key')
                
            );
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $output = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        

        // HTTP Response code from server may be required from subclass
        $info = curl_getinfo($ch);
        $this->responseCode = $info['http_code'];

        if ($output === false) {
            throw new MinervisAzureClientException('Curl error: (' . curl_errno($ch) . ') ' . curl_error($ch));
        }

        // Close the cURL resource, and free system resources
        curl_close($ch);
        return $output;
    }


    /**
     * @param string $url
     */
    public function redirect($url)
    {
        header('Location: ' . $url);
        exit;
    }







  

    /**
     * Revoke a given token - either access token or refresh token.
     * @see https://tools.ietf.org/html/rfc7009
     *
     * @param string $token
     * @param string $token_type_hint
     * @param string|null $clientId
     * @param string|null $clientSecret
     * @return mixed
     * @throws MinervisAzureClientException
     */
    public function revokeToken($token, $token_type_hint = '', $clientId = null, $clientSecret = null)
    {
        $revocation_endpoint = $this->getProviderConfigValue('revocation_endpoint');

        $post_data = array(
            'token'    => $token,
        );
        if ($token_type_hint) {
            $post_data['token_type_hint'] = $token_type_hint;
        }
        $clientId = $clientId !== null ? $clientId : $this->clientID;
        $clientSecret = $clientSecret !== null ? $clientSecret : $this->clientSecret;

        // Convert token params to string format
        $post_params = http_build_query($post_data, null, '&');
        $headers = ['Authorization: Basic ' . base64_encode(urlencode($clientId) . ':' . urlencode($clientSecret)),
            'Accept: application/json'];

        return json_decode($this->fetchURL($revocation_endpoint, $post_params, $headers));
    }

    public function checkUserDeleted($ext_account)
    {
        $body = array(
            "user" => $ext_account
        );
        $headers=array(
            'Content-Type: application/json',
            'APIKey:  '. $this->getProviderConfigValue('api_key'),
            'APISecret: '. $this->getProviderConfigValue('secret_key')

        );
        $output = json_decode($this->fetchURL($this->getProviderConfigValue('check_endpoint'), $body, $headers, false));
        $message = '';
        $status = new Stdclass();
        switch($this->responseCode){
            case self::CODE_NO_CONTENT:
                $status->status = false;
                $status->reason = self::CODE_NO_CONTENT;
                $status->message = "No Content";
                $status->level = self::LEVEL_INFO;
                break;
            case self::CODE_OK:
                $status->status = true;
                $status->reason = self::CODE_OK;
                $status->message = "Ok";
                $status->level = self::LEVEL_INFO;
                break;
            case self::CODE_INVALID_API_OR_SECRET:
                $message = 'Status Code '.$this->responseCode . ': Invalid API Key or Secret Key';
                $status->status = false;
                $status->reason = self::CODE_INVALID_API_OR_SECRET;
                $status->message = $message;
                $status->level = self::LEVEL_EXCEPTION;
                break;
            case self::CODE_METHOD_NOT_ALLOWED:
                $message = 'Status Code '.$this->responseCode . ': Method not allowed';
                $status->status = false;
                $status->reason = self::CODE_METHOD_NOT_ALLOWED;
                $status->message = $message;
                $status->level = self::LEVEL_EXCEPTION;
                break;
            case self::CODE_SERVER_ERROR:
                $message = 'Status Code '.$this->responseCode . ': Server error';
                $status->status = false;
                $status->reason = self::CODE_SERVER_ERROR;
                $status->message = $message;
                $status->level = self::LEVEL_EXCEPTION;
                break;
            case self::CODE_UNSUPPORTED_MEDIA:
                $message = 'Status Code '.$this->responseCode . ': Unsupported media type';
                $status->status = false;
                $status->reason = self::CODE_UNSUPPORTED_MEDIA;
                $status->message = $message;
                $status->level = self::LEVEL_EXCEPTION;
                break;

            default:
                $status->status = false;
                $status->reason = self::CODE_UNKNOWN_ERROR;
                $status->message = 'Status Code '.$this->responseCode . ': Unknown error.';
                $status->level = self::LEVEL_EXCEPTION;
        }
        //$this->logger->info("Encountered an error with " . $this->responseCode . " : " .$message);
        return $status;

    }
 


    /**
     * Set the access token.
     *
     * May be required for subclasses of this Client.
     *
     * @param string $accessToken
     * @return void
     */
    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
    }

    /**
     * @return string
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * @return string
     */
    public function getRefreshToken()
    {
        return $this->refreshToken;
    }

    /**
     * @return string
     */
    public function getIdToken()
    {
        return $this->idToken;
    }

    /**
     * @return object
     */
    public function getAccessTokenHeader()
    {
        return $this->decodeJWT($this->accessToken);
    }

    /**
     * @return object
     */
    public function getAccessTokenPayload()
    {
        return $this->decodeJWT($this->accessToken, 1);
    }

    /**
     * @return object
     */
    public function getIdTokenHeader()
    {
        return $this->decodeJWT($this->idToken);
    }

    /**
     * @return object
     */
    public function getIdTokenPayload()
    {
        return $this->decodeJWT($this->idToken, 1);
    }

    /**
     * @return string
     */
    public function getTokenResponse()
    {
        return $this->tokenResponse;
    }

   

    /**
     * Get the response code from last action/curl request.
     *
     * @return int
     */
    public function getResponseCode()
    {
        return $this->responseCode;
    }

    /**
     * Set timeout (seconds)
     *
     * @param int $timeout
     */
    public function setTimeout($timeout)
    {
        $this->timeOut = $timeout;
    }

    /**
     * @return int
     */
    public function getTimeout()
    {
        return $this->timeOut;
    }

    /**
     * Stores nonce
     *
     * @param string $nonce
     * @return string
     */
    protected function setNonce($nonce)
    {
        $this->setSessionKey('azure_globus_nonce', $nonce);
        return $nonce;
    }

    /**
     * Get stored nonce
     *
     * @return string
     */
    protected function getNonce()
    {
        return $this->getSessionKey('azure_globus_nonce');
    }

    /**
     * Cleanup nonce
     *
     * @return void
     */
    protected function unsetNonce()
    {
        $this->unsetSessionKey('azure_globus_nonce');
    }

    /**
     * Stores $state
     *
     * @param string $state
     * @return string
     */
    protected function setState($state)
    {
        $this->setSessionKey('azure_globus_state', $state);
        return $state;
    }

    /**
     * Get stored state
     *
     * @return string
     */
    protected function getState()
    {
        return $this->getSessionKey('azure_globus_state');
    }

    /**
     * Cleanup state
     *
     * @return void
     */
    protected function unsetState()
    {
        $this->unsetSessionKey('azure_globus_state');
    }

    /**
     * Safely calculate length of binary string
     * @param string $str
     * @return int
     */
    private static function safeLength($str)
    {
        if (function_exists('mb_strlen')) {
            return mb_strlen($str, '8bit');
        }
        return strlen($str);
    }

    /**
     * Where has_equals is not available, this provides a timing-attack safe string comparison
     * @param string $str1
     * @param string $str2
     * @return bool
     */
    private static function hashEquals($str1, $str2)
    {
        $len1=static::safeLength($str1);
        $len2=static::safeLength($str2);

        //compare strings without any early abort...
        $len = min($len1, $len2);
        $status = 0;
        for ($i = 0; $i < $len; $i++) {
            $status |= (ord($str1[$i]) ^ ord($str2[$i]));
        }
        //if strings were different lengths, we fail
        $status |= ($len1 ^ $len2);
        return ($status === 0);
    }

    /**
     * Use session to manage a nonce
     */
    protected function startSession()
    {
        if (!isset($_SESSION)) {
            @session_start();
        }
    }

    protected function commitSession()
    {
        $this->startSession();

        session_write_close();
    }

    protected function getSessionKey($key)
    {
        $this->startSession();

        return $_SESSION[$key];
    }

    protected function setSessionKey($key, $value)
    {
        $this->startSession();

        $_SESSION[$key] = $value;
    }

    protected function unsetSessionKey($key)
    {
        $this->startSession();

        unset($_SESSION[$key]);
    }




    /**
     * @return array
     */
    public function getAuthParams()
    {
        return $this->authParams;
    }
}
