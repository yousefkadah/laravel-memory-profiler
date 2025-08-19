<?php

namespace YousefKadah\LaravelMemoryProfiler;

use Illuminate\Support\ServiceProvider;

class MemoryProfilerServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(MemoryProfiler::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Publish configuration file
        $this->publishes([
            __DIR__.'/../config/memory-profiler.php' => config_path('memory-profiler.php'),
        ], 'memory-profiler-config');

        // Merge default configuration
        $this->mergeConfigFrom(
            __DIR__.'/../config/memory-profiler.php',
            'memory-profiler'
        );

        // Register the profiler command
        $this->commands([
            Commands\ProfileCommand::class,
        ]);

    }

    // ...existing code...
}
