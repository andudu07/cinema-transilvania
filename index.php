<?php
require_once 'config.php';

// data selectată din query string sau azi
$selectedDate = $_GET['date'] ?? date('Y-m-d');

// validare format data
$dt = DateTime::createFromFormat('Y-m-d', $selectedDate);
if (!$dt || $dt->format('Y-m-d') !== $selectedDate) {
    $selectedDate = date('Y-m-d');
    $dt = new DateTime($selectedDate);
}

// filme pentru data selectată
$stmt = $pdo->prepare('
    SELECT id, title, duration_minutes, synopsis, rating, image_url, show_time, projection_format
    FROM movies
    WHERE show_date = ?
    ORDER BY show_time, title
');
$stmt->execute([$selectedDate]);
$movies = $stmt->fetchAll();

function formatHour(?string $time): string {
    if (!$time) return '';
    return substr($time, 0, 5); // HH:MM
}
?>
<!doctype html>
<html lang="ro">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Cinema Transilvania — Program filme</title>
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
      position:sticky;
      top:0;
      z-index:20;
    }
    .brand{display:flex;align-items:center;gap:14px;}
    .logo{
      width:52px;height:52px;border-radius:12px;
      object-fit:cover;
      box-shadow:0 12px 30px rgba(0,0,0,0.6);
      display:block;
    }
    .brand-text-title{font-weight:700;font-size:20px;}
    .brand-text-sub{font-size:12px;color:var(--muted);}
    nav{margin-left:auto;font-size:14px;}
    nav a{
      color:var(--muted);
      text-decoration:none;
      margin-left:18px;
      font-weight:600;
    }
    nav a:hover,nav a.active{color:#fff;}
    main{
      padding:28px 32px;
      max-width:1100px;
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
    h1,h2{margin:0 0 6px;}
    p.lead{color:var(--muted); margin:0 0 18px;}
    .hero{
      display:flex;
      flex-wrap:wrap;
      gap:22px;
      align-items:flex-start;
    }
    .hero-main{flex:2 1 260px;}
    .hero-actions{margin-top:16px;display:flex;gap:10px;flex-wrap:wrap;}
    .btn{
      display:inline-flex;align-items:center;justify-content:center;
      padding:9px 16px;border-radius:999px;
      border:none;cursor:pointer;font-size:13px;font-weight:600;
      text-decoration:none;
    }
    .btn-primary{background:var(--accent);color:#111827;}
    .btn-primary:hover{filter:brightness(1.06);}
    .btn-ghost{
      background:transparent;
      border:1px solid rgba(148,163,184,0.6);
      color:#e5e7eb;
    }
    .btn-ghost:hover{background:rgba(15,23,42,0.9);}

    /* FILTRU DATA */
    .date-filter{
      display:flex;
      align-items:center;
      gap:10px;
      flex-wrap:wrap;
      margin:10px 0 16px;
      font-size:13px;
      color:var(--muted);
    }
    .date-filter label{font-weight:500;}
    .date-filter input[type=date]{
      padding:6px 10px;
      border-radius:999px;
      border:1px solid rgba(148,163,184,0.6);
      background:#020617;
      color:#e5e7eb;
      font-size:13px;
    }
    .date-filter input[type=date]:focus{
      outline:none;
      border-color:var(--accent);
      box-shadow:0 0 0 1px #ff6b6b33;
    }

    /* LIST VIEW */
    .movie-list{
      display:flex;
      flex-direction:column;
      gap:14px;
      margin-top:12px;
    }
    .movie-row{
      display:flex;
      gap:18px;
      padding:14px 14px;
      border-radius:12px;
      background:rgba(10,18,36,0.96);
      border:1px solid rgba(148,163,184,0.35);
      align-items:stretch;
    }
    .movie-poster{
      flex:0 0 110px;
      display:flex;
      align-items:center;
      justify-content:center;
    }
    .movie-poster img{
      width:100%;
      max-width:110px;
      height:160px;
      border-radius:8px;
      object-fit:cover;
      box-shadow:0 10px 26px rgba(0,0,0,0.7);
    }
    .poster-placeholder{
      width:100%;
      max-width:110px;
      height:160px;
      border-radius:8px;
      background:linear-gradient(145deg,#1f2937,#020617);
      border:1px dashed rgba(148,163,184,0.7);
      font-size:11px;
      color:var(--muted);
      display:flex;
      align-items:center;
      justify-content:center;
      text-align:center;
      padding:6px;
    }
    .movie-body{
      flex:1;
      display:flex;
      flex-direction:column;
      min-width:0;
    }
    .movie-header-row{
      display:flex;
      justify-content:space-between;
      align-items:flex-start;
      gap:12px;
      margin-bottom:4px;
    }
    .movie-title{
      font-weight:600;
      font-size:16px;
    }
    .movie-rating{
      font-size:11px;
      padding:3px 7px;
      border-radius:999px;
      border:1px solid rgba(250,250,250,0.22);
      color:#e5e7eb;
      white-space:nowrap;
    }
    .movie-meta{
      font-size:12px;
      color:var(--muted);
      margin-bottom:6px;
    }
    .movie-synopsis{
      font-size:13px;
      color:#cbd5f5;
      margin-bottom:10px;
      max-height:52px;
      overflow:hidden;
    }
    .movie-footer{
      margin-top:auto;
      display:flex;
      justify-content:space-between;
      align-items:center;
      font-size:12px;
      gap:8px;
      flex-wrap:wrap;
    }
    .movie-footer-buttons{
      display:flex;
      gap:8px;
      flex-wrap:wrap;
    }
    .badge-muted{
      padding:3px 7px;
      border-radius:999px;
      background:rgba(15,23,42,0.9);
      border:1px solid rgba(148,163,184,0.5);
      color:var(--muted);
      font-size:11px;
    }
    .empty-state{
      padding:14px 12px;
      border-radius:10px;
      border:1px dashed rgba(148,163,184,0.6);
      font-size:13px;
      color:var(--muted);
    }
    #program{
      scroll-margin-top: 90px;
    }
    footer{
      padding:18px 32px;
      color:var(--muted);
      border-top:1px solid rgba(255,255,255,0.03);
      font-size:12px;
      display:flex;
      justify-content:space-between;
      gap:16px;
    }
    @media (max-width:720px){
      header{padding:16px 18px;}
      main{padding:18px;}
      section{padding:18px 16px;}
      nav{display:none;}
      .movie-row{flex-direction:row;}
      .movie-poster{flex:0 0 80px;}
      .movie-poster img,.poster-placeholder{max-width:80px;height:120px;}
    }
  </style>
</head>
<body>
  <header>
    <div class="brand">
      <!-- logo ca imagine, cum ai setat tu -->
      <img src="images/logo.png" class="logo" alt="Cinema Transilvania" />
      <div>
        <div class="brand-text-title">Cinema Transilvania</div>
        <div class="brand-text-sub">Filme, premiere și seri tematice</div>
      </div>
    </div>
    <nav>
      <a href="#program" class="active">Program</a>
      <a href="contact.php">Contact</a>
      <a href="movies.php">Admin</a>
    </nav>
  </header>

  <main>

    <!-- PROGRAM FILME — FILTRARE PE DATA -->
    <section id="program">
      <h2>Filme pentru data selectată</h2>
      <p class="lead">
        Data curentă: <strong><?= e($dt->format('d.m.Y')) ?></strong>
      </p>

      <form method="get" class="date-filter">
        <label for="date">Alege data:</label>
        <input
          type="date"
          id="date"
          name="date"
          value="<?= e($selectedDate) ?>"
          onchange="this.form.submit()"
        />
        <noscript>
          <button type="submit" class="btn btn-primary">Vezi program</button>
        </noscript>
      </form>

      <?php if (!$movies): ?>
        <div class="empty-state">
          Nu există filme programate pentru această dată.
          Alege o altă zi sau adaugă filme în panoul de administrare.
        </div>
      <?php else: ?>
        <div class="movie-list">
          <?php foreach ($movies as $movie): ?>
            <article class="movie-row">
              <div class="movie-poster">
                <?php if (!empty($movie['image_url'])): ?>
                  <img src="<?= e($movie['image_url']) ?>" alt="Poster <?= e($movie['title']) ?>" />
                <?php else: ?>
                  <div class="poster-placeholder">
                    Poster indisponibil<br/>Actualizează filmul în admin.
                  </div>
                <?php endif; ?>
              </div>
              <div class="movie-body">
                <div class="movie-header-row">
                  <div class="movie-title"><?= e($movie['title']) ?></div>
                  <?php if (!empty($movie['rating'])): ?>
                    <div class="movie-rating"><?= e($movie['rating']) ?></div>
                  <?php endif; ?>
                </div>
                <div class="movie-meta">
                  Durată: <?= (int)$movie['duration_minutes'] ?> min ·
                  Ora: <?= e(formatHour($movie['show_time'])) ?> ·
                  Format: <?= e($movie['projection_format']) ?>
                </div>
                <div class="movie-synopsis">
                  <?php if (!empty($movie['synopsis'])): ?>
                    <?= nl2br(e($movie['synopsis'])) ?>
                  <?php else: ?>
                    <span style="color:var(--muted);">Nu există încă sinopsis pentru acest titlu.</span>
                  <?php endif; ?>
                </div>
                <div class="movie-footer">
                  <div class="movie-footer-buttons">
                    <a class="btn btn-primary" href="buy.php?id=<?= (int)$movie['id'] ?>">Cumpără bilet</a>
                    <a class="btn btn-ghost" href="movie.php?id=<?= (int)$movie['id'] ?>">Detalii &amp; info</a>
                  </div>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  </main>

  <footer>
    <div>© <?= date('Y') ?> Cinema Transilvania — Program filme pe zile.</div>
    <div>Admin: <a href="movies.php" class="link">panou de administrare filme</a></div>
  </footer>
</body>
</html>

