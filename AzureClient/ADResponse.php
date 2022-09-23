<?php

//use \Psr\Http\Message\ResponseInterface;

class ADResponse
{
    /**
     * @var int
     */
    private $status_code;
    /**
     * @var StreamInterface
     */
    private $body;

    public  function __construct(ResponseInterface $response)
    {
       $this->status_code = $response->getStatusCode();
       $this->body = $response->getBody();

    }
    public function getStatusCode(): int
    {
        return $this->status_code;
    }
    public function getBody(): StreamInterface
    {
        return $this->body;

    }
    public function getResponseInfo(): StreamInterface
    {
        return $this->body;
    }
}