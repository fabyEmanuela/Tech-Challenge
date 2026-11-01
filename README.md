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

# 3. Suba todos os containers (PHP/Apache, MySQL, Swagger UI e Mailpit)
docker compose up -d --build

# 4. Instale as dependências pelo próprio container
docker compose exec app composer install
```

---

## 🌐 Serviços Disponíveis

| Serviço | URL / Porta | Descrição |
| :--- | :--- | :--- |
| **API Principal (PHP/Apache)** | [http://localhost:8080](http://localhost:8080) | Endpoints e rotas da aplicação |
| **Swagger UI (Documentação)** | [http://localhost:8081](http://localhost:8081) | Interface interativa da documentação OpenAPI |
| **Mailpit (E-mails)** | [http://localhost:8025](http://localhost:8025) | Visualizador de e-mails capturados em desenvolvimento |
| **Banco MySQL 8.4** | `localhost:3307` | Banco de dados da oficina |

---

## 🔐 Autenticação & Credenciais Padrão para Avaliação

A API utiliza autenticação **Bearer Token (JWT)** para proteger as rotas de negócio. Para que os avaliadores possam testar os endpoints imediatamente após o `docker compose up`, o banco de dados já inicializa com as seguintes credenciais padrão:

| Perfil | E-mail | Senha | Finalidade / Permissões |
| :--- | :--- | :--- | :--- |
| **Atendente (Admin)** | `admin@oficina.com` | `123456` | Gerenciamento de clientes, veículos, serviços e peças |
| **Mecânico** | `mecanico@oficina.com` | `123456` | Consulta e execução de serviços e movimentação de peças |

### Como se autenticar pelo Swagger UI:
1. Acesse **[http://localhost:8081](http://localhost:8081)**.
2. Expanda o endpoint `POST /login` (sob a tag **Autenticação**), clique em **Try it out** e clique em **Execute** (já vem pré-preenchido com `admin@oficina.com` e `123456`).
3. Copie o valor de `access_token` retornado.
4. Clique no botão verde **Authorize** (no topo direito da página do Swagger), cole o token no campo de texto e clique em **Authorize**.
5. Pronto! Agora todas as rotas protegidas responderão com sucesso.

---

### 🛡️ Regras de Controle de Acesso por Perfil (RBAC)

O sistema implementa controle de acesso granular baseado no perfil do usuário (`role` contida no token JWT):

| Módulo / Recurso | Endpoints | Atendente (Admin) | Mecânico | Cliente | Regra de Negócio |
| :--- | :--- | :---: | :---: | :---: | :--- |
| **Autenticação & Status** | `POST /login`, `GET /` | ✅ | ✅ | ✅ | Rotas públicas (não exigem token) |
| **Usuários** | `POST /usuarios`, `PUT`, `DELETE`, `GET` | ✅ | ❌ | ❌ | **Apenas Atendente** cadastra e gerencia usuários. Mecânicos e clientes não têm permissão |
| **Clientes** | `GET /clientes` | ✅ | ✅ | ❌ | Consulta de clientes para ordens de serviço e atendimento |
| **Veículos (Gestão)** | `POST /veiculos`, `PUT`, `DELETE` | ✅ | ❌ | ❌ | Cadastro e alteração de veículos restritos ao Atendente |
| **Veículos (Consulta)** | `GET /veiculos`, `GET /veiculos/{id}` | ✅ | ✅ | ❌ | Mecânicos consultam veículos para realizar reparos |
| **Serviços (Catálogo)** | `POST /servicos`, `PUT`, `DELETE` | ✅ | ❌ | ❌ | Gestão do catálogo e precificação |
| **Serviços (Consulta)** | `GET /servicos`, `GET /servicos/{id}` | ✅ | ✅ | ❌ | Consulta de serviços disponíveis |
| **Peças (Estoque)** | `GET`, `POST`, `PUT /pecas` | ✅ | ✅ | ❌ | Consulta e movimentação de entradas/baixas de estoque |
| **Peças (Exclusão)** | `DELETE /pecas/{id}` | ✅ | ❌ | ❌ | Exclusão definitiva de peças restrita ao Atendente |
| **Ordens de Serviço (Gestão)** | `GET`, `POST`, `PUT /ordens-servico` | ✅ | ✅ | ❌ | Abertura, atualização de itens e consulta operacional |
| **OS (Status / Orçamento)** | `PATCH /status`, `POST /enviar-orcamento` | ✅ | ✅ | ❌ | Operações da equipe interna |
| **OS (Consulta Pública / Aprovação)** | `GET /acompanhamento-os`, `POST /aprovar-orcamento` | ✅ | ✅ | ✅ | Acesso público do cliente via código/placa |

> **Nota de Segurança:** Qualquer tentativa de acessar um endpoint com perfil não autorizado resulta em resposta imediata com status `403 Forbidden` (`{"erro": "Acesso negado para este perfil de usuário."}`).

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

# 4. Rodar teste de um repositório específico (ex: Ordens de Serviço):
docker compose exec app ./vendor/bin/phpunit src/tests/Integration/Repositories/OrdemServicoPdoRepositoryTest.php --testdox
```

> **Nota para Avaliação:** Os testes de integração utilizam uma base de dados isolada (`oficina_mecanica_test`) criada automaticamente pelo Docker, utilizando transações com rollback para garantir que nenhum dado residual permaneça no banco após os testes.

---

## 🏛️ Arquitetura em Camadas (Clean Architecture)

- **`src/Domain/`**: Entidades de negócio, Value Objects e validações centrais (`Validator.php`).
- **`src/Application/`**: Casos de uso (UseCases) da aplicação orquestrando regras e interfaces.
- **`src/Repositories/`**: Interfaces e implementações PDO de persistência em banco.
- **`src/Http/`**: Roteador, Controllers REST e Middlewares de segurança (`AuthMiddleware`).
- **`src/Infrastructure/`**: Conexão com banco (`Connection`), serviço de JWT (`JwtService`), adaptadores externos e Swagger.
- **`src/tests/`**: Suíte de testes automatizados (Unitários e de Integração com PHPUnit).
