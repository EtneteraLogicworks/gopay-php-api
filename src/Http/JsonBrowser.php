<?php

namespace GoPay\Http;

use GoPay\Http\Log\Logger;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;

class JsonBrowser
{

    private $logger;
    private $timeout;
    private $guzzle_args

    public function __construct(Logger $l, $timeoutInSeconds, $proxy=Null)
    {
        $this->logger = $l;
        $this->timeout = $timeoutInSeconds;

        $this->guzzle_args = array();
        $this->guzzle_args['timeout' => $this->timeout]
        if(isset($proxy)) {
             $this->guzzle_args['proxy' => $proxy]
        }

    }

    public function send(Request $r)
    {
        try {
            if (class_exists('\GuzzleHttp\Message\Request')) {
                $client = new GuzzleClient($this->guzzle_args);
                $guzzRequest = $client->createRequest($r->method, $r->url);
                $guzzRequest->setHeaders($r->headers);
                $guzzRequest->setBody(\GuzzleHttp\Stream\Stream::factory($r->body));
            } else {
                $client = new GuzzleClient($this->guzzle_args);
                $guzzRequest = new \GuzzleHttp\Psr7\Request($r->method, $r->url, $r->headers, $r->body);
            }
            $guzzResponse = $client->send($guzzRequest);
            $response = new Response((string)$guzzResponse->getBody());
            $response->statusCode = (string)$guzzResponse->getStatusCode();
            $response->json = json_decode((string)$response, true);
            $this->logger->logHttpCommunication($r, $response);
            return $response;
        } catch (ClientException $e) {
            if ($e->hasResponse()) {
                $response = new Response($e->getResponse()->getBody());
                $response->json = json_decode($e->getResponse()->getBody());
                $response->statusCode = $e->getCode();
                $this->logger->logHttpCommunication($r, $response);
                return $response;
            }
        } catch (\Exception $ex) {
            $response = new Response($ex->getMessage());
            $response->statusCode = 500;
            $this->logger->logHttpCommunication($r, $response);
            return $response;
        }

    }

}
