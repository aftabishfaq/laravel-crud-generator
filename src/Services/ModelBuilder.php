<?php

namespace Aftab\LaravelCrud\Services;

use Aftab\LaravelCrud\Services\Dto\CrudColumn;
use Aftab\LaravelCrud\Services\Dto\CrudTable;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class ModelBuilder
{
    protected Filesystem $files;

    public function __construct(?Filesystem $files = null)
    {
        $this->files = $files ?: new Filesystem();
    }

    /**
     * @param array<int, CrudTable> $tables
     * @param array<string, mixed> $options
     * @return array<int, array{path:string, filename:string, contents:string}>
     */
    public function buildModels(array $tables, array $options = []): array
    {
        $namespace = (string) (config('crud.models_namespace') ?? 'App\\Models');
        $targetDir = base_path(str_replace('\\\\', DIRECTORY_SEPARATOR, str_replace('\\', DIRECTORY_SEPARATOR, $this->namespaceToPath($namespace))));

        $results = [];
        foreach ($tables as $table) {
            $class = Str::studly(Str::singular($table->name));
            $filename = $class . '.php';
            $path = rtrim($targetDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

            $fillable = $this->inferFillable($table);
            $contents = $this->renderModel($namespace, $class, $table->name, $fillable);
            $results[] = compact('path', 'filename', 'contents');
        }

        return $results;
    }

    /**
     * @param array<int, array{path:string, filename:string, contents:string}> $models
     * @return array<int, string>
     */
    public function writeModels(array $models, bool $force = false): array
    {
        $written = [];
        foreach ($models as $model) {
            $path = $model['path'];
            if ($this->files->exists($path) && !$force) {
                // avoid overwrite: append numeric suffix
                $dir = dirname($path);
                $name = pathinfo($path, PATHINFO_FILENAME);
                $ext = '.php';
                $i = 1;
                do {
                    $candidate = $dir . DIRECTORY_SEPARATOR . $name . '_' . $i . $ext;
                    $i++;
                } while ($this->files->exists($candidate));
                $path = $candidate;
            }
            $this->files->ensureDirectoryExists(dirname($path));
            $this->files->put($path, $model['contents']);
            $written[] = $path;
        }
        return $written;
    }

    /**
     * @return array<int, string>
     */
    protected function inferFillable(CrudTable $table): array
    {
        $ignore = ['id', 'created_at', 'updated_at', 'deleted_at'];
        $names = [];
        foreach ($table->columns as $col) {
            $names[] = $col->name;
        }
        return array_values(array_filter($names, static function ($n) use ($ignore) {
            return !in_array($n, $ignore, true);
        }));
    }

    /**
     * @param array<int, string> $fillable
     */
    protected function renderModel(string $namespace, string $class, string $table, array $fillable): string
    {
        $fillableExport = '';
        if (!empty($fillable)) {
            $fillableList = implode(', ', array_map(static fn($f) => '\'' . $f . '\'', $fillable));
            $fillableExport = "\n    protected \\$fillable = [{$fillableList}];\n";
        }

        $code = '';
        $code .= "<?php\n\n";
        $code .= 'namespace ' . $namespace . ";\n\n";
        $code .= "use Illuminate\\\\Database\\\\Eloquent\\\\Model;\n";
        $code .= "use Illuminate\\\\Database\\\\Eloquent\\\\SoftDeletes;\n\n";
        $code .= 'class ' . $class . ' extends Model' . "\n";
        $code .= "{\n";
        $code .= "    // Set table explicitly to avoid naming surprises\n";
        $code .= "    protected \\$table = '{$table}';\n";
        $code .= $fillableExport;
        $code .= "    protected \\$guarded = [];\n";
        $code .= "}\n";
        return $code;
    }

    protected function namespaceToPath(string $namespace): string
    {
        // Convert e.g., App\\Models to app/Models
        $segments = explode('\\\', $namespace);
        $path = implode(DIRECTORY_SEPARATOR, $segments);
        // Assume PSR-4 base is project root; typical Laravel maps App\\ to app/
        if (str_starts_with($namespace, 'App\\')) {
            $path = 'app' . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, array_slice($segments, 1));
        }
        return $path;
    }
}


