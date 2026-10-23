<?php

namespace Repositories\Usuario;

use Domain\Usuario\TipoUsuario;
use Domain\Usuario\Usuario;

interface UsuarioRepositoryInterface
{
    public function listarTodos(?TipoUsuario $tipo = null): array;

    public function buscarPorId(int $id): ?Usuario;

    public function buscarPorEmail(string $email): ?Usuario;

    public function salvar(Usuario $usuario): Usuario;

    public function atualizar(Usuario $usuario): Usuario;

    public function deletar(int $id): bool;

    public function emailJaCadastrado(string $email, ?int $excluirId = null): bool;

    public function cpfJaCadastrado(string $cpf, ?int $excluirId = null): bool;
}
