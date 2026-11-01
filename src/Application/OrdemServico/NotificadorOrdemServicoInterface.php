<?php

declare(strict_types=1);

namespace Application\OrdemServico;

use Domain\OrdemServico\OrdemServico;
use Domain\OrdemServico\StatusOrdemServico;

interface NotificadorOrdemServicoInterface
{
    public function notificar(OrdemServico $os, ?StatusOrdemServico $anterior): void;
}
