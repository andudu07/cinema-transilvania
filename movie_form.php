<?php
require_once 'config.php';

require_login();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$editing = $id > 0;

$title = '';
$duration = '';
$synopsis = '';
$rating = '';
$image_url = '';
$show_date = '';
$show_time = '';
$projection_format = '2D';
$error = '';

if ($editing) {
    $stmt = $pdo->prepare('SELECT * FROM movies WHERE id = ?');
    $stmt->execute([$id]);
    $movie = $stmt->fetch();
    if (!$movie) {
        die('Film inexistent.');
    }
    $title = $movie['title'];
    $duration = $movie['duration_minutes'];
    $synopsis = $movie['synopsis'];
    $rating = $movie['rating'];
    $image_url = $movie['image_url'] ?? '';
    $show_date = $movie['show_date'] ?? '';
    $show_time = substr($movie['show_time'] ?? '', 0, 5); // HH:MM
    $projection_format = $movie['projection_format'] ?? '2D';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $duration = (int) ($_POST['duration'] ?? 0);
    $synopsis = trim($_POST['synopsis'] ?? '');
    $rating = trim($_POST['rating'] ?? '');
    $image_url = trim($_POST['image_url'] ?? '');
    $show_date = trim($_POST['show_date'] ?? '');
    $show_time = trim($_POST['show_time'] ?? '');
    $projection_format = $_POST['projection_format'] ?? '2D';

    if ($title === '' || $duration <= 0 || $show_date === '' || $show_time === '') {
        $error = 'Titlul, durata, data și ora sunt obligatorii.';
    } else {
        $time_full = $show_time . ':00'; // HH:MM:SS

        if ($editing) {
            $stmt = $pdo->prepare('
                UPDATE movies
                SET title = ?, duration_minutes = ?, synopsis = ?, rating = ?, image_url = ?, show_date = ?, show_time = ?, projection_format = ?
                WHERE id = ?
            ');
            $stmt->execute([$title, $duration, $synopsis, $rating, $image_url, $show_date, $time_full, $projection_format, $id]);
        } else {
            $stmt = $pdo->prepare('
                INSERT INTO movies (title, duration_minutes, synopsis, rating, image_url, show_date, show_time, projection_format)
                VALUES (?,?,?,?,?,?,?,?)
            ');
            $stmt->execute([$title, $duration, $synopsis, $rating, $image_url, $show_date, $time_full, $projection_format]);
        }
        header('Location: movies.php');
        exit;
    }
}
?>
<!doctype html>
<html lang="ro">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= $editing ? 'Editează film' : 'Adaugă film' ?> — Cinema Transilvania</title>
  <style>
    body{font-family:Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;margin:0;min-height:100vh;background:linear-gradient(180deg,#071026 0%, #081327 60%);color:#e6eef8}
    header{padding:18px 26px;display:flex;align-items:center;border-bottom:1px solid rgba(148,163,184,0.25);backdrop-filter:blur(12px);background:rgba(2,6,23,0.92)}
    .brand{font-weight:700;font-size:18px}
    .spacer{flex:1}
    a{color:#e5e7eb;text-decoration:none}
    a:hover{text-decoration:underline}
    main{padding:24px 26px;max-width:720px;margin:0 auto}
    h1{margin:0 0 4px;font-size:22px}
    .lead{margin:0 0 18px;font-size:14px;color:#9ca3af}
    form{background:rgba(15,23,42,0.92);padding:20px 18px;border-radius:14px;border:1px solid rgba(148,163,184,0.4)}
    .field{margin-bottom:14px}
    label{display:block;margin-bottom:6px;font-size:13px}
    input[type=text],input[type=number],input[type=date],input[type=time],textarea,select{
      width:100%;padding:9px 10px;border-radius:8px;border:1px solid rgba(148,163,184,0.4);
      background:#020617;color:#e5e7eb;font-size:14px;box-sizing:border-box
    }
    textarea{min-height:90px;resize:vertical}
    input:focus,textarea:focus,select:focus{outline:none;border-color:#ff6b6b;box-shadow:0 0 0 1px #ff6b6b33}
    .row-2{display:flex;gap:10px;flex-wrap:wrap}
    .row-2 .field{flex:1}
    .actions{display:flex;gap:10px;margin-top:10px}
    .btn{display:inline-block;padding:8px 16px;border-radius:999px;border:none;background:#ff6b6b;color:white;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none}
    .btn-secondary{background:transparent;border:1px solid rgba(148,163,184,0.5);color:#e5e7eb}
    .btn:hover{filter:brightness(1.06)}
    .error{background:rgba(248,113,113,0.12);color:#fecaca;padding:8px 10px;border-radius:8px;font-size:13px;margin-bottom:14px;border:1px solid rgba(248,113,113,0.4)}
    .image-hint{font-size:12px;color:#9ca3af;margin-top:4px}
  </style>
</head>
<body>
  <header>
    <div class="brand">Cinema Transilvania — Admin filme</div>
    <div class="spacer"></div>
    <div class="user">Conectat ca <strong><?= e($_SESSION['username']) ?></strong></div>
    <div style="margin-left:16px"><a href="logout.php" class="btn btn-secondary">Logout</a></div>
  </header>
  <main>
    <h1><?= $editing ? 'Editează film' : 'Adaugă film' ?></h1>
    <p class="lead">Completează câmpurile de mai jos și salvează.</p>

    <?php if ($error): ?>
      <div class="error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="">
      <div class="field">
        <label for="title">Titlu</label>
        <input type="text" id="title" name="title" required value="<?= e($title) ?>" />
      </div>

      <div class="row-2">
        <div class="field">
          <label for="duration">Durată (minute)</label>
          <input type="number" id="duration" name="duration" required min="1" value="<?= e((string)$duration) ?>" />
        </div>
        <div class="field">
          <label for="rating">Rating (ex: AP12)</label>
          <input type="text" id="rating" name="rating" value="<?= e($rating) ?>" />
        </div>
      </div>

      <div class="field">
        <label for="image_url">URL imagine / poster</label>
        <input type="text" id="image_url" name="image_url" value="<?= e($image_url) ?>" />
        <div class="image-hint">
          Poți folosi un URL absolut (https://...) sau un fișier local, de ex. <code>images/film1.jpg</code>.
        </div>
      </div>

      <div class="row-2">
        <div class="field">
          <label for="show_date">Data proiecției</label>
          <input type="date" id="show_date" name="show_date" required value="<?= e($show_date) ?>" />
        </div>
        <div class="field">
          <label for="show_time">Ora proiecției</label>
          <input type="time" id="show_time" name="show_time" required value="<?= e($show_time) ?>" />
        </div>
      </div>

      <div class="field">
        <label for="projection_format">Format</label>
        <select id="projection_format" name="projection_format">
          <option value="2D" <?= $projection_format === '2D' ? 'selected' : '' ?>>2D</option>
          <option value="3D" <?= $projection_format === '3D' ? 'selected' : '' ?>>3D</option>
        </select>
      </div>

      <div class="field">
        <label for="synopsis">Sinopsis</label>
        <textarea id="synopsis" name="synopsis"><?= e($synopsis) ?></textarea>
      </div>

      <div class="actions">
        <button type="submit" class="btn">Salvează</button>
        <a href="movies.php" class="btn btn-secondary">Renunță</a>
      </div>
    </form>
  </main>
</body>
</html>

