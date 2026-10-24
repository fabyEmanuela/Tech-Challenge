<?php

declare(strict_types=1);

namespace Domain\Exceptions;

use InvalidArgumentException;

/**
 * Exceção lançada quando ocorre violação de regras de validação no Domínio.
 */
class ValidacaoException extends InvalidArgumentException
{
}
