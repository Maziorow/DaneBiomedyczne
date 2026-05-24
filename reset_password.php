<?php
require_once __DIR__ . '/config.php';
start_session();

$errors = [];
$email = '';
$userForReset = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $email = trim($_POST['email'] ?? '');

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Podaj poprawny adres e-mail.';
    }

    if (!$errors) {
        $emailForSql = mysqli_real_escape_string(db(), $email);
        $userForReset = db_fetch_one(
            'SELECT user_id, user_email, reset_question, reset_answer_hash
             FROM users
             WHERE user_email = \'' . $emailForSql . '\'
             LIMIT 1'
        );

        if (!$userForReset) {
            $errors[] = 'Nie znaleziono konta z takim adresem e-mail.';
        }
    }

    if (!$errors && $action === 'reset') {
        $resetAnswer = trim($_POST['reset_answer'] ?? '');
        $newPassword = $_POST['new_password'] ?? '';
        $repeatPassword = $_POST['repeat_password'] ?? '';

        if (strlen($resetAnswer) < 2) {
            $errors[] = 'Podaj odpowiedź pomocniczą.';
        }

        if (strlen($newPassword) < 8) {
            $errors[] = 'Nowe hasło musi mieć co najmniej 8 znaków.';
        }

        if ($newPassword !== $repeatPassword) {
            $errors[] = 'Powtórzone hasło nie jest takie samo.';
        }

        if (!$errors && !password_verify(strtolower($resetAnswer), $userForReset['reset_answer_hash'])) {
            $errors[] = 'Odpowiedź pomocnicza jest niepoprawna.';
        }

        if (!$errors) {
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $passwordHash = mysqli_real_escape_string(db(), $passwordHash);
            $userId = (int) $userForReset['user_id'];
            $sql = 'UPDATE users
                    SET user_passwordhash = \'' . $passwordHash . '\'
                    WHERE user_id = ' . $userId;

            if (mysqli_query(db(), $sql)) {
                header('Location: login.php?reset=1');
                exit;
            }

            $errors[] = 'Błąd: ' . $sql . ' ' . mysqli_error(db());
        }
    }
}
?>
<!doctype html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset hasła</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header><h1>Reset hasła</h1></header>
<main>
    <section class="card">
        <?php foreach ($errors as $error): ?>
            <p class="message error"><?= e($error) ?></p>
        <?php endforeach; ?>

        <?php if ($userForReset): ?>
            <form method="post" action="reset_password.php">
                <input type="hidden" name="action" value="reset">
                <input type="hidden" name="email" value="<?= e($email) ?>">

                <p><strong>Pytanie pomocnicze:</strong> <?= e($userForReset['reset_question']) ?></p>

                <label for="reset_answer">Odpowiedź pomocnicza</label>
                <input type="password" id="reset_answer" name="reset_answer" required>

                <label for="new_password">Nowe hasło</label>
                <input type="password" id="new_password" name="new_password" required minlength="8">

                <label for="repeat_password">Powtórz nowe hasło</label>
                <input type="password" id="repeat_password" name="repeat_password" required minlength="8">

                <button class="button" type="submit">Ustaw nowe hasło</button>
                <a class="button secondary" href="login.php">Wróć do logowania</a>
            </form>
        <?php else: ?>
            <form method="post" action="reset_password.php">
                <input type="hidden" name="action" value="lookup">

                <label for="email">E-mail konta</label>
                <input type="email" id="email" name="email" value="<?= e($email) ?>" required>

                <button class="button" type="submit">Pokaż pytanie pomocnicze</button>
                <a class="button secondary" href="login.php">Wróć do logowania</a>
            </form>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
