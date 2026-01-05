<?php
require_once 'config.php';
require_role(['admin','editor']);


$to   = $_GET['to']   ?? date('Y-m-d', strtotime('+7 days'));
$from = $_GET['from'] ?? date('Y-m-d', strtotime('-30 days'));

if (!is_valid_date_ymd($from)) $from = date('Y-m-d', strtotime('-30 days'));
if (!is_valid_date_ymd($to))   $to   = date('Y-m-d', strtotime('+7 days'));

$stmt = $pdo->prepare("
  SELECT
    m.title AS movie_title,
    SUM(t.qty) AS tickets_sold
  FROM tickets t
  JOIN movies m ON m.id = t.movie_id
  WHERE m.show_date BETWEEN ? AND ?
  GROUP BY m.title
  ORDER BY tickets_sold DESC
  LIMIT 10
");
$stmt->execute([$from, $to]);
$rows = $stmt->fetchAll();

$labels = [];
$values = [];
foreach ($rows as $r) {
    $labels[] = (string)$r['movie_title'];
    $values[] = (int)$r['tickets_sold'];
}
?>
<!doctype html>
<html lang="ro">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Dashboard — Cinema Transilvania</title>
  <style>
    body{font-family:Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;margin:0;min-height:100vh;background:linear-gradient(180deg,#071026 0%, #081327 60%);color:#e6eef8}
    header{padding:18px 26px;display:flex;align-items:center;border-bottom:1px solid rgba(148,163,184,0.25);backdrop-filter:blur(12px);background:rgba(2,6,23,0.92)}
    .brand{font-weight:700;font-size:18px}
    .spacer{flex:1}
    main{padding:24px 26px;max-width:980px;margin:0 auto}
    a{color:#e5e7eb;text-decoration:none}
    a:hover{text-decoration:underline}
    .card{background:rgba(15,23,42,0.92);padding:18px;border-radius:14px;border:1px solid rgba(148,163,184,0.35);box-shadow:0 18px 45px rgba(0,0,0,0.35)}
    .row{display:flex;gap:10px;flex-wrap:wrap;align-items:end;margin-bottom:14px}
    label{font-size:13px;color:#9ca3af;display:block;margin-bottom:6px}
    input[type=date]{padding:8px 10px;border-radius:10px;border:1px solid rgba(148,163,184,0.4);background:#020617;color:#e5e7eb}
    .btn{display:inline-block;padding:8px 14px;border-radius:999px;border:none;background:#ff6b6b;color:white;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none}
    .btn-secondary{background:transparent;border:1px solid rgba(148,163,184,0.5);color:#e5e7eb}
    .muted{color:#9ca3af;font-size:13px}
    canvas{max-width:100%}
  </style>
</head>
<body>
<header>
  <div class="brand">Cinema Transilvania — Dashboard</div>
  <div class="spacer"></div>
  <div>Conectat ca <strong><?= e($_SESSION['username'] ?? '') ?></strong></div>
  <div style="margin-left:16px;display:flex;gap:10px;align-items:center;">
    <a class="btn btn-secondary" href="movies.php">← Filme</a>
    <form method="post" action="logout.php" style="margin:0">
      <?= csrf_field() ?>
      <button class="btn btn-secondary" type="submit">Logout</button>
    </form>
  </div>
</header>

<main>
  <div class="card">
    <h2 style="margin:0 0 6px;">Top filme după bilete vândute</h2>
    <div class="muted" style="margin-bottom:12px;">
      Interval: <strong><?= e($from) ?></strong> — <strong><?= e($to) ?></strong> (după data proiecției / show_date)
    </div>

    <form method="get" class="row">
      <div>
        <label for="from">De la</label>
        <input type="date" id="from" name="from" value="<?= e($from) ?>">
      </div>
      <div>
        <label for="to">Până la</label>
        <input type="date" id="to" name="to" value="<?= e($to) ?>">
      </div>
      <button class="btn" type="submit">Aplică</button>
      <a class="btn btn-secondary" href="dashboard.php">Ultimele 30 zile</a>
    </form>

    <?php if (!$rows): ?>
      <div class="muted">Nu există bilete în intervalul selectat.</div>
    <?php else: ?>
      <div style="height:380px;">
        <canvas id="topMoviesChart"></canvas>
      </div>
    <?php endif; ?>
  </div>
</main>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<?php if ($rows): ?>
<script>
  const labels = <?= json_encode($labels, JSON_UNESCAPED_UNICODE) ?>;
  const values = <?= json_encode($values) ?>;

  const ctx = document.getElementById('topMoviesChart');
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [{
        label: 'Bilete vândute',
        data: values,
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: true }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: { precision: 0 }
        }
      }
    }
  });
</script>
<?php endif; ?>

</body>
</html>

