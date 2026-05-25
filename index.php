<?php
require_once __DIR__ . '/config.php';
start_session();
?>
<!doctype html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pomiary biomedyczne</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header>
    <h1>Pomiary biomedyczne</h1>
    <p>Prosta aplikacja PHP do zapisywania wyników użytkownika.</p>
</header>
<main>
    <section class="card">
        <?php if (isset($_SESSION['current_user'])): ?>
            <h2>Witaj, <?= $_SESSION['current_username'] ?? 'użytkowniku' ?></h2>
            <p>Jesteś zalogowany jako użytkownik ID: <?= (string) $_SESSION['current_user'] ?>.</p>
            <nav>
                <a href="dashboard.php">Panel pomiarów</a>
                <a href="change_password.php">Zmień hasło</a>
                <a class="secondary" href="logout.php">Wyloguj</a>
            </nav>
        <?php else: ?>
            <h2>Nie jesteś zalogowany</h2>
            <p>Zaloguj się albo utwórz nowe konto, aby dodawać wyniki pomiarów.</p>
            <nav>
                <a href="login.php">Logowanie</a>
                <a class="secondary" href="register.php">Rejestracja</a>
            </nav>
        <?php endif; ?>
    </section>

    <section class="card">
        <h2>Opis projektu</h2>
        <p><a href="DokumentacjaProjektu.pdf" target="_blank">Dokumentacja projektu</a></p>
    </section>
</main>
</body>
</html>
