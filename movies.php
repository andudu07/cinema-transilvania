<?php
require_once 'config.php';
require_role(['admin', 'editor']);

// stergere film
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    require_csrf();
		require_role(['admin']); // doar admin poate sterge

    $id = (int)$_POST['delete_id'];
    if ($id > 0) {
        $stmt = $pdo->prepare('DELETE FROM movies WHERE id = ?');
        $stmt->execute([$id]);
    }

    header('Location: movies.php');
    exit;
}


// Listare filme
$stmt = $pdo->query('SELECT id, title, duration_minutes, rating, image_url FROM movies ORDER BY id DESC');
$movies = $stmt->fetchAll();
?>
<!doctype html>
<html lang="ro">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Filme — Cinema Transilvania</title>
  <style>
    body{font-family:Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;margin:0;min-height:100vh;background:linear-gradient(180deg,#071026 0%, #081327 60%);color:#e6eef8}
    header{padding:18px 26px;display:flex;align-items:center;border-bottom:1px solid rgba(148,163,184,0.25);backdrop-filter:blur(12px);background:rgba(2,6,23,0.92)}
    .brand{font-weight:700;font-size:18px}
    .spacer{flex:1}
    a{color:#e5e7eb;text-decoration:none}
    a:hover{text-decoration:underline}
    main{padding:24px 26px;max-width:960px;margin:0 auto}
    h1{margin:0 0 4px;font-size:22px}
    .lead{margin:0 0 18px;font-size:14px;color:#9ca3af}
    .toolbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px}
    .btn{display:inline-block;padding:8px 14px;border-radius:999px;border:none;background:#ff6b6b;color:white;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none}
    .btn-secondary{background:transparent;border:1px solid rgba(148,163,184,0.5);color:#e5e7eb}
    .btn:hover{filter:brightness(1.06)}
    table{width:100%;border-collapse:collapse;margin-top:10px;background:rgba(15,23,42,0.9);border-radius:12px;overflow:hidden}
    th,td{text-align:left;padding:10px 12px;font-size:14px}
    th{background:rgba(15,23,42,1);border-bottom:1px solid rgba(55,65,81,0.8);font-weight:500;color:#9ca3af}
    tr:nth-child(even){background:rgba(15,23,42,0.92)}
    tr:nth-child(odd){background:rgba(15,23,42,0.86)}
    .actions a{margin-right:8px;font-size:13px}
    .pill{display:inline-flex;align-items:center;padding:2px 8px;border-radius:999px;font-size:12px;border:1px solid rgba(148,163,184,0.5);color:#d1d5db}
    .thumb{width:36px;height:52px;border-radius:4px;object-fit:cover;border:1px solid rgba(148,163,184,0.5);}
  </style>
</head>
<body>
  <header>
    <div class="brand">Cinema Transilvania — Admin filme</div>
    <div class="spacer"></div>
    <div class="user">Conectat ca <strong><?= e($_SESSION['username']) ?></strong></div>
    <div style="margin-left:16px"><form method="post" action="logout.php" style="margin-left:16px">
  <?= csrf_field() ?>
  <button type="submit" class="btn btn-secondary">Logout</button>
</form></div>
  </header>
  <main>
    <h1>Filme</h1>
		
		<div class="toolbar">
  		<div class="pill">Total filme: <?= count($movies) ?></div>
  		<div style="display:flex; gap:10px;">
    		<a href="dashboard.php" class="btn btn-secondary">Dashboard</a>
				<a href="reports.php" class="btn btn-secondary">Rapoarte</a>
    		<a href="movie_form.php" class="btn">+ Adaugă film</a>
  		</div>
		</div>
 

    <?php if (!$movies): ?>
      <p>Nu există încă filme în baza de date.</p>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Poster</th>
            <th>Titlu</th>
            <th>Durată (min)</th>
            <th>Rating</th>
            <th>Acțiuni</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($movies as $movie): ?>
            <tr>
              <td><?= (int)$movie['id'] ?></td>
              <td>
                <?php if (!empty($movie['image_url'])): ?>
                  <img src="<?= e($movie['image_url']) ?>" alt="" class="thumb" />
                <?php else: ?>
                  <span style="font-size:12px;color:#9ca3af;">Fără imagine</span>
                <?php endif; ?>
              </td>
              <td><?= e($movie['title']) ?></td>
              <td><?= (int)$movie['duration_minutes'] ?></td>
              <td><?= e($movie['rating']) ?></td>
              <td class="actions">
                <a href="movie_form.php?id=<?= (int)$movie['id'] ?>">Editează</a>
              	<?php if (current_role() === 'admin'): ?>
  								<form method="post" action="movies.php" style="display:inline" onsubmit="return confirm('Sigur ștergeți acest film?');">
										<?= csrf_field() ?>
    								<input type="hidden" name="delete_id" value="<?= (int)$movie['id'] ?>">
    								<button type="submit" style="background:none;border:none;color:#e5e7eb;cursor:pointer;padding:0;font-size:13px;text-decoration:underline;">
      								Șterge
    								</button>
  								</form>
								<?php endif; ?>

							</td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </main>
</body>
</html>

