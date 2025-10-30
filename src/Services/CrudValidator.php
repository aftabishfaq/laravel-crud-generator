<?php

namespace Aftab\LaravelCrud\Services;

class CrudValidator
{
    /** @var array<string, true> */
    protected array $reservedWords = [
        'select' => true, 'insert' => true, 'update' => true, 'delete' => true,
        'from' => true, 'where' => true, 'table' => true, 'index' => true,
        'group' => true, 'order' => true, 'limit' => true, 'offset' => true,
        'join' => true, 'union' => true, 'drop' => true, 'truncate' => true,
    ];

    /** @var array<string, true> */
    protected array $allowedColumnTypes = [
        'string' => true, 'text' => true, 'integer' => true, 'bigInteger' => true,
        'unsignedBigInteger' => true, 'boolean' => true, 'date' => true,
        'datetime' => true, 'timestamp' => true, 'enum' => true, 'json' => true,
        'decimal' => true, 'float' => true,
    ];

    public function validate(array $schema): array
    {
        $errors = [];

        if (!isset($schema['tables']) || !is_array($schema['tables'])) {
            $errors[] = 'Root key "tables" must be an array.';
            return ['valid' => false, 'errors' => $errors];
        }

        foreach ($schema['tables'] as $tIndex => $table) {
            $prefix = "tables[{$tIndex}]";
            $this->validateTable($table, $prefix, $errors);
        }

        return [
            'valid' => count($errors) === 0,
            'errors' => $errors,
        ];
    }

    protected function validateTable($table, string $prefix, array &$errors): void
    {
        if (!is_array($table)) {
            $errors[] = "{$prefix} must be an object.";
            return;
        }

        $name = $table['name'] ?? null;
        if (!$this->isSafeIdentifier($name)) {
            $errors[] = "{$prefix}.name is required, must be a safe string, and not reserved.";
        }

        if (isset($table['columns']) && is_array($table['columns'])) {
            $columnNames = [];
            foreach ($table['columns'] as $cIndex => $column) {
                $cPrefix = "{$prefix}.columns[{$cIndex}]";
                $this->validateColumn($column, $cPrefix, $errors);
                if (isset($column['name']) && is_string($column['name'])) {
                    $columnNames[] = $column['name'];
                }
            }
            $dupes = $this->duplicates($columnNames);
            foreach ($dupes as $dup) {
                $errors[] = "{$prefix}.columns has duplicate column name: {$dup}";
            }
        } else {
            $errors[] = "{$prefix}.columns must be an array.";
        }

        if (isset($table['relationships']) && is_array($table['relationships'])) {
            foreach ($table['relationships'] as $rIndex => $rel) {
                $rPrefix = "{$prefix}.relationships[{$rIndex}]";
                $this->validateRelationship($rel, $rPrefix, $errors);
            }
        }
    }

    protected function validateColumn($column, string $prefix, array &$errors): void
    {
        if (!is_array($column)) {
            $errors[] = "{$prefix} must be an object.";
            return;
        }

        $name = $column['name'] ?? null;
        if (!$this->isSafeIdentifier($name)) {
            $errors[] = "{$prefix}.name is required, must be a safe string, and not reserved.";
        }

        $type = $column['type'] ?? null;
        if (!is_string($type) || !isset($this->allowedColumnTypes[$type])) {
            $errors[] = "{$prefix}.type must be one of: " . implode(', ', array_keys($this->allowedColumnTypes));
        }

        if (isset($column['foreign'])) {
            $foreign = $column['foreign'];
            if (!is_array($foreign) || empty($foreign['table']) || empty($foreign['column'])) {
                $errors[] = "{$prefix}.foreign must include 'table' and 'column'.";
            } else {
                if (!$this->isSafeIdentifier($foreign['table'])) {
                    $errors[] = "{$prefix}.foreign.table is not a safe identifier.";
                }
                if (!$this->isSafeIdentifier($foreign['column'])) {
                    $errors[] = "{$prefix}.foreign.column is not a safe identifier.";
                }
            }
        }

        if (isset($column['options']) && !is_array($column['options'])) {
            $errors[] = "{$prefix}.options must be an array of values.";
        }

        if (isset($column['options_source']) && !is_string($column['options_source'])) {
            $errors[] = "{$prefix}.options_source must be a string (e.g., 'table:groups').";
        }
    }

    protected function validateRelationship($rel, string $prefix, array &$errors): void
    {
        if (!is_array($rel)) {
            $errors[] = "{$prefix} must be an object.";
            return;
        }

        $type = $rel['type'] ?? null;
        if (!in_array($type, ['belongsTo', 'hasOne', 'hasMany', 'belongsToMany'], true)) {
            $errors[] = "{$prefix}.type must be one of belongsTo, hasOne, hasMany, belongsToMany.";
        }

        if (isset($rel['foreign_key']) && !$this->isSafeIdentifier($rel['foreign_key'])) {
            $errors[] = "{$prefix}.foreign_key is not a safe identifier.";
        }
    }

    protected function isSafeIdentifier($value): bool
    {
        if (!is_string($value) || $value === '') {
            return false;
        }
        if (isset($this->reservedWords[strtolower($value)])) {
            return false;
        }
        if (str_contains($value, '..') || str_contains($value, '/') || str_contains($value, '\\')) {
            return false;
        }
        return preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $value) === 1;
    }

    /**
     * @param array<int, string> $values
     * @return array<int, string>
     */
    protected function duplicates(array $values): array
    {
        $counts = array_count_values($values);
        return array_values(array_keys(array_filter($counts, static fn ($n) => $n > 1)));
    }
}


