<?php

declare(strict_types=1);

namespace Application\Veiculo;

use Domain\Entities\Veiculo;
use Domain\Exceptions\ValidacaoException;
use Domain\Repositories\VeiculoRepositoryInterface;
use Domain\ValueObjects\Placa;
use Repositories\Usuario\UsuarioRepositoryInterface;

class CriarVeiculoUseCase
{
    public function __construct(
        private VeiculoRepositoryInterface $veiculoRepository,
        private UsuarioRepositoryInterface $usuarioRepository
    ) {}

    /**
     * @param array{
     *   cliente_id: int,
     *   placa: string,
     *   marca: string,
     *   modelo: string,
     *   ano: int,
     *   cor?: string|null
     * } $dados
     */
    public function executar(array $dados): Veiculo
    {
        if (empty($dados['cliente_id'])) {
            throw new ValidacaoException('O campo "cliente_id" é obrigatório.');
        }

        $clienteId = (int) $dados['cliente_id'];
        $cliente = $this->usuarioRepository->buscarPorId($clienteId);
        if ($cliente === null) {
            throw new ValidacaoException("Cliente com ID {$clienteId} não encontrado.");
        }

        if (empty($dados['placa'])) {
            throw new ValidacaoException('O campo "placa" é obrigatório.');
        }

        $placa = new Placa((string) $dados['placa']);

        if ($this->veiculoRepository->placaJaExiste($placa)) {
            throw new ValidacaoException("Já existe um veículo cadastrado com a placa '{$placa->formatada()}'.");
        }

        if (empty($dados['marca'])) {
            throw new ValidacaoException('O campo "marca" é obrigatório.');
        }

        if (empty($dados['modelo'])) {
            throw new ValidacaoException('O campo "modelo" é obrigatório.');
        }

        if (empty($dados['ano'])) {
            throw new ValidacaoException('O campo "ano" é obrigatório.');
        }

        $cor = !empty($dados['cor']) ? (string) $dados['cor'] : null;

        $veiculo = Veiculo::criar(
            clienteId: $clienteId,
            placa:     $placa,
            marca:     (string) $dados['marca'],
            modelo:    (string) $dados['modelo'],
            ano:       (int) $dados['ano'],
            cor:       $cor
        );

        return $this->veiculoRepository->salvar($veiculo);
    }
}
