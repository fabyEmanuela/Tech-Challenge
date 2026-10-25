<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\Veiculo;
use Domain\ValueObjects\Placa;

interface VeiculoRepositoryInterface
{
    /**
     * Persiste um novo veículo no banco de dados.
     */
    public function salvar(Veiculo $veiculo): Veiculo;

    /**
     * Busca um veículo pelo seu ID.
     */
    public function buscarPorId(int $id): ?Veiculo;

    /**
     * Busca um veículo pela sua Placa.
     */
    public function buscarPorPlaca(Placa $placa): ?Veiculo;

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
     * Atualiza os dados de um veículo existente.
     */
    public function atualizar(Veiculo $veiculo): Veiculo;

    /**
     * Deleta um veículo pelo seu ID.
     */
    public function deletar(int $id): bool;

    /**
     * Verifica se uma placa já está em uso, podendo ignorar um ID (para atualização).
     */
    public function placaJaExiste(Placa $placa, ?int $ignorarId = null): bool;
}
