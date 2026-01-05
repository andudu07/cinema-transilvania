<?php
require_once 'config.php';
require_role(['admin','editor']);

$selectedDate = $_GET['date'] ?? date('Y-m-d');
if (!is_valid_date_ymd($selectedDate)) $selectedDate = date('Y-m-d');
?>
<!doctype html>
<html lang="ro">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Rapoarte — Cinema Transilvania</title>
  <style>
    body{font-family:Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;margin:0;min-height:100vh;background:linear-gradient(180deg,#071026 0%, #081327 60%);color:#e6eef8}
    header{padding:18px 26px;display:flex;align-items:center;border-bottom:1px solid rgba(148,163,184,0.25);backdrop-filter:blur(12px);background:rgba(2,6,23,0.92)}
    .spacer{flex:1}
    main{padding:24px 26px;max-width:860px;margin:0 auto}
    .card{background:rgba(15,23,42,0.92);padding:18px;border-radius:14px;border:1px solid rgba(148,163,184,0.35);margin-bottom:14px}
    a{color:#e5e7eb;text-decoration:none}
    a:hover{text-decoration:underline}
    .btn{display:inline-block;padding:8px 14px;border-radius:999px;border:none;background:#ff6b6b;color:white;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none}
    .btn-secondary{background:transparent;border:1px solid rgba(148,163,184,0.5);color:#e5e7eb}
    label{font-size:13px;color:#9ca3af;display:block;margin-bottom:6px}
    input[type=date]{padding:8px 10px;border-radius:10px;border:1px solid rgba(148,163,184,0.4);background:#020617;color:#e5e7eb}
    .row{display:flex;gap:10px;flex-wrap:wrap;align-items:end}
  </style>
</head>
<body>
<header>
  <div><strong>Cinema Transilvania — Rapoarte</strong></div>
  <div class="spacer"></div>
  <div>Conectat ca <strong><?= e($_SESSION['username'] ?? '') ?></strong></div>
  <div style="margin-left:16px">
    <form method="post" action="logout.php">
      <?= csrf_field() ?>
      <button class="btn btn-secondary" type="submit">Logout</button>
    </form>
  </div>
</header>

<main>
  <div class="card">
    <h2 style="margin:0 0 10px;">Export program (PDF)</h2>
    <form method="get" class="row" action="export_program_pdf.php">
      <div>
        <label for="date">Data</label>
        <input id="date" type="date" name="date" value="<?= e($selectedDate) ?>">
      </div>
      <button class="btn" type="submit">Descarcă PDF</button>
    </form>
  </div>

  <div class="card">
    <h2 style="margin:0 0 10px;">Export bilete / vânzări (Excel .xls)</h2>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
      <a class="btn" href="export_tickets_xls.php">Descarcă Excel</a>
    </div>
  </div>
	<a class="btn btn-secondary" href="movies.php">← Înapoi la filme</a>
  
</main>
</body>
</html>

