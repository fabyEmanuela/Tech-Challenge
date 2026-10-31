<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\OrdemServico\HistoricoStatusOS;
use Domain\OrdemServico\OrdemServico;

interface OrdemServicoRepositoryInterface
{
    /**
     * Persiste uma nova Ordem de Serviço, seus itens e gera o primeiro histórico.
     */
    public function salvar(OrdemServico $os): OrdemServico;

    /**
     * Atualiza os dados principais da OS, recalcula itens e sincroniza peças e serviços.
     */
    public function atualizar(OrdemServico $os, ?HistoricoStatusOS $historico = null): OrdemServico;

    /**
     * Busca uma OS completa pelo seu ID interno.
     */
    public function buscarPorId(int $id): ?OrdemServico;

    /**
     * Busca uma OS completa pelo número identificador (ex: OS-20261028-0001).
     */
    public function buscarPorNumero(string $numeroOs): ?OrdemServico;

    /**
     * Lista ordens de serviço aplicando filtros opcionais.
     *
     * @param array{
     *     cliente_id?: int,
     *     veiculo_id?: int,
     *     status?: string,
     *     cpf?: string,
     *     placa?: string
     * } $filtros
     * @return OrdemServico[]
     */
    public function listar(array $filtros = []): array;

    /**
     * Remove uma OS pelo ID.
     */
    public function deletar(int $id): bool;

    /**
     * Gera sequencialmente o próximo número de OS legível (ex: OS-20261028-0001).
     */
    public function gerarProximoNumeroOs(): string;

    /**
     * Retorna a lista de eventos de histórico de uma OS.
     *
     * @return HistoricoStatusOS[]
     */
    public function buscarHistoricoPorOsId(int $osId): array;
}
