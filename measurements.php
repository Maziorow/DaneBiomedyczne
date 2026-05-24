<?php
require_once __DIR__ . '/config.php';
require_login();

$typeId = (int) ($_GET['type_id'] ?? $_POST['type_id'] ?? 0);
$type = get_measurement_type($typeId);
$currentUserId = (int) $_SESSION['current_user'];

if (!$type) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];
$statErrors = [];
$editMeasurement = null;
$norm = get_norm_for_type($typeId, $currentUserId);

if (isset($_GET['delete_id'])) {
    $stmt = db()->prepare(
        'DELETE FROM measurements
         WHERE measurement_id = :measurement_id
           AND user_id = :user_id
           AND type_id = :type_id'
    );
    $stmt->execute([
        ':measurement_id' => (int) $_GET['delete_id'],
        ':user_id' => $currentUserId,
        ':type_id' => $typeId,
    ]);

    header('Location: measurements.php?type_id=' . $typeId . '&deleted=1');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';
    $measurementId = (int) ($_POST['measurement_id'] ?? 0);

    $valuePrimary = trim($_POST['value_primary'] ?? '');
    $measuredAtInput = trim($_POST['measured_at'] ?? '');

    if ($valuePrimary === '' || !is_numeric($valuePrimary)) {
        $errors[] = 'Podaj poprawną wartość pomiaru.';
    }

    $date = DateTime::createFromFormat('Y-m-d\TH:i', $measuredAtInput);
    if (!$date) {
        $errors[] = 'Podaj poprawną datę i czas pomiaru.';
    }
# edycja pomiaru
    if (!$errors && $action === 'update' && $measurementId > 0) {
        $stmt = db()->prepare(
            'UPDATE measurements
             SET value_primary = :value_primary, measured_at = :measured_at
             WHERE measurement_id = :measurement_id
               AND user_id = :user_id
               AND type_id = :type_id'
        );
        $stmt->execute([
            ':value_primary' => $valuePrimary,
            ':measured_at' => $date->format('Y-m-d H:i:s'),
            ':measurement_id' => $measurementId,
            ':user_id' => $currentUserId,
            ':type_id' => $typeId,
        ]);

        header('Location: measurements.php?type_id=' . $typeId . '&updated=1');
        exit;
    }

    if (!$errors) {
        save_measurement($currentUserId, $typeId, $valuePrimary, $date->format('Y-m-d H:i:s'));
        header('Location: measurements.php?type_id=' . $typeId . '&added=1');
        exit;
    }
}

if (isset($_GET['edit_id'])) {
    $stmt = db()->prepare(
        'SELECT *
         FROM measurements
         WHERE measurement_id = :measurement_id
           AND user_id = :user_id
           AND type_id = :type_id'
    );
    $stmt->execute([
        ':measurement_id' => (int) $_GET['edit_id'],
        ':user_id' => $currentUserId,
        ':type_id' => $typeId,
    ]);
    $editMeasurement = $stmt->fetch() ?: null;
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
    ':user_id' => $currentUserId,
    ':type_id' => $typeId,
]);
$measurements = $stmt->fetchAll();

$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo = trim($_GET['date_to'] ?? '');
$stats = null;
$outsideRows = [];

# statystyka
if ($dateFrom !== '' || $dateTo !== '') {
    $fromDate = DateTime::createFromFormat('Y-m-d', $dateFrom);
    $toDate = DateTime::createFromFormat('Y-m-d', $dateTo);

    if (!$fromDate || !$toDate) {
        $statErrors[] = 'Podaj poprawny zakres dat.';
    } elseif ($fromDate > $toDate) {
        $statErrors[] = 'Data od nie moze byc pozniejsza niz data do.';
    } else {
        $stmt = db()->prepare(
            'SELECT *
             FROM measurements
             WHERE user_id = :user_id
               AND type_id = :type_id
               AND measured_at BETWEEN :date_from AND :date_to
             ORDER BY measured_at'
        );
        $stmt->execute([
            ':user_id' => $currentUserId,
            ':type_id' => $typeId,
            ':date_from' => $fromDate->format('Y-m-d 00:00:00'),
            ':date_to' => $toDate->format('Y-m-d 23:59:59'),
        ]);
        $statRows = $stmt->fetchAll();

        if ($statRows) {
            $values = array_map('floatval', array_column($statRows, 'value_primary'));
            $lowCount = 0;
            $highCount = 0;

            foreach ($statRows as $row) {
                $status = measurement_norm_status($row['value_primary'], $norm);
                if ($status['class'] === 'status-low') {
                    $lowCount++;
                    $outsideRows[] = $row;
                }
                if ($status['class'] === 'status-high') {
                    $highCount++;
                    $outsideRows[] = $row;
                }
            }

            $stats = [
                'count' => count($values),
                'min' => min($values),
                'max' => max($values),
                'avg' => array_sum($values) / count($values),
                'low' => $lowCount,
                'high' => $highCount,
            ];
        }
    }
}
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
        <h2><?= $editMeasurement ? 'Edytuj pomiar' : 'Dopisz pomiar' ?></h2>
        <?php if (isset($_GET['added'])): ?>
            <p class="message success">Pomiar został zapisany.</p>
        <?php endif; ?>
        <?php if (isset($_GET['updated'])): ?>
            <p class="message success">Pomiar został zaktualizowany.</p>
        <?php endif; ?>
        <?php if (isset($_GET['deleted'])): ?>
            <p class="message success">Pomiar został usunięty.</p>
        <?php endif; ?>
        <?php foreach ($errors as $error): ?>
            <p class="message error"><?= e($error) ?></p>
        <?php endforeach; ?>

        <form method="post" action="measurements.php">
            <input type="hidden" name="type_id" value="<?= e((string) $typeId) ?>">
            <input type="hidden" name="action" value="<?= $editMeasurement ? 'update' : 'create' ?>">
            <input type="hidden" name="measurement_id"
                value="<?= e((string) ($editMeasurement['measurement_id'] ?? 0)) ?>">

            <label for="value_primary"><?= e($type['value_label']) ?> (<?= e($type['unit_symbol']) ?>)</label>
            <input type="number" step="0.01" id="value_primary" name="value_primary"
                value="<?= e((string) ($valuePrimary ?? ($editMeasurement['value_primary'] ?? ''))) ?>" required>

            <label for="measured_at">Data i czas wykonania</label>
        <input type="datetime-local" id="measured_at" name="measured_at"
            value="<?= e($measuredAtInput ?? ($editMeasurement ? date('Y-m-d\TH:i', strtotime($editMeasurement['measured_at'])) : date('Y-m-d\TH:i'))) ?>"
            required>

            <button class="button" type="submit"><?= $editMeasurement ? 'Zapisz zmiany' : 'Zapisz' ?></button>
            <?php if ($editMeasurement): ?>
                <a class="button secondary" href="measurements.php?type_id=<?= e((string) $typeId) ?>">Anuluj edycję</a>
            <?php endif; ?>
        </form>
