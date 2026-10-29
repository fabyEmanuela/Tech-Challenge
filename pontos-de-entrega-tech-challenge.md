# Checklist de Entregáveis — Tech Challenge Fase 1 (SOAT)

> **Tecnologias Selecionadas:**
> - **Linguagem:** PHP Puro (Monólito em camadas com PDO nativo, sem frameworks pesados)
> - **Banco de Dados:** MySQL 8.0 (Relacional com integridade referencial e transações ACID)
> - **Infraestrutura:** Docker (Apache + PHP 8.x + MySQL) & Suporte Local (WAMP/phpMyAdmin)

---

## 📄 1. Estrutura da Entrega Final (PDF Único)

A entrega deve ser realizada por meio de **um único arquivo PDF** subido na plataforma do curso, contendo:

- [ ] **Página 1 (Identificação Completa):**
  - Curso: *SOAT — Software Architecture*
  - Fase: *1*
  - Número do grupo
  - Nome completo e RM de todos os integrantes
  - Username do Discord de todos os integrantes
  - Linguagem e Tecnologias: **PHP 8.x Puro, MySQL 8.0, Docker, PDO, JWT, PHPUnit, Swagger**
- [ ] **Link do Repositório GitHub (Público):**
  - Informar a branch (`main`) e o **hash do commit final** exato entregue.
- [ ] **Link do Vídeo no YouTube (Público, máximo 15 min).**
- [ ] **Link da Documentação DDD no Miro (Público, sem necessidade de solicitar permissão).**

---

## 💻 2. Requisitos do Repositório GitHub

- [ ] **Repositório Público:** Acessível durante todo o período de correção.
- [ ] **Proteção de Branch:** Branch `main` protegida, com todas as alterações chegando via **Pull Requests (PRs)**.
- [ ] **Segurança de Dados:** Repositório limpo, sem senhas, tokens JWT ou credenciais expostas no código (usar variáveis de ambiente `.env` com `.env.example`).
- [ ] **Arquivo `README.md` Completo:**
  - Objetivo e descrição da solução da oficina mecânica.
  - Arquitetura em camadas (Domain, Application, Infrastructure).
  - Justificativa técnica da escolha do banco de dados **MySQL**.
  - Instruções de execução local com 1 comando via Docker Compose (`docker-compose up -d`).
  - Instruções para execução dos testes automatizados (PHPUnit).
  - Instruções para acesso ao Swagger / OpenAPI.
  - Credenciais e usuários de demonstração para testes das APIs administrativas.
  - Estrutura de diretórios do projeto.
  - Links da documentação DDD no Miro e do vídeo de apresentação no YouTube.

---

## 🎨 3. Documentação DDD (Miro & Arquivos no Repositório)

A documentação deve ser disponibilizada via link do Miro e também exportada na pasta `docs/ddd/` do repositório:

- [ ] `docs/ddd/event-storming.pdf` (Event Storming dos 3 fluxos principais):
  - **Fluxo 1:** Criação e Orçamento da Ordem de Serviço (OS).
  - **Fluxo 2:** Acompanhamento e Máquina de Status da OS.
  - **Fluxo 3:** Gestão de Peças, Insumos e Estoque (com Fornecedor Externo).
  - **Mapeamento de Cores:** Eventos 🟧, Comandos 🟦, Atores 🟨, Agregados 🟩, Políticas/Regras 🟪, Value Objects ⚪ e Exceções 🟥.
- [ ] `docs/ddd/c4-model.pdf` (Diagramas C4 Model até o Nível 3):
  - **C1 (Contexto):** Cliente e Funcionário interagindo com o Sistema da Oficina.
  - **C2 (Contêineres):** Contêiner PHP/Apache, Contêiner MySQL 8.0, Swagger e Mailpit local.
  - **C3 (Componentes):** Camadas do Monólito (Controllers, UseCases, Domain Entities, Value Objects, PDO Repositories).
- [ ] `docs/ddd/linguagem-ubiqua.md`:
  - Tabela com o glossário de termos do negócio da oficina e o de-para para as classes PHP.

---

## ⚙️ 4. Funcionalidades Obrigatórias da Oficina Mecânica

