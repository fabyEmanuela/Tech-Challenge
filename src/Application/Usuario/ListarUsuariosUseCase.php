<?php

namespace Application\Usuario;

use Domain\Usuario\TipoUsuario;
use Domain\Usuario\Usuario;
use Domain\Shared\Validator;
use Repositories\Usuario\UsuarioRepositoryInterface;

class ListarUsuariosUseCase
{
    public function __construct(
        private UsuarioRepositoryInterface $repository
    ) {}

    /**
     * @return Usuario[]
     */
    public function executar(?string $tipoFiltro = null, ?string $nome = null, ?string $cpf = null): array
    {
        $tipo = null;

        if ($tipoFiltro !== null) {
            $tipo = TipoUsuario::tryFrom($tipoFiltro);
        }

        if ($nome !== null && trim($nome) !== '') {
            $nome = trim($nome);
        } else {
            $nome = null;
        }

        if ($cpf !== null && trim($cpf) !== '') {
            $cpf = Validator::cpf($cpf);
        } else {
            $cpf = null;
        }

        return $this->repository->listarTodos($tipo, $nome, $cpf);
    }
}
