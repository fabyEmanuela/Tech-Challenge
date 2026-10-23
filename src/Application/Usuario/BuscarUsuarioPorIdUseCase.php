<?php

namespace Application\Usuario;

use Domain\Usuario\Usuario;
use Repositories\Usuario\UsuarioRepositoryInterface;
use RuntimeException;

class BuscarUsuarioPorIdUseCase
{
    public function __construct(
        private UsuarioRepositoryInterface $repository
    ) {}

    public function executar(int $id): Usuario
    {
        $usuario = $this->repository->buscarPorId($id);

        if ($usuario === null) {
            throw new RuntimeException("Usuário com ID $id não encontrado.");
        }

        return $usuario;
    }
}
