# 📖 Linguagem Ubíqua — Sistema de Gestão de Oficina Mecânica

Este documento formaliza a **Linguagem Ubíqua (Ubiquitous Language)** do domínio de Oficina Mecânica desenvolvida para o **Tech Challenge Fase 1 (SOAT - Software Architecture)** da FIAP.

O objetivo é estabelecer um vocabulário único e inequívoco compartilhado entre especialistas de negócio (mecânicos, atendentes, proprietários da oficina) e a equipe de desenvolvimento de software.

---

##  Tabela de Mapeamento: Negócio ➔ DDD ➔ Código ➔ Banco de Dados

| Termo da Linguagem Ubíqua | Definição no Negócio | Conceito DDD | Classe / Representação no Código PHP | Tabela / Coluna no MySQL |
| :--- | :--- | :--- | :--- | :--- |
| **Usuário** | Pessoa cadastrada no sistema da oficina, podendo atuar como Cliente, Mecânico ou Atendente. | Entidade | `Domain\Usuario\Usuario`<br>*(src/Domain/Usuario/Usuario.php)* | `usuarios` |
| **Tipo de Usuário** | Papel desempenhado pelo usuário no domínio (`cliente`, `mecanico`, `atendente`). | Enum | `Domain\Usuario\TipoUsuario`<br>*(src/Domain/Usuario/TipoUsuario.php)* | `usuarios.tipo_usuario` |
| **Cliente** | Proprietário do veículo que solicita serviços, recebe o orçamento, aprova ou rejeita a execução e acompanha o status da Ordem de Serviço. | Papel de Domínio | `Domain\Usuario\Usuario`<br>*(com TipoUsuario::CLIENTE)* | `usuarios` |
| **Mecânico** | Profissional técnico responsável por realizar o diagnóstico do veículo e executar os serviços e trocas de peças aprovadas na OS. | Papel de Domínio | `Domain\Usuario\Usuario`<br>*(com TipoUsuario::MECANICO)* | `usuarios` |
| **Atendente** | Funcionário responsável pela recepção do cliente, cadastro de veículos, abertura da Ordem de Serviço e entrega do veículo finalizado. | Papel de Domínio | `Domain\Usuario\Usuario`<br>*(com TipoUsuario::ATENDENTE)* | `usuarios` |
| **CPF / CNPJ** | Cadastro de Pessoa Física (11 dígitos) ou Jurídica (14 dígitos), validados pelo cálculo oficial dos dígitos verificadores da Receita Federal. | Value Object / Validação | `Domain\Shared\Validator::cpf()` e `Validator::cnpj()` | `usuarios.cpf` |
| **Veículo** | Automóvel pertencente a um Cliente (`cliente_id`), identificado por sua Placa única, Marca, Modelo e Ano, sobre o qual as Ordens de Serviço são abertas. | Entidade | `Domain\Veiculo\Veiculo`<br>*(src/Domain/Veiculo/Veiculo.php)* | `veiculos` |
| **Placa** | Identificador alfanumérico oficial do veículo, aceitando o padrão tradicional (`ABC-1234`) e o padrão Mercosul (`ABC1D23`). | Value Object | `Domain\Veiculo\Placa`<br>*(src/Domain/Veiculo/Placa.php)* | `veiculos.placa` |
| **Serviço** | Mão de obra técnica tabelada no catálogo da oficina (ex: Alinhamento), contendo nome, descrição, preço base e tempo estimado em horas. | Entidade | `Domain\Servico\Servico`<br>*(src/Domain/Servico/Servico.php)* | `servicos` |
| **Peça / Insumo** | Item físico ou consumível mantido no estoque da oficina, com valor unitário, saldo atual em estoque e estoque mínimo de alerta. | Entidade | `Domain\Peca\Peca`<br>*(src/Domain/Peca/Peca.php)* | `pecas` |
| **Saldo de Estoque** | Quantidade física disponível de uma Peça/Insumo na oficina. Sofre baixa quando utilizada em uma Ordem de Serviço aprovada. | Regra de Negócio | `Domain\Peca\Peca::baixarEstoque()`<br>*(src/Domain/Peca/Peca.php)* | `pecas.quantidade_estoque` |
| **Estoque Mínimo** | Limite de segurança parametrizado por peça que indica quando o saldo atual atingiu nível crítico para reposição. | Regra de Negócio | `Domain\Peca\Peca::estaAbaixoDoEstoqueMinimo()`<br>*(src/Domain/Peca/Peca.php)* | `pecas.estoque_minimo` |
| **Ordem de Serviço (OS)** | Agregado central que registra a entrada de um Veículo, Serviços/Peças necessários, Orçamento calculado e ciclo de atendimento. | Aggregate Root | `Domain\OrdemServico\OrdemServico`<br>*(src/Domain/OrdemServico/OrdemServico.php)* | `ordens_servico` |
| **Orçamento** | Valor total calculado automaticamente pela soma dos Serviços selecionados e das Peças/Insumos necessários. | Regra de Domínio | `Domain\OrdemServico\OrdemServico::recalcularTotais()` | `ordens_servico.valor_total` |
| **Status da OS** | Estado atual da Ordem de Serviço dentro do fluxo operacional da máquina de status obrigatória da oficina. | Enum / State Machine | `Domain\OrdemServico\StatusOrdemServico`<br>*(src/Domain/OrdemServico/StatusOrdemServico.php)* | `ordens_servico.status` |
| **Item de Serviço da OS** | Serviço do catálogo incluído em uma Ordem de Serviço específica, com nome, descrição, quantidade, preço unitário e tempo estimado em horas registrados no item. Seu subtotal corresponde à quantidade multiplicada pelo preço unitário. | Regra de Domínio | `Domain\OrdemServico\ItemServicoOS` | `ordem_servico_servicos` |
| **Item de Peça/Insumo da OS** | Peça ou insumo do catálogo incluído em uma Ordem de Serviço específica, com descrição, quantidade e preço unitário registrados no item. Tem subtotal calculado pela multiplicação da quantidade pelo preço unitário. | Regra de Domínio | `Domain\OrdemServico\ItemPecaOS` | `ordem_servico_pecas` |
| **Histórico de Status da OS** | Registro das mudanças de estado do atendimento, contendo status anterior, novo status, observação e data de registro. | Regra de Domínio | `Domain\OrdemServico\HistoricoStatusOS` | `ordem_servico_historico` |
| **Tempo Estimado Total** | Intervalo de tempo medido entre o início efetivo do reparo (`Em execução`) e a conclusão técnica (`Finalizada`). | Regra de Domínio | `Domain\OrdemServico\OrdemServico::tempoEstimadoTotalHoras()` | *(Calculado dinamicamente / Auditoria)* |
