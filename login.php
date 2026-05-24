<?php
require_once __DIR__ . '/config.php';
start_session();

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $user = null;
    $success = false;

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Podaj poprawny adres e-mail.';
    }

    if ($password === '') {
        $errors[] = 'Podaj hasło.';
    }

    if (!$errors) {
        try {
            $userEmail = mysqli_real_escape_string(db(), $email);
            $user = db_fetch_one("SELECT * FROM users WHERE user_email = '$userEmail' LIMIT 1");
            $success = $user && password_verify($password, $user['user_passwordhash']);

            $attemptEmail = $email !== '' ? substr($email, 0, 128) : '(brak)';
            $attemptUserId = isset($user['user_id']) ? (int) $user['user_id'] : 'NULL';
            $attemptEmail = mysqli_real_escape_string(db(), $attemptEmail);
            $wasSuccessful = $success ? 1 : 0;
            $clientIp = mysqli_real_escape_string(db(), $_SERVER['REMOTE_ADDR'] ?? 'unknown');

            $sql = 'INSERT INTO login_attempts (user_id, email, was_successful, client_ip)
                    VALUES (' . $attemptUserId . ", '$attemptEmail', $wasSuccessful, '$clientIp')";

            if (!mysqli_query(db(), $sql)) {
                $errors[] = 'Błąd: ' . $sql . ' ' . mysqli_error(db());
            }

            if ($success && !$errors) {
                session_regenerate_id(true);
                $_SESSION['current_user'] = $user['user_id'];
                $_SESSION['current_username'] = $user['user_fullname'];
                header('Location: dashboard.php');
                exit;
            }

            if (!$errors) {
                $errors[] = 'Błędny e-mail lub hasło.';
            }
        } catch (mysqli_sql_exception $exception) {
            $errors[] = 'Nie udało się połączyć z bazą danych. Sprawdź, czy MySQL jest uruchomiony.';
        }
    }
}
?>
<!doctype html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logowanie</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header><h1>Logowanie</h1></header>
<main>
    <section class="card">
        <?php if (isset($_GET['registered'])): ?>
            <p class="message success">Konto zostało utworzone. Możesz się zalogować.</p>
        <?php endif; ?>
        <?php if (isset($_GET['reset'])): ?>
            <p class="message success">Hasło zostało zmienione. Zaloguj się nowym hasłem.</p>
        <?php endif; ?>

        <?php foreach ($errors as $error): ?>
            <p class="message error"><?= e($error) ?></p>
        <?php endforeach; ?>

        <form method="post" action="login.php">
            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" value="<?= e($email) ?>" required>

            <label for="password">Hasło</label>
            <input type="password" id="password" name="password" required>

            <button class="button" type="submit">Zaloguj</button>
            <a class="button secondary" href="register.php">Nie mam konta</a>
            <a class="button secondary" href="reset_password.php">Nie pamiętam hasła</a>
        </form>
    </section>
    <section class="card">
        <h2>Opis projektu</h2>
        <p><a href="DokumentacjaProjektu.pdf" target="_blank">Dokumentacja projektu</a></p>
    </section>
</main>
</body>
</html>
