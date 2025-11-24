<?php
require_once 'config.php';
?>
<!doctype html>
<html lang="ro">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Contact — Cinema Transilvania</title>
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
    h1{margin:0 0 6px;}
    p.lead{color:var(--muted); margin:0 0 18px;}
    .contact-info{
      font-size:14px;
      line-height:1.7;
    }
    .contact-info strong{color:#e5e7eb;}
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
    }
  </style>
</head>
<body>
  <header>
    <div class="brand">
      <img src="images/logo.png" class="logo" alt="Cinema Transilvania" />
      <div>
        <div class="brand-text-title">Cinema Transilvania</div>
        <div class="brand-text-sub">Contact &amp; locație</div>
      </div>
    </div>
    <nav>
      <a href="index.php#program">Program</a>
      <a href="contact.php" class="active">Contact</a>
      <a href="movies.php">Admin</a>
    </nav>
  </header>

  <main>
    <section>
      <h1>Contact</h1>
      <p class="lead">
        Pentru rezervări, informații despre program sau evenimente speciale, ne poți găsi folosind datele de mai jos.
      </p>

      <div class="contact-info">
        <p>
          <strong>Adresă:</strong><br>
          Str. Cinematografului nr. 13<br>
          Cluj-Napoca, România
        </p>
        <p>
          <strong>Telefon:</strong><br>
          0264 000 000
        </p>
        <p>
          <strong>Email:</strong><br>
          contact@cinema-transilvania.ro
        </p>
        <p>
          <strong>Program casierie:</strong><br>
          Luni–Duminică: 12:00 – 22:00
        </p>
      </div>
    </section>
  </main>

  <footer>
    <div>© <?= date('Y') ?> Cinema Transilvania — Contact &amp; informații.</div>
    <div><a href="index.php#program" style="color:inherit;text-decoration:none;">← Înapoi la program</a></div>
  </footer>
</body>
</html>

