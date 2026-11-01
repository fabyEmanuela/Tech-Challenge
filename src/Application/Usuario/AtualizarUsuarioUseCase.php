<?php

namespace Application\Usuario;

use Domain\Shared\Validator;
use Domain\Usuario\TipoUsuario;
use Domain\Usuario\Usuario;
use Repositories\Usuario\UsuarioRepositoryInterface;
use InvalidArgumentException;
use RuntimeException;

class AtualizarUsuarioUseCase
{
    public function __construct(
        private UsuarioRepositoryInterface $repository
    ) {}

    /**
     * @param array{
     *   nome?: string,
     *   email?: string,
     *   telefone?: string|null,
     *   cpf?: string|null,
     *   cnpj?: string|null,
     *   tipo_usuario?: string,
     *   senha?: string|null,
     *   ativo?: bool
     * } $dados
     */
    public function executar(int $id, array $dados): Usuario
    {
        $usuario = $this->repository->buscarPorId($id);

        if ($usuario === null) {
            throw new RuntimeException("Usuário com ID $id não encontrado.");
        }

        $dados = $this->validarESanitizar($dados, $id, $usuario);
        $this->aplicarAlteracoes($usuario, $dados);

        return $this->repository->atualizar($usuario);
    }

    /**
     * Valida e sanitiza somente os campos presentes no payload (PATCH-like).
     *
     * @throws InvalidArgumentException
     */
    private function validarESanitizar(array $dados, int $id, Usuario $usuario): array
    {
        // --- nome ---
        if (isset($dados['nome'])) {
            $dados['nome'] = Validator::nome($dados['nome']);
        }

        // --- email ---
        if (isset($dados['email'])) {
            $dados['email'] = Validator::email($dados['email']);

            if ($this->repository->emailJaCadastrado($dados['email'], $id)) {
                throw new InvalidArgumentException('Já existe outro usuário com este e-mail.');
            }
        }

        $documentos = Validator::documentosUsuario(
            array_key_exists('cpf', $dados) ? $dados['cpf'] : $usuario->getCpf(),
            array_key_exists('cnpj', $dados) ? $dados['cnpj'] : $usuario->getCnpj(),
            $dados['tipo_usuario'] ?? $usuario->getTipoUsuario()->value
        );
        foreach (['cpf', 'cnpj'] as $campo) {
            $metodo = $campo.'JaCadastrado';
            if ($documentos[$campo] !== null && $this->repository->$metodo($documentos[$campo], $id)) {
                throw new InvalidArgumentException('Já existe outro usuário com este '.strtoupper($campo).'.');
            }
        }
        $dados = array_replace($dados, $documentos);
        
        // --- telefone ---
        if (!empty($dados['telefone'])) {
            $dados['telefone'] = Validator::telefone($dados['telefone']);
        }

        // --- tipo_usuario ---
        if (isset($dados['tipo_usuario'])) {
            Validator::textoLivre($dados['tipo_usuario'], 'tipo_usuario');

            if (TipoUsuario::tryFrom($dados['tipo_usuario']) === null) {
                $valores = implode(', ', array_column(TipoUsuario::cases(), 'value'));
                throw new InvalidArgumentException("tipo_usuario inválido. Valores aceitos: $valores.");
            }
        }

        return $dados;
    }

    private function aplicarAlteracoes(Usuario $usuario, array $dados): void
    {
        if (isset($dados['nome'])) {
            $usuario->setNome($dados['nome']);
        }
        if (isset($dados['email'])) {
            $usuario->setEmail($dados['email']);
        }
        if (array_key_exists('telefone', $dados)) {
            $usuario->setTelefone($dados['telefone']);
        }
        $usuario->definirDocumentos($dados['cpf'], $dados['cnpj'], isset($dados['tipo_usuario']) ? TipoUsuario::from($dados['tipo_usuario']) : null);
        if (!empty($dados['senha'])) {
            $usuario->setSenhaHash(password_hash($dados['senha'], PASSWORD_BCRYPT));
        }
        if (isset($dados['ativo'])) {
            $usuario->setAtivo((bool) $dados['ativo']);
        }
    }
}
