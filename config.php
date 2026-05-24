<?php

const DB_HOST = 'mysql.agh.edu.pl';
const DB_NAME = 'mateusz5';
const DB_USER = 'mateusz5';
const DB_PASS = '6n02QzScDB0j4gCk';

function db(): mysqli
{
    static $connection = null;

    if ($connection === null) {
        $connection = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if (!$connection) {
            throw new mysqli_sql_exception('Nie udało się połączyć z bazą danych: ' . mysqli_connect_error());
        }

        mysqli_set_charset($connection, 'utf8mb4');
    }

    return $connection;
}

function db_fetch_all(string $sql): array
{
    $result = mysqli_query(db(), $sql);
    if (!$result) {
        return [];
    }

    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

function db_fetch_one(string $sql): ?array
{
    $result = mysqli_query(db(), $sql);
    if (!$result) {
        return null;
    }

    $row = mysqli_fetch_assoc($result);

    return $row ?: null;
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
    return db_fetch_all(
        'SELECT mt.*, bu.unit_symbol, u.user_fullname AS creator_name
         FROM measurement_types mt
         JOIN biomedical_units bu ON bu.unit_id = mt.unit_id
         LEFT JOIN users u ON u.user_id = mt.created_by_user_id
         ORDER BY mt.type_id'
    );
}

function get_measurement_type(int $typeId): ?array
{
    $typeId = (int) $typeId;

    return db_fetch_one(
        'SELECT mt.*, bu.unit_symbol, u.user_fullname AS creator_name
         FROM measurement_types mt
         JOIN biomedical_units bu ON bu.unit_id = mt.unit_id
         LEFT JOIN users u ON u.user_id = mt.created_by_user_id
         WHERE mt.type_id = ' . $typeId
    );
}

function save_measurement(int $userId, int $typeId, string $valuePrimary, string $measuredAt): bool
{
    $userId = (int) $userId;
    $typeId = (int) $typeId;
    $valuePrimary = mysqli_real_escape_string(db(), $valuePrimary);
    $measuredAt = mysqli_real_escape_string(db(), $measuredAt);

    return mysqli_query(
        db(),
        'INSERT INTO measurements (user_id, type_id, value_primary, measured_at)
         VALUES (' . $userId . ', ' . $typeId . ", '$valuePrimary', '$measuredAt')"
    );
}

function get_norm_for_type(int $typeId, ?int $userId = null): ?array
{
    $typeId = (int) $typeId;

    if ($userId !== null) {
        $userId = (int) $userId;

        return db_fetch_one(
            'SELECT *
             FROM measurement_norms
             WHERE type_id = ' . $typeId . '
               AND (created_by_user_id = ' . $userId . ' OR created_by_user_id IS NULL)
             ORDER BY CASE WHEN created_by_user_id = ' . $userId . ' THEN 0 ELSE 1 END, norm_id DESC
             LIMIT 1'
        );
    }

    return db_fetch_one(
        'SELECT *
             FROM measurement_norms
             WHERE type_id = ' . $typeId . ' AND created_by_user_id IS NULL
             ORDER BY norm_id DESC
             LIMIT 1'
    );
}

function save_user_norm(int $typeId, int $userId, ?string $minValue, ?string $maxValue, string $source): bool
{
    $minValue = $minValue !== null && trim($minValue) !== '' ? trim($minValue) : null;
    $maxValue = $maxValue !== null && trim($maxValue) !== '' ? trim($maxValue) : null;
    $source = trim($source);

    $typeId = (int) $typeId;
    $userId = (int) $userId;
    $deleted = mysqli_query(
        db(),
        'DELETE FROM measurement_norms
         WHERE type_id = ' . $typeId . ' AND created_by_user_id = ' . $userId
    );

    if (!$deleted) {
        return false;
    }

    if ($minValue === null && $maxValue === null) {
        return true;
    }

    $minSql = $minValue !== null ? "'" . mysqli_real_escape_string(db(), $minValue) . "'" : 'NULL';
    $maxSql = $maxValue !== null ? "'" . mysqli_real_escape_string(db(), $maxValue) . "'" : 'NULL';
    $sourceSql = $source !== '' ? "'" . mysqli_real_escape_string(db(), $source) . "'" : 'NULL';

    return mysqli_query(
        db(),
        'INSERT INTO measurement_norms (type_id, min_value, max_value, source, created_by_user_id)
         VALUES (' . $typeId . ', ' . $minSql . ', ' . $maxSql . ', ' . $sourceSql . ', ' . $userId . ')'
    );
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
