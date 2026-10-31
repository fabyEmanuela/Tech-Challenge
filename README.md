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

## 🔐 Autenticação & Credenciais Padrão para Avaliação

A API utiliza autenticação **Bearer Token (JWT)** para proteger as rotas de negócio. Para que os avaliadores possam testar os endpoints imediatamente após o `docker compose up`, o banco de dados já inicializa com as seguintes credenciais padrão:

| Perfil | E-mail | Senha | Finalidade / Permissões |
| :--- | :--- | :--- | :--- |
| **Atendente (Admin)** | `admin@oficina.com` | `123456` | Gerenciamento de clientes, veículos, serviços e peças |
| **Mecânico** | `mecanico@oficina.com` | `123456` | Consulta e execução de serviços e peças da oficina |

### Como se autenticar pelo Swagger UI:
1. Acesse **[http://localhost:8081](http://localhost:8081)**.
2. Expanda o endpoint `POST /login` (sob a tag **Autenticação**), clique em **Try it out** e clique em **Execute** (já vem pré-preenchido com `admin@oficina.com` e `123456`).
3. Copie o valor de `access_token` retornado.
4. Clique no botão verde **Authorize** (no topo direito da página do Swagger), cole o token no campo de texto e clique em **Authorize**.
5. Pronto! Agora todas as rotas protegidas responderão com sucesso.

---

## 🧪 Como Executar a Suíte de Testes Unitários

Para rodar os testes unitários da aplicação via PHPUnit dentro do container Docker:

```bash
docker compose exec app ./vendor/bin/phpunit --testdox
```

---

## 🏛️ Arquitetura em Camadas (Clean Architecture)

- **`src/Domain/`**: Entidades de negócio, Value Objects e validações centrais (`Validator.php`).
- **`src/Application/`**: Casos de uso (UseCases) da aplicação orquestrando regras e interfaces.
- **`src/Repositories/`**: Interfaces e implementações PDO de persistência em banco.
- **`src/Http/`**: Roteador, Controllers REST e Middlewares de segurança (`AuthMiddleware`).
- **`src/Infrastructure/`**: Conexão com banco (`Connection`), serviço de JWT (`JwtService`) e Swagger.
- **`src/tests/`**: Suíte de testes unitários automatizados com PHPUnit.
