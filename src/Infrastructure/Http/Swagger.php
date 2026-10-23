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
class Swagger
{
}
