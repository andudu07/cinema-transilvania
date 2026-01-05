<?php
require_once 'config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    die('Film invalid.');
}

$backDate = $_GET['date'] ?? ($_SESSION['last_program_date'] ?? null);
$programBack = program_url(is_string($backDate) ? $backDate : null);

$stmt = $pdo->prepare('SELECT id, title, duration_minutes, rating, show_date, show_time FROM movies WHERE id = ?');
$stmt->execute([$id]);
$movie = $stmt->fetch();

if (!$movie) {
    die('Film inexistent.');
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_login();
    require_role(['buyer']);
    require_csrf();

    $qty = (int)($_POST['qty'] ?? 0);
    $ticket_type = $_POST['ticket_type'] ?? 'normal';

    $allowedTypes = ['normal', 'student'];
    if ($qty < 1 || $qty > 10) {
        $error = 'Cantitatea trebuie să fie între 1 și 10.';
    } elseif (!in_array($ticket_type, $allowedTypes, true)) {
        $error = 'Tip bilet invalid.';
    } else {
        $ticket_code = generate_ticket_code(16);

        // Inserare ticket
        $stmt = $pdo->prepare('
            INSERT INTO tickets (ticket_code, user_id, movie_id, qty, ticket_type, status)
            VALUES (?,?,?,?,?,?)
        ');
        $stmt->execute([
            $ticket_code,
            (int)$_SESSION['user_id'],
            (int)$movie['id'],
            $qty,
            $ticket_type,
            'paid'
        ]);

        $ticketId = (int)$pdo->lastInsertId();

        // Email
        $to = $_SESSION['email'] ?? '';
        if ($to !== '') {
            $ticket = [
                'id' => $ticketId,
                'ticket_code' => $ticket_code,
                'qty' => $qty,
                'ticket_type' => $ticket_type
            ];

            $movieForEmail = $movie;

            $sent = send_ticket_email($to, $ticket, $movieForEmail);
            if (!$sent) {
                $success = 'Bilet emis. Email-ul nu a putut fi trimis (verifică SMTP/mail server). Cod: ' . e($ticket_code);
            } else {
                $success = 'Bilet emis și trimis pe email. Cod: ' . e($ticket_code);
            }
        } else {
            $success = 'Bilet emis. Nu există email în cont. Cod: ' . e($ticket_code);
        }

    }
}



?>
<!doctype html>
<html lang="ro">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Cumpără bilet — <?= e($movie['title']) ?> | Cinema Transilvania</title>
  <style>
    :root {
      --bg:#0f1724;
      --card:#0b1220;
      --accent:#ff6b6b;
      --muted:#98a0b3;
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
    .meta{font-size:13px;color:var(--muted);margin-bottom:16px;}
    .pill{
      display:inline-flex;
      align-items:center;
      padding:3px 9px;
      border-radius:999px;
      border:1px solid rgba(148,163,184,0.6);
      font-size:11px;
      margin-right:6px;
    }
    .notice{
      background:rgba(15,23,42,0.95);
      border-radius:10px;
      border:1px dashed rgba(148,163,184,0.6);
      padding:12px 12px;
      font-size:13px;
      color:var(--muted);
      margin-bottom:16px;
    }
    .actions{
      display:flex;
      gap:10px;
      flex-wrap:wrap;
      margin-top:10px;
    }
    .btn{
      display:inline-flex;align-items:center;justify-content:center;
      padding:8px 16px;border-radius:999px;
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
        <div class="brand-text-sub">Cumpără bilet</div>
      </div>
    </div>
  </header>

  <main>
    <section>
      <h1>Cumpără bilet — <?= e($movie['title']) ?></h1>
      <div class="meta">
        <span class="pill">Durată: <?= (int)$movie['duration_minutes'] ?> min</span>
        <?php if (!empty($movie['rating'])): ?>
          <span class="pill">Rating: <?= e($movie['rating']) ?></span>
        <?php endif; ?>
      </div>

      <?php if ($error): ?>
  			<div class="notice" style="border-color: rgba(248,113,113,0.6); color:#fecaca;">
    			<?= e($error) ?>
  			</div>
			<?php endif; ?>

			<?php if ($success): ?>
  			<div class="notice" style="border-color: rgba(34,197,94,0.6); color:#bbf7d0;">
    			<?= $success ?>
  			</div>
			<?php endif; ?>
			<?php if (!is_logged_in()): ?>
				<div class="notice">
					Pentru a cumpăra bilete trebuie să te autentifici ca <strong>buyer</strong>.
				</div>
				<div class="actions">
					<a href="login.php" class="btn btn-primary">Autentificare</a>
				</div>

			<?php elseif (current_role() !== 'buyer'): ?>
				<div class="notice">
					Contul tău nu are rol de cumpărător (rol curent: <strong><?= e(current_role()) ?></strong>).
				</div>
			<?php else: ?>
				  <form method="post" action="">
						<?= csrf_field() ?>

						<div style="margin-bottom:12px;">
							<label for="qty" style="display:block;margin-bottom:6px;font-size:13px;color:var(--muted);">
								Număr bilete
							</label>
							<input
								type="number"
								id="qty"
								name="qty"
								min="1"
								max="10"
								value="1"
								required
								style="width:100%;padding:9px 10px;border-radius:8px;border:1px solid rgba(148,163,184,0.4);background:#020617;color:#e5e7eb;font-size:14px;"
							/>
						</div>

						<div style="margin-bottom:12px;">
							<label for="ticket_type" style="display:block;margin-bottom:6px;font-size:13px;color:var(--muted);">
								Tip bilet
							</label>
							<select
								id="ticket_type"
								name="ticket_type"
								style="width:100%;padding:9px 10px;border-radius:8px;border:1px solid rgba(148,163,184,0.4);background:#020617;color:#e5e7eb;font-size:14px;"
							>
								<option value="normal">Normal</option>
								<option value="student">Student</option>
							</select>
						</div>

						<div class="actions">
							<button type="submit" class="btn btn-primary">Confirmă cumpărarea</button>
						</div>
					</form>
				<?php endif; ?>

      
      <div class="actions">
				<a href="<?= e($programBack) ?>" class="btn btn-secondary">← Înapoi la listă</a>
				<a href="movie.php?id=<?= (int)$movie['id'] ?>&date=<?= e($backDate ?? '') ?>" class="btn btn-primary">Vezi detalii film</a>
      </div>
    </section>
  </main>
</body>
</html>

