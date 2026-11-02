<?php

declare(strict_types=1);

namespace Application\OrdemServico;

use Domain\OrdemServico\OrdemServico;
use Repositories\OrdemServico\OrdemServicoRepositoryInterface;

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
     *     cnpj?: string|null,
     *     placa?: string
     * } $filtros
     * @return OrdemServico[]
     */
    public function executar(array $filtros = []): array
    {
        return $this->ordemServicoRepository->listar($filtros);
    }
}
