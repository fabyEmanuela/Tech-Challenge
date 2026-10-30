<?php

namespace Application\Veiculo;

use Domain\Shared\Validator;
use Domain\Veiculo\Veiculo;
use InvalidArgumentException;
use Repositories\Usuario\UsuarioRepositoryInterface;
use Repositories\Veiculo\VeiculoRepositoryInterface;

class CriarVeiculoUseCase
{
    public function __construct(
        private VeiculoRepositoryInterface $veiculoRepository,
        private UsuarioRepositoryInterface $usuarioRepository,
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
        $dados = $this->validarESanitizar($dados);

        $veiculo = new Veiculo(
            id:           null,
            clienteId:    $dados['cliente_id'],
            placa:        $dados['placa'],
            marca:        $dados['marca'],
            modelo:       $dados['modelo'],
            ano:          $dados['ano'],
            cor:          $dados['cor'] ?? null,
            criadoEm:     null,
            atualizadoEm: null,
        );

        return $this->veiculoRepository->salvar($veiculo);
    }

    /**
     * Valida e sanitiza todos os campos de entrada.
     *
     * @throws InvalidArgumentException
     */
    private function validarESanitizar(array $dados): array
    {
        // --- cliente_id (obrigatório) ---
        if (!isset($dados['cliente_id']) || $dados['cliente_id'] === '') {
            throw new InvalidArgumentException('O campo "cliente_id" é obrigatório.');
        }
        $dados['cliente_id'] = Validator::idPositivo($dados['cliente_id'], 'cliente_id');

        if ($this->usuarioRepository->buscarPorId($dados['cliente_id']) === null) {
            throw new InvalidArgumentException("Cliente com ID {$dados['cliente_id']} não encontrado.");
        }

        // --- placa (obrigatório) ---
        if (empty($dados['placa']) || trim((string) $dados['placa']) === '') {
            throw new InvalidArgumentException('O campo "placa" é obrigatório.');
        }
        $dados['placa'] = Validator::placa((string) $dados['placa']);

        if ($this->veiculoRepository->placaJaCadastrada($dados['placa'])) {
            throw new InvalidArgumentException(
                "Já existe um veículo cadastrado com a placa '{$dados['placa']->getFormatada()}'."
            );
        }

        // --- marca (obrigatório) ---
        if (empty($dados['marca']) || trim((string) $dados['marca']) === '') {
            throw new InvalidArgumentException('O campo "marca" é obrigatório.');
        }
        $dados['marca'] = Validator::marcaVeiculo((string) $dados['marca']);

        // --- modelo (obrigatório) ---
        if (empty($dados['modelo']) || trim((string) $dados['modelo']) === '') {
            throw new InvalidArgumentException('O campo "modelo" é obrigatório.');
        }
        $dados['modelo'] = Validator::modeloVeiculo((string) $dados['modelo']);

        // --- ano (obrigatório) ---
        if (!isset($dados['ano']) || $dados['ano'] === '') {
            throw new InvalidArgumentException('O campo "ano" é obrigatório.');
        }
        $dados['ano'] = Validator::anoVeiculo($dados['ano']);

        // --- cor (opcional) ---
        if (isset($dados['cor']) && $dados['cor'] !== null && trim((string) $dados['cor']) !== '') {
            $dados['cor'] = Validator::corVeiculo((string) $dados['cor']);
        } else {
            $dados['cor'] = null;
        }

        return $dados;
    }
}
