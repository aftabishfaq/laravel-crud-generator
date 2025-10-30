<?php

namespace Aftab\LaravelCrud\Commands;

use Aftab\LaravelCrud\Services\CrudParser;
use Aftab\LaravelCrud\Services\CrudValidator;
use Illuminate\Console\Command;

class CrudExecuteCommand extends Command
{
    protected $signature = 'crud:execute {file?} {--generate} {--migrate} {--force} {--dry-run} {--input=} {--temp}';

    protected $description = 'Parse CRUD config (JSON/YAML), validate, and (optionally) generate artifacts.';

    public function handle(): int
    {
        $file = (string) ($this->argument('file') ?? '');
        $input = (string) ($this->option('input') ?? '');
        $dryRun = (bool) $this->option('dry-run');

        if ($file === '' && $input === '') {
            $default = base_path('crud.yaml');
            if (is_file($default)) {
                $file = $default;
                $this->line('<info>Using default config:</info> ' . $file);
            }
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

        $validator = new CrudValidator();
        $result = $validator->validate($schema);
        if (!$result['valid']) {
            $this->error('Schema validation failed:');
            foreach ($result['errors'] as $err) {
                $this->line(" - {$err}");
            }
            return self::FAILURE;
        }

        if ($dryRun) {
            $this->info('Dry run: No files will be written. Summary below.');
            $this->renderSummary($schema);
            return self::SUCCESS;
        }

        $generated = false;
        if ($this->option('generate')) {
            $this->generateMigrationsFlow($schema);
            $this->generateModelsFlow($schema);
            $generated = true;
        }

        if (!$generated) {
            $this->info('Schema is valid. Use --generate to write files.');
            return self::SUCCESS;
        }

        if ($this->option('migrate')) {
            if (!$this->option('force')) {
                if (! $this->confirm('Run database migrations now?', true)) {
                    $this->warn('Skipped running migrations.');
                    return self::SUCCESS;
                }
            }
            $this->runMigrationsAfterGeneration();
        }
        return self::SUCCESS;
    }

    /**
     * @param array<string, mixed> $schema
     */
    protected function renderSummary(array $schema): void
    {
        $tables = $schema['tables'] ?? [];
        foreach ($tables as $table) {
            $name = $table['name'] ?? '(unknown)';
            $this->line("Table: {$name}");
            $columns = $table['columns'] ?? [];
            foreach ($columns as $col) {
                $cName = $col['name'] ?? '?';
                $cType = $col['type'] ?? '?';
                $note = [];
                if (!empty($col['required'])) { $note[] = 'required'; }
                if (!empty($col['unique'])) { $note[] = 'unique'; }
                if (!empty($col['foreign'])) { $note[] = 'foreign'; }
                $suffix = $note ? ' [' . implode(', ', $note) . ']' : '';
                $this->line("  - {$cName}: {$cType}{$suffix}");
            }
            $rels = $table['relationships'] ?? [];
            if ($rels) {
                $this->line('  Relationships:');
                foreach ($rels as $rel) {
                    $rType = $rel['type'] ?? '?';
                    $rModel = $rel['model'] ?? '?';
                    $rFk = $rel['foreign_key'] ?? '?';
                    $this->line("   * {$rType} {$rModel} (fk: {$rFk})");
                }
            }
        }
    }

    /**
     * @param array<string, mixed> $schema
     */
    protected function generateMigrationsFlow(array $schema): void
    {
        $mapper = new \Aftab\LaravelCrud\Services\CrudModelMapper();
        $tables = $mapper->map($schema);

        $builder = new \Aftab\LaravelCrud\Services\MigrationBuilder();
        $options = [
            'temp' => (bool) $this->option('temp'),
        ];
        $migrations = $builder->buildMigrations($tables, $options);
        $written = $builder->writeMigrations($migrations, (bool) $this->option('force'));

        $this->info('Generated migration files:');
        foreach ($written as $p) {
            $this->line(" - {$p}");
        }
    }

    protected function runMigrationsAfterGeneration(): void
    {
        $this->warn('Running migrations programmatically. Ensure you have DB backups.');
        $builder = new \Aftab\LaravelCrud\Services\MigrationBuilder();
        $code = $builder->runMigrate();
        $this->info('Artisan migrate exit code: ' . $code);
    }

    /**
     * @param array<string, mixed> $schema
     */
    protected function generateModelsFlow(array $schema): void
    {
        $mapper = new \Aftab\LaravelCrud\Services\CrudModelMapper();
        $tables = $mapper->map($schema);

        $builder = new \Aftab\LaravelCrud\Services\ModelBuilder();
        $models = $builder->buildModels($tables);
        $written = $builder->writeModels($models, (bool) $this->option('force'));

        $this->info('Generated model files:');
        foreach ($written as $p) {
            $this->line(" - {$p}");
        }
    }
}


