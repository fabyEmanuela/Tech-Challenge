<?php

namespace Application\Peca;

use Domain\Peca\Peca;
use Repositories\Peca\PecaRepositoryInterface;

class ListarPecasUseCase
{
    public function __construct(
        private PecaRepositoryInterface $repository
    ) {}

    /**
     * @return Peca[]
     */
    public function executar(?string $ativoFiltro = null): array
    {
        $ativo = null;

        if ($ativoFiltro !== null && $ativoFiltro !== '') {
            $ativo = filter_var($ativoFiltro, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        return $this->repository->listarTodos($ativo);
    }
}

