<?php
require 'config.php';
$mensagens = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['respostas']['tmp_name'][0])) {
    $letters = ['A', 'B', 'C', 'D', 'E', 'F'];

    foreach ($_FILES['respostas']['tmp_name'] as $i => $tmpPath) {
        $nomeArquivo = htmlspecialchars($_FILES['respostas']['name'][$i]);
        $json = file_get_contents($tmpPath);
        $data = json_decode($json, true);

        if (!$data || !isset($data['answers'])) {
            $mensagens[] = "<p class='err'>{$nomeArquivo}: arquivo inválido.</p>";
            continue;
        }

        try {
            // 1) localizar a prova pelo quiz_id
            $stmt = $pdo->prepare("SELECT id FROM provas WHERE quiz_id = ?");
            $stmt->execute([$data['quizId'] ?? '']);
            $provaId = $stmt->fetchColumn();

            if (!$provaId) {
                $mensagens[] = "<p class='err'>{$nomeArquivo}: prova não encontrada no banco (importe o gabarito.json correspondente primeiro).</p>";
                continue;
            }

            $pdo->beginTransaction();

            // 2) localizar ou criar o aluno
            $nome      = trim($data['studentName'] ?? 'Aluno sem nome');
            $matricula = trim($data['studentNumero'] ?? '') ?: null;
            $turma     = trim($data['studentTurma'] ?? '') ?: null;

            if ($matricula) {
                $find = $pdo->prepare("SELECT id FROM alunos WHERE matricula = ?");
                $find->execute([$matricula]);
                $alunoId = $find->fetchColumn();
                if (!$alunoId) {
                    $ins = $pdo->prepare("INSERT INTO alunos (nome, turma, matricula) VALUES (?,?,?)");
                    $ins->execute([$nome, $turma, $matricula]);
                    $alunoId = $pdo->lastInsertId();
                }
            } else {
                // sem matrícula: tenta casar por nome + turma, senão cria novo
                $find = $pdo->prepare("SELECT id FROM alunos WHERE nome = ? AND (turma <=> ?) AND matricula IS NULL");
                $find->execute([$nome, $turma]);
                $alunoId = $find->fetchColumn();
                if (!$alunoId) {
                    $ins = $pdo->prepare("INSERT INTO alunos (nome, turma, matricula) VALUES (?,?,NULL)");
                    $ins->execute([$nome, $turma]);
                    $alunoId = $pdo->lastInsertId();
                }
            }

            // 3) buscar as questões da prova, na ordem
            $qStmt = $pdo->prepare("SELECT id, correta, peso FROM questoes WHERE prova_id = ? ORDER BY ordem ASC");
            $qStmt->execute([$provaId]);
            $questoes = $qStmt->fetchAll();

            // 4) limpar respostas anteriores desse aluno nessa prova (permite reimportar)
            $pdo->prepare("
                DELETE r FROM respostas r
                JOIN questoes q ON q.id = r.questao_id
                WHERE r.aluno_id = ? AND q.prova_id = ?
            ")->execute([$alunoId, $provaId]);

            // 5) gravar cada resposta e calcular a nota
            $insR = $pdo->prepare("INSERT INTO respostas (aluno_id, prova_id, questao_id, resposta) VALUES (?,?,?,?)");
            $acertos = 0;
            $pesoTotal = 0;
            $pesoObtido = 0;

            foreach ($questoes as $idx => $q) {
                $pesoTotal += $q['peso'];
                $respIdx = $data['answers'][$idx] ?? null;
                $respLetra = ($respIdx === null) ? null : ($letters[$respIdx] ?? null);

                $insR->execute([$alunoId, $provaId, $q['id'], $respLetra]);

                if ($respLetra !== null && $respLetra === $q['correta']) {
                    $acertos++;
                    $pesoObtido += $q['peso'];
                }
            }

            $total = count($questoes);
            $nota = $pesoTotal > 0 ? round(($pesoObtido / $pesoTotal) * 10, 2) : 0;

            // 6) gravar/atualizar o resultado consolidado
            $insRes = $pdo->prepare("
                INSERT INTO resultados (aluno_id, prova_id, acertos, total, nota)
                VALUES (?,?,?,?,?)
                ON DUPLICATE KEY UPDATE acertos=VALUES(acertos), total=VALUES(total), nota=VALUES(nota), corrigido_em=NOW()
            ");
            $insRes->execute([$alunoId, $provaId, $acertos, $total, $nota]);

            $pdo->commit();
            $mensagens[] = "<p class='ok'>✓ {$nomeArquivo}: <b>" . htmlspecialchars($nome) . "</b> — {$acertos}/{$total} acertos, nota <b>{$nota}</b>.</p>";
        } catch (\Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $mensagens[] = "<p class='err'>{$nomeArquivo}: erro — " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Importar Respostas</title>
<?= pageStyle() ?>
</head>
<body>
<div class="wrap">
  <nav><a href="importar_gabarito.php">Importar Gabarito</a><a href="importar_resposta.php">Importar Respostas</a><a href="listar_resultados.php">Ver Resultados</a></nav>
  <span class="tag">Fase 4 · Banco de Dados</span>
  <h1>Importar Respostas dos Alunos</h1>

  <div class="card">
    <h2>Selecione um ou vários resposta-*.json</h2>
    <form method="post" enctype="multipart/form-data">
      <input type="file" name="respostas[]" accept=".json" multiple required>
      <br>
      <button type="submit">Corrigir e salvar no banco</button>
    </form>
    <?php foreach ($mensagens as $m) echo $m; ?>
  </div>
</div>
</body>
</html>
