<?php

namespace Aftab\LaravelCrud;

use Illuminate\Support\ServiceProvider;

class CrudGeneratorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $configPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'crud.php';
        if (file_exists($configPath)) {
            $this->mergeConfigFrom($configPath, 'crud');
        }
    }

    public function boot(): void
    {
        // Load views from package with namespace 'crud'
        $viewsPath = __DIR__ . DIRECTORY_SEPARATOR . 'Resources' . DIRECTORY_SEPARATOR . 'views';
        if (is_dir($viewsPath)) {
            $this->loadViewsFrom($viewsPath, 'crud');
        }

        // Optionally load routes if a routes file is provided later
        $routesPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'routes' . DIRECTORY_SEPARATOR . 'crud.php';
        if (file_exists($routesPath)) {
            $this->loadRoutesFrom($routesPath);
        }

        // Register publishable assets (config, views)
        $this->registerPublishing($viewsPath);

        // Register console commands (only when running in console and class exists)
        if ($this->app->runningInConsole()) {
            $commands = [
                \Aftab\LaravelCrud\Commands\CrudExecuteCommand::class,
                \Aftab\LaravelCrud\Commands\CrudPreviewCommand::class,
                \Aftab\LaravelCrud\Commands\CrudRollbackCommand::class,
            ];
            $this->commands(array_values(array_filter($commands, 'class_exists')));
        }
    }

    protected function registerPublishing(string $viewsPath): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $configFrom = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'crud.php';
        if (file_exists($configFrom)) {
            $this->publishes([
                $configFrom => config_path('crud.php'),
            ], 'crud-config');
        }

        if (is_dir($viewsPath)) {
            $this->publishes([
                $viewsPath => resource_path('views/vendor/laravel-crud'),
            ], 'crud-views');
        }
    }
}


