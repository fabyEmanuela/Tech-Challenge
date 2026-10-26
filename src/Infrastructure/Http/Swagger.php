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
#[OA\Tag(
    name: "Usuários",
    description: "Gerenciamento de clientes e usuários do sistema"
)]
#[OA\Tag(
    name: "Serviços",
    description: "Gerenciamento do catálogo de serviços da oficina mecânica"
)]
#[OA\Tag(
    name: "Status",
    description: "Verificação de conectividade e saúde da API"
)]
class Swagger
{
}
