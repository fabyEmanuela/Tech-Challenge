<?php

namespace Domain\Usuario;

enum TipoUsuario: string
{
    case CLIENTE    = 'cliente';
    case MECANICO   = 'mecanico';
    case ATENDENTE  = 'atendente';

    public function label(): string
    {
        return match($this) {
            self::CLIENTE    => 'Cliente',
            self::MECANICO   => 'Mecânico',
            self::ATENDENTE  => 'Atendente',
        };
    }
}
