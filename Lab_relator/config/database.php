<?php
declare(strict_types=1);

// Arquivo: config/database.php
// Lab Relator — Projeto Integrador TADS UniEinstein 2026
// Modificado por: Codex — correção QA

// ── INÍCIO CORREÇÃO QA ──
// -- INICIO CORRECAO LOCAL --
$localConfigPath = __DIR__ . '/database.local.php';
$localConfig = is_file($localConfigPath) ? require $localConfigPath : [];

$host = getenv('DB_HOST') ?: (string) ($localConfig['host'] ?? '127.0.0.1');
$port = getenv('DB_PORT') ?: (string) ($localConfig['port'] ?? '3306');
$database = getenv('DB_NAME') ?: (getenv('DB_DATABASE') ?: (string) ($localConfig['database'] ?? 'lab_relator'));
$username = getenv('DB_USER') ?: (getenv('DB_USERNAME') ?: (string) ($localConfig['username'] ?? 'root'));
$password = getenv('DB_PASS') ?: (getenv('DB_PASSWORD') ?: (string) ($localConfig['password'] ?? ''));
$charset = getenv('DB_CHARSET') ?: (string) ($localConfig['charset'] ?? 'utf8mb4');
// -- FIM CORRECAO LOCAL --

$config = [
    'driver' => 'mysql',
    'host' => $host,
    'port' => $port,
    'db' => $database,
    'database' => $database,
    'user' => $username,
    'username' => $username,
    'pass' => $password,
    'password' => $password,
    'charset' => $charset,
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ],
];

if (PHP_SAPI === 'cli' && realpath((string) ($_SERVER['SCRIPT_FILENAME'] ?? '')) === __FILE__) {
    try {
        $dsn = sprintf(
            '%s:host=%s;port=%s;dbname=%s;charset=%s',
            $config['driver'],
            $config['host'],
            $config['port'],
            $config['db'],
            $config['charset']
        );

        new PDO($dsn, $config['user'], $config['pass'], $config['options']);
        echo "[OK] Conexão com banco estabelecida.\n";
    } catch (PDOException $e) {
        echo '[ERRO] ' . $e->getMessage() . "\n";
    }
}
// ── FIM CORREÇÃO QA ──

return $config;
