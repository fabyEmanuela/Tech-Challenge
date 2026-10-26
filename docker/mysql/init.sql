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
    cpf           VARCHAR(14)     NULL UNIQUE,
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
-- Tabela: serviços
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
