<?php

namespace Tests\Integration\Repositories;

use Domain\Usuario\Usuario;
use Repositories\Usuario\UsuarioPdoRepository;
use Tests\Integration\IntegrationTestCase;
use Domain\Usuario\TipoUsuario;


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
        $Usuario = new Usuario(
            id:            null,
            nome:         'Sheila maria',
			email:        'sheilaamaria@gmail.com',	
			telefone:     '(83) 99999-9999' ,     
			cpf:          '529.982.247-25',
            tipoUsuario:  TipoUsuario::CLIENTE,
			senhaHash:    null,
            ativo:        true,
            criadoEm:     null,
            atualizadoEm: null,

        );

        $salvo = $this->repository->salvar($Usuario);

        $this->assertNotNull($salvo->getId());
        $this->assertSame('Sheila maria', $salvo->getNome());
        $this->assertSame('sheilaamaria@gmail.com', $salvo->getEmail());
        $this->assertSame(TipoUsuario::CLIENTE, $salvo->getTipoUsuario());

        // Busca direto no MySQL real
        $buscada = $this->repository->buscarPorId($salvo->getId());

        $this->assertNotNull($buscada);
        $this->assertSame($salvo->getId(), $buscada->getId());
        $this->assertSame('Sheila maria', $buscada->getNome());
    }

    public function testDeveAtualizarUsuarioNoBancoReal(): void
    {
        $Usuario = new Usuario(
            id:                null,
            nome:         'Sheila maria',
			email:        'sheilaamaria@gmail.com',	
			telefone:     '(83) 99999-9999' ,     
			cpf:          '529.982.247-25',
            tipoUsuario:  TipoUsuario::CLIENTE,
			senhaHash:    null,
            ativo:        true,
            criadoEm:     null,
            atualizadoEm: null,
        );

        $salvo = $this->repository->salvar($Usuario);

        // Modifica os dados
        $salvo->setNome('Sheila maria andre');
        
        $atualizado = $this->repository->atualizar($salvo);

        $this->assertSame('Sheila maria andre', $atualizado->getNome());

        $buscado = $this->repository->buscarPorId($salvo->getId());
        $this->assertSame('Sheila maria andre', $buscado->getNome());

      
    }

    public function testDeveDeletarUsuarioDoBancoReal(): void
    {
        $Usuario = new Usuario(
            id:            null,
            nome:         'Sheila maria andre',
			email:        'sheilaamaria@gmail.com',	
			telefone:     '(83) 99999-9999' ,     
			cpf:          '529.982.247-25',
            tipoUsuario:  TipoUsuario::CLIENTE,
			senhaHash:    null,
            ativo:        true,
            criadoEm:     null,
            atualizadoEm: null,
        );

        $salva = $this->repository->salvar($Usuario);
        $id = $salva->getId();

        $deletado = $this->repository->deletar($id);

        $this->assertTrue($deletado);
        $this->assertNull($this->repository->buscarPorId($id));
    }
}

