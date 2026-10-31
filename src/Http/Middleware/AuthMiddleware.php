<?php

namespace Http\Middleware;

use Infrastructure\Auth\JwtService;
use Infrastructure\Http\JsonResponse;

class AuthMiddleware
{
    public function __construct(private JwtService $jwtService) {}

    public function __invoke(): bool
    {
        return $this->handle();
    }

    /**
     * Retorna um callable para validar perfis específicos em rotas protegidas (RBAC)
     */
    public function comRoles(array $roles): callable
    {
        return fn(): bool => $this->handle($roles);
    }

    public function handle(?array $rolesPermitidas = null): bool
    {
        $token = $this->extrairBearerToken();

        if (!$token) {
            JsonResponse::send(['erro' => 'Token de autorização não fornecido.'], 401);
            return false;
        }

        $payload = $this->jwtService->validateToken($token);

        if (!$payload) {
            JsonResponse::send(['erro' => 'Token inválido ou expirado.'], 401);
            return false;
        }

        if ($rolesPermitidas !== null && !in_array($payload['role'] ?? '', $rolesPermitidas, true)) {
            JsonResponse::send(['erro' => 'Acesso negado para este perfil de usuário.'], 403);
            return false;
        }

        // Salva os dados do usuário autenticado para uso posterior
        $_REQUEST['auth_user'] = $payload;

        return true;
    }

    private function extrairBearerToken(): ?string
    {
        $headers = function_exists('apache_request_headers') ? apache_request_headers() : [];

        $authHeader = $_SERVER['HTTP_AUTHORIZATION']
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
            ?? $headers['Authorization']
            ?? $headers['authorization']
            ?? '';

        if (preg_match('/Bearer\s+(\S+)/i', $authHeader, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
