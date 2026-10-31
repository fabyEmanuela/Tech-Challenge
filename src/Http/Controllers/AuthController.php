<?php

namespace Http\Controllers;

use Infrastructure\Auth\JwtService;
use Infrastructure\Http\JsonResponse;
use OpenApi\Attributes as OA;
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

    #[OA\Post(
        path: "/login",
        summary: "Autentica um usuário e retorna o token JWT",
        description: "Valida as credenciais (e-mail e senha) de um usuário cadastrado e retorna um token JWT de acesso com validade de 2 horas contendo o identificador (sub) e o perfil de acesso (role).",
        tags: ["Autenticação"],
        requestBody: new OA\RequestBody(
            required: true,
            description: "Credenciais de acesso do usuário",
            content: new OA\JsonContent(
                required: ["email", "senha"],
                properties: [
                    new OA\Property(
                        property: "email",
                        type: "string",
                        format: "email",
                        description: "E-mail cadastrado do usuário",
                        example: "admin@oficina.com"
                    ),
                    new OA\Property(
                        property: "senha",
                        type: "string",
                        format: "password",
                        description: "Senha do usuário",
                        example: "123456"
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Login realizado com sucesso. Retorna o token JWT para autenticação.",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: "access_token",
                            type: "string",
                            description: "Token JWT assinado com algoritmo HS256",
                            example: "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOjEsInJvbGUiOiJhdGVuZGVudGUiLCJpYXQiOjE2OTg3OTY4MDAsImV4cCI6MTY5ODgwNDAwMH0.abcdef..."
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "E-mail ou senha não informados na requisição",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "erro", type: "string", example: "E-mail e senha são obrigatórios.")
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: "Credenciais inválidas (usuário inexistente, sem senha cadastrada ou senha incorreta)",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "erro", type: "string", example: "Credenciais inválidas")
                    ]
                )
            )
        ]
    )]
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

        if ($usuario && $usuario->getSenhaHash() !== null && password_verify($senha, $usuario->getSenhaHash())) {
            $payload = [
                'sub'  => $usuario->getId(),
                'role' => $usuario->getTipoUsuario()->value,
                'iat'  => time(),
                'exp'  => time() + (2 * 60 * 60), // Expira em 2 horas
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
