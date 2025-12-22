<?php
require_once 'config.php';

$error = '';

if (is_logged_in()) {
    header('Location: movies.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

		$username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Introduceți utilizatorul și parola.';
    } else {
        $stmt = $pdo->prepare('SELECT id, username, password_hash, role FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
						$_SESSION['role'] = $user['role'] ?? 'buyer';
            if ($_SESSION['role'] === 'buyer') {
    					header('Location: index.php#program');
						} else {
    					header('Location: movies.php');
						}
						exit;
        } else {
            $error = 'Credențiale invalide.';
        }
    }
}
?>
<!doctype html>
<html lang="ro">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Autentificare — Cinema Transilvania</title>
  <style>
    body{font-family:Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:linear-gradient(180deg,#071026 0%, #081327 60%);color:#e6eef8}
    .card{background:rgba(15,23,36,0.98);padding:32px 28px;border-radius:14px;box-shadow:0 18px 45px rgba(0,0,0,0.5);width:100%;max-width:380px;border:1px solid rgba(255,255,255,0.05)}
    h1{margin:0 0 6px;font-size:22px}
    p{margin:0 0 18px;font-size:14px;color:#98a0b3}
    label{display:block;margin-bottom:6px;font-size:13px}
    input[type=text],input[type=password]{width:100%;padding:9px 10px;border-radius:8px;border:1px solid rgba(148,163,184,0.4);background:#020617;color:#e5e7eb;font-size:14px;box-sizing:border-box}
    input[type=text]:focus,input[type=password]:focus{outline:none;border-color:#ff6b6b;box-shadow:0 0 0 1px #ff6b6b33}
    .field{margin-bottom:14px}
    .btn{width:100%;padding:10px 12px;border-radius:999px;border:none;background:#ff6b6b;color:white;font-weight:600;font-size:14px;cursor:pointer}
    .btn:hover{filter:brightness(1.06)}
    .error{background:rgba(248,113,113,0.12);color:#fecaca;padding:8px 10px;border-radius:8px;font-size:13px;margin-bottom:14px;border:1px solid rgba(248,113,113,0.4)}
    .helper{margin-top:10px;font-size:12px;color:#64748b}
    .helper code{background:#020617;padding:2px 4px;border-radius:4px;font-size:11px}
  </style>
</head>
<body>
  <div class="card">
    <h1>Cinema Transilvania</h1>

    <?php if ($error): ?>
      <div class="error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="">
			<?= csrf_field() ?>

      <div class="field">
        <label for="username">Utilizator</label>
        <input type="text" id="username" name="username" required />
      </div>
      <div class="field">
        <label for="password">Parolă</label>
        <input type="password" id="password" name="password" required />
      </div>
      <button class="btn" type="submit">Autentificare</button>
    </form>

    <div class="helper">
      Admin: <code>admin</code> / <code>admin123</code>
    </div>
		<div class="helper">
      Buyer: <code>buyer1</code> / <code>parolamea123</code>
    </div>
  </div>
</body>
</html>

