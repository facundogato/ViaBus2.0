<?php

// Base de datos SQLite: no requiere instalar ni configurar un servidor MySQL.
// El archivo se crea (y se inicializa con sql/schema.sql) automáticamente
// la primera vez que se usa.

define('DB_DIR', __DIR__ . '/../data');
define('DB_PATH', DB_DIR . '/viabus.sqlite');
define('DB_SCHEMA', __DIR__ . '/../sql/schema.sql');

function getConnection(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $isNew = !file_exists(DB_PATH);

            if (!is_dir(DB_DIR)) {
                mkdir(DB_DIR, 0777, true);
            }

            $pdo = new PDO('sqlite:' . DB_PATH);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->exec('PRAGMA foreign_keys = ON');

            if ($isNew && file_exists(DB_SCHEMA)) {
                $pdo->exec(file_get_contents(DB_SCHEMA));
            }
        } catch (PDOException $e) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Error de conexión a la base de datos: ' . $e->getMessage()]);
            exit;
        }
    }
    return $pdo;
}
