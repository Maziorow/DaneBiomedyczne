<?php
const DB_HOST = 'mysql.agh.edu.pl';
const DB_NAME = 'mateusz5';
const DB_USER = 'mateusz5';
const DB_PASS = '6n02QzScDB0j4gCk';

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
