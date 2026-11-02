-- Executar na base de aplicação e na base de testes existentes.
ALTER TABLE usuarios
    MODIFY cpf VARCHAR(14) NULL,
    ADD COLUMN cnpj VARCHAR(18) NULL AFTER cpf,
    ADD UNIQUE KEY uq_usuarios_cnpj (cnpj),
    ADD CONSTRAINT chk_usuarios_documento CHECK (
        (tipo_usuario = 'cliente' AND ((cpf IS NOT NULL AND cnpj IS NULL) OR (cpf IS NULL AND cnpj IS NOT NULL)))
        OR (tipo_usuario <> 'cliente' AND cnpj IS NULL)
    ),
    ADD CONSTRAINT chk_usuarios_documento_vazio CHECK ((cpf IS NULL OR TRIM(cpf) <> '') AND (cnpj IS NULL OR TRIM(cnpj) <> ''));
