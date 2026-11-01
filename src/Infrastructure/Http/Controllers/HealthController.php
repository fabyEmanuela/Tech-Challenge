<?php

declare(strict_types=1);

namespace Infrastructure\Http\Controllers;

use Infrastructure\Database\Connection;
use Infrastructure\Http\JsonResponse;
use OpenApi\Attributes as OA;
use Throwable;

final class HealthController
{
    #[OA\Get(
        path: "/",
        summary: "Verifica a saúde da API e do Banco de Dados",
        description: "Testa a conectividade com o MySQL e retorna as versões do PHP e do banco.",
        tags: ["Status"],
        security: [],
        responses: [
            new OA\Response(
                response: 200,
                description: "API e Banco de Dados operacionais",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "string", example: "ok"),
                        new OA\Property(property: "php", type: "string", example: "8.3.0"),
                        new OA\Property(property: "mysql", type: "string", example: "8.4.0")
                    ]
                )
            ),
            new OA\Response(
                response: 503,
                description: "Banco de dados indisponível",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "string", example: "erro"),
                        new OA\Property(property: "php", type: "string", example: "8.3.0"),
                        new OA\Property(property: "mysql", type: "string", example: "indisponível")
                    ]
                )
            )
        ]
    )]
    public function check(): void
    {
        try {
            $versao = Connection::getInstance()->query('SELECT VERSION()')->fetchColumn();

            JsonResponse::send([
                'status' => 'ok',
                'php'    => PHP_VERSION,
                'mysql'  => $versao,
            ]);
        } catch (Throwable $e) {
            JsonResponse::send([
                'status' => 'erro',
                'php'    => PHP_VERSION,
                'mysql'  => 'indisponível',
            ], 503);
        }
    }
}
