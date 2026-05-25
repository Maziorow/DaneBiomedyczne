<?php
require_once __DIR__ . '/config.php';
require_login();

$errors = [];
$success = '';
$editType = null;
$currentUserId = (int) $_SESSION['current_user'];

if (isset($_GET['edit_id'])) {
    $editType = get_measurement_type((int) $_GET['edit_id']);
}

$units = db_fetch_all('SELECT * FROM biomedical_units ORDER BY unit_id');
$unitIds = array_map('intval', array_column($units, 'unit_id'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $typeId = (int) ($_POST['type_id'] ?? 0);
    $typeName = trim($_POST['type_name'] ?? '');
    $unitId = (int) ($_POST['unit_id'] ?? 0);
    $valueLabel = trim($_POST['value_label'] ?? '');

    if ($typeName === '') {
        $errors[] = 'Podaj nazwę badania.';
    }

    if (!in_array($unitId, $unitIds, true)) {
        $errors[] = 'Wybierz poprawną jednostkę.';
    }

    if ($valueLabel === '') {
        $errors[] = 'Podaj etykietę wartości.';
    }

    if (!$errors && $action === 'create') {
        $row = db_fetch_one(
            'SELECT COUNT(*) AS count_types
             FROM measurement_types
             WHERE created_by_user_id = ' . $currentUserId
        );
        $createdCount = (int) $row['count_types'];

        if ($createdCount >= 5) {
            $errors[] = 'Nie możesz utworzyć więcej niż 5 własnych pozycji katalogowych.';
        }
    }

    if (!$errors) {
        $typeNameSql = mysqli_real_escape_string(db(), $typeName);
        $valueLabelSql = mysqli_real_escape_string(db(), $valueLabel);

        if ($action === 'update' && $typeId > 0) {
            $sql = "UPDATE measurement_types
                    SET type_name = '$typeNameSql',
                        unit_id = $unitId,
                        value_label = '$valueLabelSql'
                    WHERE type_id = $typeId";
            $result = mysqli_query(db(), $sql);

            if ($result) {
                header('Location: measurement_types.php?updated=1');
                exit;
            }
        } else {
            $sql = "INSERT INTO measurement_types
                    (type_name, unit_id, value_label, created_by_user_id)
                    VALUES ('$typeNameSql', $unitId, '$valueLabelSql', $currentUserId)";
            $result = mysqli_query(db(), $sql);

            if ($result) {
                header('Location: measurement_types.php?added=1');
                exit;
            }
        }

        $errors[] = 'Błąd: ' . $sql . ' ' . mysqli_error(db());
    }
}

if (isset($_GET['added'])) {
    $success = 'Badanie zostało dodane.';
}
if (isset($_GET['updated'])) {
    $success = 'Badanie zostało zaktualizowane.';
}

if (isset($_GET['norm_updated'])) {
    $success = 'Norma została zaktualizowana.';
}

$types = get_measurement_types();
?>
<!doctype html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog badań</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header>
    <h1>Katalog badań</h1>
    <p>Dodawanie i edycja dostępnych wielkości mierzonych.</p>
</header>
<main>
    <section class="card">
        <nav>
            <a href="dashboard.php">Panel pomiarów</a>
            <a href="units.php">Jednostki</a>
            <a class="secondary" href="logout.php">Wyloguj</a>
        </nav>
    </section>

    <section class="card">
        <h2><?= $editType ? 'Edytuj badanie' : 'Dodaj badanie' ?></h2>

        <?php if ($success): ?>
            <p class="message success"><?= $success ?></p>
        <?php endif; ?>
        <?php foreach ($errors as $error): ?>
            <p class="message error"><?= $error ?></p>
        <?php endforeach; ?>

        <form method="post" action="measurement_types.php">
            <input type="hidden" name="action" value="<?= $editType ? 'update' : 'create' ?>">
            <input type="hidden" name="type_id" value="<?= (string) ($editType['type_id'] ?? 0) ?>">

            <label for="type_name">Nazwa badania</label>
            <input type="text" id="type_name" name="type_name" value="<?= $editType['type_name'] ?? '' ?>" required>

            <label for="unit_id">Jednostka</label>
            <select id="unit_id" name="unit_id" required>
                <?php foreach ($units as $unit): ?>
                    <option value="<?= (string) $unit['unit_id'] ?>" <?= isset($editType['unit_id']) && (int) $editType['unit_id'] === (int) $unit['unit_id'] ? 'selected' : '' ?>>
                        <?= $unit['unit_name'] ?> (<?= $unit['unit_symbol'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="value_label">Etykieta wartości</label>
            <input type="text" id="value_label" name="value_label" value="<?= $editType['value_label'] ?? '' ?>" required>

            <button class="button" type="submit"><?= $editType ? 'Zapisz zmiany' : 'Dodaj badanie' ?></button>
            <?php if ($editType): ?>
                <a class="button secondary" href="measurement_types.php">Anuluj edycję</a>
            <?php endif; ?>
        </form>
    </section>

    <section class="card">
        <h2>Lista badań</h2>
        <table>
            <thead><tr><th>Nazwa</th><th>Jednostka</th><th>Norma</th><th>Autor</th><th>Akcje</th></tr></thead>
            <tbody>
            <?php foreach ($types as $type): ?>
                <?php $typeNorm = get_norm_for_type((int) $type['type_id'], $currentUserId); ?>
                <tr>
                    <td><?= $type['type_name'] ?></td>
                    <td><?= $type['unit_symbol'] ?></td>
                    <td><?= format_norm($typeNorm, $type['unit_symbol']) ?></td>
                    <td><?= $type['creator_name'] ?? 'System' ?></td>
                    <td class="actions">
                        <a href="measurement_types.php?edit_id=<?= (string) $type['type_id'] ?>">Edytuj</a>
                        <a href="measurement_norm.php?type_id=<?= (string) $type['type_id'] ?>">Norma</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</main>
</body>
</html>
