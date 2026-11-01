-- Migration: Criação das tabelas do módulo de Ordem de Serviço (OS)
-- Data: 2026-10-28

USE oficina_mecanica;

-- -----------------------------------------------------------------------
-- Tabela principal: ordens_servico
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
