<?php

declare(strict_types=1);

namespace Repositories\OrdemServico;

use Domain\OrdemServico\HistoricoStatusOS;
use Domain\OrdemServico\ItemPecaOS;
use Domain\OrdemServico\ItemServicoOS;
use Domain\OrdemServico\OrdemServico;
use Domain\OrdemServico\StatusOrdemServico;
use Domain\Shared\Validator;
use PDO;
use Throwable;

class OrdemServicoPdoRepository implements OrdemServicoRepositoryInterface
{
    public function __construct(private PDO $pdo) {}

    public function salvar(OrdemServico $os): OrdemServico
    {
        $this->pdo->beginTransaction();

        try {
            $os->recalcularTotais();

            $sql = "INSERT INTO ordens_servico (
                        numero_os, cliente_id, veiculo_id, status, descricao_problema,
                        diagnostico, valor_servicos, valor_pecas, valor_total,
                        orcamento_enviado_em, orcamento_aprovado_em, finalizado_em, entregue_em
                    ) VALUES (
                        :numero_os, :cliente_id, :veiculo_id, :status, :descricao_problema,
                        :diagnostico, :valor_servicos, :valor_pecas, :valor_total,
                        :orcamento_enviado_em, :orcamento_aprovado_em, :finalizado_em, :entregue_em
                    )";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':numero_os'            => $os->numeroOs(),
                ':cliente_id'           => $os->clienteId(),
                ':veiculo_id'           => $os->veiculoId(),
                ':status'               => $os->status()->value,
                ':descricao_problema'   => $os->descricaoProblema(),
                ':diagnostico'          => $os->diagnostico(),
                ':valor_servicos'       => $os->valorServicos(),
                ':valor_pecas'          => $os->valorPecas(),
                ':valor_total'          => $os->valorTotal(),
                ':orcamento_enviado_em' => $os->orcamentoEnviadoEm(),
                ':orcamento_aprovado_em'=> $os->orcamentoAprovadoEm(),
                ':finalizado_em'        => $os->finalizadoEm(),
                ':entregue_em'          => $os->entregueEm(),
            ]);

            $osId = (int) $this->pdo->lastInsertId();
            $os->definirId($osId);

            // Inserir serviços
            $stmtServico = $this->pdo->prepare(
                "INSERT INTO ordem_servico_servicos (
                    ordem_servico_id, servico_id, nome, descricao, quantidade,
                    preco_unitario, tempo_estimado_horas, subtotal
                ) VALUES (
                    :ordem_servico_id, :servico_id, :nome, :descricao, :quantidade,
                    :preco_unitario, :tempo_estimado_horas, :subtotal
                )"
            );

            foreach ($os->servicos() as $servico) {
                $stmtServico->execute([
                    ':ordem_servico_id'     => $osId,
                    ':servico_id'           => $servico->servicoId(),
                    ':nome'                 => $servico->nome(),
                    ':descricao'            => $servico->descricao(),
                    ':quantidade'           => $servico->quantidade(),
                    ':preco_unitario'       => $servico->precoUnitario(),
                    ':tempo_estimado_horas' => $servico->tempoEstimadoHoras(),
                    ':subtotal'             => $servico->subtotal(),
                ]);
            }

            // Inserir peças
            $stmtPeca = $this->pdo->prepare(
                "INSERT INTO ordem_servico_pecas (
                    ordem_servico_id, peca_id, descricao, quantidade, preco_unitario, subtotal
                ) VALUES (
                    :ordem_servico_id, :peca_id, :descricao, :quantidade, :preco_unitario, :subtotal
                )"
            );

            foreach ($os->pecas() as $peca) {
                $stmtPeca->execute([
                    ':ordem_servico_id' => $osId,
                    ':peca_id'          => $peca->pecaId(),
                    ':descricao'        => $peca->descricao(),
                    ':quantidade'       => $peca->quantidade(),
                    ':preco_unitario'   => $peca->precoUnitario(),
                    ':subtotal'         => $peca->subtotal(),
                ]);
            }

            // Registrar histórico inicial
            $stmtHist = $this->pdo->prepare(
                "INSERT INTO ordem_servico_historico (ordem_servico_id, status_de, status_para, observacao)
                 VALUES (:os_id, NULL, :status_para, :observacao)"
            );
            $stmtHist->execute([
                ':os_id'       => $osId,
                ':status_para' => $os->status()->value,
                ':observacao'  => 'Ordem de serviço criada com sucesso.',
            ]);

            $this->pdo->commit();

            return $this->buscarPorId($osId) ?? $os;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function atualizar(OrdemServico $os, ?HistoricoStatusOS $historico = null): OrdemServico
    {
        $this->pdo->beginTransaction();

        try {
            $os->recalcularTotais();
            $osId = $os->id();

            $sql = "UPDATE ordens_servico SET
                        status = :status,
                        descricao_problema = :descricao_problema,
                        diagnostico = :diagnostico,
                        valor_servicos = :valor_servicos,
                        valor_pecas = :valor_pecas,
                        valor_total = :valor_total,
                        orcamento_enviado_em = :orcamento_enviado_em,
                        orcamento_aprovado_em = :orcamento_aprovado_em,
                        finalizado_em = :finalizado_em,
                        entregue_em = :entregue_em
                    WHERE id = :id";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':id'                   => $osId,
                ':status'               => $os->status()->value,
                ':descricao_problema'   => $os->descricaoProblema(),
                ':diagnostico'          => $os->diagnostico(),
                ':valor_servicos'       => $os->valorServicos(),
                ':valor_pecas'          => $os->valorPecas(),
                ':valor_total'          => $os->valorTotal(),
                ':orcamento_enviado_em' => $os->orcamentoEnviadoEm(),
                ':orcamento_aprovado_em'=> $os->orcamentoAprovadoEm(),
                ':finalizado_em'        => $os->finalizadoEm(),
                ':entregue_em'          => $os->entregueEm(),
            ]);

            // Atualiza itens de serviços (remove antigos e grava a lista atualizada)
            $this->sincronizarServicos($osId, $os->servicos());

            // Atualiza itens de peças
            $this->sincronizarPecas($osId, $os->pecas());

            if ($historico !== null) {
                $this->registrarHistorico(
                    $osId,
                    $historico->statusDe(),
                    $historico->statusPara(),
                    $historico->observacao()
                );
            }

            $this->pdo->commit();

            return $this->buscarPorId($osId) ?? $os;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function buscarPorId(int $id): ?OrdemServico
    {
        $stmt = $this->pdo->prepare("SELECT * FROM ordens_servico WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return $this->montarCompleto($row);
    }

    public function buscarPorNumero(string $numeroOs): ?OrdemServico
    {
        $stmt = $this->pdo->prepare("SELECT * FROM ordens_servico WHERE numero_os = :numero LIMIT 1");
        $stmt->execute([':numero' => trim($numeroOs)]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return $this->montarCompleto($row);
    }

    public function listar(array $filtros = []): array
    {
        $sql = "SELECT os.*, 
                       u.nome AS cliente_nome, u.cpf AS cliente_cpf, u.email AS cliente_email, u.telefone AS cliente_telefone,
                       v.placa AS veiculo_placa, v.marca AS veiculo_marca, v.modelo AS veiculo_modelo, 
                       COALESCE(v.ano_modelo, v.ano_fabricacao) AS veiculo_ano, v.cor AS veiculo_cor
                FROM ordens_servico os
                JOIN usuarios u ON os.cliente_id = u.id
                JOIN veiculos v ON os.veiculo_id = v.id";

        $where = [];
        $params = [];

        if (!empty($filtros['status'])) {
            $where[] = "os.status = :status";
            $params[':status'] = $filtros['status'];
        }

        if (!empty($filtros['cliente_id'])) {
            $where[] = "os.cliente_id = :cliente_id";
            $params[':cliente_id'] = (int) $filtros['cliente_id'];
        }

        if (!empty($filtros['veiculo_id'])) {
            $where[] = "os.veiculo_id = :veiculo_id";
            $params[':veiculo_id'] = (int) $filtros['veiculo_id'];
        }

        if (!empty($filtros['cpf'])) {
            $where[] = 'u.cpf = :cpf';
            $params[':cpf'] = Validator::cpf((string) $filtros['cpf']);
        }

        if (!empty($filtros['placa'])) {
            $placaLimpa = strtoupper(str_replace('-', '', (string) $filtros['placa']));
            $where[] = "REPLACE(v.placa, '-', '') = :placa";
            $params[':placa'] = $placaLimpa;
        }

        if (count($where) > 0) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " ORDER BY os.id DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $resultado = [];
        foreach ($rows as $row) {
            $resultado[] = $this->montarCompleto($row);
        }

        return $resultado;
    }

    public function deletar(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM ordens_servico WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function gerarProximoNumeroOs(): string
    {
        $dataReferencia = date('Y-m-d');
        $stmt = $this->pdo->prepare(
            'INSERT INTO ordem_servico_contadores (data_referencia, ultimo_numero)
             VALUES (:data_referencia, LAST_INSERT_ID(1))
             ON DUPLICATE KEY UPDATE ultimo_numero = LAST_INSERT_ID(ultimo_numero + 1)'
        );
        $stmt->execute([':data_referencia' => $dataReferencia]);

        $sequencial = (int) $this->pdo->query('SELECT LAST_INSERT_ID()')->fetchColumn();

        return sprintf('OS-%s-%04d', str_replace('-', '', $dataReferencia), $sequencial);
    }

    public function registrarHistorico(int $osId, ?string $statusDe, string $statusPara, ?string $observacao = null): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO ordem_servico_historico (ordem_servico_id, status_de, status_para, observacao)
             VALUES (:os_id, :status_de, :status_para, :observacao)"
        );
        $stmt->execute([
            ':os_id'       => $osId,
            ':status_de'   => $statusDe,
            ':status_para' => $statusPara,
            ':observacao'  => $observacao,
        ]);
    }

    public function buscarHistoricoPorOsId(int $osId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM ordem_servico_historico WHERE ordem_servico_id = :os_id ORDER BY id ASC"
        );
        $stmt->execute([':os_id' => $osId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $itens = [];
        foreach ($rows as $r) {
            $itens[] = new HistoricoStatusOS(
                id: (int) $r['id'],
                statusDe: $r['status_de'],
                statusPara: $r['status_para'],
                observacao: $r['observacao'],
                criadoEm: $r['criado_em'],
            );
        }
        return $itens;
    }

    /**
     * @param ItemServicoOS[] $servicos
     */
    private function sincronizarServicos(int $osId, array $servicos): void
    {
        $idsExistentes = $this->buscarIdsDosItens('ordem_servico_servicos', $osId);
        $idsMantidos = [];

        $inserir = $this->pdo->prepare(
            'INSERT INTO ordem_servico_servicos (ordem_servico_id, servico_id, nome, descricao, quantidade, preco_unitario, tempo_estimado_horas, subtotal)
             VALUES (:ordem_servico_id, :servico_id, :nome, :descricao, :quantidade, :preco_unitario, :tempo_estimado_horas, :subtotal)'
        );
        $atualizar = $this->pdo->prepare(
            'UPDATE ordem_servico_servicos
             SET servico_id = :servico_id, nome = :nome, descricao = :descricao, quantidade = :quantidade,
                 preco_unitario = :preco_unitario, tempo_estimado_horas = :tempo_estimado_horas, subtotal = :subtotal
             WHERE id = :id AND ordem_servico_id = :ordem_servico_id'
        );

        foreach ($servicos as $servico) {
            $params = [
                ':ordem_servico_id' => $osId,
                ':servico_id' => $servico->servicoId(),
                ':nome' => $servico->nome(),
                ':descricao' => $servico->descricao(),
                ':quantidade' => $servico->quantidade(),
                ':preco_unitario' => $servico->precoUnitario(),
                ':tempo_estimado_horas' => $servico->tempoEstimadoHoras(),
                ':subtotal' => $servico->subtotal(),
            ];

            if ($servico->id() !== null && in_array($servico->id(), $idsExistentes, true)) {
                $atualizar->execute($params + [':id' => $servico->id()]);
                $idsMantidos[] = $servico->id();
                continue;
            }

            $inserir->execute($params);
        }

        $this->removerItensAusentes('ordem_servico_servicos', $osId, $idsExistentes, $idsMantidos);
    }

    /**
     * @param ItemPecaOS[] $pecas
     */
    private function sincronizarPecas(int $osId, array $pecas): void
    {
        $idsExistentes = $this->buscarIdsDosItens('ordem_servico_pecas', $osId);
        $idsMantidos = [];

        $inserir = $this->pdo->prepare(
            'INSERT INTO ordem_servico_pecas (ordem_servico_id, peca_id, descricao, quantidade, preco_unitario, subtotal)
             VALUES (:ordem_servico_id, :peca_id, :descricao, :quantidade, :preco_unitario, :subtotal)'
        );
        $atualizar = $this->pdo->prepare(
            'UPDATE ordem_servico_pecas
             SET peca_id = :peca_id, descricao = :descricao, quantidade = :quantidade,
                 preco_unitario = :preco_unitario, subtotal = :subtotal
             WHERE id = :id AND ordem_servico_id = :ordem_servico_id'
        );

        foreach ($pecas as $peca) {
            $params = [
                ':ordem_servico_id' => $osId,
                ':peca_id' => $peca->pecaId(),
                ':descricao' => $peca->descricao(),
                ':quantidade' => $peca->quantidade(),
                ':preco_unitario' => $peca->precoUnitario(),
                ':subtotal' => $peca->subtotal(),
            ];

            if ($peca->id() !== null && in_array($peca->id(), $idsExistentes, true)) {
                $atualizar->execute($params + [':id' => $peca->id()]);
                $idsMantidos[] = $peca->id();
                continue;
            }

            $inserir->execute($params);
        }

        $this->removerItensAusentes('ordem_servico_pecas', $osId, $idsExistentes, $idsMantidos);
    }

    private function buscarIdsDosItens(string $tabela, int $osId): array
    {
        $stmt = $this->pdo->prepare("SELECT id FROM {$tabela} WHERE ordem_servico_id = :os_id");
        $stmt->execute([':os_id' => $osId]);

        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    private function removerItensAusentes(string $tabela, int $osId, array $idsExistentes, array $idsMantidos): void
    {
        foreach ($idsExistentes as $id) {
            if (in_array($id, $idsMantidos, true)) {
                continue;
            }

            $stmt = $this->pdo->prepare("DELETE FROM {$tabela} WHERE id = :id AND ordem_servico_id = :os_id");
            $stmt->execute([':id' => $id, ':os_id' => $osId]);
        }
    }

    private function montarCompleto(array $row): OrdemServico
    {
        $osId = (int) $row['id'];

        $os = new OrdemServico(
            id:                  $osId,
            numeroOs:            $row['numero_os'],
            clienteId:           (int) $row['cliente_id'],
            veiculoId:           (int) $row['veiculo_id'],
            status:              StatusOrdemServico::fromValor($row['status']),
            descricaoProblema:   $row['descricao_problema'] ?? null,
            diagnostico:         $row['diagnostico'] ?? null,
            orcamentoEnviadoEm:  $row['orcamento_enviado_em'] ?? null,
            orcamentoAprovadoEm: $row['orcamento_aprovado_em'] ?? null,
            finalizadoEm:        $row['finalizado_em'] ?? null,
            entregueEm:          $row['entregue_em'] ?? null,
            criadoEm:            $row['criado_em'] ?? null,
            atualizadoEm:        $row['atualizado_em'] ?? null,
        );

        // Se a query já trouxe dados de cliente/veículo via JOIN, aproveita. Caso contrário, busca.
        if (isset($row['cliente_nome'])) {
            $os->definirDadosCliente([
                'id'       => (int) $row['cliente_id'],
                'nome'     => $row['cliente_nome'],
                'cpf'      => $row['cliente_cpf'],
                'email'    => $row['cliente_email'],
                'telefone' => $row['cliente_telefone'],
            ]);
        } else {
            $stmtCli = $this->pdo->prepare("SELECT id, nome, email, telefone, cpf FROM usuarios WHERE id = :id");
            $stmtCli->execute([':id' => $row['cliente_id']]);
            $cli = $stmtCli->fetch(PDO::FETCH_ASSOC);
            if ($cli) {
                $os->definirDadosCliente($cli);
            }
        }

        if (isset($row['veiculo_placa'])) {
            $os->definirDadosVeiculo([
                'id'     => (int) $row['veiculo_id'],
                'placa'  => $row['veiculo_placa'],
                'marca'  => $row['veiculo_marca'],
                'modelo' => $row['veiculo_modelo'],
                'ano'    => (int) $row['veiculo_ano'],
                'cor'    => $row['veiculo_cor'],
            ]);
        } else {
            $stmtVei = $this->pdo->prepare("SELECT id, placa, marca, modelo, COALESCE(ano_modelo, ano_fabricacao) as ano, cor FROM veiculos WHERE id = :id");
            $stmtVei->execute([':id' => $row['veiculo_id']]);
            $vei = $stmtVei->fetch(PDO::FETCH_ASSOC);
            if ($vei) {
                $os->definirDadosVeiculo($vei);
            }
        }

        // Buscar serviços
        $stmtServicos = $this->pdo->prepare("SELECT * FROM ordem_servico_servicos WHERE ordem_servico_id = :id ORDER BY id ASC");
        $stmtServicos->execute([':id' => $osId]);
        $rowsServicos = $stmtServicos->fetchAll(PDO::FETCH_ASSOC);
        $itensServico = [];
        foreach ($rowsServicos as $s) {
            $itensServico[] = new ItemServicoOS(
                id: (int) $s['id'],
                servicoId: isset($s['servico_id']) ? (int) $s['servico_id'] : null,
                nome: $s['nome'],
                descricao: $s['descricao'],
                quantidade: (int) $s['quantidade'],
                precoUnitario: (float) $s['preco_unitario'],
                tempoEstimadoHoras: (float) ($s['tempo_estimado_horas'] ?? 0.0),
                criadoEm: $s['criado_em'] ?? null,
            );
        }
        $os->definirServicos($itensServico);

        // Buscar peças
        $stmtPecas = $this->pdo->prepare("SELECT * FROM ordem_servico_pecas WHERE ordem_servico_id = :id ORDER BY id ASC");
        $stmtPecas->execute([':id' => $osId]);
        $rowsPecas = $stmtPecas->fetchAll(PDO::FETCH_ASSOC);
        $itensPeca = [];
        foreach ($rowsPecas as $p) {
            $itensPeca[] = new ItemPecaOS(
                id: (int) $p['id'],
                pecaId: isset($p['peca_id']) ? (int) $p['peca_id'] : null,
                descricao: $p['descricao'],
                quantidade: (float) $p['quantidade'],
                precoUnitario: (float) $p['preco_unitario'],
                criadoEm: $p['criado_em'] ?? null,
            );
        }
        $os->definirPecas($itensPeca);

        // Totais gravados no banco
        $os->definirTotais(
            (float) ($row['valor_servicos'] ?? 0.0),
            (float) ($row['valor_pecas'] ?? 0.0),
            (float) ($row['valor_total'] ?? 0.0)
        );

        // Histórico
        $os->definirHistorico($this->buscarHistoricoPorOsId($osId));

        return $os;
    }
}
