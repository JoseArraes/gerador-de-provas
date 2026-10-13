<?php
require 'config.php';

$provaFiltro = $_GET['prova_id'] ?? '';

$provas = $pdo->query("SELECT id, titulo FROM provas ORDER BY criado_em DESC")->fetchAll();

$sql = "
  SELECT r.id, a.nome, a.turma, a.matricula, p.titulo AS prova_titulo, p.id AS prova_id,
         r.acertos, r.total, r.nota, r.corrigido_em
  FROM resultados r
  JOIN alunos a ON a.id = r.aluno_id
  JOIN provas p ON p.id = r.prova_id
";
$params = [];
if ($provaFiltro !== '') {
    $sql .= " WHERE p.id = ?";
    $params[] = $provaFiltro;
}
$sql .= " ORDER BY p.id DESC, r.nota DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$resultados = $stmt->fetchAll();

$media = null;
if ($resultados) {
    $media = round(array_sum(array_column($resultados, 'nota')) / count($resultados), 2);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Resultados</title>
<?= pageStyle() ?>
</head>
<body>
<div class="wrap">
  <nav><a href="importar_gabarito.php">Importar Gabarito</a><a href="importar_resposta.php">Importar Respostas</a><a href="listar_resultados.php">Ver Resultados</a></nav>
  <span class="tag">Fase 4 · Banco de Dados</span>
  <h1>Resultados</h1>

  <div class="card">
    <h2>Filtrar por prova</h2>
    <form method="get">
      <select name="prova_id" onchange="this.form.submit()" style="padding:9px 12px; border-radius:8px; border:1px solid var(--line); font-size:14px; width:100%;">
        <option value="">Todas as provas</option>
        <?php foreach ($provas as $p): ?>
          <option value="<?= $p['id'] ?>" <?= $provaFiltro == $p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['titulo']) ?></option>
        <?php endforeach; ?>
      </select>
    </form>
  </div>

  <div class="card">
    <h2>
      <?= count($resultados) ?> resultado(s)
      <?php if ($media !== null): ?> &middot; média da turma: <span class="<?= notaClass($media) ?>"><?= number_format($media, 1, ',', '') ?></span><?php endif; ?>
    </h2>

    <?php if (empty($resultados)): ?>
      <p class="muted">Nenhum resultado lançado ainda. Importe respostas em "Importar Respostas".</p>
    <?php else: ?>
      <table>
        <thead><tr><th>Aluno</th><th>Turma</th><th>Prova</th><th>Acertos</th><th>Nota</th><th>Corrigido em</th></tr></thead>
        <tbody>
          <?php foreach ($resultados as $r): ?>
            <tr>
              <td><?= htmlspecialchars($r['nome']) ?></td>
              <td><?= htmlspecialchars($r['turma'] ?? '-') ?></td>
              <td><?= htmlspecialchars($r['prova_titulo']) ?></td>
              <td><?= $r['acertos'] ?>/<?= $r['total'] ?></td>
              <td class="<?= notaClass($r['nota']) ?>"><?= number_format($r['nota'], 1, ',', '') ?></td>
              <td class="muted"><?= $r['corrigido_em'] ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
