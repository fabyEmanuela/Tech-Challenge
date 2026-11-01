<?php

declare(strict_types=1);

namespace Application\OrdemServico;

use Domain\OrdemServico\OrdemServico;
use Repositories\Peca\PecaRepositoryInterface;

class VerificarEstoqueOS
{
    public function __construct(private PecaRepositoryInterface $pecas) {}

    public function executar(OrdemServico $os, ?array $pecasBloqueadas = null): array
    {
        $quantidades = [];
        foreach ($os->pecas() as $item) {
            $id = $item->pecaId();
            $quantidades[$id] = ($quantidades[$id] ?? 0) + $item->quantidade();
        }

        $avisos = [];
        $faltas = [];
        foreach ($quantidades as $id => $quantidade) {
            $peca = $pecasBloqueadas === null ? $this->pecas->buscarPorId((int) $id) : ($pecasBloqueadas[$id] ?? null);

            if (!$peca || $peca->getQuantidadeEstoque() < $quantidade) {
                $faltas[] = sprintf('Peça %s: estoque %s (disponível: %d; solicitado: %s).', $peca?->getNome() ?? $id, ($peca?->getQuantidadeEstoque() ?? 0) === 0 ? 'zerado' : 'insuficiente', $peca?->getQuantidadeEstoque() ?? 0, $quantidade);
            }

            if ($peca && ($peca->getQuantidadeEstoque() - $quantidade <= $peca->getEstoqueMinimo())) {
                $avisos[] = sprintf('Peça %s: é necessário repor o estoque para não acabar (atual: %d; mínimo: %d).', $peca->getNome(), $peca->getQuantidadeEstoque(), $peca->getEstoqueMinimo());
            }
        }
        
        return ['avisos' => array_merge($faltas, $avisos), 'faltas' => $faltas];
    }
}
