<?php

declare(strict_types=1);

namespace NFePHP\Ibpt\Tests;

use InvalidArgumentException;
use NFePHP\Ibpt\DTO\TributosResponse;
use NFePHP\Ibpt\Exceptions\IbptException;
use NFePHP\Ibpt\Ibpt;
use NFePHP\Ibpt\RestInterface;
use PHPUnit\Framework\TestCase;

class IbptTest extends TestCase
{
    public Ibpt $ibpt;
    public $dummyRest;

    public array $expectedProd = [
        'Codigo' => '60063210',
        'UF' => 'SP',
        'EX' => 0,
        'Descricao' => 'Tecidos de malha de fibras sinteticas, tintos, de náilon ou de outras poliamidas',
        'Nacional' => 13.45,
        'Estadual' => 18.0,
        'Importado' => 36.08,
        'Municipal' => 0.0,
        'Tipo' => 0,
        'VigenciaInicio' => '01/04/2018',
        'VigenciaFim' => '30/06/2018',
        'Chave' => 'F3W1D7',
        'Versao' => '18.1.B',
        'Fonte' => 'IBPT/empresometro.com.br',
        'Valor' => 60.0,
        'ValorTributoNacional' => 8.07,
        'ValorTributoEstadual' => 10.8,
        'ValorTributoImportado' => 21.65,
        'ValorTributoMunicipal' => 0.0,
    ];

    public array $expectedServ = [
        'Codigo' => '0107',
        'UF' => 'SP',
        'Descricao' => 'Suporte técnico em informática, inclusive instalação, configuração e manutenção de programas de computação e bancos de dados.',
        'Tipo' => 2,
        'Nacional' => 13.45,
        'Estadual' => 0.0,
        'Municipal' => 2.7,
        'Importado' => 15.45,
        'VigenciaInicio' => '01/04/2018',
        'VigenciaFim' => '30/06/2018',
        'Chave' => 'F3W1D7',
        'Versao' => '18.1.B',
        'Fonte' => 'IBPT/empresometro.com.br',
        'Valor' => 500.0,
        'ValorTributoNacional' => 67.25,
        'ValorTributoEstadual' => 0.0,
        'ValorTributoImportado' => 77.25,
        'ValorTributoMunicipal' => 13.5,
    ];

    protected function setUp(): void
    {
        $this->dummyRest = $this->createMock(RestInterface::class);
        $this->dummyRest->method('pull')->willReturn(json_encode($this->expectedProd));
        $this->ibpt = new Ibpt('12.345.678/0001-95', 'a5354bf3890s0849', [], $this->dummyRest);
    }

    public function testShouldInstantiate(): void
    {
        $this->assertInstanceOf(Ibpt::class, $this->ibpt);
        $this->assertSame('12345678000195', $this->ibpt->getCnpj());
        $this->assertSame('a5354bf3890s0849', $this->ibpt->getToken());
    }

    public function testValidationThrowsOnEmptyCnpj(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Ibpt('', 'token123');
    }

    public function testValidationThrowsOnEmptyToken(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Ibpt('12345678000195', '');
    }

    public function testProductTaxesCompatibility(): void
    {
        $expected = json_decode(json_encode($this->expectedProd));
        $resp = $this->ibpt->productTaxes(
            'SP',
            '60063210',
            0,
            'Tecidos de malha',
            'kg',
            60.00,
            'SEM GTIN'
        );
        $this->assertEquals($expected, $resp);
    }

    public function testServiceTaxesCompatibility(): void
    {
        $mockRest = $this->createMock(RestInterface::class);
        $mockRest->method('pull')->willReturn(json_encode($this->expectedServ));

        $ibpt = new Ibpt('12345678000195', 'token', [], $mockRest);

        $expected = json_decode(json_encode($this->expectedServ));
        $resp = $ibpt->serviceTaxes(
            'SP',
            '0107',
            'Suporte técnico em informática',
            'un',
            500.00
        );
        $this->assertEquals($expected, $resp);
    }

    public function testConsultarProdutoRetornaDTO(): void
    {
        $dto = $this->ibpt->consultarProduto('SP', '60063210', 0, 'Tecidos', 'kg', 60.0);
        $this->assertInstanceOf(TributosResponse::class, $dto);
        $this->assertSame('60063210', $dto->codigo);
        $this->assertSame('SP', $dto->uf);
        $this->assertSame(13.45, $dto->nacional);
        $this->assertSame(18.0, $dto->estadual);
        $this->assertSame(0.0, $dto->municipal);
        $this->assertSame(31.45, $dto->getAliquotaTotalNacional());
        $this->assertSame(18.87, $dto->getValorTributoTotalNacional());
        $this->assertIsArray($dto->toArray());
    }

    public function testConsultarServicoRetornaDTO(): void
    {
        $mockRest = $this->createMock(RestInterface::class);
        $mockRest->method('pull')->willReturn(json_encode($this->expectedServ));

        $ibpt = new Ibpt('12345678000195', 'token', [], $mockRest);
        $dto = $ibpt->consultarServico('SP', '0107', 'Suporte', 'UN', 500.0);

        $this->assertInstanceOf(TributosResponse::class, $dto);
        $this->assertSame('0107', $dto->codigo);
        $this->assertSame(13.45, $dto->nacional);
        $this->assertSame(2.7, $dto->municipal);
        $this->assertSame(16.15, $dto->getAliquotaTotalNacional());
        $this->assertSame(80.75, $dto->getValorTributoTotalNacional());
    }

    public function testThrowsExceptionOnApiError(): void
    {
        $mockRest = $this->createMock(RestInterface::class);
        $mockRest->method('pull')->willReturn(json_encode(['error' => 'Token invalido ou expirado']));

        $ibpt = new Ibpt('12345678000195', 'token', [], $mockRest);

        $this->expectException(IbptException::class);
        $this->expectExceptionMessage('Erro retornado pelo IBPT: Token invalido ou expirado');
        $ibpt->productTaxes('SP', '60063210');
    }
}
