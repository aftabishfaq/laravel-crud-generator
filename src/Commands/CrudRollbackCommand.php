<?php

namespace Aftab\LaravelCrud\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class CrudRollbackCommand extends Command
{
    protected $signature = 'crud:rollback {--yes}';
    protected $description = 'Undo last generated migrations by deleting the files recorded in the log.';

    public function handle(): int
    {
        $logPath = storage_path('logs/laravel-crud-migrations.log');
        if (!is_file($logPath)) {
            $this->warn('No migration log found. Nothing to rollback.');
            return self::SUCCESS;
        }
        $lines = file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        if (empty($lines)) {
            $this->warn('Migration log is empty.');
            return self::SUCCESS;
        }
        $last = json_decode(end($lines), true);
        $files = is_array($last['files'] ?? null) ? $last['files'] : [];
        if (empty($files)) {
            $this->warn('No files recorded in last log entry.');
            return self::SUCCESS;
        }

        $this->info('Files to delete:');
        foreach ($files as $f) { $this->line(' - ' . $f); }

        if (!$this->option('yes')) {
            if (!$this->confirm('Delete these migration files?', false)) {
                $this->warn('Aborted.');
                return self::SUCCESS;
            }
        }

        $fs = new Filesystem();
        $deleted = 0;
        foreach ($files as $f) {
            if (is_file($f)) {
                $fs->delete($f);
                $deleted++;
            }
        }
        $this->info("Deleted {$deleted} files.");
        return self::SUCCESS;
    }
}


