<?php

namespace Aftab\LaravelCrud\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CrudHelper
{
    public static function isTableAllowed(string $table): bool
    {
        $allowed = config('crud.allowed_tables');
        if (is_array($allowed) && !empty($allowed)) {
            return in_array($table, $allowed, true);
        }
        // If not explicitly configured, ensure table exists in the schema
        return self::tableExists($table);
    }

    public static function tableExists(string $table): bool
    {
        try {
            return DB::getSchemaBuilder()->hasTable($table);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * @return array<int, string>
     */
    public static function tableColumns(string $table): array
    {
        try {
            return DB::getSchemaBuilder()->getColumnListing($table);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * @return array<string, string> map of column => doctrine type name (best-effort)
     */
    public static function columnTypes(string $table): array
    {
        $map = [];
        try {
            $conn = DB::connection();
            $schema = $conn->getDoctrineSchemaManager();
            foreach ($schema->listTableColumns($table) as $col) {
                $map[$col->getName()] = $col->getType()->getName();
            }
        } catch (\Throwable $e) {
        }
        return $map;
    }

    /**
     * Build basic validation rules based on inferred types.
     * @return array<string, array<int, string>>
     */
    public static function inferredValidation(string $table, bool $isUpdate = false): array
    {
        $types = self::columnTypes($table);
        $rules = [];
        foreach ($types as $name => $type) {
            if (in_array($name, ['id', 'created_at', 'updated_at', 'deleted_at'], true)) {
                continue;
            }
            $colRules = [];
            $colRules[] = $isUpdate ? 'sometimes' : 'required';
            if (str_contains($type, 'int')) {
                $colRules[] = 'integer';
            } elseif (in_array($type, ['decimal', 'float'], true)) {
                $colRules[] = 'numeric';
            } elseif ($type === 'boolean') {
                $colRules[] = 'boolean';
            } else {
                $colRules[] = 'string';
                $colRules[] = 'max:255';
            }

            // Heuristic: foreign key exists rule for *_id columns
            if (Str::endsWith($name, '_id')) {
                $base = substr($name, 0, -3);
                $fkTable = Str::plural(Str::snake($base));
                if (self::tableExists($fkTable)) {
                    $colRules[] = 'exists:' . $fkTable . ',id';
                }
            }
            $rules[$name] = $colRules;
        }

        // Apply custom rules from config
        $custom = (array) (config('crud.custom_validation') ?? []);
        if (isset($custom[$table]) && is_array($custom[$table])) {
            foreach ($custom[$table] as $col => $extra) {
                $extraArr = is_array($extra) ? $extra : array_filter(array_map('trim', explode('|', (string) $extra)));
                if (!isset($rules[$col])) {
                    $rules[$col] = $extraArr;
                } else {
                    $rules[$col] = array_values(array_unique(array_merge($rules[$col], $extraArr)));
                }
            }
        }
        return $rules;
    }

    /**
     * Sanitize sort inputs.
     * @param array<int, string> $columns
     */
    public static function sanitizeSort(?string $sort, ?string $dir, array $columns): array
    {
        $sort = $sort && in_array($sort, $columns, true) ? $sort : 'id';
        $dir = strtolower((string) $dir);
        $dir = in_array($dir, ['asc', 'desc'], true) ? $dir : 'asc';
        return [$sort, $dir];
    }

    /**
     * Whitelisted fillable columns for a table.
     * @return array<int, string>
     */
    public static function fillableColumns(string $table): array
    {
        $cols = self::tableColumns($table);
        return array_values(array_filter($cols, static function ($c) {
            return !in_array($c, ['id', 'created_at', 'updated_at', 'deleted_at'], true);
        }));
    }

    /**
     * Handle configured file uploads and update $data in-place with stored paths.
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function processUploads(Request $request, string $table, array $data): array
    {
        $uploads = (array) (config('crud.uploads') ?? []);
        $disk = (string) ($uploads['disk'] ?? 'public');
        $fields = (array) ($uploads['fields'] ?? []);

        foreach ($fields as $key => $opts) {
            if (!is_string($key)) { continue; }
            [$t, $column] = array_pad(explode('.', $key, 2), 2, null);
            if ($t !== $table || empty($column)) { continue; }
            if (!$request->hasFile($column)) { continue; }
            $file = $request->file($column);
            if (!$file || !$file->isValid()) { continue; }
            $path = (string) ($opts['path'] ?? ('uploads/' . $table));
            $stored = $file->store($path, $disk);
            if ($stored) {
                $data[$column] = $stored;
            }
        }
        return $data;
    }
}


