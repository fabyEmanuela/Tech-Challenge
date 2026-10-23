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
