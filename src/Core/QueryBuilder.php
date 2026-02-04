<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

class QueryBuilder
{
    private PDO $db;

    private array $fields = [];
    private string $table = '';
    private array $joins = [];
    private array $conditions = [];
    private array $params = [];
    private array $orderBy = [];
    private ?int $limit = null;
    private ?int $offset = null;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function select(array $fields): self
    {
        $this->fields = $fields;
        return $this;
    }

    public function from(string $table, string $alias = ''): self
    {
        $this->table = $alias ? "$table $alias" : $table;
        return $this;
    }

    public function join(string $table, string $on): self
    {
        $this->joins[] = "JOIN $table ON $on";
        return $this;
    }

    public function leftJoin(string $table, string $on): self
    {
        $this->joins[] = "LEFT JOIN $table ON $on";
        return $this;
    }

    public function where(string $column, string $operator, mixed $value): self
    {
        $paramName = ':' . str_replace(['.', ' '], '_', $column) . '_' . count($this->params);
        $this->conditions[] = "$column $operator $paramName";
        $this->params[$paramName] = $value;
        return $this;
    }

    public function whereRaw(string $sql, array $bindings = []): self
    {
        $this->conditions[] = $sql;
        $this->params = array_merge($this->params, $bindings);
        return $this;
    }

    public function orderBy(string $field, string $direction = 'DESC'): self
    {
        $this->orderBy[] = "$field $direction";
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    public function get(): array
    {
        $sql = "SELECT " . implode(', ', $this->fields) . " FROM " . $this->table;

        if (!empty($this->joins)) {
            $sql .= " " . implode(' ', $this->joins);
        }

        if (!empty($this->conditions)) {
            $sql .= " WHERE " . implode(' AND ', $this->conditions);
        }

        if (!empty($this->orderBy)) {
            $sql .= " ORDER BY " . implode(', ', $this->orderBy);
        }

        if ($this->limit !== null) {
            $sql .= " LIMIT " . $this->limit;
        }

        if ($this->offset !== null) {
            $sql .= " OFFSET " . $this->offset;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($this->params);

        return $stmt->fetchAll();
    }

    public function first(): ?array
    {
        $this->limit(1);
        $results = $this->get();
        return $results[0] ?? null;
    }
}