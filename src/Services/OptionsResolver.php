<?php

namespace Aftab\LaravelCrud\Services;

use Illuminate\Support\Facades\DB;

class OptionsResolver
{
    /**
     * Resolve options for a given table.column using config('crud.field_sources').
     * Returns array of [value => label].
     * @return array<string, string>
     */
    public function resolve(string $table, string $column): array
    {
        $sources = (array) (config('crud.field_sources') ?? []);
        $key = $table . '.' . $column;
        if (!isset($sources[$key]) || !is_array($sources[$key])) {
            return [];
        }
        $conf = $sources[$key];
        $type = (string) ($conf['type'] ?? 'static');
        return match ($type) {
            'table' => $this->fromTable($conf),
            'api' => $this->fromApi($conf),
            default => $this->fromStatic($conf),
        };
    }

    /**
     * @return array<string, string>
     */
    protected function fromStatic(array $conf): array
    {
        $options = $conf['options'] ?? [];
        if (is_array($options)) {
            // Normalize to [value => label]
            $out = [];
            foreach ($options as $k => $v) {
                if (is_int($k)) {
                    $out[(string) $v] = (string) $v;
                } else {
                    $out[(string) $k] = (string) $v;
                }
            }
            return $out;
        }
        return [];
    }

    /**
     * @return array<string, string>
     */
    protected function fromTable(array $conf): array
    {
        $table = (string) ($conf['table'] ?? '');
        $value = (string) ($conf['value'] ?? 'id');
        $label = (string) ($conf['label'] ?? 'name');
        if ($table === '') { return []; }
        try {
            return DB::table($table)->select([$value, $label])->orderBy($label)->get()
                ->mapWithKeys(fn ($row) => [(string) $row->{$value} => (string) $row->{$label}])->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * @return array<string, string>
     */
    protected function fromApi(array $conf): array
    {
        if (!config('crud.field_sources_allow_api_callbacks', false)) {
            return [];
        }
        $callback = $conf['callback'] ?? null;
        if (is_string($callback) && function_exists($callback)) {
            $res = call_user_func($callback);
            return is_array($res) ? $res : [];
        }
        if (is_callable($callback)) {
            $res = $callback();
            return is_array($res) ? $res : [];
        }
        return [];
    }
}


