<?php

use GuzzleHttp\Client;

require_once "Customizing/global/plugins/Services/Authentication/AuthenticationHook/AzureAD/AzureClient/ADResponse.php";



class ADRequest
{
    const GET_METHOD = "GET";
    const POST_METHOD = "POST";
    /**
     * @var array
     */
    private $headers;

    /**
     * @var string
     */
    private $method;

    /**
     * @var array|null
     */
    private $body;

    /**
     * @var string
     */
    private $proxy;

    /**
     * @var ADResponse
     */
    private $response ;
    /**
     * @var string
     */
    private $uri;
    /**
     * @var array
     */
    private $params;

    /**
     * @param string $uri
     * @param array $headers
     * @param array $body
     * @param string $method
     * @param string $proxy
     */
    public function __construct(
        string  $uri,
        array $headers = array(),
        array $body = array(),
        string $method = "POST",
        string $proxy = ""
    )
    {
        $this->uri = $uri;
        $this->headers = $headers;
        $this->body = $body;
        $this->method = $method;
        $this->proxy = $proxy;
        $this->initParams();
    }
    public function initParams()
    {
        $this->params = array(
            "uri" => $this->uri,
            "method" => $this->method,
            "proxy" => $this->proxy,
            "headers" => $this->headers,
            "body" => $this->body
        );
    }
    public function withHeaders( array $headers): ADRequest
    {
        return $this;
    }
    public function withResources ($resources): ADRequest
    {
        return $this;
    }
    public function withMethod (string $method): ADRequest
    {
        return $this;
    }
    public  function withBody( array $body): ADRequest
    {
        return $this;
    }

    public function withProxy ( $proxy): ADRequest
    {
        return $this;
    }
    public function getResponse(): ADResponse
    {
        return  $this->response;
    }

    /**
     * @throws GuzzleException
     */
    public  function send(): ADResponse
    {
        $req_pars = $this->params;
        $req = new Request($req_pars["method"], $req_pars["uri"], $req_pars["headers"], $req_pars["body"]);
        $client_options = [];
        if($req_pars["proxy"]) $client_options = [ "proxy" => $req_pars["proxy"] ];
        $client = new Client($client_options);
        $this->response =  ADResponse($client->send($req));
        return $this->response;
    }

    /**
     * @throws MinervisAzureClientException
     * @throws Exception
     */
    public  function fetch()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if($this->isMethodPost()){
            curl_setopt($ch, CURLOPT_POST, 1);
        }else{
            curl_setopt($ch, CURLOPT_POST, 0);
            curl_setopt($ch, CURLOPT_HTTPGET, 1);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        }
        if ($this->body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_body));
        }

        curl_setopt($ch, CURLOPT_PROXY, $this->proxy);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);

        $output = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }

        $this->response = new ADResponse();
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
     * @throws Exception
     */
    private function isMethodPost(): bool
    {
        if (!$this->isMethodValid()){
            throw new Exception("Wrong HTTP Method");
        }
        return $this->method === self::POST_METHOD;
    }
    private function isMethodValid(): bool
    {
        switch ($this->method){
            case self::POST_METHOD:
            case self::GET_METHOD:
                return true;
            default:
                return false;
        }
    }

}