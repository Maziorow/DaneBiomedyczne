<?php
require_once __DIR__ . '/config.php';
require_login();

$errors = [];
$success = '';
$editUnit = null;

if (isset($_GET['edit_id'])) {
    $stmt = db()->prepare('SELECT * FROM biomedical_units WHERE unit_id = :unit_id');
    $stmt->execute([':unit_id' => (int) $_GET['edit_id']]);
    $editUnit = $stmt->fetch() ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $unitName = trim($_POST['unit_name'] ?? '');
    $unitSymbol = trim($_POST['unit_symbol'] ?? '');
    $unitId = (int) ($_POST['unit_id'] ?? 0);

    if ($unitName === '') {
        $errors[] = 'Podaj nazwę jednostki.';
    }

    if ($unitSymbol === '') {
        $errors[] = 'Podaj symbol jednostki.';
    }

    if (!$errors) {
        try {
            if ($action === 'update' && $unitId > 0) {
                $stmt = db()->prepare(
                    'UPDATE biomedical_units
                     SET unit_name = :unit_name, unit_symbol = :unit_symbol
                     WHERE unit_id = :unit_id'
                );
                $stmt->execute([
                    ':unit_name' => $unitName,
                    ':unit_symbol' => $unitSymbol,
                    ':unit_id' => $unitId,
                ]);
                header('Location: units.php?updated=1');
                exit;
            }

            $stmt = db()->prepare(
                'INSERT INTO biomedical_units (unit_name, unit_symbol)
                 VALUES (:unit_name, :unit_symbol)'
            );
            $stmt->execute([
                ':unit_name' => $unitName,
                ':unit_symbol' => $unitSymbol,
            ]);
            header('Location: units.php?added=1');
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $errors[] = 'Jednostka z takim symbolem już istnieje.';
            } else {
                $errors[] = 'Wystąpił błąd podczas zapisu jednostki.';
            }
        }
    }
}

if (isset($_GET['added'])) {
    $success = 'Jednostka została dodana.';
}
if (isset($_GET['updated'])) {
    $success = 'Jednostka została zaktualizowana.';
}

$units = db()->query('SELECT * FROM biomedical_units ORDER BY unit_id')->fetchAll();
?>
<!doctype html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jednostki biomedyczne</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header>
    <h1>Jednostki biomedyczne</h1>
    <p>Zarządzanie jednostkami używanymi w badaniach.</p>
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
        <h2><?= $editUnit ? 'Edytuj jednostkę' : 'Dodaj jednostkę' ?></h2>

        <?php if ($success): ?>
            <p class="message success"><?= e($success) ?></p>
        <?php endif; ?>
        <?php foreach ($errors as $error): ?>
            <p class="message error"><?= e($error) ?></p>
        <?php endforeach; ?>

        <form method="post" action="units.php">
            <input type="hidden" name="action" value="<?= $editUnit ? 'update' : 'create' ?>">
            <input type="hidden" name="unit_id" value="<?= e((string) ($editUnit['unit_id'] ?? 0)) ?>">

            <label for="unit_name">Nazwa jednostki</label>
            <input type="text" id="unit_name" name="unit_name" value="<?= e($editUnit['unit_name'] ?? '') ?>" required>

            <label for="unit_symbol">Symbol</label>
            <input type="text" id="unit_symbol" name="unit_symbol" value="<?= e($editUnit['unit_symbol'] ?? '') ?>" required>

            <button class="button" type="submit"><?= $editUnit ? 'Zapisz zmiany' : 'Dodaj jednostkę' ?></button>
            <?php if ($editUnit): ?>
                <a class="button secondary" href="units.php">Anuluj edycję</a>
            <?php endif; ?>
        </form>
    </section>

    <section class="card">
        <h2>Lista jednostek</h2>
        <table>
            <thead><tr><th>Nazwa</th><th>Symbol</th><th>Edycja</th></tr></thead>
            <tbody>
            <?php foreach ($units as $unit): ?>
                <tr>
                    <td><?= e($unit['unit_name']) ?></td>
                    <td><?= e($unit['unit_symbol']) ?></td>
                    <td><a href="units.php?edit_id=<?= e((string) $unit['unit_id']) ?>">Edytuj</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</main>
</body>
</html>
