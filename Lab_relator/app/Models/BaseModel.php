<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use InvalidArgumentException;
use PDO;
use PDOStatement;

abstract class BaseModel
{
    protected static string $table = '';

    protected static string $primaryKey = 'id';

    protected PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    /** @param array<string, mixed> $conditions */
    public function findAll(array $conditions = [], string $orderBy = ''): array
    {
        $table = $this->identifier(static::$table);
        $sql = 'SELECT * FROM ' . $table;
        $params = [];

        if ($conditions !== []) {
            $where = [];
            foreach ($conditions as $column => $value) {
                $param = 'where_' . $this->identifier($column);
                $where[] = $this->identifier($column) . ' = :' . $param;
                $params[$param] = $value;
            }

            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        if ($orderBy !== '') {
            $sql .= ' ORDER BY ' . $this->orderBy($orderBy);
        }

        return $this->query($sql, $params)->fetchAll();
    }

    public function findById(int|string $id): array|false
    {
        $table = $this->identifier(static::$table);
        $primaryKey = $this->identifier(static::$primaryKey);

        return $this
            ->query("SELECT * FROM {$table} WHERE {$primaryKey} = :id LIMIT 1", ['id' => $id])
            ->fetch();
    }

    /** @param array<string, mixed> $data */
    public function insert(array $data): int
    {
        if ($data === []) {
            throw new InvalidArgumentException('Insert data cannot be empty.');
        }

        $table = $this->identifier(static::$table);
        $columns = array_map(fn (string $column): string => $this->identifier($column), array_keys($data));
        $params = array_map(fn (string $column): string => ':' . $column, array_keys($data));

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $table,
            implode(', ', $columns),
            implode(', ', $params)
        );

        $this->query($sql, $data);

        return (int)$this->pdo->lastInsertId();
    }

    /** @param array<string, mixed> $data */
    public function update(int|string $id, array $data): bool
    {
        if ($data === []) {
            return false;
        }

        $table = $this->identifier(static::$table);
        $primaryKey = $this->identifier(static::$primaryKey);
        $sets = [];

        foreach ($data as $column => $_value) {
            $sets[] = $this->identifier($column) . ' = :' . $column;
        }

        $data['id'] = $id;

        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s = :id',
            $table,
            implode(', ', $sets),
            $primaryKey
        );

        return $this->query($sql, $data)->rowCount() > 0;
    }

    public function delete(int|string $id): bool
    {
        $table = $this->identifier(static::$table);
        $primaryKey = $this->identifier(static::$primaryKey);

        return $this
            ->query("DELETE FROM {$table} WHERE {$primaryKey} = :id", ['id' => $id])
            ->rowCount() > 0;
    }

    /** @param array<string|int, mixed> $params */
    protected function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt;
    }

    private function identifier(string $value): string
    {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $value)) {
            throw new InvalidArgumentException('Invalid SQL identifier: ' . $value);
        }

        return $value;
    }

    private function orderBy(string $value): string
    {
        if (!preg_match('/^[a-zA-Z0-9_,.\s`]+(?:\s+(?:ASC|DESC))?$/i', $value)) {
            throw new InvalidArgumentException('Invalid ORDER BY clause.');
        }

        return $value;
    }
}
