<?php
const DB_HOST = 'mysql.agh.edu.pl';
const DB_NAME = 'mateusz5';
const DB_USER = 'mateusz5';
const DB_PASS = 'V0Mn4JuL3iZMwaUR';

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    return $pdo;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function start_session(): void
{
    if (!headers_sent()) {
        header('Content-Type: text/html; charset=UTF-8');
    }

    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function require_login(): void
{
    start_session();

    if (!isset($_SESSION['current_user'])) {
        header('Location: login.php');
        exit;
    }
}

function get_measurement_types(): array
{
    $stmt = db()->query(
        'SELECT mt.*, bu.unit_symbol, u.user_fullname AS creator_name
         FROM measurement_types mt
         JOIN biomedical_units bu ON bu.unit_id = mt.unit_id
         LEFT JOIN users u ON u.user_id = mt.created_by_user_id
         ORDER BY mt.type_id'
    );

    return $stmt->fetchAll();
}

function get_measurement_type(int $typeId): ?array
{
    $stmt = db()->prepare(
        'SELECT mt.*, bu.unit_symbol, u.user_fullname AS creator_name
         FROM measurement_types mt
         JOIN biomedical_units bu ON bu.unit_id = mt.unit_id
         LEFT JOIN users u ON u.user_id = mt.created_by_user_id
         WHERE mt.type_id = :id'
    );
    $stmt->execute([':id' => $typeId]);
    $type = $stmt->fetch();

    return $type ?: null;
}

function save_measurement(int $userId, int $typeId, string $valuePrimary, ?string $valueSecondary, string $measuredAt): void
{
    $stmt = db()->prepare(
        'INSERT INTO measurements (user_id, type_id, value_primary, value_secondary, measured_at)
         VALUES (:user_id, :type_id, :value_primary, :value_secondary, :measured_at)'
    );
    $stmt->execute([
        ':user_id' => $userId,
        ':type_id' => $typeId,
        ':value_primary' => $valuePrimary,
        ':value_secondary' => $valueSecondary,
        ':measured_at' => $measuredAt,
    ]);
}

function get_norm_for_type(int $typeId, ?int $userId = null): ?array
{
    if ($userId !== null) {
        $stmt = db()->prepare(
            'SELECT *
             FROM measurement_norms
             WHERE type_id = :type_id
               AND (created_by_user_id = :user_id_filter OR created_by_user_id IS NULL)
             ORDER BY CASE WHEN created_by_user_id = :user_id_order THEN 0 ELSE 1 END, norm_id DESC
             LIMIT 1'
        );
        $stmt->execute([
            ':type_id' => $typeId,
            ':user_id_filter' => $userId,
            ':user_id_order' => $userId,
        ]);
    } else {
        $stmt = db()->prepare(
            'SELECT *
             FROM measurement_norms
             WHERE type_id = :type_id AND created_by_user_id IS NULL
             ORDER BY norm_id DESC
             LIMIT 1'
        );
        $stmt->execute([':type_id' => $typeId]);
    }

    $norm = $stmt->fetch();

    return $norm ?: null;
}

function save_user_norm(int $typeId, int $userId, ?string $minValue, ?string $maxValue, string $source): void
{
    $minValue = $minValue !== null && trim($minValue) !== '' ? trim($minValue) : null;
    $maxValue = $maxValue !== null && trim($maxValue) !== '' ? trim($maxValue) : null;
    $source = trim($source);

    $stmt = db()->prepare(
        'DELETE FROM measurement_norms
         WHERE type_id = :type_id AND created_by_user_id = :user_id'
    );
    $stmt->execute([
        ':type_id' => $typeId,
        ':user_id' => $userId,
    ]);

    if ($minValue === null && $maxValue === null) {
        return;
    }

    $stmt = db()->prepare(
        'INSERT INTO measurement_norms (type_id, min_value, max_value, source, created_by_user_id)
         VALUES (:type_id, :min_value, :max_value, :source, :user_id)'
    );
    $stmt->execute([
        ':type_id' => $typeId,
        ':min_value' => $minValue,
        ':max_value' => $maxValue,
        ':source' => $source !== '' ? $source : null,
        ':user_id' => $userId,
    ]);
}

function measurement_norm_status(string $value, ?array $norm): array
{
    if (!$norm) {
        return ['label' => 'Brak normy', 'class' => 'status-missing', 'outside' => false];
    }

    $number = (float) $value;
    $min = $norm['min_value'] !== null ? (float) $norm['min_value'] : null;
    $max = $norm['max_value'] !== null ? (float) $norm['max_value'] : null;

    if ($min !== null && $number < $min) {
        return ['label' => 'Poniżej normy', 'class' => 'status-low', 'outside' => true];
    }

    if ($max !== null && $number > $max) {
        return ['label' => 'Powyżej normy', 'class' => 'status-high', 'outside' => true];
    }

    return ['label' => 'W normie', 'class' => 'status-ok', 'outside' => false];
}

function format_norm(?array $norm, string $unitSymbol): string
{
    if (!$norm) {
        return 'Brak normy';
    }

    $min = $norm['min_value'];
    $max = $norm['max_value'];

    if ($min !== null && $max !== null) {
        return e($min) . ' - ' . e($max) . ' ' . e($unitSymbol);
    }

    if ($min !== null) {
        return 'od ' . e($min) . ' ' . e($unitSymbol);
    }

    if ($max !== null) {
        return 'do ' . e($max) . ' ' . e($unitSymbol);
    }

    return 'Brak normy';
}

function format_value(array $row): string
{
    return e($row['value_primary']) . ' ' . e($row['unit_symbol']);
}

function make_slug(string $text): string
{
    $text = strtr($text, [
        'ą' => 'a', 'ć' => 'c', 'ę' => 'e', 'ł' => 'l', 'ń' => 'n',
        'ó' => 'o', 'ś' => 's', 'ż' => 'z', 'ź' => 'z',
        'Ą' => 'a', 'Ć' => 'c', 'Ę' => 'e', 'Ł' => 'l', 'Ń' => 'n',
        'Ó' => 'o', 'Ś' => 's', 'Ż' => 'z', 'Ź' => 'z',
    ]);
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');

    return $text !== '' ? $text : 'badanie';
}
