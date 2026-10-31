<?php

namespace Infrastructure\Http;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: "1.0.0",
    title: "Tech Challenge API - Oficina Mecânica",
    description: "API de gerenciamento da Oficina Mecânica desenvolvida para o Tech Challenge (SOAT - Fase 1)."
)]
#[OA\Server(
    url: "http://localhost:8080",
    description: "Servidor Local (Docker)"
)]
#[OA\SecurityScheme(
    securityScheme: "bearerAuth",
    type: "http",
    scheme: "bearer",
    bearerFormat: "JWT",
    description: "Insira o token JWT gerado na rota /login para autenticar as requisições protegidas."
)]
#[OA\Tag(
    name: "Autenticação",
    description: "Endpoints para login e emissão de tokens JWT de acesso"
)]
#[OA\Tag(
    name: "Usuários",
    description: "Gerenciamento de clientes e usuários do sistema"
)]
#[OA\Tag(
    name: "Veículos",
    description: "Gerenciamento de veículos de clientes"
)]
#[OA\Tag(
    name: "Serviços",
    description: "Gerenciamento do catálogo de serviços da oficina mecânica"
)]
#[OA\Tag(
    name: "Peças",
    description: "Gerenciamento de peças, insumos e controle de estoque"
)]
#[OA\Tag(
    name: "Status",
    description: "Verificação de conectividade e saúde da API"
)]
class Swagger
{
}
