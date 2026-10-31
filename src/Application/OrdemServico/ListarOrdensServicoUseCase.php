<?php

declare(strict_types=1);

namespace Application\OrdemServico;

use Domain\OrdemServico\OrdemServico;
use Domain\Repositories\OrdemServicoRepositoryInterface;

class ListarOrdensServicoUseCase
{
    public function __construct(
        private OrdemServicoRepositoryInterface $ordemServicoRepository
    ) {}

    /**
     * @param array{
     *     status?: string,
     *     cliente_id?: int,
     *     veiculo_id?: int,
     *     cpf?: string,
     *     placa?: string
     * } $filtros
     * @return OrdemServico[]
     */
    public function executar(array $filtros = []): array
    {
        return $this->ordemServicoRepository->listar($filtros);
    }
}
