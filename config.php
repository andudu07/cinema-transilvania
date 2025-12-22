<?php
session_start();

$host = 'localhost';
$db   = 'cinema_transilvania';
$user = 'root';
$pass = 'root'; 

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

function send_ticket_email(string $to, array $ticket, array $movie): bool {
    // IMPORTANT: pe localhost mail() poate sa nu functioneze fara SMTP configurat.
    $subject = 'Bilet Cinema Transilvania — ' . $movie['title'];

    $lines = [];
    $lines[] = "Salut!";
    $lines[] = "";
    $lines[] = "Biletul tău a fost emis cu succes.";
    $lines[] = "Cod bilet: " . $ticket['ticket_code'];
    $lines[] = "Film: " . $movie['title'];
    $lines[] = "Data: " . $movie['show_date'];
    $lines[] = "Ora: " . substr($movie['show_time'], 0, 5);
    $lines[] = "Cantitate: " . $ticket['qty'];
    $lines[] = "Tip: " . $ticket['ticket_type'];
    $lines[] = "";
    $lines[] = "Îți mulțumim!";
    $message = implode("\r\n", $lines);

    $headers = [];
    $headers[] = 'From: Cinema Transilvania <no-reply@cinema-transilvania.ro>';
    $headers[] = 'Content-Type: text/plain; charset=UTF-8';

    return mail($to, $subject, $message, implode("\r\n", $headers));
}

