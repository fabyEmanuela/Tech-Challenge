<?php

namespace Http\Controllers;

use Infrastructure\Auth\JwtService;
use Infrastructure\Http\JsonResponse;
use Repositories\Usuario\UsuarioRepositoryInterface;

class AuthController
{
    private UsuarioRepositoryInterface $usuarioRepository;
    private JwtService $jwtService;

    public function __construct(
        UsuarioRepositoryInterface $usuarioRepository,
        JwtService $jwtService
    ) {
        $this->usuarioRepository = $usuarioRepository;
        $this->jwtService = $jwtService;
    }

    public function login(): void
    {
        $dados = $this->lerCorpo();
        $email = trim((string) ($dados['email'] ?? ''));
        $senha = (string) ($dados['senha'] ?? '');

        if ($email === '' || $senha === '') {
            JsonResponse::send(['erro' => 'E-mail e senha são obrigatórios.'], 400);
            return;
        }

        // Busca o usuário via repositório
        $usuario = $this->usuarioRepository->buscarPorEmail($email);

        if ($usuario && password_verify($senha, $usuario->getSenha())) {
            $payload = [
                'sub'  => $usuario->getId(),
                'role' => $usuario->getTipoUsuario()->value,
                'iat'  => time(),
                'exp'  => time() + (2 * 60 * 60),
            ];

            $token = $this->jwtService->generateToken($payload);

            JsonResponse::send(['access_token' => $token], 200);
            return;
        }

        JsonResponse::send(['erro' => 'Credenciais inválidas'], 401);
    }

    private function lerCorpo(): array
    {
        $conteudo = file_get_contents('php://input');
        if (empty($conteudo)) {
            return [];
        }

        $dados = json_decode($conteudo, true);
        return is_array($dados) ? $dados : [];
    }
}