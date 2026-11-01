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
            id:           1,
            nome:         'Administrador Oficina',
            email:        'admin@oficina.com',
            telefone:     '(11) 98765-4321',
            cpf:          '111.444.777-35',
            tipoUsuario:  TipoUsuario::ATENDENTE,
            senhaHash:    '$2y$10$abcdef...',
            ativo:        true,
            criadoEm:     '2026-11-01 10:00:00',
            atualizadoEm: '2026-11-01 10:00:00',
        );

        $this->assertSame(1, $usuario->getId());
        $this->assertSame('Administrador Oficina', $usuario->getNome());
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
            cpf:         null,
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

    public function testDeveConverterParaArraySemExporSenhaHash(): void
    {
        $usuario = new Usuario(
            id:          5,
            nome:        'Carlos Mecânico',
            email:       'carlos@oficina.com',
            telefone:    '(11) 98765-1111',
            cpf:         '333.666.999-57',
            tipoUsuario: TipoUsuario::MECANICO,
            senhaHash:   'hash_super_secreto',
            ativo:       true,
            criadoEm:    '2026-11-01 12:00:00',
            atualizadoEm:null,
        );

        $array = $usuario->paraArray();

        $this->assertSame(5, $array['id']);
        $this->assertSame('Carlos Mecânico', $array['nome']);
        $this->assertSame('carlos@oficina.com', $array['email']);
        $this->assertSame('mecanico', $array['tipo_usuario']);
        $this->assertArrayNotHasKey('senha_hash', $array);
        $this->assertArrayNotHasKey('senha', $array);
    }
}
