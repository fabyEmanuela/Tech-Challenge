-- Migration: Criação da tabela de serviços
-- Data: 2026-10-23

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
