<?php
session_start();

$host = 'localhost';
$db   = 'cinema_transilvania';
$user = 'root';
$pass = 'root'; 

/*
$host = 'sql109.infinityfree.com';
$db   = 'if0_40489356_cinematransilvania';
$user = 'if0_40489356';
$pass = 'fX1z0BoIM7r8';
*/

$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
		PDO::MYSQL_ATTR_MULTI_STATEMENTS => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

// reCAPTCHA v3
define('RECAPTCHA_SITE_KEY', '6LfezjMsAAAAABWNH5RHmmn9EGNERPaxRgmL9SdK');
define('RECAPTCHA_SECRET_KEY', '6LfezjMsAAAAAN5MFL5nsZssYTsZ_ZkLKkD8qItm');

define('OMDB_API_KEY', '8e27dcc6');
define('OMDB_BASE_URL', 'https://www.omdbapi.com/');

// Email / SMTP
define('SMTP_HOST', 'smtp.gmail.com'); 
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls'); // tls / ssl
define('SMTP_USER', 'cinematransilvania@gmail.com');
define('SMTP_PASS', 'vwut ytvw rqmj qqdh');
define('MAIL_FROM', SMTP_USER);
define('MAIL_FROM_NAME', 'Cinema Transilvania');

define('ADMIN_EMAIL', 'cinematransilvania@gmail.com');
define('ADMIN_NAME', 'Admin Cinema');


function is_logged_in(): bool {
    return isset($_SESSION['user_id']);
}

function current_role(): string {
    // daca nu e logat -> il tratam ca "guest"
    if (!is_logged_in()) {
        return 'guest';
    }
    // daca e logat, dar dintr-un motiv nu exista -> fallback
    return $_SESSION['role'] ?? 'editor';
}

function require_login(): void {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function require_role(array $roles): void {
    // 1) daca nu e logat, il trimit la login
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }

    // 2) daca e logat, dar nu are voie
    if (!in_array(current_role(), $roles, true)) {
        http_response_code(403);
        die('Acces interzis.');
    }
}



function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function require_csrf(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    $token = $_POST['csrf'] ?? '';

    if (!is_string($token) || $token === '' || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $token)) {
        http_response_code(403);
        die('CSRF invalid.');
    }
}


function sanitize_image_url(string $url): string {
    $url = trim($url);
    if ($url === '') return '';

    // permite doar path local "images/..." 
    if (preg_match('#^images/[a-zA-Z0-9/_\-.]+\.(png|jpg|jpeg|webp|gif)$#', $url)) {
        return $url;
    }

    // permite doar http/https
    if (filter_var($url, FILTER_VALIDATE_URL)) {
        $parts = parse_url($url);
        $scheme = strtolower($parts['scheme'] ?? '');
        if (in_array($scheme, ['http', 'https'], true)) {
            return $url;
        }
    }

    // altfel respingi
    return '';
}

function is_valid_date_ymd(string $s): bool {
    $dt = DateTime::createFromFormat('Y-m-d', $s);
    return $dt && $dt->format('Y-m-d') === $s;
}

function is_valid_time_hm(string $s): bool {
    return (bool)preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $s);
}


