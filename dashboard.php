<?php
require_once __DIR__ . '/config.php';
start_session();

if (!isset($_SESSION['current_user'])) {
    header('Location: login.php');
    exit;
}
?>
<!doctype html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel użytkownika</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header><h1>Panel użytkownika</h1></header>
<main>
    <section class="card">
        <p>Użytkownik jest zalogowany: <strong><?= e((string) $_SESSION['current_user']) ?></strong></p>
        <p>Jako: <strong><?= e($_SESSION['current_username'] ?? '') ?></strong></p>
        <nav>
            <a href="index.php">Strona główna</a>
            <a class="secondary" href="logout.php">Wyloguj</a>
        </nav>
    </section>
</main>
</body>
</html>
