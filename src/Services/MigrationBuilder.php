<?php

namespace Aftab\LaravelCrud\Services;

use Aftab\LaravelCrud\Services\Dto\CrudColumn;
use Aftab\LaravelCrud\Services\Dto\CrudTable;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Artisan;

class MigrationBuilder
{
    protected Filesystem $files;

    public function __construct(?Filesystem $files = null)
    {
        $this->files = $files ?: new Filesystem();
    }

    /**
     * Build migration file contents for provided tables.
     * @param array<int, CrudTable> $tables
     * @return array<int, array{path:string, filename:string, contents:string}>
     */
    /**
     * @param array<int, CrudTable> $tables
     * @param array<string, mixed> $options
     * @return array<int, array{path:string, filename:string, contents:string}>
     */
    public function buildMigrations(array $tables, array $options = []): array
    {
        $now = time();
        $timestampBase = date('Y_m_d_His', $now);
        $targetDir = $this->resolveTargetDirectory($options);

        $results = [];
        $counter = 0;
        foreach ($tables as $table) {
            $timestamp = $this->incrementTimestamp($timestampBase, $counter++);
            $filename = $timestamp . '_create_' . $table->name . '_table.php';
            $path = rtrim($targetDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
            $contents = $this->renderMigration($table, $timestamp);
            $results[] = compact('path', 'filename', 'contents');
        }

        return $results;
    }

    /**
     * Write migration files safely. If file exists and !force, adjust timestamp to avoid overwrite.
     * Returns array of written paths.
     * @param array<int, array{path:string, filename:string, contents:string}> $migrations
     * @return array<int, string>
     */
    /**
     * @param array<int, array{path:string, filename:string, contents:string}> $migrations
     * @return array<int, string>
     */
    public function writeMigrations(array $migrations, bool $force = false): array
    {
        $written = [];
        foreach ($migrations as $migration) {
            $path = $migration['path'];
            if ($this->files->exists($path) && !$force) {
                // bump one second until unique
                [$dir, $file] = [dirname($path), basename($path)];
                [$prefix, $rest] = [substr($file, 0, 17), substr($file, 17)]; // YYYY_MM_DD_HHMMSS_
                $ts = \DateTime::createFromFormat('Y_m_d_His', rtrim($prefix, '_')) ?: new \DateTime();
                do {
                    $ts->modify('+1 second');
                    $newFile = $ts->format('Y_m_d_His') . '_' . ltrim($rest, '_');
                    $path = $dir . DIRECTORY_SEPARATOR . $newFile;
                } while ($this->files->exists($path));
            }
            $this->files->ensureDirectoryExists(dirname($path));
            $this->files->put($path, $migration['contents']);
            $written[] = $path;
        }

        $this->logGenerated($written);
        return $written;
    }

    /**
     * Optionally run migrations programmatically. When path is provided, use --path for targeted run.
     */
    public function runMigrate(?string $path = null): int
    {
        $params = [];
        if ($path) {
            $params['--path'] = $this->relativePathForMigrate($path);
        }
        return Artisan::call('migrate', $params);
    }

    /**
     * @param array<string, mixed> $options
     */
    protected function resolveTargetDirectory(array $options): string
    {
        $configPath = (string) (config('crud.migrations_path') ?? 'database/migrations');
        $default = base_path($configPath);

        if (!empty($options['temp'])) {
            return storage_path('app/laravel-crud/tmp_migrations');
        }

        return $default;
    }

    protected function incrementTimestamp(string $base, int $offsetSeconds): string
    {
        $dt = \DateTime::createFromFormat('Y_m_d_His', $base) ?: new \DateTime();
        if ($offsetSeconds > 0) {
            $dt->modify("+{$offsetSeconds} seconds");
        }
        return $dt->format('Y_m_d_His');
    }

    protected function renderMigration(CrudTable $table, string $timestamp): string
    {
        $className = 'Create' . Str::studly($table->name) . 'Table';
        $tableName = $table->name;

        $upBody = $this->renderUpBody($table);
        $downBody = "Schema::dropIfExists('{$tableName}');";

        return <<<PHP
<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('{$tableName}', function (Blueprint \$table) {
{$upBody}
        });
    }

    public function down(): void
    {
        {$downBody}
    }
};
PHP;
    }

    protected function renderUpBody(CrudTable $table): string
    {
        $lines = [];
        $lines[] = '            $table->id();';
        foreach ($table->columns as $column) {
            $lines = array_merge($lines, $this->renderColumn($column));
        }
        if ($table->softDeletes) {
            $lines[] = '            $table->softDeletes();';
        }
        if ($table->timestamps) {
            $lines[] = '            $table->timestamps();';
        }
        return implode("\n", $lines);
    }

    /**
     * @return array<int, string>
     */
    protected function renderColumn(CrudColumn $col): array
    {
        $lines = [];
        [$definition, $post] = $this->columnDefinition($col);
        $lines[] = '            ' . $definition . ';';
        foreach ($post as $p) {
            $lines[] = '            ' . $p . ';';
        }
        return $lines;
    }

    /**
     * Returns [definitionLine, postLines[]]
     * definitionLine is a single line like $table->string('name')->nullable()
     * postLines are additional statements like foreign keys if needed
     * @return array{0:string,1:array<int,string>}
     */
    protected function columnDefinition(CrudColumn $col): array
    {
        $method = $this->schemaMethodForType($col);
        $chain = [];
        if ($col->isNullable) {
            $chain[] = 'nullable()';
        }
        if ($col->isUnique) {
            $chain[] = 'unique()';
        }
        if ($col->defaultValue !== null) {
            $default = var_export($col->defaultValue, true);
            $chain[] = "default({$default})";
        }

        $definition = "\$table->{$method}('{$col->name}')";
        if (!empty($chain)) {
            $definition .= '->' . implode('->', $chain);
        }

        $post = [];
        if ($col->foreignTable && $col->foreignColumn) {
            // If the base type wasn't unsignedBigInteger, ensure FK-compatible column was used
            if ($method !== 'unsignedBigInteger' && $method !== 'foreignId') {
                // add a dedicated unsignedBigInteger and use that for FK
                $definition = "\$table->unsignedBigInteger('{$col->name}')" . (!empty($chain) ? '->' . implode('->', $chain) : '');
            }

            $fkLine = "\$table->foreign('{$col->name}')->references('{$col->foreignColumn}')->on('{$col->foreignTable}')";
            if ($col->onDelete) {
                $fkLine .= "->onDelete('{$col->onDelete}')";
            }
            $post[] = $fkLine;
        }

        return [$definition, $post];
    }

    protected function schemaMethodForType(CrudColumn $col): string
    {
        $type = $col->type;
        return match ($type) {
            'string' => 'string',
            'text' => 'text',
            'integer' => 'integer',
            'bigInteger' => 'bigInteger',
            'unsignedBigInteger' => 'unsignedBigInteger',
            'boolean' => 'boolean',
            'date' => 'date',
            'datetime' => 'dateTime',
            'timestamp' => 'timestamp',
            'json' => 'json',
            'decimal' => 'decimal',
            'float' => 'float',
            'enum' => $this->enumMethod($col),
            default => 'string',
        };
    }

    protected function enumMethod(CrudColumn $col): string
    {
        if (is_array($col->options) && count($col->options) > 0) {
            $values = array_map(static fn($v) => var_export($v, true), $col->options);
            return "enum('{$col->name}', [" . implode(', ', $values) . '])'; // will be embedded specially
        }
        return 'string';
    }

    protected function relativePathForMigrate(string $absPath): string
    {
        $base = base_path();
        if (Str::startsWith($absPath, $base)) {
            return ltrim(str_replace('\\', '/', substr($absPath, strlen($base))), '/');
        }
        return $absPath;
    }

    /**
     * @param array<int, string> $paths
     */
    protected function logGenerated(array $paths): void
    {
        if (empty($paths)) {
            return;
        }
        $logPath = storage_path('logs/laravel-crud-migrations.log');
        $entry = json_encode(['at' => date(DATE_ATOM), 'files' => $paths], JSON_UNESCAPED_SLASHES);
        $this->files->append($logPath, $entry . PHP_EOL);
    }
}


