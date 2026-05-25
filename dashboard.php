<?php
require_once __DIR__ . '/config.php';
require_login();

$errors = [];
$types = get_measurement_types();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $typeId = (int) ($_POST['type_id'] ?? 0);
    $valuePrimary = trim($_POST['value_primary'] ?? '');
    $measuredAtInput = trim($_POST['measured_at'] ?? '');
    $type = get_measurement_type($typeId);

    if (!$type) {
        $errors[] = 'Wybierz rodzaj pomiaru.';
    }

    if ($valuePrimary === '' || !is_numeric($valuePrimary)) {
        $errors[] = 'Podaj poprawną wartość pomiaru.';
    }

    $date = DateTime::createFromFormat('Y-m-d\TH:i', $measuredAtInput);
    if (!$date) {
        $errors[] = 'Podaj poprawną datę i czas pomiaru.';
    }

    if (!$errors) {
        if (save_measurement((int) $_SESSION['current_user'], $typeId, $valuePrimary, $date->format('Y-m-d H:i:s'))) {
            header('Location: dashboard.php?added=1');
            exit;
        }

        $errors[] = 'Błąd zapisu pomiaru: ' . mysqli_error(db());
    }
}

$units = db_fetch_all('SELECT * FROM biomedical_units ORDER BY unit_id');

$measurements = db_fetch_all(
    'SELECT m.*, mt.type_name, bu.unit_symbol
     FROM measurements m
     JOIN measurement_types mt ON mt.type_id = m.type_id
     JOIN biomedical_units bu ON bu.unit_id = mt.unit_id
     WHERE m.user_id = ' . (int) $_SESSION['current_user'] . '
     ORDER BY m.measured_at DESC
     LIMIT 10'
);
?>
<!doctype html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel pomiarów</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header>
    <h1>Panel pomiarów</h1>
    <p>Zalogowany użytkownik: <?= $_SESSION['current_username'] ?? '' ?></p>
</header>
<main>
    <section class="card">
        <nav>
            <a href="index.php">Start</a>
            <a href="units.php">Jednostki</a>
            <a href="measurement_types.php">Katalog badań</a>
            <a href="change_password.php">Zmień hasło</a>
            <a class="secondary" href="logout.php">Wyloguj</a>
        </nav>
    </section>

    <section class="card">
        <h2>Dodaj nowy pomiar</h2>
        <?php if (isset($_GET['added'])): ?>
            <p class="message success">Pomiar został zapisany.</p>
        <?php endif; ?>
        <?php foreach ($errors as $error): ?>
            <p class="message error"><?= $error ?></p>
        <?php endforeach; ?>

        <form method="post" action="dashboard.php">
            <label for="type_id">Rodzaj pomiaru</label>
            <select id="type_id" name="type_id" required>
                <?php foreach ($types as $type): ?>
                    <option value="<?= (string) $type['type_id'] ?>">
                        <?= $type['type_name'] ?> (<?= $type['unit_symbol'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="value_primary">Wartość pomiaru</label>
            <input type="number" step="0.01" id="value_primary" name="value_primary" required>

            <label for="measured_at">Data i czas wykonania</label>
            <input type="datetime-local" id="measured_at" name="measured_at" value="<?= date('Y-m-d\TH:i') ?>" required>

            <button class="button" type="submit">Zapisz pomiar</button>
        </form>
    </section>

    <section class="card">
        <h2>Jednostki wielkości biomedycznych</h2>
        <table>
            <thead><tr><th>Nazwa</th><th>Symbol</th></tr></thead>
            <tbody>
            <?php foreach ($units as $unit): ?>
                <tr><td><?= $unit['unit_name'] ?></td><td><?= $unit['unit_symbol'] ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <section class="card">
        <h2>Katalog wielkości mierzonych</h2>
        <form method="get" action="measurements.php">
            <label for="catalog_type_id">Wybierz wielkość</label>
            <select id="catalog_type_id" name="type_id" required>
                <?php foreach ($types as $type): ?>
                    <option value="<?= (string) $type['type_id'] ?>">
                        <?= $type['type_name'] ?> (<?= $type['unit_symbol'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <button class="button" type="submit">Pokaż zapisy</button>
        </form>

        <table>
            <thead><tr><th>Nazwa</th><th>Jednostka</th><th>Autor</th></tr></thead>
            <tbody>
            <?php foreach ($types as $type): ?>
                <tr>
                    <td><?= $type['type_name'] ?></td>
                    <td><?= $type['unit_symbol'] ?></td>
                    <td><?= $type['creator_name'] ?? 'System' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <section class="card">
        <h2>Ostatnie zapisane wyniki</h2>
        <?php if (!$measurements): ?>
            <p>Brak zapisanych pomiarów.</p>
        <?php else: ?>
            <table>
                <thead><tr><th>Parametr</th><th>Wartość</th><th>Data pomiaru</th></tr></thead>
                <tbody>
                <?php foreach ($measurements as $row): ?>
                    <tr>
                        <td><?= $row['type_name'] ?></td>
                        <td><?= format_value($row) ?></td>
                        <td><?= $row['measured_at'] ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
