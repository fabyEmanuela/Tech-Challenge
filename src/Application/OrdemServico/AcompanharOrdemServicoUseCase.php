<?php

declare(strict_types=1);

namespace Application\OrdemServico;

use Domain\Exceptions\ValidacaoException;
use Repositories\OrdemServico\OrdemServicoRepositoryInterface;

class AcompanharOrdemServicoUseCase
{
    public function __construct(
        private OrdemServicoRepositoryInterface $ordemServicoRepository
    ) {}

    /**
     * Consulta por ID da OS.
     */
    public function executarPorId(int $id): array
    {
        $os = $this->ordemServicoRepository->buscarPorId($id);

        if (!$os) {
            throw new ValidacaoException(sprintf('Ordem de serviço com ID %d não foi encontrada.', $id));
        }

        return $os->paraAcompanhamentoCliente();
    }

    /**
     * Consulta por Número da OS (ex: OS-20261028-0001).
     */
    public function executarPorNumero(string $numeroOs): array
    {
        $os = $this->ordemServicoRepository->buscarPorNumero($numeroOs);

        if (!$os) {
            throw new ValidacaoException(sprintf('Ordem de serviço número "%s" não foi encontrada.', $numeroOs));
        }

        return $os->paraAcompanhamentoCliente();
    }

    /**
     * Consulta por CPF do cliente e/ou Placa do veículo.
     * Retorna a lista de ordens acompanhadas com progresso.
     */
    public function executarPorFiltros(array $filtros): array
    {
        if (empty($filtros['cpf']) && empty($filtros['cnpj']) && empty($filtros['placa']) && empty($filtros['numero_os']) && empty($filtros['id'])) {
            throw new ValidacaoException('Para consultar o progresso, informe ao menos o CPF, CNPJ, placa do veículo ou número da OS.');
        }

        if (!empty($filtros['numero_os'])) {
            return [$this->executarPorNumero((string) $filtros['numero_os'])];
        }

        if (!empty($filtros['id'])) {
            return [$this->executarPorId((int) $filtros['id'])];
        }

        $ordens = $this->ordemServicoRepository->listar($filtros);

        if (count($ordens) === 0) {
            throw new ValidacaoException('Nenhuma ordem de serviço foi localizada com os parâmetros informados.');
        }

        return array_map(fn($os) => $os->paraAcompanhamentoCliente(), $ordens);
    }
}
