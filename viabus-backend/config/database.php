<?php

// Base de datos PostgreSQL.
// Requiere tener un servidor Postgres corriendo y la extensión pdo_pgsql
// habilitada en PHP (php -m | grep pgsql).
//
// Los datos de conexión se toman de variables de entorno, con valores por
// defecto pensados para desarrollo local. Para producción, definí las
// variables DB_HOST / DB_PORT / DB_NAME / DB_USER / DB_PASS en el entorno
// (o en un .env cargado antes de este archivo) en vez de tocar el código.

define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '5432');
define('DB_NAME', getenv('DB_NAME') ?: 'viabus');
define('DB_USER', getenv('DB_USER') ?: 'postgres');
define('DB_PASS', getenv('DB_PASS') ?: 'postgres');
define('DB_SCHEMA', __DIR__ . '/../sql/schema.sql');

function getConnection(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = 'pgsql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME;
            $pdo = new PDO($dsn, DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            // Verificar si la tabla principal Y la columna "dias" existen.
            // Esto permite reinicializar la BD cuando actualizás schema.sql
            // (sin tener que borrarla manualmente). Si alguna de las dos
            // no existe, se ejecuta el script completo de schema.sql.
            $stmt = $pdo->query("SELECT to_regclass('public.usuarios')");
            $existe_usuarios = $stmt->fetchColumn();
            
            $stmt = $pdo->query("
                SELECT EXISTS(
                    SELECT 1 FROM information_schema.columns 
                    WHERE table_name='servicios' AND column_name='dias'
                )
            ");
            $existe_dias = $stmt->fetchColumn();

            // Si no existe la tabla de usuarios O no existe la columna "dias"
            // en servicios, es porque schema.sql se actualizó. Reinicializamos todo.
            if ((!$existe_usuarios || !$existe_dias) && file_exists(DB_SCHEMA)) {
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