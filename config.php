<?php
declare(strict_types=1);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

const DB_HOST = 'localhost';
const DB_NAME = 'dmsoghwg_a2webarebel_survey';
const DB_USER = 'dmsoghwg_a2webarebel_survey';
const DB_PASS = 'A2webarebel';

const ADMIN_USERNAME = 'admin';
const ADMIN_PASSWORD = 'webarebel';

function get_db(): mysqli
{
    static $db = null;

    if ($db instanceof mysqli) {
        return $db;
    }

    $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $db->set_charset('utf8mb4');

    return $db;
}
