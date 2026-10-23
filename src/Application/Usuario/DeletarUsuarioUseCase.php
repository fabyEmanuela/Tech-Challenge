<?php

namespace Application\Usuario;

use Repositories\Usuario\UsuarioRepositoryInterface;
use RuntimeException;

class DeletarUsuarioUseCase
{
    public function __construct(
        private UsuarioRepositoryInterface $repository
    ) {}

    public function executar(int $id): void
    {
        $usuario = $this->repository->buscarPorId($id);

        if ($usuario === null) {
            throw new RuntimeException("Usuário com ID $id não encontrado.");
        }

        $this->repository->deletar($id);
    }
}
