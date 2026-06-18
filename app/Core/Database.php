<?php
namespace App\Core;

class Database
{
    private static ?self $instance = null;
    private \PDO $pdo;

    private function __construct()
    {
        $driver = Config::get('database.default', 'mysql');
        if ($driver === 'sqlite') {
            $path = Config::get('database.sqlite.path', BASE_PATH . '/database/sqlite.db');
            $this->ensureSqliteDirectory($path);
            $this->pdo = new \PDO("sqlite:$path", null, null, $this->pdoOptions());
            $this->pdo->exec('PRAGMA journal_mode=WAL');
        } else {
            $host = Config::get('database.mysql.host', '127.0.0.1');
            $port = Config::get('database.mysql.port', '3306');
            $dbname = Config::get('database.mysql.database', 'autopublisher');
            $user = Config::get('database.mysql.username', 'root');
            $pass = Config::get('database.mysql.password', '');
            if (Config::get('database.auto_create', true)) {
                $this->ensureMysqlDatabase($host, $port, $dbname, $user, $pass);
            }

            $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
            $this->pdo = new \PDO($dsn, $user, $pass, $this->pdoOptions());
        }
    }

    public function driver(): string
    {
        return Config::get('database.default', 'mysql');
    }

    private function ensureSqliteDirectory(string $path): void
    {
        $directory = dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new \RuntimeException('Unable to create SQLite database directory: ' . $directory);
        }
    }

    private function ensureMysqlDatabase(string $host, string $port, string $dbname, string $user, string $pass): void
    {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $dbname)) {
            throw new \InvalidArgumentException('Invalid MySQL database name: ' . $dbname);
        }

        $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
        $pdo = new \PDO($dsn, $user, $pass, $this->pdoOptions());
        $database = $this->quoteIdentifier($dbname);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS $database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }

    private function pdoOptions(): array
    {
        return [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ];
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getPdo(): \PDO
    {
        return $this->pdo;
    }

    public function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function insert(string $table, array $data): string
    {
        $this->assertDataIsNotEmpty($data);
        $columns = implode(', ', array_map([$this, 'quoteIdentifier'], array_keys($data)));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $table = $this->quoteIdentifier($table);
        $this->query("INSERT INTO $table ($columns) VALUES ($placeholders)", array_values($data));
        return $this->pdo->lastInsertId();
    }

    public function update(string $table, array $data, array $where): int
    {
        $this->assertDataIsNotEmpty($data);
        $this->assertDataIsNotEmpty($where);
        $table = $this->quoteIdentifier($table);
        $sets = implode(', ', array_map(fn($col) => $this->quoteIdentifier($col) . ' = ?', array_keys($data)));
        $whereClauses = implode(' AND ', array_map(fn($col) => $this->quoteIdentifier($col) . ' = ?', array_keys($where)));
        $params = array_values($data);
        $params = array_merge($params, array_values($where));
        $stmt = $this->query("UPDATE $table SET $sets WHERE $whereClauses", $params);
        return $stmt->rowCount();
    }

    public function delete(string $table, array $where): int
    {
        $this->assertDataIsNotEmpty($where);
        $table = $this->quoteIdentifier($table);
        $whereClauses = implode(' AND ', array_map(fn($col) => $this->quoteIdentifier($col) . ' = ?', array_keys($where)));
        $stmt = $this->query("DELETE FROM $table WHERE $whereClauses", array_values($where));
        return $stmt->rowCount();
    }

    private function quoteIdentifier(string $identifier): string
    {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $identifier)) {
            throw new \InvalidArgumentException('Invalid database identifier: ' . $identifier);
        }

        return '`' . $identifier . '`';
    }

    private function assertDataIsNotEmpty(array $data): void
    {
        if ($data === []) {
            throw new \InvalidArgumentException('Database operation requires at least one field.');
        }
    }

    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }
}