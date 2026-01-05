<?php
require_once 'config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    die('Film invalid.');
}

$stmt = $pdo->prepare('SELECT id, title, duration_minutes, synopsis, rating, genre, director, actors FROM movies WHERE id = ?');
$stmt->execute([$id]);
$movie = $stmt->fetch();

if (!$movie) {
    die('Film inexistent.');
}
?>
<!doctype html>
<html lang="ro">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title><?= e($movie['title']) ?> — Cinema Transilvania</title>
  <style>
    :root {
      --bg:#0f1724;
      --card:#0b1220;
      --accent:#ff6b6b;
      --muted:#98a0b3;
      --glass:rgba(255,255,255,0.03);
    }
    *{box-sizing:border-box;}
    body{
      margin:0;
      font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
      background:linear-gradient(180deg,#071026 0%, #081327 60%);
      color:#e6eef8;
      line-height:1.5;
    }
    header{
      padding:24px 32px;
      display:flex;
      align-items:center;
      gap:18px;
      border-bottom:1px solid rgba(255,255,255,0.05);
      background:rgba(10,16,32,0.92);
      backdrop-filter:blur(12px);
    }
    .brand{display:flex;align-items:center;gap:14px;}
   	.logo{
			width:52px;
			height:52px;
			border-radius:12px;
			object-fit:cover;
			box-shadow:0 12px 30px rgba(0,0,0,0.6);
			display:block;
		}
 
    .brand-text-title{font-weight:700;font-size:18px;}
    .brand-text-sub{font-size:11px;color:var(--muted);}
    main{
      padding:28px 32px;
      max-width:800px;
      margin:0 auto;
    }
    section{
      background:linear-gradient(180deg, rgba(255,255,255,0.04), rgba(255,255,255,0.005));
      border-radius:14px;
      padding:24px 22px 26px;
      margin-bottom:24px;
      border:1px solid rgba(255,255,255,0.04);
      box-shadow:0 18px 45px rgba(0,0,0,0.4);
    }
    h1{margin:0 0 8px;}
    .meta{font-size:13px;color:var(--muted);margin-bottom:14px;}
    .pill{
      display:inline-flex;
      align-items:center;
      padding:3px 9px;
      border-radius:999px;
      border:1px solid rgba(148,163,184,0.6);
      font-size:11px;
      margin-right:6px;
    }
    .synopsis{font-size:14px;margin-bottom:16px;}
    .actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:10px;}
    .btn{
      display:inline-flex;align-items:center;justify-content:center;
      padding:8px 14px;border-radius:999px;
      border:none;cursor:pointer;font-size:13px;font-weight:600;
      text-decoration:none;
    }
    .btn-primary{background:var(--accent);color:#111827;}
    .btn-secondary{
      background:transparent;
      border:1px solid rgba(148,163,184,0.6);
      color:#e5e7eb;
    }
    .btn:hover{filter:brightness(1.06);}
    footer{
      padding:16px 32px;
      color:var(--muted);
      border-top:1px solid rgba(255,255,255,0.03);
      font-size:12px;
    }
    @media (max-width:720px){
      header{padding:16px 18px;}
      main{padding:18px;}
      section{padding:18px 16px;}
    }
  </style>
</head>
<body>
  <header>
    <div class="brand">
    	<img src="images/logo.png" class="logo" alt="Cinema Transilvania" />
 
      <div>
        <div class="brand-text-title">Cinema Transilvania</div>
        <div class="brand-text-sub">Detalii film</div>
      </div>
    </div>
  </header>

  <main>
    <section>
      <h1><?= e($movie['title']) ?></h1>
      <div class="meta">
        <span class="pill">Durată: <?= (int)$movie['duration_minutes'] ?> min</span>
        <?php if (!empty($movie['rating'])): ?>
          <span class="pill">Rating: <?= e($movie['rating']) ?></span>
        <?php endif; ?>
      </div>

			<?php if (!empty($movie['genre']) || !empty($movie['director']) || !empty($movie['actors'])): ?>
				<div class="meta" style="margin-top:10px;">
					<?php if (!empty($movie['genre'])): ?>
						<div><strong>Gen:</strong> <?= e($movie['genre']) ?></div>
					<?php endif; ?>
					<?php if (!empty($movie['director'])): ?>
						<div><strong>Regizor:</strong> <?= e($movie['director']) ?></div>
					<?php endif; ?>
					<?php if (!empty($movie['actors'])): ?>
						<div><strong>Actori:</strong> <?= e($movie['actors']) ?></div>
					<?php endif; ?>
				</div>
			<?php endif; ?>


      <div class="synopsis">
        <?php if (!empty($movie['synopsis'])): ?>
          <?= nl2br(e($movie['synopsis'])) ?>
        <?php else: ?>
          <span style="color:var(--muted);">Nu există încă un sinopsis pentru acest film.</span>
        <?php endif; ?>
      </div>

      <div class="actions">
        <a href="index.php#program" class="btn btn-secondary">← Înapoi la program</a>
      </div>
    </section>
  </main>
</body>
</html>

