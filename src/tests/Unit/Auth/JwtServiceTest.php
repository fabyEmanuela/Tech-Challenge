<?php

namespace Tests\Unit\Auth;

use Infrastructure\Auth\JwtService;
use PHPUnit\Framework\TestCase;

class JwtServiceTest extends TestCase
{
    private JwtService $jwtService;
    private string $secret = 'minha-chave-secreta-de-teste-12345';

    protected function setUp(): void
    {
        parent::setUp();
        $this->jwtService = new JwtService($this->secret);
    }

    public function testDeveGerarTokenJwtComTresPartesValidas(): void
    {
        $payload = [
            'sub'  => 1,
            'role' => 'atendente',
            'exp'  => time() + 3600,
        ];

        $token = $this->jwtService->generateToken($payload);

        $this->assertIsString($token);
        $partes = explode('.', $token);
        $this->assertCount(3, $partes);

        // Header decodificado
        $headerJson = base64_decode(strtr($partes[0], '-_', '+/'));
        $header = json_decode($headerJson, true);
        $this->assertSame('JWT', $header['typ']);
        $this->assertSame('HS256', $header['alg']);
    }

    public function testDeveValidarTokenCorretamenteERetornarPayload(): void
    {
        $payloadOriginal = [
            'sub'  => 10,
            'role' => 'mecanico',
            'exp'  => time() + 3600,
        ];

        $token = $this->jwtService->generateToken($payloadOriginal);
        $resultado = $this->jwtService->validateToken($token);

        $this->assertIsArray($resultado);
        $this->assertSame(10, $resultado['sub']);
        $this->assertSame('mecanico', $resultado['role']);
    }

    public function testDeveRejeitarTokenQuandoAssinaturaForAdulterada(): void
    {
        $payload = [
            'sub'  => 5,
            'role' => 'cliente',
            'exp'  => time() + 3600,
        ];

        $token = $this->jwtService->generateToken($payload);
        $partes = explode('.', $token);

        // Altera a assinatura
        $tokenAdulterado = $partes[0] . '.' . $partes[1] . '.assinaturaFalsa123';

        $resultado = $this->jwtService->validateToken($tokenAdulterado);
        $this->assertFalse($resultado);
    }

    public function testDeveRejeitarTokenQuandoExpirado(): void
    {
        $payload = [
            'sub'  => 1,
            'role' => 'atendente',
            'exp'  => time() - 60, // Expirado há 1 minuto
        ];

        $token = $this->jwtService->generateToken($payload);
        $resultado = $this->jwtService->validateToken($token);

        $this->assertFalse($resultado);
    }

    public function testDeveRejeitarTokenMalformado(): void
    {
        $resultado1 = $this->jwtService->validateToken('token_sem_pontos');
        $resultado2 = $this->jwtService->validateToken('apenas.duaspartes');

        $this->assertFalse($resultado1);
        $this->assertFalse($resultado2);
    }
}

