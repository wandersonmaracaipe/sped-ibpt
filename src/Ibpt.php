<?php

declare(strict_types=1);

namespace NFePHP\Ibpt;

use InvalidArgumentException;
use NFePHP\Ibpt\DTO\TributosResponse;
use NFePHP\Ibpt\Exceptions\IbptException;
use stdClass;

/**
 * Class to get taxes informations for consumers from IBPT (De Olho no Imposto).
 */
class Ibpt
{
    /**
     * URL base da API do IBPT
     */
    protected string $url = 'https://apidoni.ibpt.org.br/api/v1/';

    /**
     * CNPJ do autorizado a fazer a consulta no IBPT
     */
    private string $cnpj;

    /**
     * Token do IBPT
     */
    private string $token;

    /**
     * Classe Rest Client
     */
    protected RestInterface $rest;

    /**
     * Constructor
     *
     * @param string $cnpj CNPJ do autorizado (somente dígitos ou formatado)
     * @param string $token Token de acesso gerado no IBPT
     * @param array $proxy Dados para proxy ['IP','PORT','USER','PASS']
     * @param RestInterface|null $rest Cliente HTTP personalizado
     * @param string|null $url URL base da API (opcional)
     */
    public function __construct(
        string $cnpj,
        string $token,
        array $proxy = [],
        ?RestInterface $rest = null,
        ?string $url = null
    ) {
        $cleanCnpj = (string) preg_replace('/\D/', '', $cnpj);
        $cleanToken = trim($token);

        if (empty($cleanCnpj)) {
            throw new InvalidArgumentException('O CNPJ do autorizado no IBPT deve ser informado.');
        }
        if (empty($cleanToken)) {
            throw new InvalidArgumentException('O Token do IBPT deve ser informado.');
        }

        $this->cnpj = $cleanCnpj;
        $this->token = $cleanToken;

        if ($url !== null && trim($url) !== '') {
            $this->url = rtrim(trim($url), '/') . '/';
        }

        $this->rest = $rest ?? new Rest($proxy);
    }

    /**
     * Retorna a URL base configurada
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Retorna o CNPJ configurado
     */
    public function getCnpj(): string
    {
        return $this->cnpj;
    }

    /**
     * Retorna o Token configurado
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * Consulta impostos aproximados para produtos (NCM) no IBPT.
     * Retorna stdClass para compatibilidade com versões anteriores.
     *
     * @param string $uf Sigla da UF (2 letras)
     * @param string $ncm Código NCM (8 dígitos)
     * @param int $extarif Código EX da TIPI (padrão 0)
     * @param string $descricao Descrição do produto
     * @param string $unidadeMedida Unidade de medida (ex: UN, KG)
     * @param float|int $valor Valor do produto
     * @param string $gtin Código GTIN/EAN do produto
     * @param string $codigoInterno Código interno do produto (opcional)
     * @return stdClass
     * @throws IbptException
     */
    public function productTaxes(
        $uf,
        $ncm,
        $extarif = 0,
        $descricao = '',
        $unidadeMedida = 'UN',
        $valor = 0.0,
        $gtin = 'SEM GTIN',
        $codigoInterno = ''
    ) {
        $cleanNcm = (string) preg_replace('/\D/', '', (string) $ncm);
        $cleanUf = strtoupper(trim((string) $uf));

        $params = [
            'token'         => $this->token,
            'cnpj'          => $this->cnpj,
            'codigo'        => $cleanNcm,
            'uf'            => $cleanUf,
            'ex'            => (int) $extarif,
            'descricao'     => (string) $descricao,
            'unidadeMedida' => (string) ($unidadeMedida ?: 'UN'),
            'valor'         => (float) $valor,
            'gtin'          => (string) ($gtin ?: 'SEM GTIN'),
        ];

        if (!empty($codigoInterno)) {
            $params['codigoInterno'] = (string) $codigoInterno;
        }

        $uri = $this->url . 'produtos?' . http_build_query($params);
        $raw = $this->rest->pull($uri);

        $decoded = json_decode($raw);
        if (!($decoded instanceof stdClass)) {
            throw new IbptException('Resposta inválida do serviço IBPT para produtos: ' . substr($raw, 0, 200));
        }

        // Verifica se a API retornou mensagem de erro no corpo
        if (isset($decoded->error) && !empty($decoded->error)) {
            throw new IbptException("Erro retornado pelo IBPT: {$decoded->error}", 400, 400, $decoded);
        }

        return $decoded;
    }

    /**
     * Consulta impostos aproximados para serviços (LC 116) no IBPT.
     * Retorna stdClass para compatibilidade com versões anteriores.
     *
     * @param string $uf Sigla da UF
     * @param string $codigo Código do serviço (LC 116/2003)
     * @param string $descricao Descrição do serviço
     * @param string $unidadeMedida Unidade de medida
     * @param float|int $valor Valor do serviço
     * @return stdClass
     * @throws IbptException
     */
    public function serviceTaxes(
        $uf,
        $codigo,
        $descricao = '',
        $unidadeMedida = 'UN',
        $valor = 0.0
    ) {
        $cleanCodigo = trim((string) $codigo);
        $cleanUf = strtoupper(trim((string) $uf));

        $params = [
            'token'         => $this->token,
            'cnpj'          => $this->cnpj,
            'codigo'        => $cleanCodigo,
            'uf'            => $cleanUf,
            'descricao'     => (string) $descricao,
            'unidadeMedida' => (string) ($unidadeMedida ?: 'UN'),
            'valor'         => (float) $valor,
        ];

        $uri = $this->url . 'servicos?' . http_build_query($params);
        $raw = $this->rest->pull($uri);

        $decoded = json_decode($raw);
        if (!($decoded instanceof stdClass)) {
            throw new IbptException('Resposta inválida do serviço IBPT para serviços: ' . substr($raw, 0, 200));
        }

        if (isset($decoded->error) && !empty($decoded->error)) {
            throw new IbptException("Erro retornado pelo IBPT: {$decoded->error}", 400, 400, $decoded);
        }

        return $decoded;
    }

    /**
     * Consulta tipada para produtos, retornando DTO TributosResponse.
     */
    public function consultarProduto(
        string $uf,
        string $ncm,
        int $ex = 0,
        string $descricao = '',
        string $unidadeMedida = 'UN',
        float $valor = 0.0,
        string $gtin = 'SEM GTIN',
        string $codigoInterno = ''
    ): TributosResponse {
        $resp = $this->productTaxes($uf, $ncm, $ex, $descricao, $unidadeMedida, $valor, $gtin, $codigoInterno);
        return TributosResponse::fromStdClass($resp);
    }

    /**
     * Consulta tipada para serviços, retornando DTO TributosResponse.
     */
    public function consultarServico(
        string $uf,
        string $codigo,
        string $descricao = '',
        string $unidadeMedida = 'UN',
        float $valor = 0.0
    ): TributosResponse {
        $resp = $this->serviceTaxes($uf, $codigo, $descricao, $unidadeMedida, $valor);
        return TributosResponse::fromStdClass($resp);
    }
}
