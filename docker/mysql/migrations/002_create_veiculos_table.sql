-- Migration: Criação da tabela de veiculos
-- Data: 2026-10-23

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
    -- impede deletar um cliente que possui veículos cadastrados efeito CASCADE
    CONSTRAINT fk_veiculos_cliente
        FOREIGN KEY (cliente_id)
        REFERENCES usuarios (id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;