<?php
declare(strict_types=1);

/*
 * NAVTOOL 2.0 configuration
 * Copy this file to config/config.php and fill in your MySQL data.
 */
const DB_HOST = 'localhost';
const DB_NAME = 'navtool';
const DB_USER = 'YOUR_DB_USER';
const DB_PASS = 'YOUR_DB_PASSWORD';
const DB_CHARSET = 'utf8mb4';

const APP_NAME = 'NAVTOOL';
const APP_URL = 'https://navtool.de';

/*
 * Used only for privacy-preserving visitor hashes.
 * Generate a long random value and keep it secret.
 */
const ANALYTICS_SALT = 'CHANGE_THIS_TO_A_LONG_RANDOM_SECRET_VALUE';

date_default_timezone_set('Europe/Berlin');

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}
