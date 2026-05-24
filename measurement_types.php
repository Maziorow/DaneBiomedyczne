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

$units = db()->query('SELECT * FROM biomedical_units ORDER BY unit_id')->fetchAll();
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
        $stmt = db()->prepare(
            'SELECT COUNT(*) AS count_types
             FROM measurement_types
             WHERE created_by_user_id = :user_id'
        );
        $stmt->execute([':user_id' => $currentUserId]);
        $createdCount = (int) $stmt->fetch()['count_types'];

        if ($createdCount >= 5) {
            $errors[] = 'Nie możesz utworzyć więcej niż 5 własnych pozycji katalogowych.';
        }
    }

    if (!$errors) {
        try {
            if ($action === 'update' && $typeId > 0) {
                $stmt = db()->prepare(
                    'UPDATE measurement_types
                     SET type_name = :type_name,
                         type_slug = :type_slug,
                         unit_id = :unit_id,
                         has_second_value = 0,
                         value_label = :value_label,
                         second_value_label = NULL
                     WHERE type_id = :type_id'
                );
                $stmt->execute([
                    ':type_name' => $typeName,
                    ':type_slug' => make_slug($typeName),
                    ':unit_id' => $unitId,
                    ':value_label' => $valueLabel,
                    ':type_id' => $typeId,
                ]);
                header('Location: measurement_types.php?updated=1');
                exit;
            }

            $stmt = db()->prepare(
                'INSERT INTO measurement_types
                 (type_name, type_slug, unit_id, has_second_value, value_label, second_value_label, created_by_user_id)
                 VALUES (:type_name, :type_slug, :unit_id, 0, :value_label, NULL, :created_by_user_id)'
            );
            $stmt->execute([
                ':type_name' => $typeName,
                ':type_slug' => make_slug($typeName),
                ':unit_id' => $unitId,
                ':value_label' => $valueLabel,
                ':created_by_user_id' => $currentUserId,
            ]);
            header('Location: measurement_types.php?added=1');
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $errors[] = 'Badanie o takiej nazwie już istnieje.';
            } else {
                $errors[] = 'Wystąpił błąd podczas zapisu badania.';
            }
        }
    }
}

if (isset($_GET['added'])) {
    $success = 'Badanie zostało dodane.';
}
if (isset($_GET['updated'])) {
    $success = 'Badanie zostało zaktualizowane.';
}

if (isset($_GET['norm_updated'])) {
    $success = 'Norma zostala zaktualizowana.';
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
            <p class="message success"><?= e($success) ?></p>
        <?php endif; ?>
        <?php foreach ($errors as $error): ?>
            <p class="message error"><?= e($error) ?></p>
        <?php endforeach; ?>

        <form method="post" action="measurement_types.php">
            <input type="hidden" name="action" value="<?= $editType ? 'update' : 'create' ?>">
            <input type="hidden" name="type_id" value="<?= e((string) ($editType['type_id'] ?? 0)) ?>">

            <label for="type_name">Nazwa badania</label>
            <input type="text" id="type_name" name="type_name" value="<?= e($editType['type_name'] ?? '') ?>" required>

            <label for="unit_id">Jednostka</label>
            <select id="unit_id" name="unit_id" required>
                <?php foreach ($units as $unit): ?>
                    <option value="<?= e((string) $unit['unit_id']) ?>" <?= isset($editType['unit_id']) && (int) $editType['unit_id'] === (int) $unit['unit_id'] ? 'selected' : '' ?>>
                        <?= e($unit['unit_name']) ?> (<?= e($unit['unit_symbol']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="value_label">Etykieta wartości</label>
            <input type="text" id="value_label" name="value_label" value="<?= e($editType['value_label'] ?? '') ?>" required>

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
                    <td><?= e($type['type_name']) ?></td>
                    <td><?= e($type['unit_symbol']) ?></td>
                    <td><?= format_norm($typeNorm, $type['unit_symbol']) ?></td>
                    <td><?= e($type['creator_name'] ?? 'System') ?></td>
                    <td class="actions">
                        <a href="measurement_types.php?edit_id=<?= e((string) $type['type_id']) ?>">Edytuj</a>
                        <a href="measurement_norm.php?type_id=<?= e((string) $type['type_id']) ?>">Norma</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</main>
</body>
</html>
