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

## 🧪 Como Executar os Testes Automatizados 

A aplicação conta com uma suíte completa de testes automatizados com o **PHPUnit 12**, cobrindo tanto as regras de domínio e casos de uso (Testes Unitários) quanto a persistência no MySQL real (Testes de Integração).

Para executar os testes diretamente no container Docker, utilize os comandos abaixo:

```bash
# 1. Rodar TODOS os testes (Unitários + Integração):
docker compose exec app ./vendor/bin/phpunit --testdox

# 2. Rodar apenas os Testes Unitários:
docker compose exec app ./vendor/bin/phpunit --testsuite Unit --testdox

# 3. Rodar apenas os Testes de Integração (com o MySQL real):
docker compose exec app ./vendor/bin/phpunit --testsuite Integration --testdox
```

> **Nota para Avaliação:** Os testes de integração utilizam uma base de dados isolada (`oficina_mecanica_test`) criada automaticamente pelo Docker, utilizando transações com rollback para garantir que nenhum dado residual permaneça no banco após os testes.

---

## 🏛️ Arquitetura em Camadas (Clean Architecture)

- **`src/Domain/`**: Entidades de negócio, Value Objects e validações centrais (`Validator.php`).
- **`src/Application/`**: Casos de uso (UseCases) da aplicação orquestrando regras e interfaces.
- **`src/Repositories/`**: Interfaces e implementações PDO de persistência em banco.
- **`src/Http/`**: Roteador, Controllers REST e Middlewares de segurança.
- **`src/Infrastructure/`**: Conexão com banco (`Connection`), adaptadores externos e Swagger.
- **`src/tests/`**: Suíte de testes automatizados (Unitários e de Integração).
