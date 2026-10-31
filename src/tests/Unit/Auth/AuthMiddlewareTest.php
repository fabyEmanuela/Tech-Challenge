<?php

namespace Tests\Unit\Auth;

use Http\Middleware\AuthMiddleware;
use Infrastructure\Auth\JwtService;
use PHPUnit\Framework\TestCase;

class AuthMiddlewareTest extends TestCase
{
    private JwtService $jwtService;
    private AuthMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->jwtService = new JwtService('chave-secreta-de-teste-unitario');
        $this->middleware = new AuthMiddleware($this->jwtService);

        // Limpa variáveis globais que possam influenciar
        unset($_SERVER['HTTP_AUTHORIZATION'], $_SERVER['REDIRECT_HTTP_AUTHORIZATION'], $_REQUEST['auth_user']);
    }

    protected function tearDown(): void
    {
        unset($_SERVER['HTTP_AUTHORIZATION'], $_SERVER['REDIRECT_HTTP_AUTHORIZATION'], $_REQUEST['auth_user']);
        parent::tearDown();
    }

    public function testDevePermitirAcessoComTokenValido(): void
    {
        $token = $this->jwtService->generateToken([
            'sub'  => 42,
            'role' => 'atendente',
            'exp'  => time() + 3600,
        ]);

        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;

        $resultado = ($this->middleware)();

        $this->assertTrue($resultado);
        $this->assertArrayHasKey('auth_user', $_REQUEST);
        $this->assertSame(42, $_REQUEST['auth_user']['sub']);
        $this->assertSame('atendente', $_REQUEST['auth_user']['role']);
    }

    public function testDeveNegarAcessoQuandoTokenNaoForFornecido(): void
    {
        ob_start();
        $resultado = $this->middleware->handle();
        $saida = ob_get_clean();

        $this->assertFalse($resultado);
        $this->assertStringContainsString('Token de autorização não fornecido.', $saida);
    }

    public function testDeveNegarAcessoQuandoTokenForInvalido(): void
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer token.completamente.falso';

        ob_start();
        $resultado = $this->middleware->handle();
        $saida = ob_get_clean();

        $this->assertFalse($resultado);
        $this->assertStringContainsString('Token inválido ou expirado.', $saida);
    }

    public function testDeveNegarAcessoQuandoPerfilNaoForPermitido(): void
    {
        $token = $this->jwtService->generateToken([
            'sub'  => 1,
            'role' => 'cliente',
            'exp'  => time() + 3600,
        ]);

        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;

        ob_start();
        // Apenas mecanico ou atendente podem acessar
        $resultado = $this->middleware->handle(['mecanico', 'atendente']);
        $saida = ob_get_clean();

        $this->assertFalse($resultado);
        $this->assertStringContainsString('Acesso negado para este perfil de usuário.', $saida);
    }

    public function testDevePermitirAcessoQuandoPerfilEstiverNaLista(): void
    {
        $token = $this->jwtService->generateToken([
            'sub'  => 2,
            'role' => 'mecanico',
            'exp'  => time() + 3600,
        ]);

        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;

        $resultado = $this->middleware->handle(['mecanico', 'atendente']);

        $this->assertTrue($resultado);
        $this->assertSame(2, $_REQUEST['auth_user']['sub']);
    }
}

