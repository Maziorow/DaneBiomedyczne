<?php
require_once __DIR__ . '/config.php';
start_session();

$errors = [];
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($name === '') {
        $errors[] = 'Podaj imię i nazwisko.';
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Podaj poprawny adres e-mail.';
    }

    if (strlen($password) < 8) {
        $errors[] = 'Hasło musi mieć co najmniej 8 znaków.';
    }

    if (!$errors) {
        try {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = db()->prepare(
                'INSERT INTO users (user_fullname, user_email, user_passwordhash) VALUES (:name, :email, :hash)'
            );
            $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':hash' => $passwordHash,
            ]);

            header('Location: login.php?registered=1');
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $errors[] = 'Użytkownik z tym adresem e-mail już istnieje.';
            } else {
                $errors[] = 'Wystąpił błąd podczas rejestracji.';
            }
        }
    }
}
?>
<!doctype html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rejestracja</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header><h1>Rejestracja</h1></header>
<main>
    <section class="card">
        <?php foreach ($errors as $error): ?>
            <p class="message error"><?= e($error) ?></p>
        <?php endforeach; ?>

        <form method="post" action="register.php">
            <label for="name">Imię i nazwisko</label>
            <input type="text" id="name" name="name" value="<?= e($name) ?>" required>

            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" value="<?= e($email) ?>" required>

            <label for="password">Hasło</label>
            <input type="password" id="password" name="password" required minlength="8">

            <button class="button" type="submit">Zarejestruj</button>
            <a class="button secondary" href="login.php">Mam już konto</a>
        </form>
    </section>
</main>
</body>
</html>