function generate_ticket_code(int $length = 16): string {
    // hex => 32 char
    return strtoupper(substr(bin2hex(random_bytes(16)), 0, $length));
}
function build_ticket_pdf_data(array $ticket, array $movie): string
{
    require_once __DIR__ . '/lib/tcpdf/tcpdf.php';

    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('Cinema Transilvania');
    $pdf->SetAuthor('Cinema Transilvania');
    $pdf->SetTitle('Bilet - ' . ($ticket['ticket_code'] ?? ''));
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(true, 15);
    $pdf->AddPage();

    // Font UTF-8 (merge cu diacritice)
    $pdf->SetFont('dejavusans', '', 12);

    $ticketCode = (string)($ticket['ticket_code'] ?? '');
    $title      = (string)($movie['title'] ?? '');
    $date       = (string)($movie['show_date'] ?? '');
    $time       = substr((string)($movie['show_time'] ?? ''), 0, 5);
    $qty        = (int)($ticket['qty'] ?? 0);
    $type       = (string)($ticket['ticket_type'] ?? '');

    $qrText = "CINEMA_TRANSILVANIA|TICKET|" . $ticketCode;

    // Header
    $pdf->SetFont('dejavusans', 'B', 16);
    $pdf->Cell(0, 10, 'Bilet Cinema Transilvania', 0, 1, 'L');

    $pdf->Ln(2);
    $pdf->SetFont('dejavusans', '', 12);

    // Detalii
    $html = '
      <table cellpadding="4">
        <tr><td><b>Cod bilet:</b></td><td>' . htmlspecialchars($ticketCode, ENT_QUOTES, 'UTF-8') . '</td></tr>
        <tr><td><b>Film:</b></td><td>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</td></tr>
        <tr><td><b>Data:</b></td><td>' . htmlspecialchars($date, ENT_QUOTES, 'UTF-8') . '</td></tr>
        <tr><td><b>Ora:</b></td><td>' . htmlspecialchars($time, ENT_QUOTES, 'UTF-8') . '</td></tr>
        <tr><td><b>Cantitate:</b></td><td>' . $qty . '</td></tr>
        <tr><td><b>Tip:</b></td><td>' . htmlspecialchars($type, ENT_QUOTES, 'UTF-8') . '</td></tr>
      </table>
    ';
    $pdf->writeHTML($html, true, false, true, false, '');

    $style = [
        'border' => 0,
        'padding' => 1,
        'fgcolor' => [0, 0, 0],
        'bgcolor' => false
    ];

    $pdf->write2DBarcode($qrText, 'QRCODE,M', 140, 55, 50, 50, $style, 'N');

    $pdf->SetXY(140, 108);
    $pdf->SetFont('dejavusans', '', 9);
    $pdf->MultiCell(50, 0, "Scanează pentru cod.\n" . $ticketCode, 0, 'C');

    return $pdf->Output('', 'S');
}
function send_ticket_email(string $to, array $ticket, array $movie): bool {
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) return false;

    require_once __DIR__ . '/lib/PHPMailer/src/Exception.php';
    require_once __DIR__ . '/lib/PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/lib/PHPMailer/src/SMTP.php';

    $subject = 'Bilet Cinema Transilvania — ' . ($movie['title'] ?? '');

    $showTime = (string)($movie['show_time'] ?? '');
    $textBody =
        "Salut!\n\nBiletul tău a fost emis cu succes.\n" .
        "Cod bilet: " . ($ticket['ticket_code'] ?? '') . "\n" .
        "Film: " . ($movie['title'] ?? '') . "\n" .
        "Data: " . ($movie['show_date'] ?? '') . "\n" .
        "Ora: " . ($showTime ? substr($showTime, 0, 5) : '') . "\n" .
        "Cantitate: " . (int)($ticket['qty'] ?? 0) . "\n" .
        "Tip: " . ($ticket['ticket_type'] ?? '') . "\n\n" .
        "Am atașat PDF-ul biletului cu cod QR.\n" .
        "Îți mulțumim!\n";

    $htmlBody = '<div style="font-family:Arial,sans-serif">
      <h2>Bilet emis cu succes</h2>
      <p>Ți-am atașat PDF-ul biletului cu cod QR.</p>
      <p><b>Cod:</b> ' . htmlspecialchars((string)($ticket['ticket_code'] ?? ''), ENT_QUOTES, 'UTF-8') . '</p>
    </div>';

    try {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->CharSet = 'UTF-8';
				$mail->SMTPDebug = 2;
				$mail->Debugoutput = function ($str, $level) {
    			error_log("SMTP[$level] $str");
				};

        // SMTP Gmail
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;      
        $mail->SMTPSecure = SMTP_SECURE;    
        $mail->Port       = SMTP_PORT;      

        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($to);

        $mail->Subject = $subject;
        $mail->isHTML(true);
        $mail->Body    = $htmlBody;
        $mail->AltBody = $textBody;

        $pdfData = build_ticket_pdf_data($ticket, $movie);
        $filename = 'bilet_' . ($ticket['ticket_code'] ?? 'ticket') . '.pdf';
        $mail->addStringAttachment($pdfData, $filename, 'base64', 'application/pdf');

        return $mail->send();
    } catch (\Throwable $e) {
        error_log('send_ticket_email failed: ' . $e->getMessage());
        return false;
    }
}


function http_get_json(string $url, int $timeout = 6): ?array {
    // Prefer cURL
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 2,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $resp = curl_exec($ch);
        curl_close($ch);
        if ($resp === false) return null;

        $data = json_decode($resp, true);
        return is_array($data) ? $data : null;
    }

    // Fallback
    $ctx = stream_context_create([
        'http' => ['timeout' => $timeout],
        'ssl'  => ['verify_peer' => true, 'verify_peer_name' => true],
    ]);
    $resp = @file_get_contents($url, false, $ctx);
    if ($resp === false) return null;

    $data = json_decode($resp, true);
    return is_array($data) ? $data : null;
}

function parse_runtime_minutes(string $runtime): int {
    // ex "142 min" => 142
    if (preg_match('/(\d+)/', $runtime, $m)) return (int)$m[1];
    return 0;
}

/**
 Intoarce array cu date mapate pentru formular
 */
function omdb_autofill_movie(string $title, ?string $year = null): array {
    $title = trim($title);
    if ($title === '') return ['ok' => false, 'error' => 'Titlu lipsă.'];

    $params = [
        'apikey' => OMDB_API_KEY,
        't' => $title,
        'plot' => 'full',
    ];
    if ($year) $params['y'] = $year;

    $url = OMDB_BASE_URL . '?' . http_build_query($params);

    $data = http_get_json($url, 7);
    if (!$data) return ['ok' => false, 'error' => 'Nu am putut contacta OMDb.'];

    if (($data['Response'] ?? 'False') !== 'True') {
        return ['ok' => false, 'error' => (string)($data['Error'] ?? 'Film negăsit.')];
    }

    $runtime = (string)($data['Runtime'] ?? '');
    $minutes = parse_runtime_minutes($runtime);

    $plot = (string)($data['Plot'] ?? '');
    if ($plot === 'N/A') $plot = '';

    $rated = (string)($data['Rated'] ?? '');
    if ($rated === 'N/A') $rated = '';
		
		$genre = (string)($data['Genre'] ?? '');
		if ($genre === 'N/A') $genre = '';

		$director = (string)($data['Director'] ?? '');
		if ($director === 'N/A') $director = '';
		
		$actors = (string)($data['Actors'] ?? '');
		if ($actors === 'N/A') $actors = '';

    $poster = (string)($data['Poster'] ?? '');
    if ($poster === 'N/A') $poster = '';

    return [
        'ok' => true,
        'movie' => [
            'title' => (string)($data['Title'] ?? $title),
            'duration_minutes' => $minutes > 0 ? $minutes : null,
            'synopsis' => $plot,
            'rating' => $rated,
            'image_url' => $poster,
						'genre' => $genre,
						'director' => $director,
						'actors' => $actors
        ]
    ];
}



