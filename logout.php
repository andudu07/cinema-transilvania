<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Method Not Allowed');
}

require_csrf();

session_unset();
session_destroy();

header('Location: login.php');
exit;

