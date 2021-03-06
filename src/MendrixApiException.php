<?php


namespace Egcs;


use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Response;

class MendrixApiException extends \Exception
{

    public function hasResponse (): bool
    {
        return $prev = $this->getPrevious() and
            ($prev instanceof ServerException || $prev instanceof ClientException) and
            $prev->hasResponse() and method_exists($prev, 'getResponseBody');
    }

    public function getResponse (): ?Response
    {
        return $this->hasResponse() ? $this->getPrevious()->getResponseBody() : null;
    }

    public function getResponseData (): ?array
    {
        if (!$this->hasResponse()) {
            return null;
        }
        $body = (string)$this->getResponse()->getBody();
        return json_decode($body, true);
    }

}
