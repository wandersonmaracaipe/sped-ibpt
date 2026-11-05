<?php

declare(strict_types=1);

namespace NFePHP\Ibpt;

use NFePHP\Ibpt\Exceptions\IbptException;

/**
 * Class to connect to restful services
 */
class Rest implements RestInterface
{
    /**
     * Parametros do proxy
     */
    protected array $proxy = [];

    /**
     * Verifica certificados SSL
     */
    protected bool $verifySsl;

    /**
     * Constructor
     *
     * @param array $proxy Parameter for proxy ['IP','PORT','USER','PASS']
     * @param int $timeout Timeout for request
     * @param bool $verifySsl Se deve verificar o certificado SSL (false por padrão para compatibilidade em ambientes Windows/dev)
     */
    public function __construct(array $proxy = [], int $timeout = 30, bool $verifySsl = false)
    {
        $this->proxy = $proxy;
        $this->timeout = $timeout;
        $this->verifySsl = $verifySsl;
    }

    /**
     * Pull data from IBPT Restful service to obtain taxes values
     *
     * @param string $uri
     * @return string
     * @throws IbptException
     */
    public function pull($uri)
    {
        $oCurl = curl_init($uri);
        if ($oCurl === false) {
            throw new IbptException("Não foi possível inicializar cURL para a URL: {$uri}");
        }

        if (!empty($this->proxy)) {
            $this->setProxy($oCurl, $this->proxy);
        }

        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($oCurl, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($oCurl, CURLOPT_CONNECTTIMEOUT, min(10, $this->timeout));
        curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, $this->verifySsl ? 2 : 0);
        curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, $this->verifySsl);
        curl_setopt($oCurl, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'User-Agent: ValuorERP/sped-ibpt (PHP ' . PHP_VERSION . ')',
        ]);

        $response = curl_exec($oCurl);
        $curlError = curl_error($oCurl);
        $curlErrorCode = curl_errno($oCurl);
        $httpCode = (int) curl_getinfo($oCurl, CURLINFO_HTTP_CODE);
        curl_close($oCurl);

        if ($curlErrorCode !== 0) {
            throw new IbptException("Erro de conexão cURL [{$curlErrorCode}]: {$curlError}", $curlErrorCode, $httpCode);
        }

        if ($httpCode !== 200) {
            $rawMsg = is_string($response) ? trim($response) : '';
            $decoded = json_decode($rawMsg, true);
            $msg = $decoded['error'] ?? $decoded['message'] ?? $rawMsg ?: "Código HTTP {$httpCode}";

            throw new IbptException(
                "Falha na comunicação com o IBPT (HTTP {$httpCode}): {$msg}",
                $httpCode,
                $httpCode,
                $decoded ?? $rawMsg
            );
        }

        return (string) $response;
    }

    /**
     * Set proxy parameters
     */
    protected function setProxy($curl, array $proxy): void
    {
        if (!empty($proxy['IP'])) {
            curl_setopt($curl, CURLOPT_PROXY, $proxy['IP']);
        }
        if (!empty($proxy['PORT'])) {
            curl_setopt($curl, CURLOPT_PROXYPORT, (int) $proxy['PORT']);
        }
        if (!empty($proxy['USER']) && !empty($proxy['PASS'])) {
            curl_setopt($curl, CURLOPT_PROXYUSERPWD, "{$proxy['USER']}:{$proxy['PASS']}");
        }
    }
}
