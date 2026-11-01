-- Inicialização do banco de dados da Oficina Mecânica
-- Este script é executado automaticamente ao subir o container MySQL

CREATE DATABASE IF NOT EXISTS oficina_mecanica
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE oficina_mecanica;

-- -----------------------------------------------------------------------
-- Tabela: usuarios
-- Armazena clientes, mecânicos e atendentes
-- -----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
    id            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    nome          VARCHAR(100)    NOT NULL,
    email         VARCHAR(150)    NOT NULL UNIQUE,
    telefone      VARCHAR(20)     NULL,
    cpf           VARCHAR(14)     NOT NULL UNIQUE,
    tipo_usuario  ENUM('cliente', 'mecanico', 'atendente') NOT NULL DEFAULT 'cliente',
    senha_hash    VARCHAR(255)    NULL COMMENT 'Hash bcrypt — usado apenas por mecânico e atendente',
    ativo         TINYINT(1)      NOT NULL DEFAULT 1,
    criado_em     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_tipo_usuario (tipo_usuario),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------
-- Tabela: veiculos
-- Armazena os veículos dos clientes
-- -----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS veiculos (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    cliente_id      INT UNSIGNED    NOT NULL COMMENT 'FK para usuarios.id (deve ser tipo cliente)',
    placa           VARCHAR(8)      NOT NULL UNIQUE COMMENT 'Placa formato Mercosul (ABC1D23) ou Antiga (ABC1234/ABC-1234)',
    marca           VARCHAR(50)     NOT NULL COMMENT 'Ex: Volkswagen, Chevrolet, Toyota',
    modelo          VARCHAR(50)     NOT NULL COMMENT 'Ex: Gol 1.0, Corolla XEi, Onix Turbo',
    ano_fabricacao  SMALLINT UNSIGNED NOT NULL COMMENT 'Ano de fabricação do veículo',
    ano_modelo      SMALLINT UNSIGNED NOT NULL COMMENT 'Ano do modelo do veículo',
    cor             VARCHAR(30)     NULL COMMENT 'Ex: Preto, Branco, Prata',
    criado_em       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE INDEX uq_veiculos_placa (placa),
    INDEX idx_veiculos_cliente_id (cliente_id),
    CONSTRAINT fk_veiculos_cliente
        FOREIGN KEY (cliente_id)
        REFERENCES usuarios (id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------
-- Tabela: servicos
-- Armazena informações sobre os serviços oferecidos
-- -----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS servicos (
    id                   INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    nome                 VARCHAR(100)    NOT NULL,
    descricao            VARCHAR(255)    NULL,
    preco                DECIMAL(10, 2)  NOT NULL,
    tempo_estimado_horas DECIMAL(10, 2)  NOT NULL,
    ativo                TINYINT(1)      NOT NULL DEFAULT 1,
    criado_em            DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------
-- Tabela: pecas
-- Armazena peças de reposição e insumos para veículos
-- -----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS pecas (
    id                  INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    nome                VARCHAR(100)     NOT NULL COMMENT 'Nome da peça (ex: Filtro de Óleo)',
    descricao           VARCHAR(255)     NULL,
    valor_unitario      DECIMAL(10, 2)   NOT NULL COMMENT 'Preço unitário da peça',
    quantidade_estoque  INT              NOT NULL DEFAULT 0 COMMENT 'Saldo atual em estoque',
    estoque_minimo      INT              NOT NULL DEFAULT 0 COMMENT 'Alerta para reposição',
    ativo               TINYINT(1)       NOT NULL DEFAULT 1,
    criado_em           DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em       DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_pecas_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------
-- Carga Inicial (Seed de Usuários Padrão para Autenticação e Avaliação)
-- Senha de acesso para ambos: 123456
-- -----------------------------------------------------------------------
INSERT INTO usuarios (nome, email, telefone, cpf, tipo_usuario, senha_hash, ativo) VALUES
('Administrador Oficina', 'admin@oficina.com', '(11) 98765-4321', '111.444.777-35', 'atendente', '$2y$10$.gp1cxGzaoCRjqEYLiGNjO09IFR4W3IuPs7i8KdnW0DGBgb9daTFK', 1),
('Mecânico Oficina', 'mecanico@oficina.com', '(11) 98765-4322', '222.555.888-46', 'mecanico', '$2y$10$.gp1cxGzaoCRjqEYLiGNjO09IFR4W3IuPs7i8KdnW0DGBgb9daTFK', 1)
ON DUPLICATE KEY UPDATE nome = VALUES(nome);

-- -----------------------------------------------------------------------
-- Tabela: ordens_servico
-- Gerenciamento de Ordens de Serviço (OS)
-- -----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ordens_servico (
    id                   INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    numero_os            VARCHAR(30)      NOT NULL,
    cliente_id           INT UNSIGNED     NOT NULL,
    veiculo_id           INT UNSIGNED     NOT NULL,
    status               ENUM(
        'recebida',
        'em_diagnostico',
        'aguardando_aprovacao',
        'em_execucao',
        'finalizada',
        'entregue'
    ) NOT NULL DEFAULT 'recebida',
    descricao_problema   TEXT             NULL COMMENT 'Relato inicial do cliente',
    diagnostico          TEXT             NULL COMMENT 'Diagnóstico detalhado da oficina',
    valor_servicos       DECIMAL(10, 2)   NOT NULL DEFAULT 0.00,
    valor_pecas          DECIMAL(10, 2)   NOT NULL DEFAULT 0.00,
    valor_total          DECIMAL(10, 2)   NOT NULL DEFAULT 0.00,
    orcamento_enviado_em DATETIME         NULL,
    orcamento_aprovado_em DATETIME        NULL,
    finalizado_em        DATETIME         NULL,
    entregue_em          DATETIME         NULL,
    criado_em            DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em        DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE INDEX uq_ordens_servico_numero (numero_os),
    INDEX idx_os_cliente_id (cliente_id),
    INDEX idx_os_veiculo_id (veiculo_id),
    INDEX idx_os_status (status),
    CONSTRAINT fk_os_cliente
        FOREIGN KEY (cliente_id)
        REFERENCES usuarios (id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT fk_os_veiculo
        FOREIGN KEY (veiculo_id)
        REFERENCES veiculos (id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ordem_servico_contadores (
    data_referencia DATE         NOT NULL,
    ultimo_numero   INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (data_referencia)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------
-- Tabela: ordem_servico_servicos
-- Itens de serviços inclusos na OS
-- -----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ordem_servico_servicos (
    id                   INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    ordem_servico_id     INT UNSIGNED    NOT NULL,
    servico_id           INT UNSIGNED    NULL COMMENT 'FK opcional para tabela de servicos',
    nome                 VARCHAR(100)    NOT NULL,
    descricao            VARCHAR(255)    NULL,
    quantidade           INT UNSIGNED    NOT NULL DEFAULT 1,
    preco_unitario       DECIMAL(10, 2)  NOT NULL DEFAULT 0.00,
    tempo_estimado_horas DECIMAL(10, 2)  NOT NULL DEFAULT 0.00,
    subtotal             DECIMAL(10, 2)  NOT NULL DEFAULT 0.00,
    criado_em            DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_os_servicos_os (ordem_servico_id),
    INDEX idx_os_servicos_servico (servico_id),
    CONSTRAINT fk_os_servicos_os
        FOREIGN KEY (ordem_servico_id)
        REFERENCES ordens_servico (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_os_servicos_servico
        FOREIGN KEY (servico_id)
        REFERENCES servicos (id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------
-- Tabela: ordem_servico_pecas
-- Itens de peças e insumos inclusos na OS
-- -----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ordem_servico_pecas (
    id                   INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    ordem_servico_id     INT UNSIGNED    NOT NULL,
    peca_id              INT UNSIGNED    NULL COMMENT 'FK opcional para tabela de pecas',
    descricao            VARCHAR(150)    NOT NULL,
    quantidade           DECIMAL(10, 2)  NOT NULL DEFAULT 1.00,
    preco_unitario       DECIMAL(10, 2)  NOT NULL DEFAULT 0.00,
    subtotal             DECIMAL(10, 2)  NOT NULL DEFAULT 0.00,
    criado_em            DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_os_pecas_os (ordem_servico_id),
    INDEX idx_os_pecas_peca (peca_id),
    CONSTRAINT fk_os_pecas_os
        FOREIGN KEY (ordem_servico_id)
        REFERENCES ordens_servico (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_os_pecas_peca
        FOREIGN KEY (peca_id)
        REFERENCES pecas (id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------
-- Tabela: ordem_servico_historico
-- Registro histórico de alterações de status para auditoria e acompanhamento
-- -----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ordem_servico_historico (
    id                   INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    ordem_servico_id     INT UNSIGNED    NOT NULL,
    status_de            VARCHAR(30)     NULL,
    status_para          VARCHAR(30)     NOT NULL,
    observacao           VARCHAR(255)    NULL,
    criado_em            DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_os_historico_os (ordem_servico_id),
    CONSTRAINT fk_os_historico_os
        FOREIGN KEY (ordem_servico_id)
        REFERENCES ordens_servico (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------
-- Base de Dados Exclusiva para Testes de Integração
-- -----------------------------------------------------------------------
CREATE DATABASE IF NOT EXISTS oficina_mecanica_test
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS oficina_mecanica_test.usuarios LIKE oficina_mecanica.usuarios;
CREATE TABLE IF NOT EXISTS oficina_mecanica_test.veiculos LIKE oficina_mecanica.veiculos;
CREATE TABLE IF NOT EXISTS oficina_mecanica_test.servicos LIKE oficina_mecanica.servicos;
CREATE TABLE IF NOT EXISTS oficina_mecanica_test.pecas LIKE oficina_mecanica.pecas;
CREATE TABLE IF NOT EXISTS oficina_mecanica_test.ordens_servico LIKE oficina_mecanica.ordens_servico;
CREATE TABLE IF NOT EXISTS oficina_mecanica_test.ordem_servico_contadores LIKE oficina_mecanica.ordem_servico_contadores;
CREATE TABLE IF NOT EXISTS oficina_mecanica_test.ordem_servico_servicos LIKE oficina_mecanica.ordem_servico_servicos;
CREATE TABLE IF NOT EXISTS oficina_mecanica_test.ordem_servico_pecas LIKE oficina_mecanica.ordem_servico_pecas;
CREATE TABLE IF NOT EXISTS oficina_mecanica_test.ordem_servico_historico LIKE oficina_mecanica.ordem_servico_historico;
