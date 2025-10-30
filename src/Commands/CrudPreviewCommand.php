<?php

namespace Aftab\LaravelCrud\Commands;

use Aftab\LaravelCrud\Services\CrudModelMapper;
use Aftab\LaravelCrud\Services\CrudParser;
use Illuminate\Console\Command;

class CrudPreviewCommand extends Command
{
    protected $signature = 'crud:preview {file?} {--input=}';
    protected $description = 'Display a human-readable report of tables and fields.';

    public function handle(): int
    {
        $file = (string) ($this->argument('file') ?? '');
        $input = (string) ($this->option('input') ?? '');

        if ($file === '' && $input === '') {
            $default = base_path('crud.yaml');
            if (is_file($default)) { $file = $default; }
        }
        if ($file === '' && $input === '') {
            $this->error('Provide a file (crud.yaml by default) or --input payload.');
            return self::INVALID;
        }

        $parser = new CrudParser();
        try {
            $schema = $file !== '' ? $parser->parseFile($file) : $parser->parseInput($input);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $mapper = new CrudModelMapper();
        $tables = $mapper->map($schema);
        foreach ($tables as $table) {
            $this->line('<info>Table:</info> ' . $table->name . ($table->displayName ? ' ('. $table->displayName .')' : ''));
            foreach ($table->columns as $col) {
                $meta = [];
                if ($col->isUnique) $meta[] = 'unique';
                if ($col->isNullable) $meta[] = 'nullable';
                if ($col->foreignTable) $meta[] = 'fk:' . $col->foreignTable;
                $this->line('  - ' . $col->name . ' : ' . $col->type . (empty($meta)?'':' ['.implode(', ',$meta).']'));
            }
            if ($table->relationships) {
                $this->line('  Relationships:');
                foreach ($table->relationships as $rel) {
                    $this->line('   * ' . $rel->type . ' ' . ($rel->model ?? '?') . ' fk:' . ($rel->foreignKey ?? '?'));
                }
            }
            $this->newLine();
        }
        return self::SUCCESS;
    }
}


