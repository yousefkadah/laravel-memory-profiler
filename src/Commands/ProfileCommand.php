<?php

namespace YousefKadah\LaravelMemoryProfiler\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use YousefKadah\LaravelMemoryProfiler\MemoryProfiler;

class ProfileCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'profile:memory {profiled_command : The Artisan command to profile} {--arguments=* : Arguments to pass to the command} {--options=* : Options to pass to the command} {--output= : Custom output file path} {--format= : Report format (json, html, both)} {--interval= : Sampling interval in milliseconds}';

    /**
     * The console command description.
     */
    protected $description = 'Profile memory usage of an Artisan command';

    /**
     * The memory profiler instance.
     */
    protected MemoryProfiler $profiler;

    /**
     * Create a new command instance.
     */
    public function __construct(MemoryProfiler $profiler)
    {
        parent::__construct();
        $this->profiler = $profiler;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $commandName = $this->argument('profiled_command');
        /** @var array<int, string> $arguments */
        $arguments = $this->option('arguments') ?? [];
        /** @var array<int, string> $options */
        $options = $this->option('options') ?? [];

        // Parse arguments and options
        /** @var array<string, mixed> $commandArguments */
        $commandArguments = $this->parseCommandArguments($arguments, $options);

        // Configure profiler
        $this->configureProfiler();

        $this->info("Starting memory profiling for command: {$commandName}");
        $this->info('Profiling configuration:');
        $this->line("  - Sampling interval: {$this->profiler->getSamplingInterval()}ms");
        $this->line("  - Output directory: {$this->profiler->getOutputDirectory()}");
        $this->line("  - Report format: {$this->profiler->getReportFormat()}");
        $this->newLine();

        // Start profiling
        $this->profiler->start($commandName, $commandArguments);

        try {
            // Execute the command
            $exitCode = Artisan::call($commandName, $commandArguments);

            // Stop profiling and generate report
            $reportPath = $this->profiler->stop();

            $this->newLine();
            $this->info("Command executed with exit code: {$exitCode}");
            $this->info('Memory profiling completed!');
            $this->line("Report saved to: {$reportPath}");

            // Display summary
            $this->displaySummary();

            return $exitCode;

        } catch (\Exception $e) {
            $this->profiler->stop();
            $this->error('Command execution failed: '.$e->getMessage());

            return 1;
        }
    }

    /**
     * Configure the profiler based on command options.
     */
    protected function configureProfiler(): void
    {
        if ($output = $this->option('output')) {
            $this->profiler->setOutputPath($output);
        }

        if ($format = $this->option('format')) {
            $this->profiler->setReportFormat($format);
        }

        if ($interval = $this->option('interval')) {
            $this->profiler->setSamplingInterval((int) $interval);
        }
    }

    /**
     * Parse command arguments and options from the input.
     *
     * @param  array<int, string>  $arguments
     * @param  array<int, string>  $options
     * @return array<string, mixed>
     */
    protected function parseCommandArguments(array $arguments, array $options): array
    {
        /** @var array<string, mixed> $commandArguments */
        $commandArguments = [];

        // Parse arguments (format: key=value or just value)
        foreach ($arguments as $argument) {
            if (strpos($argument, '=') !== false) {
                [$key, $value] = explode('=', $argument, 2);
                $commandArguments[$key] = $value;
            } else {
                $commandArguments[] = $argument;
            }
        }

        // Parse options (format: --key=value or --key)
        foreach ($options as $option) {
            if (strpos($option, '=') !== false) {
                [$key, $value] = explode('=', $option, 2);
                $key = ltrim($key, '-');
                $commandArguments["--{$key}"] = $value;
            } else {
                $key = ltrim($option, '-');
                $commandArguments["--{$key}"] = true;
            }
        }

        return $commandArguments;
    }

    /**
     * Display a summary of the profiling results.
     */
    protected function displaySummary(): void
    {
        /** @var array<string, mixed> $summary */
        $summary = $this->profiler->getSummary();

        $this->newLine();
        $this->line('<comment>Memory Profiling Summary:</comment>');
        $this->line('  Peak Memory Usage: '.$this->formatBytes($summary['peak_memory']));
        $this->line('  Final Memory Usage: '.$this->formatBytes($summary['final_memory']));
        $this->line('  Memory Difference: '.$this->formatBytes($summary['memory_difference']));
        $this->line('  Execution Time: '.number_format($summary['execution_time'], 2).'s');
        $this->line('  Samples Collected: '.$summary['sample_count']);

        if ($summary['memory_difference'] > 0) {
            $this->warn('⚠️  Potential memory leak detected! Memory increased by '.
                       $this->formatBytes($summary['memory_difference']));
        } else {
            $this->info('✅ No memory leak detected.');
        }

        if ($summary['peak_memory'] > config('memory-profiler.memory_threshold', 128 * 1024 * 1024)) {
            $this->warn('⚠️  High memory usage detected! Peak usage exceeded threshold.');
        }
    }

    /**
     * Format bytes into human-readable format.
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        if ($pow === 0.0) {
            return $bytes.' '.$units[$pow];
        }

        return number_format($bytes, 2).' '.$units[$pow];
    }
}
