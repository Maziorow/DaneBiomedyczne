<?php
require_once __DIR__ . '/config.php';
start_session();
?>
<!doctype html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System logowania</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header>
    <h1>System rejestracji i logowania</h1>
    <p>Strona zbudowana na podstawie kodu z prezentacji PHP/MySQL.</p>
</header>
<main>
    <section class="card">
        <?php if (isset($_SESSION['current_user'])): ?>
            <h2>Witaj, <?= e($_SESSION['current_username'] ?? 'użytkowniku') ?>!</h2>
            <p>Jesteś zalogowany/a jako użytkownik ID: <?= e((string) $_SESSION['current_user']) ?>.</p>
            <nav>
                <a href="dashboard.php">Panel użytkownika</a>
                <a class="secondary" href="logout.php">Wyloguj</a>
            </nav>
        <?php else: ?>
            <h2>Nie jesteś zalogowany/a</h2>
            <p>Załóż konto albo zaloguj się do istniejącego konta.</p>
            <nav>
                <a href="register.php">Rejestracja</a>
                <a class="secondary" href="login.php">Logowanie</a>
            </nav>
        <?php endif; ?>
    </section>
</main>
<footer>PHP + MySQL + sesje</footer>
</body>
</html>
