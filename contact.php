<?php
require_once 'config.php';
require_once 'mailer.php';

$success = '';
$error = '';
$old = ['name'=>'', 'email'=>'', 'subject'=>'', 'message'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    // Honeypot (extra anti-bot, no UX impact)
    if (!empty($_POST['website'] ?? '')) {
        http_response_code(403);
        $error = 'Spam detected.';
    } else {
        $old['name'] = trim($_POST['name'] ?? '');
        $old['email'] = trim($_POST['email'] ?? '');
        $old['subject'] = trim($_POST['subject'] ?? '');
        $old['message'] = trim($_POST['message'] ?? '');

        // Server-side validation
        if ($old['name'] === '' || mb_strlen($old['name']) > 120) {
            $error = 'Numele este obligatoriu (max 120 caractere).';
        } elseif (!filter_var($old['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($old['email']) > 255) {
            $error = 'Email invalid.';
        } elseif ($old['subject'] === '' || mb_strlen($old['subject']) > 160) {
            $error = 'Subiect obligatoriu (max 160 caractere).';
        } elseif ($old['message'] === '' || mb_strlen($old['message']) < 10 || mb_strlen($old['message']) > 4000) {
            $error = 'Mesajul trebuie să aibă între 10 și 4000 caractere.';
        } else {
            // reCAPTCHA v3 verify
            $token = $_POST['g-recaptcha-response'] ?? '';
            $expectedAction = 'contact';

            if (!is_string($token) || $token === '') {
                $error = 'reCAPTCHA lipsă. Reîncearcă.';
            } else {
                $verifyUrl = 'https://www.google.com/recaptcha/api/siteverify';
                $postData = http_build_query([
                    'secret' => RECAPTCHA_SECRET_KEY,
                    'response' => $token,
                    'remoteip' => $_SERVER['REMOTE_ADDR'] ?? null,
                ]);

                // Prefer cURL (common on hosting). Fallback to file_get_contents if needed.
                $resp = false;
                if (function_exists('curl_init')) {
                    $ch = curl_init($verifyUrl);
                    curl_setopt_array($ch, [
                        CURLOPT_POST => true,
                        CURLOPT_POSTFIELDS => $postData,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_TIMEOUT => 8,
                    ]);
                    $resp = curl_exec($ch);
                    $curlErr = curl_error($ch);
                    curl_close($ch);

                    if ($resp === false) {
                        error_log('reCAPTCHA verify curl error: ' . $curlErr);
                    }
                } else {
                    $opts = [
                        'http' => [
                            'method'  => 'POST',
                            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                            'content' => $postData,
                            'timeout' => 8,
                        ]
                    ];
                    $context = stream_context_create($opts);
                    $resp = @file_get_contents($verifyUrl, false, $context);
                }

                if ($resp === false) {
                    $error = 'Eroare verificare reCAPTCHA. Reîncearcă.';
                } else {
                    $data = json_decode($resp, true);

                    if (!is_array($data) || empty($data['success'])) {
                        $error = 'reCAPTCHA invalid. Reîncearcă.';
                    } else {
                        $score = (float)($data['score'] ?? 0.0);
                        $action = (string)($data['action'] ?? '');

                        if ($action !== $expectedAction) {
                            $error = 'reCAPTCHA action mismatch.';
                        } elseif ($score < 0.5) {
                            $error = 'Mesaj blocat (scor reCAPTCHA prea mic).';
                        } else {
                            $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
                            $ua = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);

                            try {
                                $stmt = $pdo->prepare('
                                    INSERT INTO contact_messages (name, email, subject, message, ip, user_agent)
                                    VALUES (?,?,?,?,?,?)
                                ');
                                $stmt->execute([
                                    $old['name'],
                                    $old['email'],
                                    $old['subject'],
                                    $old['message'],
                                    $ip,
                                    $ua
                                ]);
																
																$contactId = (int)$pdo->lastInsertId();

																// Email către admin
																$adminSubject = "Mesaj contact #{$contactId} — " . $old['subject'];
																$adminText =
																"Mesaj nou din formularul de contact\n\n" .
																"ID: {$contactId}\n" .
																"Nume: {$old['name']}\n" .
																"Email: {$old['email']}\n" .
																"Subiect: {$old['subject']}\n" .
																"IP: {$ip}\n" .
																"User-Agent: {$ua}\n\n" .
																"Mesaj:\n{$old['message']}\n";

																$sentAdmin = send_email_smtp(ADMIN_EMAIL, $adminSubject, $adminText);

																// Auto-reply către utilizator
																$userSubject = "Am primit mesajul tău (ID #{$contactId}) — Cinema Transilvania";
																$userText =
																"Salut, {$old['name']}!\n\n" .
																"Îți mulțumim! Am primit mesajul tău și revenim cât mai repede.\n\n" .
																"ID mesaj: {$contactId}\n" .
																"Subiect: {$old['subject']}\n\n" .
																"— Cinema Transilvania";

																$sentUser = send_email_smtp($old['email'], $userSubject, $userText);

																// Nu bloca succesul dacă emailul eșuează
																if (!$sentAdmin || !$sentUser) {
																		error_log("Contact email not fully sent. admin={$sentAdmin} user={$sentUser} id={$contactId}");
																}


                                $success = 'Mesajul a fost trimis cu succes!';
                                $old = ['name'=>'', 'email'=>'', 'subject'=>'', 'message'=>'']; // clear form
                            } catch (Throwable $e) {
                                error_log('DB insert contact_messages failed: ' . $e->getMessage());
                                $error = 'A apărut o eroare la salvare. Reîncearcă.';
                            }
                        }
                    }
                }
            }
        }
    }
}
?>
<!doctype html>
<html lang="ro">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Contact — Cinema Transilvania</title>

  <script src="https://www.google.com/recaptcha/api.js?render=<?= e(RECAPTCHA_SITE_KEY) ?>"></script>

  <style>
    :root { --bg:#0f1724; --card:#0b1220; --accent:#ff6b6b; --muted:#98a0b3; }
    *{box-sizing:border-box;}
    body{margin:0;font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
      background:linear-gradient(180deg,#071026 0%, #081327 60%);color:#e6eef8;line-height:1.5;}
    header{padding:24px 32px;display:flex;align-items:center;gap:18px;border-bottom:1px solid rgba(255,255,255,0.05);
      background:rgba(10,16,32,0.92);backdrop-filter:blur(12px);}
    .brand{display:flex;align-items:center;gap:14px;}
    .logo{width:52px;height:52px;border-radius:12px;object-fit:cover;box-shadow:0 12px 30px rgba(0,0,0,0.6);display:block;}
    .brand-text-title{font-weight:700;font-size:20px;}
    .brand-text-sub{font-size:12px;color:var(--muted);}
    nav{margin-left:auto;font-size:14px;}
    nav a{color:var(--muted);text-decoration:none;margin-left:18px;font-weight:600;}
    nav a:hover,nav a.active{color:#fff;}
    main{padding:28px 32px;max-width:850px;margin:0 auto;}
    section{background:linear-gradient(180deg, rgba(255,255,255,0.04), rgba(255,255,255,0.005));
      border-radius:14px;padding:24px 22px 26px;margin-bottom:24px;border:1px solid rgba(255,255,255,0.04);
      box-shadow:0 18px 45px rgba(0,0,0,0.4);}
    h1{margin:0 0 6px;}
    p.lead{color:var(--muted); margin:0 0 18px;}
    .notice{background:rgba(15,23,42,0.95);border-radius:10px;border:1px dashed rgba(148,163,184,0.6);
      padding:12px 12px;font-size:13px;color:var(--muted);margin:12px 0 16px;}
    .notice.error{border-color: rgba(248,113,113,0.6); color:#fecaca;}
    .notice.success{border-color: rgba(34,197,94,0.6); color:#bbf7d0;}
    form{margin-top:10px;}
    .field{margin-bottom:14px;}
    label{display:block;margin-bottom:6px;font-size:13px;color:var(--muted);}
    input[type=text],input[type=email],textarea{width:100%;padding:9px 10px;border-radius:8px;
      border:1px solid rgba(148,163,184,0.4);background:#020617;color:#e5e7eb;font-size:14px;box-sizing:border-box}
    textarea{min-height:110px;resize:vertical}
    input:focus,textarea:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 1px #ff6b6b33}
    .actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:10px;}
    .btn{display:inline-flex;align-items:center;justify-content:center;padding:9px 16px;border-radius:999px;border:none;
      cursor:pointer;font-size:13px;font-weight:600;text-decoration:none;}
    .btn-primary{background:var(--accent);color:#111827;}
    .btn-secondary{background:transparent;border:1px solid rgba(148,163,184,0.6);color:#e5e7eb;}
    .btn:hover{filter:brightness(1.06);}
    .hint{font-size:12px;color:var(--muted);margin-top:6px;}
    footer{padding:18px 32px;color:var(--muted);border-top:1px solid rgba(255,255,255,0.03);
      font-size:12px;display:flex;justify-content:space-between;gap:16px;}
    .hp{position:absolute;left:-9999px;top:-9999px;height:0;width:0;opacity:0;}
    @media (max-width:720px){header{padding:16px 18px;}main{padding:18px;}section{padding:18px 16px;}nav{display:none;}}
  </style>
</head>
<body>
  <header>
    <div class="brand">
      <img src="images/logo.png" class="logo" alt="Cinema Transilvania" />
      <div>
        <div class="brand-text-title">Cinema Transilvania</div>
        <div class="brand-text-sub">Contact</div>
      </div>
    </div>
    <nav>
      <a href="index.php#program">Program</a>
      <a href="contact.php" class="active">Contact</a>
      <a href="admin.php">Log in</a>
    </nav>
  </header>

  <main>
    <section>
      <h1>Contact</h1>
      <p class="lead">Trimite-ne un mesaj și revenim cât mai repede.</p>

      <?php if ($error): ?>
        <div class="notice error"><?= e($error) ?></div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="notice success"><?= e($success) ?></div>
      <?php endif; ?>

      <form id="contact-form" method="post" action="" novalidate>
        <?= csrf_field() ?>

        <div class="hp">
          <label>Nu completa acest câmp</label>
          <input type="text" name="website" value="">
        </div>

        <div class="field">
          <label for="name">Nume</label>
          <input type="text" id="name" name="name" required value="<?= e($old['name']) ?>">
        </div>

        <div class="field">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" required value="<?= e($old['email']) ?>">
        </div>

        <div class="field">
          <label for="subject">Subiect</label>
          <input type="text" id="subject" name="subject" required value="<?= e($old['subject']) ?>">
        </div>

        <div class="field">
          <label for="message">Mesaj</label>
          <textarea id="message" name="message" required><?= e($old['message']) ?></textarea>
        </div>

        <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response" value="">

        <div class="actions">
          <button type="submit" class="btn btn-primary" id="btn-submit">Trimite mesaj</button>
          <a href="index.php#program" class="btn btn-secondary">← Înapoi la program</a>
        </div>

        <noscript>
          <div class="notice error">
            Pentru a trimite mesajul, ai nevoie de JavaScript activ (reCAPTCHA).
          </div>
        </noscript>
      </form>
    </section>
  </main>

  <footer>
    <div>© <?= date('Y') ?> Cinema Transilvania — Contact.</div>
    <div><a href="index.php#program" style="color:inherit;text-decoration:none;">← Înapoi la program</a></div>
  </footer>

  <script>
    const form = document.getElementById('contact-form');
    const btn = document.getElementById('btn-submit');

    form.addEventListener('submit', function(e){
      e.preventDefault();
      btn.disabled = true;
      btn.textContent = 'Se trimite...';

      grecaptcha.ready(() => {
        grecaptcha.execute('<?= e(RECAPTCHA_SITE_KEY) ?>', {action: 'contact'}).then(token => {
          document.getElementById('g-recaptcha-response').value = token;
          form.submit();
        }).catch(() => {
          btn.disabled = false;
          btn.textContent = 'Trimite mesaj';
          alert('Eroare reCAPTCHA. Reîncearcă.');
        });
      });
    });
  </script>
</body>
</html>

