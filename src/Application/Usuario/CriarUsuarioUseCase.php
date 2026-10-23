<?php

namespace Application\Usuario;

use Domain\Shared\Validator;
use Domain\Usuario\TipoUsuario;
use Domain\Usuario\Usuario;
use Repositories\Usuario\UsuarioRepositoryInterface;
use InvalidArgumentException;

class CriarUsuarioUseCase
{
    public function __construct(
        private UsuarioRepositoryInterface $repository
    ) {}

    /**
     * @param array{
     *   nome: string,
     *   email: string,
     *   telefone?: string|null,
     *   cpf?: string|null,
     *   tipo_usuario?: string,
     *   senha?: string|null,
     *   ativo?: bool
     * } $dados
     */
    public function executar(array $dados): Usuario
    {
        $dados = $this->validarESanitizar($dados);

        $tipo = TipoUsuario::from($dados['tipo_usuario'] ?? TipoUsuario::CLIENTE->value);

        $senhaHash = null;
        if (!empty($dados['senha'])) {
            $senhaHash = password_hash($dados['senha'], PASSWORD_BCRYPT);
        }

        $usuario = new Usuario(
            id:           null,
            nome:         $dados['nome'],
            email:        $dados['email'],
            telefone:     $dados['telefone'] ?? null,
            cpf:          $dados['cpf'] ?? null,
            tipoUsuario:  $tipo,
            senhaHash:    $senhaHash,
            ativo:        $dados['ativo'] ?? true,
            criadoEm:     null,
            atualizadoEm: null,
        );

        return $this->repository->salvar($usuario);
    }

    /**
     * Valida e sanitiza todos os campos de entrada.
     * Retorna o array com os valores normalizados (e.g. CPF formatado, e-mail em lowercase).
     *
     * @throws InvalidArgumentException
     */
    private function validarESanitizar(array $dados): array
    {
        // --- nome (obrigatório) ---
        if (empty($dados['nome'])) {
            throw new InvalidArgumentException('O campo "nome" é obrigatório.');
        }
        $dados['nome'] = Validator::nome($dados['nome']);

        // --- email (obrigatório) ---
        if (empty($dados['email'])) {
            throw new InvalidArgumentException('O campo "email" é obrigatório.');
        }
        $dados['email'] = Validator::email($dados['email']);

        // --- cpf (opcional, mas validado se informado) ---
        if (!empty($dados['cpf'])) {
            $dados['cpf'] = Validator::cpf($dados['cpf']);
        }

        // --- telefone (opcional, mas validado se informado) ---
        if (!empty($dados['telefone'])) {
            $dados['telefone'] = Validator::telefone($dados['telefone']);
        }

        // --- tipo_usuario (opcional, usa default 'cliente') ---
        if (!empty($dados['tipo_usuario'])) {
            Validator::textoLivre($dados['tipo_usuario'], 'tipo_usuario');

            if (TipoUsuario::tryFrom($dados['tipo_usuario']) === null) {
                $valores = implode(', ', array_column(TipoUsuario::cases(), 'value'));
                throw new InvalidArgumentException("tipo_usuario inválido. Valores aceitos: $valores.");
            }
        }

        // --- unicidade no banco ---
        if ($this->repository->emailJaCadastrado($dados['email'])) {
            throw new InvalidArgumentException('Já existe um usuário com este e-mail.');
        }

        if (!empty($dados['cpf']) && $this->repository->cpfJaCadastrado($dados['cpf'])) {
            throw new InvalidArgumentException('Já existe um usuário com este CPF.');
        }

        return $dados;
    }
}
