# 🚗 Tech Challenge — Sistema de Gestão de Oficina Mecânica (SOAT - Fase 1)

Projeto desenvolvido para a pós-graduação em **Software Architecture (SOAT)** da **FIAP**.

---

## 🚀 Como Executar o Projeto Localmente (Para a Equipe e Avaliadores)

### Pré-requisitos
- Ter o **Docker** e o **Docker Compose** instalados na sua máquina.

### 1. Clonar e Configurar o Ambiente
```bash
# 1. Clone o repositório
git clone https://github.com/fabyEmanuela/Tech-Challenge.git
cd Tech-Challenge

# 2. Crie o arquivo .env a partir do exemplo (se ainda não existir)
cp .env.example .env

# 3. Suba todos os containers (PHP/Apache, MySQL e Swagger UI)
docker compose up -d --build

# 4. Instale as dependências pelo próprio container
docker compose exec app composer install
```

---

## 🌐 Serviços Disponíveis

| Serviço | URL / Porta | Descrição |
| :--- | :--- | :--- |
| **API Principal (PHP/Apache)** | [http://localhost:8080](http://localhost:8080) | Endpoints e rotas da aplicação |
| **Swagger UI (Documentação)** | [http://localhost:8081](http://localhost:8081) | Interface interativa da documentação |
| **Banco MySQL 8.4** | `localhost:3307` | Banco de dados da oficina |

---
remover isto anteas da entrega 
## 👩‍💻 Guia para a Equipe: Como Criar e Documentar Novas Rotas

Para que o time mantenha o mesmo padrão arquitetural e o Swagger sempre atualizado:

### 1. Padrão de Resposta HTTP
Use sempre a classe helper `JsonResponse` para emitir as respostas dos seus Controllers:
```php
use Infrastructure\Http\JsonResponse;

JsonResponse::send(["mensagem" => "Sucesso"], 200);
```

### 2. Documentar o Endpoint no Swagger
Em cima do método do seu Controller, adicione os atributos OpenAPI:
```php
use OpenApi\Attributes as OA;

#[OA\Get(
    path: "/veiculos",
    summary: "Lista todos os veículos",
    tags: ["Veículos"],
    responses: [
        new OA\Response(response: 200, description: "Lista de veículos retornada com sucesso")
    ]
)]
public function listar(): void
{
    // ...
}
```

### 3. Como Atualizar a Documentação do Swagger
Sempre que você criar ou alterar uma rota, rode no terminal:
```bash
docker compose exec app ./vendor/bin/openapi src/ -o docs/swagger.json
```
Depois, basta atualizar a página **[http://localhost:8081](http://localhost:8081)** para ver as novidades no Swagger!

---

## 🏛️ Arquitetura em Camadas (Clean Architecture)

- **`src/Domain/`**: Entidades de negócio, Value Objects e Interfaces (PHP puro, sem SQL/frameworks).
- **`src/Application/`**: Casos de uso (UseCases) e DTOs de orquestração.
- **`src/Infrastructure/`**: Conexão com banco (PDO), repositórios SQL, Controllers HTTP e Swagger.