</section>

    <section class="card">
        <h2>Statystyka</h2>
        <?php foreach ($statErrors as $error): ?>
            <p class="message error"><?= e($error) ?></p>
        <?php endforeach; ?>

        <form method="get" action="measurements.php">
            <input type="hidden" name="type_id" value="<?= e((string) $typeId) ?>">

            <label for="date_from">Data od</label>
            <input type="date" id="date_from" name="date_from" value="<?= e($dateFrom) ?>">

            <label for="date_to">Data do</label>
            <input type="date" id="date_to" name="date_to" value="<?= e($dateTo) ?>">

            <button class="button" type="submit">Pokaż statystykę</button>
        </form>

        <?php if ($stats): ?>
            <table>
                <tbody>
                    <tr>
                        <th>Liczba pomiarów</th>
                        <td><?= e((string) $stats['count']) ?></td>
                    </tr>
                    <tr>
                        <th>Minimum</th>
                        <td><?= e(number_format($stats['min'], 2)) ?>     <?= e($type['unit_symbol']) ?></td>
                    </tr>
                    <tr>
                        <th>Maksimum</th>
                        <td><?= e(number_format($stats['max'], 2)) ?>     <?= e($type['unit_symbol']) ?></td>
                    </tr>
                    <tr>
                        <th>Srednia</th>
                        <td><?= e(number_format($stats['avg'], 2)) ?>     <?= e($type['unit_symbol']) ?></td>
                    </tr>
                    <tr>
                        <th>Ponizej normy</th>
                        <td><?= e((string) $stats['low']) ?></td>
                    </tr>
                    <tr>
                        <th>Powyzej normy</th>
                        <td><?= e((string) $stats['high']) ?></td>
                    </tr>
                </tbody>
            </table>
            <?php if ($outsideRows): ?>
                <h3>Przekroczenia normy</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Wartość</th>
                            <th>Data pomiaru</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($outsideRows as $row): ?>
                            <?php $rowStatus = measurement_norm_status($row['value_primary'], $norm); ?>
                            <tr>
                                <td><?= e($row['value_primary']) ?>             <?= e($type['unit_symbol']) ?></td>
                                <td><?= e($row['measured_at']) ?></td>
                                <td><span class="status <?= e($rowStatus['class']) ?>"><?= e($rowStatus['label']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php elseif ($dateFrom !== '' || $dateTo !== ''): ?>
            <p>Brak pomiarów w wybranym okresie.</p>
        <?php endif; ?>
    </section>

        <section class="card">
            <h2>Zapisane wyniki</h2>
            <?php if (!$measurements): ?>
                <p>Brak pomiarów dla tego parametru.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Wartość</th>
                            <th>Data pomiaru</th>
                            <th>Norma</th>
                            <th>Status</th>
                            <th>Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($measurements as $row): ?>
                            <?php $status = measurement_norm_status($row['value_primary'], $norm); ?>
                            <tr>
                                <td><?= format_value($row) ?></td>
                                <td><?= e($row['measured_at']) ?></td>
                                <td><?= format_norm($norm, $type['unit_symbol']) ?></td>
                                <td><span class="status <?= e($status['class']) ?>"><?= e($status['label']) ?></span></td>
                                <td class="actions">
                                    <a
                                        href="measurements.php?type_id=<?= e((string) $typeId) ?>&edit_id=<?= e((string) $row['measurement_id']) ?>">Edytuj</a>
                                    <a href="measurements.php?type_id=<?= e((string) $typeId) ?>&delete_id=<?= e((string) $row['measurement_id']) ?>"
                                        onclick="return confirm('Usunac ten pomiar?');">Usun</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    </main>
</body>

</html>
