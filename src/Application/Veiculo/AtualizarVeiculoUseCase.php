<?php

declare(strict_types=1);

namespace Application\Veiculo;

use Domain\Entities\Veiculo;
use Domain\Exceptions\ValidacaoException;
use Domain\Repositories\VeiculoRepositoryInterface;
use Domain\ValueObjects\Placa;
use Repositories\Usuario\UsuarioRepositoryInterface;

class AtualizarVeiculoUseCase
{
    public function __construct(
        private VeiculoRepositoryInterface $veiculoRepository,
        private UsuarioRepositoryInterface $usuarioRepository
    ) {}

    /**
     * @param array{
     *   cliente_id?: int,
     *   placa?: string,
     *   marca?: string,
     *   modelo?: string,
     *   ano?: int,
     *   cor?: string|null
     * } $dados
     */
    public function executar(int $id, array $dados): Veiculo
    {
        $veiculo = $this->veiculoRepository->buscarPorId($id);
        if ($veiculo === null) {
            throw new ValidacaoException("Veículo com ID {$id} não encontrado.");
        }

        $clienteId = isset($dados['cliente_id']) ? (int) $dados['cliente_id'] : $veiculo->clienteId();
        if ($clienteId !== $veiculo->clienteId()) {
            $cliente = $this->usuarioRepository->buscarPorId($clienteId);
            if ($cliente === null) {
                throw new ValidacaoException("Cliente com ID {$clienteId} não encontrado.");
            }
        }

        $placa = isset($dados['placa']) ? new Placa((string) $dados['placa']) : $veiculo->placa();
        if ($this->veiculoRepository->placaJaExiste($placa, $id)) {
            throw new ValidacaoException("Já existe outro veículo cadastrado com a placa '{$placa->formatada()}'.");
        }

        $marca  = isset($dados['marca']) ? (string) $dados['marca'] : $veiculo->marca();
        $modelo = isset($dados['modelo']) ? (string) $dados['modelo'] : $veiculo->modelo();
        $ano    = isset($dados['ano']) ? (int) $dados['ano'] : $veiculo->ano();
        $cor    = array_key_exists('cor', $dados) ? ($dados['cor'] !== null ? (string) $dados['cor'] : null) : $veiculo->cor();

        $veiculo->atualizar(
            clienteId: $clienteId,
            placa:     $placa,
            marca:     $marca,
            modelo:    $modelo,
            ano:       $ano,
            cor:       $cor
        );

        return $this->veiculoRepository->atualizar($veiculo);
    }
}
