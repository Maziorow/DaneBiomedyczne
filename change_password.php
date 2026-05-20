<?php
require_once __DIR__ . '/config.php';
require_login();

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldPassword = $_POST['old_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $repeatPassword = $_POST['repeat_password'] ?? '';

    if ($oldPassword === '') {
        $errors[] = 'Podaj aktualne hasło.';
    }

    if (strlen($newPassword) < 8) {
        $errors[] = 'Nowe hasło musi mieć co najmniej 8 znaków.';
    }

    if ($newPassword !== $repeatPassword) {
        $errors[] = 'Powtórzone hasło nie jest takie samo.';
    }

    if (!$errors) {
        $stmt = db()->prepare('SELECT user_passwordhash FROM users WHERE user_id = :user_id LIMIT 1');
        $stmt->execute([':user_id' => $_SESSION['current_user']]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($oldPassword, $user['user_passwordhash'])) {
            $errors[] = 'Aktualne hasło jest niepoprawne.';
        } else {
            $stmt = db()->prepare(
                'UPDATE users
                 SET user_passwordhash = :password_hash
                 WHERE user_id = :user_id'
            );
            $stmt->execute([
                ':password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
                ':user_id' => $_SESSION['current_user'],
            ]);
            $success = true;
        }
    }
}
?>
<!doctype html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zmiana hasła</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header>
    <h1>Zmiana hasła</h1>
    <p>Zalogowany użytkownik: <?= e($_SESSION['current_username'] ?? '') ?></p>
</header>
<main>
    <section class="card">
        <nav>
            <a href="dashboard.php">Panel pomiarów</a>
            <a href="index.php">Start</a>
            <a class="secondary" href="logout.php">Wyloguj</a>
        </nav>
    </section>

    <section class="card">
        <?php if ($success): ?>
            <p class="message success">Hasło zostało zmienione.</p>
        <?php endif; ?>

        <?php foreach ($errors as $error): ?>
            <p class="message error"><?= e($error) ?></p>
        <?php endforeach; ?>

        <form method="post" action="change_password.php">
            <label for="old_password">Aktualne hasło</label>
            <input type="password" id="old_password" name="old_password" required>

            <label for="new_password">Nowe hasło</label>
            <input type="password" id="new_password" name="new_password" required minlength="8">

            <label for="repeat_password">Powtórz nowe hasło</label>
            <input type="password" id="repeat_password" name="repeat_password" required minlength="8">

            <button class="button" type="submit">Zmień hasło</button>
        </form>
    </section>
</main>
</body>
</html>
