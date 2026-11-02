# 🛡️ Relatório de Análise Estática de Segurança (SAST)

Este documento centraliza as evidências e configurações de **Segurança e Análise de Vulnerabilidades (SAST)** da API da Oficina Mecânica, atendendo aos requisitos obrigatórios do **Tech Challenge Fase 1 (SOAT - Software Architecture)**.

---

## 1. Auditoria Estática de Dependências (`composer audit`)

A auditoria de vulnerabilidades em bibliotecas de terceiros foi realizada por meio do comando nativo `composer audit` integrado ao ecossistema PHP.

- **Comando executado:**
  ```bash
  docker compose exec app composer audit
  ```
- **Evidências no repositório:**
  - [composer-audit-report.txt](./composer-audit-report.txt): Relatório direto da execução em linha de comando.
  - [composer-audit.json](./composer-audit.json): Exportação estruturada em JSON para integração contínua (CI/CD).
- **Resultado obtido:**
  ```text
  No security vulnerability advisories found.
  ```
  *(Zero vulnerabilidades conhecidas nas dependências de produção e desenvolvimento).*

---

## 2. Configuração do SonarQube / SonarCloud

O projeto possui configuração formal para scan estático de código através do arquivo localizado na raiz:
- **Arquivo de Configuração:** [`sonar-project.properties`](../../sonar-project.properties)

### Diretrizes de Análise:
- **Escopo do Código de Produção (`sonar.sources`):** Varredura completa da camada de Domínio, Casos de Uso, Controladores e Infraestrutura (`src/`).
- **Exclusões (`sonar.exclusions`):** Pastas de documentação (`docs/`), dependências de terceiros (`vendor/`) e ponto de entrada (`public/`).
- **Suíte de Testes (`sonar.tests`):** Varredura isolada da suíte de testes unitários e de integração (`src/tests/`).
- **Versão da Linguagem:** PHP 8.3 com checagem estrita de tipos (`declare(strict_types=1)`).

---

## 3. Práticas de Segurança Implementadas no Código

Além das ferramentas automatizadas de SAST, a aplicação adota padrões arquiteturais defensivos:

1. **Prevenção contra SQL Injection:**
   - 100% das consultas aos bancos `oficina_mecanica` e `oficina_mecanica_test` utilizam PDO com *Prepared Statements* e *parameter binding*, impossibilitando injeção de SQL.
2. **Autenticação Segura (JWT):**
   - Emissão de tokens nativos assinados via algoritmo HMAC-SHA256 (`HS256`).
   - Segredo mantido em variável de ambiente (`JWT_SECRET`) sem vazamento em arquivos estáticos.
3. **Controle de Acesso Baseado em Papéis (RBAC):**
   - Middleware de segurança com controle estrito entre perfis `atendente` e `mecanico`, retornando `403 Forbidden` em acessos indevidos.
4. **Proteção de Senhas:**
   - Armazenamento de credenciais usando hash com salt através do algoritmo seguro `PASSWORD_BCRYPT`.
5. **Sanitização e Validação no Domínio:**
   - Validação matemática de CPF com verificação de dígitos verificadores.
   - Validação de placas por expressão regular (padrão tradicional e Mercosul).

