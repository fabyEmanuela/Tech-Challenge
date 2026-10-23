<?php

namespace Application\Usuario;

use Domain\Usuario\TipoUsuario;
use Domain\Usuario\Usuario;
use Repositories\Usuario\UsuarioRepositoryInterface;

class ListarUsuariosUseCase
{
    public function __construct(
        private UsuarioRepositoryInterface $repository
    ) {}

    /**
     * @return Usuario[]
     */
    public function executar(?string $tipoFiltro = null): array
    {
        $tipo = null;

        if ($tipoFiltro !== null) {
            $tipo = TipoUsuario::tryFrom($tipoFiltro);
        }

        return $this->repository->listarTodos($tipo);
    }
}
