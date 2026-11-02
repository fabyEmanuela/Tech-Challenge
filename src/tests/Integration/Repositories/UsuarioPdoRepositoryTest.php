<?php

namespace Tests\Integration\Repositories;

use Domain\Usuario\TipoUsuario;
use Domain\Usuario\Usuario;
use Repositories\Usuario\UsuarioPdoRepository;
use Tests\Integration\IntegrationTestCase; 

class UsuarioPdoRepositoryTest extends IntegrationTestCase
{
    private UsuarioPdoRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new UsuarioPdoRepository($this->pdo);
    }

    public function testDeveInserirEBuscarUsuarioNoBancoReal(): void
    {
        $usuario = new Usuario(
            id:           null,
            nome:         'Sheila maria',
            email:        'sheilaamaria@gmail.com',
            telefone:     '(83) 99999-9999',
            cpf:          '529.982.247-25',
            tipoUsuario:  TipoUsuario::CLIENTE,
            senhaHash:    null,
            ativo:        true,
            criadoEm:     null,
            atualizadoEm: null,
        );

        $salvo = $this->repository->salvar($usuario);

        $this->assertNotNull($salvo->getId());
        $this->assertSame('Sheila maria', $salvo->getNome());
        $this->assertSame('sheilaamaria@gmail.com', $salvo->getEmail());
        $this->assertSame(TipoUsuario::CLIENTE, $salvo->getTipoUsuario());

        // Busca direto no MySQL real
        $buscado = $this->repository->buscarPorId($salvo->getId());

        $this->assertNotNull($buscado);
        $this->assertSame($salvo->getId(), $buscado->getId());
        $this->assertSame('Sheila maria', $buscado->getNome());
    }

    public function testDeveAtualizarUsuarioNoBancoReal(): void
    {
        $usuario = new Usuario(
            id:           null,
            nome:         'Sheila maria',
            email:        'sheilaamaria@gmail.com',
            telefone:     '(83) 99999-9999',
            cpf:          '529.982.247-25',
            tipoUsuario:  TipoUsuario::CLIENTE,
            senhaHash:    null,
            ativo:        true,
            criadoEm:     null,
            atualizadoEm: null,
        );

        $salvo = $this->repository->salvar($usuario);

        // Modifica os dados
        $salvo->setNome('Sheila maria andre');

        $atualizado = $this->repository->atualizar($salvo);

        $this->assertSame('Sheila maria andre', $atualizado->getNome());

        $buscado = $this->repository->buscarPorId($salvo->getId());
        $this->assertSame('Sheila maria andre', $buscado->getNome());
    }

    public function testDeveDeletarUsuarioDoBancoReal(): void
    {
        $usuario = new Usuario(
            id:           null,
            nome:         'Sheila maria andre',
            email:        'sheilaamaria@gmail.com',
            telefone:     '(83) 99999-9999',
            cpf:          '529.982.247-25',
            tipoUsuario:  TipoUsuario::CLIENTE,
            senhaHash:    null,
            ativo:        true,
            criadoEm:     null,
            atualizadoEm: null,
        );

        $salvo = $this->repository->salvar($usuario);
        $id = $salvo->getId();

        $deletado = $this->repository->deletar($id);

        $this->assertTrue($deletado);
        $this->assertNull($this->repository->buscarPorId($id));
    }
}
