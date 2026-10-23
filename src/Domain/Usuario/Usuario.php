<?php

namespace Domain\Usuario;

class Usuario
{
    public function __construct(
        private ?int       $id,
        private string     $nome,
        private string     $email,
        private ?string    $telefone,
        private ?string    $cpf,
        private TipoUsuario $tipoUsuario,
        private ?string    $senhaHash,
        private bool       $ativo,
        private ?string    $criadoEm,
        private ?string    $atualizadoEm,
    ) {}

    // --- Getters ---

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNome(): string
    {
        return $this->nome;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getTelefone(): ?string
    {
        return $this->telefone;
    }

    public function getCpf(): ?string
    {
        return $this->cpf;
    }

    public function getTipoUsuario(): TipoUsuario
    {
        return $this->tipoUsuario;
    }

    public function getSenhaHash(): ?string
    {
        return $this->senhaHash;
    }

    public function isAtivo(): bool
    {
        return $this->ativo;
    }

    public function getCriadoEm(): ?string
    {
        return $this->criadoEm;
    }

    public function getAtualizadoEm(): ?string
    {
        return $this->atualizadoEm;
    }

    // --- Setters ---

    public function setNome(string $nome): void
    {
        $this->nome = $nome;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function setTelefone(?string $telefone): void
    {
        $this->telefone = $telefone;
    }

    public function setCpf(?string $cpf): void
    {
        $this->cpf = $cpf;
    }

    public function setTipoUsuario(TipoUsuario $tipoUsuario): void
    {
        $this->tipoUsuario = $tipoUsuario;
    }

    public function setSenhaHash(?string $senhaHash): void
    {
        $this->senhaHash = $senhaHash;
    }

    public function setAtivo(bool $ativo): void
    {
        $this->ativo = $ativo;
    }

    /**
     * Serializa o usuário para array (sem expor senha_hash).
     */
    public function paraArray(): array
    {
        return [
            'id'           => $this->id,
            'nome'         => $this->nome,
            'email'        => $this->email,
            'telefone'     => $this->telefone,
            'cpf'          => $this->cpf,
            'tipo_usuario' => $this->tipoUsuario->value,
            'ativo'        => $this->ativo,
            'criado_em'    => $this->criadoEm,
            'atualizado_em'=> $this->atualizadoEm,
        ];
    }
}
