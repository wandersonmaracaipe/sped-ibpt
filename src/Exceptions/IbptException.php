<?php

declare(strict_types=1);

namespace NFePHP\Ibpt\Exceptions;

use RuntimeException;

class IbptException extends RuntimeException
{
    private ?int $httpCode;
    private $response;

    public function __construct(string $message, int $code = 0, ?int $httpCode = null, $response = null, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->httpCode = $httpCode;
        $this->response = $response;
    }

    public function getHttpCode(): ?int
    {
        return $this->httpCode;
    }

    public function getResponse()
    {
        return $this->response;
    }
}
