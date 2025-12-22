<?php
require_once 'config.php';

// Daca esti logat ca buyer, trebuie sa schimbi contul pentru admin,
// deci te delogam si te trimitem la login
if (is_logged_in() && current_role() === 'buyer') {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

// Daca nu esti logat -> login
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

// Daca esti editor/admin -> intri in panou
header('Location: movies.php');
exit;

