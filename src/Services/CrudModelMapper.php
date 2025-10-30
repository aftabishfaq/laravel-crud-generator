<?php

namespace Aftab\LaravelCrud\Services;

use Aftab\LaravelCrud\Services\Dto\CrudColumn;
use Aftab\LaravelCrud\Services\Dto\CrudRelationship;
use Aftab\LaravelCrud\Services\Dto\CrudTable;

class CrudModelMapper
{
    /**
     * @param array<string, mixed> $schema
     * @return array<int, CrudTable>
     */
    public function map(array $schema): array
    {
        $tables = $schema['tables'] ?? [];
        $result = [];
        foreach ($tables as $table) {
            $result[] = $this->mapTable($table);
        }
        return $result;
    }

    /**
     * @param array<string, mixed> $table
     */
    protected function mapTable(array $table): CrudTable
    {
        $name = (string) ($table['name'] ?? '');
        $displayName = isset($table['display_name']) ? (string) $table['display_name'] : null;
        $timestamps = (bool) ($table['timestamps'] ?? true);
        $softDeletes = (bool) ($table['soft_deletes'] ?? false);

        $columns = [];
        foreach (($table['columns'] ?? []) as $col) {
            $columns[] = $this->mapColumn((array) $col);
        }

        $relationships = [];
        foreach (($table['relationships'] ?? []) as $rel) {
            $relationships[] = $this->mapRelationship((array) $rel);
        }

        $routePrefix = null;
        $middleware = null;
        $controller = null;
        if (isset($table['routes']) && is_array($table['routes'])) {
            $routePrefix = isset($table['routes']['prefix']) ? (string) $table['routes']['prefix'] : null;
            $middleware = isset($table['routes']['middleware']) && is_array($table['routes']['middleware'])
                ? array_map('strval', $table['routes']['middleware'])
                : null;
            $controller = isset($table['routes']['controller']) ? (string) $table['routes']['controller'] : null;
        }

        return new CrudTable(
            $name,
            $displayName,
            $columns,
            $relationships,
            $timestamps,
            $softDeletes,
            $routePrefix,
            $middleware,
            $controller,
        );
    }

    /**
     * @param array<string, mixed> $col
     */
    protected function mapColumn(array $col): CrudColumn
    {
        $name = (string) ($col['name'] ?? '');
        $type = (string) ($col['type'] ?? 'string');
        $label = isset($col['label']) ? (string) $col['label'] : null;
        $inputType = isset($col['input_type']) ? (string) $col['input_type'] : null;
        $isNullable = !empty($col['nullable']) || empty($col['required']);
        $isUnique = !empty($col['unique']);
        $defaultValue = $col['default'] ?? null;

        $validationRules = null;
        if (isset($col['validation'])) {
            if (is_string($col['validation'])) {
                $validationRules = array_filter(array_map('trim', explode('|', $col['validation'])));
            } elseif (is_array($col['validation'])) {
                $validationRules = array_map('strval', $col['validation']);
            }
        }

        $foreignTable = null;
        $foreignColumn = null;
        $onDelete = null;
        if (isset($col['foreign']) && is_array($col['foreign'])) {
            $foreignTable = isset($col['foreign']['table']) ? (string) $col['foreign']['table'] : null;
            $foreignColumn = isset($col['foreign']['column']) ? (string) $col['foreign']['column'] : null;
            $onDelete = isset($col['foreign']['on_delete']) ? (string) $col['foreign']['on_delete'] : null;
        }

        $options = isset($col['options']) && is_array($col['options']) ? array_values($col['options']) : null;
        $optionsSource = isset($col['options_source']) ? (string) $col['options_source'] : null;

        return new CrudColumn(
            $name,
            $type,
            $label,
            $inputType,
            $isNullable,
            $isUnique,
            $defaultValue,
            $validationRules,
            $foreignTable,
            $foreignColumn,
            $onDelete,
            $options,
            $optionsSource,
        );
    }

    /**
     * @param array<string, mixed> $rel
     */
    protected function mapRelationship(array $rel): CrudRelationship
    {
        $type = (string) ($rel['type'] ?? 'belongsTo');
        $model = isset($rel['model']) ? (string) $rel['model'] : null;
        $foreignKey = isset($rel['foreign_key']) ? (string) $rel['foreign_key'] : null;
        return new CrudRelationship($type, $model, $foreignKey);
    }
}


