<?php
require 'config.php';
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['gabarito']) && $_FILES['gabarito']['error'] === UPLOAD_ERR_OK) {
    $json = file_get_contents($_FILES['gabarito']['tmp_name']);
    $data = json_decode($json, true);

    if (!$data || empty($data['questions'])) {
        $msg = "<p class='err'>Arquivo inválido — confira se é o gabarito.json gerado pelo sistema.</p>";
    } else {
        try {
            $pdo->beginTransaction();

            // insere a prova (ou atualiza se o quiz_id já existir) e recupera o id de forma confiável
            $stmt = $pdo->prepare("
                INSERT INTO provas (quiz_id, titulo, disciplina, professor, turma, tempo, data)
                VALUES (:quiz_id, :titulo, :disciplina, :professor, :turma, :tempo, :data)
                ON DUPLICATE KEY UPDATE
                    id = LAST_INSERT_ID(id),
                    titulo = VALUES(titulo), disciplina = VALUES(disciplina),
                    professor = VALUES(professor), turma = VALUES(turma),
                    tempo = VALUES(tempo), data = VALUES(data)
            ");
            $stmt->execute([
                ':quiz_id'    => $data['quizId'] ?? null,
                ':titulo'     => $data['title'] ?? 'Prova sem título',
                ':disciplina' => $data['disciplina'] ?? null,
                ':professor'  => $data['professor'] ?? null,
                ':turma'      => $data['turma'] ?? null,
                ':tempo'      => $data['tempo'] ?? null,
                ':data'       => !empty($data['data']) ? $data['data'] : null,
            ]);
            $provaId = $pdo->lastInsertId();

            // limpa questões antigas dessa prova (permite reimportar se você editar o gabarito)
            $pdo->prepare("DELETE FROM questoes WHERE prova_id = ?")->execute([$provaId]);

            $letters = ['A', 'B', 'C', 'D', 'E', 'F'];
            $qStmt = $pdo->prepare("
                INSERT INTO questoes (prova_id, ordem, enunciado, a, b, c, d, correta, peso)
                VALUES (:prova_id, :ordem, :enunciado, :a, :b, :c, :d, :correta, :peso)
            ");

            foreach ($data['questions'] as $i => $q) {
                $alts = $q['alts'] ?? [];
                $qStmt->execute([
                    ':prova_id'  => $provaId,
                    ':ordem'     => $i + 1,
                    ':enunciado' => $q['q'] ?? '',
                    ':a'         => $alts[0] ?? null,
                    ':b'         => $alts[1] ?? null,
                    ':c'         => $alts[2] ?? null,
                    ':d'         => $alts[3] ?? null,
                    ':correta'   => $letters[$q['correct']] ?? 'A',
                    ':peso'      => $q['peso'] ?? 1,
                ]);
            }

            $pdo->commit();
            $msg = "<p class='ok'>✓ Prova <b>" . htmlspecialchars($data['title']) . "</b> importada — "
                 . count($data['questions']) . " questão(ões). ID no banco: <b>{$provaId}</b>.</p>";
        } catch (\Exception $e) {
            $pdo->rollBack();
            $msg = "<p class='err'>Erro ao importar: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
}

// lista as últimas provas importadas
$provas = $pdo->query("
    SELECT p.id, p.titulo, p.disciplina, p.turma, p.criado_em, COUNT(q.id) AS n_questoes
    FROM provas p LEFT JOIN questoes q ON q.prova_id = p.id
    GROUP BY p.id ORDER BY p.criado_em DESC LIMIT 10
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Importar Gabarito</title>
<?= pageStyle() ?>
</head>
<body>
<div class="wrap">
  <nav><a href="importar_gabarito.php">Importar Gabarito</a><a href="importar_resposta.php">Importar Respostas</a><a href="listar_resultados.php">Ver Resultados</a></nav>
  <span class="tag">Fase 4 · Banco de Dados</span>
  <h1>Importar Gabarito</h1>

  <div class="card">
    <h2>Selecione o arquivo gabarito.json</h2>
    <form method="post" enctype="multipart/form-data">
      <input type="file" name="gabarito" accept=".json" required>
      <br>
      <button type="submit">Importar</button>
    </form>
    <?= $msg ?>
  </div>

  <div class="card">
    <h2>Provas cadastradas recentemente</h2>
    <?php if (empty($provas)): ?>
      <p class="muted">Nenhuma prova importada ainda.</p>
    <?php else: ?>
      <table>
        <thead><tr><th>ID</th><th>Título</th><th>Disciplina</th><th>Turma</th><th>Questões</th><th>Importada em</th></tr></thead>
        <tbody>
          <?php foreach ($provas as $p): ?>
            <tr>
              <td>#<?= $p['id'] ?></td>
              <td><?= htmlspecialchars($p['titulo']) ?></td>
              <td><?= htmlspecialchars($p['disciplina'] ?? '-') ?></td>
              <td><?= htmlspecialchars($p['turma'] ?? '-') ?></td>
              <td><?= $p['n_questoes'] ?></td>
              <td class="muted"><?= $p['criado_em'] ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
