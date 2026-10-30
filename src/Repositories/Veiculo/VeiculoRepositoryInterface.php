<?php

declare(strict_types=1);

namespace Repositories\Veiculo;

use Domain\Veiculo\Placa;
use Domain\Veiculo\Veiculo;

interface VeiculoRepositoryInterface
{
    /**
     * Lista todos os veículos cadastrados.
     *
     * @return Veiculo[]
     */
    public function listarTodos(): array;

    /**
     * Lista veículos de um cliente específico.
     *
     * @return Veiculo[]
     */
    public function listarPorClienteId(int $clienteId): array;

    /**
     * Busca um veículo pelo seu ID.
     */
    public function buscarPorId(int $id): ?Veiculo;

    /**
     * Busca um veículo pela sua Placa.
     */
    public function buscarPorPlaca(Placa $placa): ?Veiculo;

    /**
     * Verifica se uma placa já está cadastrada, podendo ignorar um ID (para atualização).
     */
    public function placaJaCadastrada(Placa $placa, ?int $excluirId = null): bool;

    /**
     * Persiste um novo veículo no banco de dados.
     */
    public function salvar(Veiculo $veiculo): Veiculo;

    /**
     * Atualiza os dados de um veículo existente.
     */
    public function atualizar(Veiculo $veiculo): Veiculo;

    /**
     * Deleta um veículo pelo seu ID.
     */
    public function deletar(int $id): bool;
}

