<?php

namespace Egcs;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Uri;
use kamermans\OAuth2\GrantType\ClientCredentials;
use kamermans\OAuth2\OAuth2Middleware;
use kamermans\OAuth2\Persistence\FileTokenPersistence;

class MendrixApi {

    protected $api_host = 'http://api.mendrix.nl';

    protected $client_id;
    protected $client_secret;
    protected $scope;

    protected $token_path;

    protected $client;
    protected $cookies = [];

    public function __construct (int $client_id, string $client_secret, string $scope = '')
    {
        $this->client_id = $client_id;
        $this->client_secret = $client_secret;
        $this->scope = $scope;
    }

    public function setApiHost (string $api_host): MendrixApi
    {
        $this->api_host = $api_host;
        return $this;
    }

    public function setTokenPath (string $token_path): MendrixApi
    {
        $this->token_path = $token_path;
        return $this;
    }

    public function setCookies (array $cookies): MendrixApi
    {
        $this->cookies = $cookies;
        return $this;
    }

    public function getUser ()
    {
        try {
            $response = $this->getClient()->get('user', [
                'cookies' => $this->getCookies(),
            ]);
            $body = (string)$response->getBody();
            return json_decode($body, true);
        } catch (Exception $e) {
            throw new MendrixApiException('Error in getUser: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    public function getServerdate ()
    {
        try {
            $response = $this->getClient()->get('mendrix/serverdate', [
                'cookies' => $this->getCookies(),
            ]);
            $body = (string)$response->getBody();
            return json_decode($body, true);
        } catch (Exception $e) {
            throw new MendrixApiException('Error in getServerdate: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    public function getOrderIds (string $from, string $to, int $clientNo, int $operatorId = -1)
    {
        try {
            $response = $this->getClient()->get('mendrix/orderids', [
                'cookies' => $this->getCookies(),
                'query' => compact('from', 'to', 'clientNo', 'operatorId'),
            ]);
            $body = (string)$response->getBody();
            return json_decode($body, true);
        } catch (Exception $e) {
            throw new MendrixApiException('Error in getOrderids: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    public function getOrderByIds ()
    {
        try {
            $response = $this->getClient()->get('mendrix/orderbyids', [
                'cookies' => $this->getCookies(),
            ]);
            $body = (string)$response->getBody();
            return json_decode($body, true);
        } catch (Exception $e) {
            throw new MendrixApiException('Error in getOrderbyids: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    public function getTracesGoods ()
    {
        try {
            $response = $this->getClient()->get('mendrix/tracesgoods', [
                'cookies' => $this->getCookies(),
            ]);
            $body = (string)$response->getBody();
            return json_decode($body, true);
        } catch (Exception $e) {
            throw new MendrixApiException('Error in getTracesGoods: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * https://packagist.org/packages/kamermans/guzzle-oauth2-subscriber
     * @return Client
     * @throws MendrixApiException
     */
    protected function getClient ()
    {
        if (!isset($this->client)) {

            try {
                // Authorization client - this is used to request OAuth access tokens
                $reauth_client = new Client([
                    // URL for access_token request
                    'base_uri' => $this->api_host . '/oauth/token',
                ]);
                $reauth_config = [
                    'client_id' => $this->client_id,
                    'client_secret' => $this->client_secret,
                    'scope' => $this->scope,
                ];
                $grant_type = new ClientCredentials($reauth_client, $reauth_config);
                $oauth = new OAuth2Middleware($grant_type);
                if ($this->token_path) {
                    $token_persistence = new FileTokenPersistence($this->token_path);
                    $oauth->setTokenPersistence($token_persistence);
                }
                $stack = HandlerStack::create();
                $stack->push($oauth);
                // This is the normal Guzzle client that you use in your application
                $this->client = new Client([
                    'base_uri' => $this->api_host . '/api/',
                    'handler' => $stack,
                    'auth' => 'oauth',
                ]);
            } catch (Exception $e) {
                throw new MendrixApiException('Error setting up client: ' . $e->getMessage(), $e->getCode(), $e);
            }
        }
        return $this->client;
    }

    protected function getCookies (): CookieJar
    {
        return CookieJar::fromArray($this->cookies, (new Uri($this->api_host))->getHost());
    }

}
