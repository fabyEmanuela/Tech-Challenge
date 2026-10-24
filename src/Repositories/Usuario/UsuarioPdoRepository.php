<?php

namespace Repositories\Usuario;

use Domain\Usuario\TipoUsuario;
use Domain\Usuario\Usuario;
use PDO;

class UsuarioPdoRepository implements UsuarioRepositoryInterface
{
    public function __construct(private PDO $pdo) {}

    // ------------------------------------------------------------------
    // Auxiliares
    // ------------------------------------------------------------------

    private function montarUsuario(array $linha): Usuario
    {
        return new Usuario(
            id:           (int) $linha['id'],
            nome:         $linha['nome'],
            email:        $linha['email'],
            telefone:     $linha['telefone'] ?? null,
            cpf:          $linha['cpf'] ?? null,
            tipoUsuario:  TipoUsuario::from($linha['tipo_usuario']),
            senhaHash:    $linha['senha_hash'] ?? null,
            ativo:        (bool) $linha['ativo'],
            criadoEm:     $linha['criado_em'] ?? null,
            atualizadoEm: $linha['atualizado_em'] ?? null,
        );
    }

    // ------------------------------------------------------------------
    // Consultas
    // ------------------------------------------------------------------

    public function listarTodos(?TipoUsuario $tipo = null): array
    {
        if ($tipo !== null) {
            $stmt = $this->pdo->prepare(
                'SELECT * FROM usuarios WHERE tipo_usuario = :tipo ORDER BY nome'
            );
            $stmt->execute([':tipo' => $tipo->value]);
        } else {
            $stmt = $this->pdo->query('SELECT * FROM usuarios ORDER BY nome');
        }

        return array_map(
            fn(array $linha) => $this->montarUsuario($linha),
            $stmt->fetchAll()
        );
    }

    public function buscarPorId(int $id): ?Usuario
    {
        $stmt = $this->pdo->prepare('SELECT * FROM usuarios WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $linha = $stmt->fetch();

        return $linha ? $this->montarUsuario($linha) : null;
    }

    public function buscarPorEmail(string $email): ?Usuario
    {
        $stmt = $this->pdo->prepare('SELECT * FROM usuarios WHERE email = :email');
        $stmt->execute([':email' => $email]);
        $linha = $stmt->fetch();

        return $linha ? $this->montarUsuario($linha) : null;
    }

    public function emailJaCadastrado(string $email, ?int $excluirId = null): bool
    {
        $sql    = 'SELECT COUNT(*) FROM usuarios WHERE email = :email';
        $params = [':email' => $email];

        if ($excluirId !== null) {
            $sql .= ' AND id != :id';
            $params[':id'] = $excluirId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function cpfJaCadastrado(string $cpf, ?int $excluirId = null): bool
    {
        $sql    = 'SELECT COUNT(*) FROM usuarios WHERE cpf = :cpf';
        $params = [':cpf' => $cpf];

        if ($excluirId !== null) {
            $sql .= ' AND id != :id';
            $params[':id'] = $excluirId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    // ------------------------------------------------------------------
    // Comandos
    // ------------------------------------------------------------------

    public function salvar(Usuario $usuario): Usuario
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO usuarios (nome, email, telefone, cpf, tipo_usuario, senha_hash, ativo)
             VALUES (:nome, :email, :telefone, :cpf, :tipo_usuario, :senha_hash, :ativo)'
        );

        $stmt->execute([
            ':nome'         => $usuario->getNome(),
            ':email'        => $usuario->getEmail(),
            ':telefone'     => $usuario->getTelefone(),
            ':cpf'          => $usuario->getCpf(),
            ':tipo_usuario' => $usuario->getTipoUsuario()->value,
            ':senha_hash'   => $usuario->getSenhaHash(),
            ':ativo'        => (int) $usuario->isAtivo(),
        ]);

        return $this->buscarPorId((int) $this->pdo->lastInsertId());
    }

    public function atualizar(Usuario $usuario): Usuario
    {
        $stmt = $this->pdo->prepare(
            'UPDATE usuarios
             SET nome = :nome,
                 email = :email,
                 telefone = :telefone,
                 cpf = :cpf,
                 tipo_usuario = :tipo_usuario,
                 senha_hash = :senha_hash,
                 ativo = :ativo
             WHERE id = :id'
        );

        $stmt->execute([
            ':id'           => $usuario->getId(),
            ':nome'         => $usuario->getNome(),
            ':email'        => $usuario->getEmail(),
            ':telefone'     => $usuario->getTelefone(),
            ':cpf'          => $usuario->getCpf(),
            ':tipo_usuario' => $usuario->getTipoUsuario()->value,
            ':senha_hash'   => $usuario->getSenhaHash(),
            ':ativo'        => (int) $usuario->isAtivo(),
        ]);

        return $this->buscarPorId($usuario->getId());
    }

    public function deletar(int $id): bool
    {
        try {
            $stmt = $this->pdo->prepare('DELETE FROM usuarios WHERE id = :id');
            $stmt->execute([':id' => $id]);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000' || (isset($e->errorInfo[1]) && $e->errorInfo[1] === 1451)) {
                throw new \Domain\Exceptions\ValidacaoException(
                    'Não é possível remover este cliente porque existem veículos vinculados a ele. Remova os veículos antes de excluir o cliente.'
                );
            }
            throw $e;
        }
    }
}
