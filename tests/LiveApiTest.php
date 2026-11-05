<?php

declare(strict_types=1);

namespace NFePHP\Ibpt\Tests;

use NFePHP\Ibpt\Ibpt;
use PHPUnit\Framework\TestCase;

class LiveApiTest extends TestCase
{
    private const TEST_TOKEN = '43UdNt0TIvzrIo2JXbeX4g9FabJQP5qysuscDizErxmletgA5UnVwM5DvniCuLYb';
    private const TEST_CNPJ = '67863933000183';

    public function testConsultaAoVivoProduto(): void
    {
        $ibpt = new Ibpt(self::TEST_CNPJ, self::TEST_TOKEN);
        $res = $ibpt->consultarProduto('SP', '33049990', 0, 'PRODUTO TESTE', 'UN', 50.0);

        $this->assertSame('33049990', $res->codigo);
        $this->assertSame('SP', $res->uf);
        $this->assertGreaterThan(0, $res->nacional);
        $this->assertGreaterThan(0, $res->estadual);
        $this->assertNotEmpty($res->chave);
        $this->assertNotEmpty($res->versao);
        $this->assertSame(50.0, $res->valor);
    }

    public function testConsultaAoVivoServico(): void
    {
        $ibpt = new Ibpt(self::TEST_CNPJ, self::TEST_TOKEN);
        $res = $ibpt->consultarServico('SP', '0107', 'SERVICO TESTE', 'UN', 100.0);

        $this->assertSame('0107', $res->codigo);
        $this->assertSame('SP', $res->uf);
        $this->assertGreaterThan(0, $res->nacional);
        $this->assertGreaterThan(0, $res->municipal);
        $this->assertNotEmpty($res->chave);
        $this->assertSame(100.0, $res->valor);
    }
}

