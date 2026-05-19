<?php
require_once __DIR__ . '/config.php';
start_session();

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Podaj poprawny adres e-mail.';
    }

    if ($password === '') {
        $errors[] = 'Podaj hasło.';
    }

    if (!$errors) {
        $stmt = db()->prepare('SELECT * FROM users WHERE user_email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['user_passwordhash'])) {
            session_regenerate_id(true);
            $_SESSION['current_user'] = $user['user_id'];
            $_SESSION['current_username'] = $user['user_fullname'];
            header('Location: dashboard.php');
            exit;
        }

        $errors[] = 'Błędny e-mail lub hasło.';
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
        </form>
    </section>
</main>
</body>
</html>
