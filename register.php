<?php
require_once 'config.php';

if (is_logged_in()) {
    if (current_role() === 'buyer') {
        header('Location: index.php#program');
    } else {
        header('Location: movies.php');
    }
    exit;
}

$error = '';
$success = '';
$old = ['username'=>'', 'email'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $username = trim((string)($_POST['username'] ?? ''));
    $email    = trim((string)($_POST['email'] ?? ''));
    $pass1    = (string)($_POST['password'] ?? '');
    $pass2    = (string)($_POST['password2'] ?? '');

    $old['username'] = $username;
    $old['email'] = $email;

    if ($username === '' || mb_strlen($username) < 3 || mb_strlen($username) > 32 || !preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $error = 'Username invalid. Folosește 3–32 caractere (litere/cifre/_).';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 255) {
        $error = 'Email invalid.';
    } elseif (mb_strlen($pass1) < 8) {
        $error = 'Parola trebuie să aibă minim 8 caractere.';
    } elseif ($pass1 !== $pass2) {
        $error = 'Parolele nu coincid.';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1');
        $stmt->execute([$username, $email]);
        $exists = $stmt->fetch();

        if ($exists) {
            $error = 'Username sau email deja există.';
        } else {
            $hash = password_hash($pass1, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare('
                INSERT INTO users (username, email, password_hash, role)
                VALUES (?,?,?,?)
            ');
            $stmt->execute([$username, $email, $hash, 'buyer']);

            $userId = (int)$pdo->lastInsertId();

            $_SESSION['user_id'] = $userId;
            $_SESSION['username'] = $username;
            $_SESSION['email'] = $email;
            $_SESSION['role'] = 'buyer';

            header('Location: index.php#program');
            exit;
        }
    }
}
?>
<!doctype html>
<html lang="ro">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Înregistrare — Cinema Transilvania</title>
  <style>
    body{font-family:Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:linear-gradient(180deg,#071026 0%, #081327 60%);color:#e6eef8}
    .card{background:rgba(15,23,36,0.98);padding:32px 28px;border-radius:14px;box-shadow:0 18px 45px rgba(0,0,0,0.5);width:100%;max-width:420px;border:1px solid rgba(255,255,255,0.05)}
    h1{margin:0 0 6px;font-size:22px}
    p{margin:0 0 18px;font-size:14px;color:#98a0b3}
    label{display:block;margin-bottom:6px;font-size:13px}
    input[type=text],input[type=email],input[type=password]{width:100%;padding:9px 10px;border-radius:8px;border:1px solid rgba(148,163,184,0.4);background:#020617;color:#e5e7eb;font-size:14px;box-sizing:border-box}
    input:focus{outline:none;border-color:#ff6b6b;box-shadow:0 0 0 1px #ff6b6b33}
    .field{margin-bottom:14px}
    .btn{width:100%;padding:10px 12px;border-radius:999px;border:none;background:#ff6b6b;color:white;font-weight:600;font-size:14px;cursor:pointer}
    .btn:hover{filter:brightness(1.06)}
    .error{background:rgba(248,113,113,0.12);color:#fecaca;padding:8px 10px;border-radius:8px;font-size:13px;margin-bottom:14px;border:1px solid rgba(248,113,113,0.4)}
    .helper{margin-top:12px;font-size:13px;color:#94a3b8}
    .helper a{color:#e5e7eb;text-decoration:underline}
  </style>
</head>
<body>
  <div class="card">
    <h1>Înregistrare buyer</h1>
    <p>Creează un cont nou pentru a cumpăra bilete.</p>

    <?php if ($error): ?>
      <div class="error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="">
      <?= csrf_field() ?>

      <div class="field">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required value="<?= e($old['username']) ?>" />
      </div>

      <div class="field">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required value="<?= e($old['email']) ?>" />
      </div>

      <div class="field">
        <label for="password">Parolă</label>
        <input type="password" id="password" name="password" required />
      </div>

      <div class="field">
        <label for="password2">Repetă parola</label>
        <input type="password" id="password2" name="password2" required />
      </div>

      <button class="btn" type="submit">Creează cont</button>
    </form>

    <div class="helper">
      Ai deja cont? <a href="login.php">Autentificare</a>
    </div>
  </div>
</body>
</html>

