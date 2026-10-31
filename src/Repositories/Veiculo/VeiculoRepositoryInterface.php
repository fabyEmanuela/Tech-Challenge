<?php

namespace Repositories\Veiculo;

use Domain\Veiculo\Placa;
use Domain\Veiculo\Veiculo;

interface VeiculoRepositoryInterface
{
    public function listarTodos(): array;

    public function listarPorClienteId(int $clienteId): array;

    public function buscarPorId(int $id): ?Veiculo;

    public function buscarPorPlaca(Placa $placa): ?Veiculo;

    public function salvar(Veiculo $veiculo): Veiculo;

    public function atualizar(Veiculo $veiculo): Veiculo;

    public function deletar(int $id): bool;

    public function placaJaCadastrada(Placa $placa, ?int $excluirId = null): bool;
}
