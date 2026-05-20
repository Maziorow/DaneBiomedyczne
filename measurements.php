<?php
require_once __DIR__ . '/config.php';
require_login();

$typeId = (int) ($_GET['type_id'] ?? $_POST['type_id'] ?? 0);
$type = get_measurement_type($typeId);

if (!$type) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $valuePrimary = trim($_POST['value_primary'] ?? '');
    $measuredAtInput = trim($_POST['measured_at'] ?? '');

    if ($valuePrimary === '' || !is_numeric($valuePrimary)) {
        $errors[] = 'Podaj poprawną wartość pomiaru.';
    }

    $date = DateTime::createFromFormat('Y-m-d\TH:i', $measuredAtInput);
    if (!$date) {
        $errors[] = 'Podaj poprawną datę i czas pomiaru.';
    }

    if (!$errors) {
        save_measurement((int) $_SESSION['current_user'], $typeId, $valuePrimary, null, $date->format('Y-m-d H:i:s'));
        header('Location: measurements.php?type_id=' . $typeId . '&added=1');
        exit;
    }
}

$stmt = db()->prepare(
    'SELECT m.*, mt.type_name, mt.has_second_value, bu.unit_symbol
     FROM measurements m
     JOIN measurement_types mt ON mt.type_id = m.type_id
     JOIN biomedical_units bu ON bu.unit_id = mt.unit_id
     WHERE m.user_id = :user_id AND m.type_id = :type_id
     ORDER BY m.measured_at DESC'
);
$stmt->execute([
    ':user_id' => $_SESSION['current_user'],
    ':type_id' => $typeId,
]);
$measurements = $stmt->fetchAll();
?>
<!doctype html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($type['type_name']) ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header>
    <h1><?= e($type['type_name']) ?></h1>
    <p>Zapisy jednego parametru u zalogowanego użytkownika.</p>
</header>
<main>
    <section class="card">
        <nav>
            <a href="dashboard.php">Panel pomiarów</a>
            <a href="measurement_types.php">Katalog badań</a>
            <a class="secondary" href="logout.php">Wyloguj</a>
        </nav>
    </section>

    <section class="card">
        <h2>Dopisz pomiar</h2>
        <?php if (isset($_GET['added'])): ?>
            <p class="message success">Pomiar został zapisany.</p>
        <?php endif; ?>
        <?php foreach ($errors as $error): ?>
            <p class="message error"><?= e($error) ?></p>
        <?php endforeach; ?>

        <form method="post" action="measurements.php">
            <input type="hidden" name="type_id" value="<?= e((string) $typeId) ?>">

            <label for="value_primary"><?= e($type['value_label']) ?> (<?= e($type['unit_symbol']) ?>)</label>
            <input type="number" step="0.01" id="value_primary" name="value_primary" required>

            <label for="measured_at">Data i czas wykonania</label>
            <input type="datetime-local" id="measured_at" name="measured_at" value="<?= e(date('Y-m-d\TH:i')) ?>" required>

            <button class="button" type="submit">Zapisz</button>
        </form>
    </section>

    <section class="card">
        <h2>Zapisane wyniki</h2>
        <?php if (!$measurements): ?>
            <p>Brak pomiarów dla tego parametru.</p>
        <?php else: ?>
            <table>
                <thead><tr><th>Wartość</th><th>Data pomiaru</th></tr></thead>
                <tbody>
                <?php foreach ($measurements as $row): ?>
                    <tr>
                        <td><?= format_value($row) ?></td>
                        <td><?= e($row['measured_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
