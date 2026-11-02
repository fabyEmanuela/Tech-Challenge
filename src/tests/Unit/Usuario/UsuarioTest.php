<?php

declare(strict_types=1);

namespace Tests\Unit\Usuario;

use Domain\Usuario\TipoUsuario;
use Domain\Usuario\Usuario;
use PHPUnit\Framework\TestCase;

class UsuarioTest extends TestCase
{
    public function testDeveInstanciarUsuarioCorretamente(): void
    {
        $usuario = new Usuario(
            id:          1,
            nome:        'Carlos Atendente',
            email:       'admin@oficina.com',
            telefone:    '(11) 98765-4321',
            cpf:         '111.444.777-35',
            tipoUsuario: TipoUsuario::ATENDENTE,
            senhaHash:   '$2y$10$abcdef...',
            ativo:       true,
            criadoEm:    '2026-11-01 10:00:00',
            atualizadoEm:'2026-11-01 10:00:00'
        );

        $this->assertSame(1, $usuario->getId());
        $this->assertSame('Carlos Atendente', $usuario->getNome());
        $this->assertSame('admin@oficina.com', $usuario->getEmail());
        $this->assertSame('(11) 98765-4321', $usuario->getTelefone());
        $this->assertSame('111.444.777-35', $usuario->getCpf());
        $this->assertSame(TipoUsuario::ATENDENTE, $usuario->getTipoUsuario());
        $this->assertSame('$2y$10$abcdef...', $usuario->getSenhaHash());
        $this->assertTrue($usuario->isAtivo());
        $this->assertSame('2026-11-01 10:00:00', $usuario->getCriadoEm());
        $this->assertSame('2026-11-01 10:00:00', $usuario->getAtualizadoEm());
    }

    public function testDeveAlterarDadosViaSetters(): void
    {
        $usuario = new Usuario(
            id:          null,
            nome:        'Nome Inicial',
            email:       'inicial@oficina.com',
            telefone:    null,
            cpf:         '111.444.777-35',
            tipoUsuario: TipoUsuario::CLIENTE,
            senhaHash:   null,
            ativo:       true,
            criadoEm:    null,
            atualizadoEm:null,
        );

        $usuario->setNome('Novo Nome');
        $usuario->setEmail('novo@oficina.com');
        $usuario->setTelefone('(11) 99999-8888');
        $usuario->setCpf('222.555.888-46');
        $usuario->setTipoUsuario(TipoUsuario::MECANICO);
        $usuario->setSenhaHash('novo_hash');
        $usuario->setAtivo(false);

        $this->assertSame('Novo Nome', $usuario->getNome());
        $this->assertSame('novo@oficina.com', $usuario->getEmail());
        $this->assertSame('(11) 99999-8888', $usuario->getTelefone());
        $this->assertSame('222.555.888-46', $usuario->getCpf());
        $this->assertSame(TipoUsuario::MECANICO, $usuario->getTipoUsuario());
        $this->assertSame('novo_hash', $usuario->getSenhaHash());
        $this->assertFalse($usuario->isAtivo());
    }

    public function testDeveExportarParaArraySemExporSenha(): void
    {
        $usuario = new Usuario(
            id:          10,
            nome:        'Roberto Mecânico',
            email:       'mecanico@oficina.com',
            telefone:    '(11) 91234-5678',
            cpf:         '111.444.777-35',
            tipoUsuario: TipoUsuario::MECANICO,
            senhaHash:   '$2y$10$senhaUltraSecretaHashBcrypt',
            ativo:       true,
            criadoEm:    '2026-11-01 12:00:00',
            atualizadoEm:'2026-11-01 12:00:00'
        );

        $array = $usuario->paraArray();

        $this->assertSame(10, $array['id']);
        $this->assertSame('Roberto Mecânico', $array['nome']);
        $this->assertSame('mecanico@oficina.com', $array['email']);
        $this->assertSame('mecanico', $array['tipo_usuario']);
        $this->assertTrue($array['ativo']);
        $this->assertArrayNotHasKey('senha_hash', $array);
        $this->assertArrayNotHasKey('senha', $array);
    }
}
