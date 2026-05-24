<?php
require_once __DIR__ . '/config.php';
require_login();

$typeId = (int) ($_GET['type_id'] ?? $_POST['type_id'] ?? 0);
$type = get_measurement_type($typeId);
$currentUserId = (int) $_SESSION['current_user'];

if (!$type) {
    header('Location: measurement_types.php');
    exit;
}

$errors = [];
$norm = get_norm_for_type($typeId, $currentUserId);
$normMin = $norm['min_value'] ?? '';
$normMax = $norm['max_value'] ?? '';
$normSource = $norm['source'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $normMin = trim($_POST['norm_min'] ?? '');
    $normMax = trim($_POST['norm_max'] ?? '');
    $normSource = trim($_POST['norm_source'] ?? '');

    if ($normMin !== '' && !is_numeric($normMin)) {
        $errors[] = 'Minimalna wartość normy musi byc liczba.';
    }

    if ($normMax !== '' && !is_numeric($normMax)) {
        $errors[] = 'Maksymalna wartość normy musi byc liczba.';
    }

    if ($normMin !== '' && $normMax !== '' && (float) $normMin > (float) $normMax) {
        $errors[] = 'Minimalna wartość normy nie moze byc większa od maksymalnej.';
    }

    if (!$errors) {
        if (save_user_norm($typeId, $currentUserId, $normMin, $normMax, $normSource)) {
            header('Location: measurement_types.php?norm_updated=1');
            exit;
        }

        $errors[] = 'Błąd zapisu normy: ' . mysqli_error(db());
    }
}
?>
<!doctype html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Norma badania</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header>
    <h1>Norma badania</h1>
    <p><?= e($type['type_name']) ?> (<?= e($type['unit_symbol']) ?>)</p>
</header>
<main>
    <section class="card">
        <nav>
            <a href="measurement_types.php">Katalog badań</a>
            <a href="measurements.php?type_id=<?= e((string) $typeId) ?>">Zapisy pomiarów</a>
            <a class="secondary" href="logout.php">Wyloguj</a>
        </nav>
    </section>

    <section class="card">
        <h2>Norma referencyjna</h2>

        <?php foreach ($errors as $error): ?>
            <p class="message error"><?= e($error) ?></p>
        <?php endforeach; ?>

        <form method="post" action="measurement_norm.php">
            <input type="hidden" name="type_id" value="<?= e((string) $typeId) ?>">

            <label for="norm_min">Minimalna wartość normy</label>
            <input type="number" step="0.01" id="norm_min" name="norm_min" value="<?= e((string) $normMin) ?>">

            <label for="norm_max">Maksymalna wartość normy</label>
            <input type="number" step="0.01" id="norm_max" name="norm_max" value="<?= e((string) $normMax) ?>">

            <label for="norm_source">Źrodło normy</label>
            <input type="text" id="norm_source" name="norm_source" value="<?= e((string) $normSource) ?>">

            <button class="button" type="submit">Zapisz normę</button>
            <a class="button secondary" href="measurement_types.php">Anuluj</a>
        </form>
    </section>
</main>
</body>
</html>
