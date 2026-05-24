<?php
require_once __DIR__ . '/config.php';
start_session();

session_unset();
session_destroy();
?>
<!doctype html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wylogowano</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header><h1>Wylogowano</h1></header>
<main>
    <section class="card">
        <p>Wylogowano pomyślnie.</p>
        <a class="button" href="login.php">Zaloguj ponownie</a>
    </section>
</main>
</body>
</html>
