<?php
declare(strict_types=1);

function lab_config(): array
{
    return [
        'host' => getenv('LAB_DB_HOST') ?: '127.0.0.1',
        'port' => getenv('LAB_DB_PORT') ?: '3306',
        'name' => getenv('LAB_DB_NAME') ?: 'php_labs',
        'user' => getenv('LAB_DB_USER') ?: 'root',
        'pass' => (string) (getenv('LAB_DB_PASS') ?: ''),
    ];
}

function db(string $schemaFile = ''): PDO
{
    static $pool = [];
    if (isset($pool[$schemaFile])) {
        return $pool[$schemaFile];
    }

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    if ((getenv('LAB_DB_DRIVER') ?: 'mysql') === 'sqlite') {
        $file = getenv('LAB_SQLITE_FILE') ?: sys_get_temp_dir() . '/php_labs_' . md5($schemaFile) . '.sqlite';
        $pdo = new PDO('sqlite:' . $file, null, null, $options);
        if ($schemaFile !== '') {
            sqlite_install_schema($pdo, $schemaFile);
        }
    } else {
        $c = lab_config();
        $dsn = "mysql:host={$c['host']};port={$c['port']};dbname={$c['name']};charset=utf8mb4";
        $pdo = new PDO($dsn, $c['user'], $c['pass'], $options);
    }
    return $pool[$schemaFile] = $pdo;
}

function schema_to_sqlite(string $sql): string
{
    $sql = preg_replace('/--[^\n]*/', '', $sql) ?? $sql;
    $sql = preg_replace('/^\s*(CREATE DATABASE|USE)\b[^;]*;/mi', '', $sql) ?? $sql;
    $sql = preg_replace('/\)\s*ENGINE\s*=[^;]*;/i', ');', $sql) ?? $sql;
    $sql = preg_replace('/(\w+)\s+INT(?:\s+UNSIGNED)?\s+NOT NULL\s+AUTO_INCREMENT/i', '$1 INTEGER PRIMARY KEY AUTOINCREMENT', $sql) ?? $sql;
    $sql = preg_replace('/^\s*PRIMARY KEY\s*\([^)]*\)\s*,?\s*$/mi', '', $sql) ?? $sql;
    $sql = preg_replace('/\bUNSIGNED\b/i', '', $sql) ?? $sql;
    $sql = preg_replace('/ENUM\s*\([^)]*\)/i', 'TEXT', $sql) ?? $sql;
    $sql = preg_replace('/UNIQUE KEY\s+\w+\s*\(/i', 'UNIQUE (', $sql) ?? $sql;
    $sql = preg_replace('/^\s*(?:INDEX|KEY)\s+\w+\s*\([^)]*\)\s*,?\s*$/mi', '', $sql) ?? $sql;
    $sql = preg_replace('/\s+COMMENT\s+\'[^\']*\'/i', '', $sql) ?? $sql;
    $sql = preg_replace('/,(\s*\n\s*)\)/', '$1)', $sql) ?? $sql;
    return $sql;
}

function sqlite_install_schema(PDO $pdo, string $schemaFile): void
{
    $sql = schema_to_sqlite((string) file_get_contents($schemaFile));
    foreach (explode(';', $sql) as $statement) {
        if (trim($statement) !== '') {
            $pdo->exec($statement);
        }
    }
}
