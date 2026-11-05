<?php

declare(strict_types=1);

namespace NFePHP\Ibpt\DTO;

use stdClass;

class TributosResponse
{
    public string $codigo;
    public string $uf;
    public int $ex;
    public string $descricao;
    public float $nacional;
    public float $estadual;
    public float $importado;
    public float $municipal;
    public int $tipo;
    public ?string $vigenciaInicio;
    public ?string $vigenciaFim;
    public string $chave;
    public string $versao;
    public string $fonte;
    public float $valor;
    public float $valorTributoNacional;
    public float $valorTributoEstadual;
    public float $valorTributoImportado;
    public float $valorTributoMunicipal;

    public function __construct(stdClass $data)
    {
        $this->codigo = (string) ($data->Codigo ?? '');
        $this->uf = (string) ($data->UF ?? '');
        $this->ex = (int) ($data->EX ?? 0);
        $this->descricao = (string) ($data->Descricao ?? '');
        $this->nacional = (float) ($data->Nacional ?? 0.0);
        $this->estadual = (float) ($data->Estadual ?? 0.0);
        $this->importado = (float) ($data->Importado ?? 0.0);
        $this->municipal = (float) ($data->Municipal ?? 0.0);
        $this->tipo = (int) ($data->Tipo ?? 0);
        $this->vigenciaInicio = isset($data->VigenciaInicio) ? (string) $data->VigenciaInicio : null;
        $this->vigenciaFim = isset($data->VigenciaFim) ? (string) $data->VigenciaFim : null;
        $this->chave = (string) ($data->Chave ?? '');
        $this->versao = (string) ($data->Versao ?? '');
        $this->fonte = (string) ($data->Fonte ?? '');
        $this->valor = (float) ($data->Valor ?? 0.0);
        $this->valorTributoNacional = (float) ($data->ValorTributoNacional ?? 0.0);
        $this->valorTributoEstadual = (float) ($data->ValorTributoEstadual ?? 0.0);
        $this->valorTributoImportado = (float) ($data->ValorTributoImportado ?? 0.0);
        $this->valorTributoMunicipal = (float) ($data->ValorTributoMunicipal ?? 0.0);
    }

    public static function fromStdClass(stdClass $data): self
    {
        return new self($data);
    }

    /**
     * Soma das alíquotas Nacional + Estadual + Municipal (para produtos nacionais).
     */
    public function getAliquotaTotalNacional(): float
    {
        return round($this->nacional + $this->estadual + $this->municipal, 2);
    }

    /**
     * Soma das alíquotas Importado + Estadual + Municipal (para produtos importados).
     */
    public function getAliquotaTotalImportado(): float
    {
        return round($this->importado + $this->estadual + $this->municipal, 2);
    }

    /**
     * Valor total aproximado de tributos (Nacional + Estadual + Municipal).
     */
    public function getValorTributoTotalNacional(): float
    {
        return round($this->valorTributoNacional + $this->valorTributoEstadual + $this->valorTributoMunicipal, 2);
    }

    /**
     * Valor total aproximado de tributos para importado (Importado + Estadual + Municipal).
     */
    public function getValorTributoTotalImportado(): float
    {
        return round($this->valorTributoImportado + $this->valorTributoEstadual + $this->valorTributoMunicipal, 2);
    }

    public function toArray(): array
    {
        return [
            'codigo'                  => $this->codigo,
            'uf'                      => $this->uf,
            'ex'                      => $this->ex,
            'descricao'               => $this->descricao,
            'nacional'                => $this->nacional,
            'estadual'                => $this->estadual,
            'importado'               => $this->importado,
            'municipal'               => $this->municipal,
            'tipo'                    => $this->tipo,
            'vigencia_inicio'         => $this->vigenciaInicio,
            'vigencia_fim'            => $this->vigenciaFim,
            'chave'                   => $this->chave,
            'versao'                  => $this->versao,
            'fonte'                   => $this->fonte,
            'valor'                   => $this->valor,
            'valor_tributo_nacional'  => $this->valorTributoNacional,
            'valor_tributo_estadual'  => $this->valorTributoEstadual,
            'valor_tributo_importado' => $this->valorTributoImportado,
            'valor_tributo_municipal' => $this->valorTributoMunicipal,
            'aliquota_total_nacional' => $this->getAliquotaTotalNacional(),
            'valor_total_nacional'    => $this->getValorTributoTotalNacional(),
        ];
    }
}