### A. Criação da Ordem de Serviço (OS)
- [ ] **Identificação do Cliente:** Busca/Cadastro por CPF ou CNPJ com validação matemática dos dígitos verificadores.
- [ ] **Cadastro do Veículo:** Vínculo obrigatório com o cliente (`cliente_id`), registro de placa (com validação Regex Antiga/Mercosul), marca, modelo e ano.
- [ ] **Inclusão de Itens:** Seleção de serviços solicitados e peças/insumos necessários.
- [ ] **Geração Automática de Orçamento:** Cálculo do valor total (`Serviços + Peças`).
- [ ] **Aprovação / Rejeição:** Disponibilização e registro formal de aprovação ou rejeição do orçamento pelo cliente.

### B. Acompanhamento e Ciclo de Status da OS
- [ ] **Máquina de Status Opcional/Obrigatória:**
  $$\text{Recebida} \longrightarrow \text{Em diagnóstico} \longrightarrow \text{Aguardando aprovação} \longrightarrow \text{Em execução} \longrightarrow \text{Finalizada} \longrightarrow \text{Entregue}$$
- [ ] **Automação de Transição:** Controle estrito das regras de transição de status no código PHP.
- [ ] **Consulta Pública do Cliente:** Endpoint de API pública para o cliente acompanhar o progresso da sua OS via código/ID.

### C. Gestão Administrativa
- [ ] **CRUD de Clientes** (API REST).
- [ ] **CRUD de Veículos** (API REST).
- [ ] **CRUD de Serviços** (API REST).
- [ ] **CRUD de Peças e Insumos** (API REST com controle de saldo de estoque e baixa automática).
- [ ] **Listagem e Detalhamento de OSs.**
- [ ] **Métrica do Tempo Médio de Execução:** Cálculo do tempo médio entre o início (`Em execução`) e o término (`Finalizada`).

---

## 🔒 5. Requisitos Técnicos, Segurança e Qualidade

- [ ] **Arquitetura Monolítica em Camadas:**
  - `src/Domain/`: Entidades, Value Objects, Enums e Interfaces em PHP Puro (sem dependência de banco ou frameworks).
  - `src/Application/`: Casos de Uso (UseCases) e DTOs.
  - `src/Infrastructure/`: PDO MySQL Connection, Repositórios SQL, Controllers e Middlewares.
- [ ] **Justificativa do Banco de Dados MySQL:**
  > *Justificativa:* O MySQL 8.0 foi escolhido por ser um banco de dados relacional robusto que garante integridade referencial por chaves estrangeiras (`FOREIGN KEY`), controle estrito de transações ACID (essencial para evitar inconsistências em orçamentos e baixa de estoque) e compatibilidade nativa com a extensão PDO do PHP puro sem necessidade de ORM.
- [ ] **Autenticação JWT:** Proteção com token Bearer JWT para todas as rotas administrativas.
- [ ] **Validação de Dados Sensíveis:** Validações de CPF/CNPJ e Placa encapsuladas em *Value Objects* na camada de Domínio.
- [ ] **Testes Automatizados (Meta: 80%+ de Cobertura):**
  - Testes Unitários em PHPUnit cobrindo as regras do Domínio (máquina de status, orçamento, validações).
  - Testes de Integração testando consultas e repositórios PDO.
- [ ] **Análise de Vulnerabilidades (SAST):** Relatório de scan de código (ex: `composer audit`, Snyk, PHPStan ou OWASP) salvo no repositório.
- [ ] **Orquestração Docker:**
  - `Dockerfile`: Imagem PHP 8.x com Apache e extensão `pdo_mysql`.
  - `docker-compose.yml`: Subida simultânea da API em PHP e do banco MySQL 8.0 com script de inicialização `init.sql`.

---

## 🎬 6. Vídeo de Apresentação (YouTube — Máx. 15 Minutos)

O vídeo deve demonstrar na prática:
1. Proposta da solução e arquitetura do monólito em camadas em PHP Puro.
2. Justificativa da escolha do MySQL.
3. Apresentação da documentação DDD no Miro (Event Storming e C4 Model).
4. Subida da aplicação via Docker Compose.
5. Demonstração prática no Swagger do fluxo completo de uma OS (Criação, Diagnóstico, Orçamento, Aprovação, Baixa no Estoque e Mudança de Status).
6. Autenticação JWT nas rotas administrativas.
7. Execução do PHPUnit mostrando a cobertura de testes de 80%+.
8. Apresentação do relatório de análise de vulnerabilidades.
9. Identificação de todos os integrantes do grupo.
