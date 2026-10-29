-- Migration: Criação da tabela de peças e insumos
-- Data: 2026-10-23

CREATE TABLE IF NOT EXISTS pecas (
    id                  INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    nome                VARCHAR(100)     NOT NULL COMMENT 'Nome da peça (ex: Filtro de Óleo)',
    descricao           VARCHAR(255)     NULL COMMENT 'Descrição opcional da peça/insumo',
    valor_unitario      DECIMAL(10, 2)   NOT NULL COMMENT 'Preço unitário da peça',
    quantidade_estoque  INT              NOT NULL DEFAULT 0 COMMENT 'Saldo atual em estoque',
    estoque_minimo      INT              NOT NULL DEFAULT 0 COMMENT 'Alerta de quantidade mínima para reposição',
    ativo               TINYINT(1)       NOT NULL DEFAULT 1 COMMENT '1 = Ativo, 0 = Inativo',
    criado_em           DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em       DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_pecas_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
