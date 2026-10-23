# Tech-Challenge-fase-1 - Sistema de Gestão de Oficina Mecânica

API REST desenvolvida em PHP puro (sem frameworks pesados), seguindo princípios de Clean Architecture / DDD, orientada a objetos e conteinerizada com Docker.

---

## 🚀 Como Executar o Projeto

1. Copie o arquivo de variáveis de ambiente:
   ```bash
   cp .env.example .env
   ```

2. Suba os containers com Docker Compose:
   ```bash
   docker compose up -d --build
   ```

3. A API estará acessível em:
   - **Base URL:** `http://localhost:8080`

---

## 📬 Testando com Postman

Uma collection completa e pronta para uso foi disponibilizada:
- Arquivo na raiz: [`Tech-Challenge-Fase-1.postman_collection.json`](file:///c:/Users/maria/Documents/Projeto-pos-tech/Tech-Challenge-fase-1/Tech-Challenge-Fase-1.postman_collection.json)
- Arquivo em docs: [`docs/Tech-Challenge-Fase-1.postman_collection.json`](file:///c:/Users/maria/Documents/Projeto-pos-tech/Tech-Challenge-fase-1/docs/Tech-Challenge-Fase-1.postman_collection.json)

### Como importar:
1. Abra o **Postman**.
2. Clique em **Import** (canto superior esquerdo).
3. Selecione o arquivo `Tech-Challenge-Fase-1.postman_collection.json`.
4. A collection será carregada com as seguintes variáveis configuradas:
   - `{{base_url}}`: `http://localhost:8080` (ajuste se necessário)
   - `{{usuario_id}}`: preenchido dinamicamente ao cadastrar um usuário

---

## 📋 Endpoints Disponíveis

| Método | Rota | Descrição |
|---|---|---|
| `GET` | `/usuarios` | Lista todos os usuários cadastrados |
| `GET` | `/usuarios?tipo={tipo}` | Filtra usuários por tipo (`cliente`, `mecanico`, `atendente`) |
| `GET` | `/usuarios/{id}` | Busca detalhes de um usuário específico por ID |
| `POST` | `/usuarios` | Cria um novo usuário |
| `PUT` | `/usuarios/{id}` | Atualiza dados de um usuário existente |
| `DELETE` | `/usuarios/{id}` | Remove um usuário |
| `GET` | `/clientes` | Atalho para listar usuários do tipo cliente |
