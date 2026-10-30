<?php

declare(strict_types=1);

namespace Application\Veiculo;

use Domain\Shared\Validator;
use Domain\Veiculo\Veiculo;
use InvalidArgumentException;
use Repositories\Usuario\UsuarioRepositoryInterface;
use Repositories\Veiculo\VeiculoRepositoryInterface;
use RuntimeException;

class AtualizarVeiculoUseCase
{
    public function __construct(
        private VeiculoRepositoryInterface $veiculoRepository,
        private UsuarioRepositoryInterface $usuarioRepository,
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
            throw new RuntimeException("Veículo com ID $id não encontrado.");
        }

        $dados = $this->validarESanitizar($dados, $veiculo);
        $this->aplicarAlteracoes($veiculo, $dados);

        return $this->veiculoRepository->atualizar($veiculo);
    }

    /**
     * Valida e sanitiza somente os campos presentes no payload (PATCH-like).
     *
     * @throws InvalidArgumentException
     */
    private function validarESanitizar(array $dados, Veiculo $veiculoAtual): array
    {
        $id = (int) $veiculoAtual->getId();

        // --- cliente_id ---
        if (array_key_exists('cliente_id', $dados)) {
            $dados['cliente_id'] = Validator::idPositivo($dados['cliente_id'], 'cliente_id');

            if (
                $dados['cliente_id'] !== $veiculoAtual->getClienteId()
                && $this->usuarioRepository->buscarPorId($dados['cliente_id']) === null
            ) {
                throw new InvalidArgumentException("Cliente com ID {$dados['cliente_id']} não encontrado.");
            }
        }

        // --- placa ---
        if (array_key_exists('placa', $dados)) {
            if (empty($dados['placa'])) {
                throw new InvalidArgumentException('O campo "placa" é obrigatório.');
            }

            $dados['placa'] = Validator::placa((string) $dados['placa']);

            if ($this->veiculoRepository->placaJaCadastrada($dados['placa'], $id)) {
                throw new InvalidArgumentException(
                    "Já existe outro veículo cadastrado com a placa '{$dados['placa']->getFormatada()}'."
                );
            }
        }

        // --- marca ---
        if (array_key_exists('marca', $dados)) {
            if (empty($dados['marca'])) {
                throw new InvalidArgumentException('O campo "marca" é obrigatório.');
            }

            $dados['marca'] = Validator::marcaVeiculo((string) $dados['marca']);
        }

        // --- modelo ---
        if (array_key_exists('modelo', $dados)) {
            if (trim((string) $dados['modelo']) === '') {
                throw new InvalidArgumentException('O campo "modelo" é obrigatório.');
            }

            $dados['modelo'] = Validator::modeloVeiculo((string) $dados['modelo']);
        }

        // --- ano ---
        if (array_key_exists('ano', $dados)) {
            $dados['ano'] = Validator::anoVeiculo($dados['ano']);
        }

        // --- cor ---
        if (array_key_exists('cor', $dados)) {
            if ($dados['cor'] !== null && trim((string) $dados['cor']) !== '') {
                $dados['cor'] = Validator::corVeiculo((string) $dados['cor']);
            } else {
                $dados['cor'] = null;
            }
        }

        return $dados;
    }

    private function aplicarAlteracoes(Veiculo $veiculo, array $dados): void
    {
        if (isset($dados['cliente_id'])) {
            $veiculo->setClienteId($dados['cliente_id']);
        }
        if (isset($dados['placa'])) {
            $veiculo->setPlaca($dados['placa']);
        }
        if (isset($dados['marca'])) {
            $veiculo->setMarca($dados['marca']);
        }
        if (isset($dados['modelo'])) {
            $veiculo->setModelo($dados['modelo']);
        }
        if (isset($dados['ano'])) {
            $veiculo->setAno($dados['ano']);
        }
        if (array_key_exists('cor', $dados)) {
            $veiculo->setCor($dados['cor']);
        }
    }
}
