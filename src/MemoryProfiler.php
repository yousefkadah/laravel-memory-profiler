<?php

namespace YousefKadah\LaravelMemoryProfiler;

use Illuminate\Support\Facades\File;
use YousefKadah\LaravelMemoryProfiler\Exceptions\ProfilerException;
use YousefKadah\LaravelMemoryProfiler\Reporters\HtmlReporter;
use YousefKadah\LaravelMemoryProfiler\Reporters\JsonReporter;
use YousefKadah\LaravelMemoryProfiler\Trackers\DatabaseTracker;
use YousefKadah\LaravelMemoryProfiler\Trackers\MemoryTracker;

class MemoryProfiler
{
    /**
     * Tracker instances.
     */
    protected MemoryTracker $memoryTracker;

    protected DatabaseTracker $databaseTracker;

    /**
     * Profiling data storage.
     */
    protected string $commandName;

    /**
     * @var array<string, mixed>
     */
    protected array $commandArguments;

    protected bool $isRunning = false;

    protected float $startTime;

    /**
     * Configuration options.
     */
    protected string $outputDirectory;

    protected int $samplingInterval;

    protected string $reportFormat;

    protected string $outputPath;

    protected int $memoryThreshold;

    protected bool $trackDatabaseQueries;

    protected bool $trackGarbageCollection;

    /**
     * Create a new memory profiler instance.
     */
    public function __construct()
    {
        $this->outputDirectory = config('memory-profiler.output_directory', storage_path('memory-profiles'));
        $this->samplingInterval = config('memory-profiler.sampling_interval', 100);
        $this->reportFormat = config('memory-profiler.report_format', 'both');
        $this->memoryThreshold = config('memory-profiler.memory_threshold', 128 * 1024 * 1024);
        $this->trackDatabaseQueries = config('memory-profiler.track_database_queries', true);
        $this->trackGarbageCollection = config('memory-profiler.track_garbage_collection', true);

        // Initialize trackers
        $this->memoryTracker = new MemoryTracker($this->samplingInterval);
        $this->databaseTracker = new DatabaseTracker;

        // Ensure output directory exists
        if (! File::exists($this->outputDirectory)) {
            File::makeDirectory($this->outputDirectory, 0755, true);
        }
    }

    /**
     * Start profiling a command.
     */
    /**
     * @param string $commandName
     * @param array<string, mixed> $commandArguments
     */
    public function start(string $commandName, array $commandArguments = []): void
    {
        if ($this->isRunning) {
            throw new ProfilerException('Profiler is already running');
        }

        $this->commandName = $commandName;
        $this->commandArguments = $commandArguments;
        $this->startTime = microtime(true);
        $this->isRunning = true;

        // Start memory tracking
        $this->memoryTracker->start();

        // Start database tracking if enabled
        if ($this->trackDatabaseQueries) {
            $this->databaseTracker->start();
        }
    }

    /**
     * Stop profiling and generate report.
     */
    public function stop(): string
    {
        if (! $this->isRunning) {
            throw new ProfilerException('Profiler is not running');
        }

        $this->isRunning = false;

        // Stop trackers
        $this->memoryTracker->stop();

        if ($this->trackDatabaseQueries) {
            $this->databaseTracker->stop();
        }

        // Generate and save report
        return $this->generateReport();
    }

    /**
     * Generate profiling report.
     */
    /**
     * @return string
     */
    protected function generateReport(): string
    {
        $reportData = $this->compileReportData();

        $timestamp = date('Y-m-d_H-i-s');
        $baseFilename = "{$this->commandName}_{$timestamp}";
        $reportPaths = [];

        // Generate JSON report
        if (in_array($this->reportFormat, ['json', 'both'])) {
            $jsonPath = $this->outputDirectory."/{$baseFilename}.json";
            $jsonReporter = new JsonReporter;
            File::put($jsonPath, $jsonReporter->generate($reportData));
            $reportPaths[] = $jsonPath;
        }

        // Generate HTML report
        if (in_array($this->reportFormat, ['html', 'both'])) {
            $htmlPath = $this->outputDirectory."/{$baseFilename}.html";
            $htmlReporter = new HtmlReporter;
            File::put($htmlPath, $htmlReporter->generate($reportData));
            $reportPaths[] = $htmlPath;
        }

        return implode(', ', $reportPaths);
    }

    /**
     * Compile all profiling data into a report structure.
     */
    /**
     * @return array<string, mixed>
     */
    protected function compileReportData(): array
    {
        $memoryStats = $this->memoryTracker->getStatistics();
        $samples = $this->memoryTracker->getSamples();
        $databaseStats = $this->trackDatabaseQueries ? $this->databaseTracker->getStatistics() : [];

        return [
            'command' => [
                'name' => $this->commandName,
                'arguments' => $this->commandArguments,
            ],
            'execution' => [
                'start_time' => $this->startTime,
                'end_time' => microtime(true),
                'duration' => $memoryStats['duration'] ?? 0,
            ],
            'memory' => [
                'initial_usage' => $memoryStats['memory']['initial'] ?? 0,
                'final_usage' => $memoryStats['memory']['current'] ?? 0,
                'peak_usage' => $memoryStats['memory']['peak'] ?? 0,
                'difference' => $memoryStats['memory']['difference'] ?? 0,
                'threshold_exceeded' => ($memoryStats['memory']['peak'] ?? 0) > $this->memoryThreshold,
                'trend' => $memoryStats['trend'] ?? 'unknown',
                'leak_detected' => $memoryStats['leak_detected'] ?? false,
            ],
            'samples' => $samples,
            'database' => $databaseStats,
            'analysis' => $this->analyzeData($memoryStats, $databaseStats),
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Analyze the profiling data for insights.
     */
    /**
     * @param array<string, mixed> $memoryStats
     * @param array<string, mixed> $databaseStats
     * @return array<string, mixed>
     */
    protected function analyzeData(array $memoryStats, array $databaseStats): array
    {
        $analysis = [
            'memory_leak_detected' => $memoryStats['leak_detected'] ?? false,
            'memory_trend' => $memoryStats['trend'] ?? 'unknown',
            'peak_memory_time' => null,
            'query_count' => $databaseStats['total_queries'] ?? 0,
            'potential_issues' => [],
        ];

        // Combine memory and database issues
        $memoryIssues = $this->analyzeMemoryIssues($memoryStats);
        $databaseIssues = $databaseStats['potential_issues'] ?? [];

        $analysis['potential_issues'] = array_merge($memoryIssues, $databaseIssues);

        return $analysis;
    }

    /**
     * Analyze memory-specific issues.
     */
    /**
     * @param array<string, mixed> $memoryStats
     * @return array<int, array<string, mixed>>
     */
    protected function analyzeMemoryIssues(array $memoryStats): array
    {
        $issues = [];

        // Memory leak detection
        if ($memoryStats['leak_detected'] ?? false) {
            $issues[] = [
                'type' => 'memory_leak',
                'message' => 'Memory leak detected - final memory usage is higher than initial',
                'severity' => 'high',
            ];
        }

        // High memory usage
        if (($memoryStats['memory']['peak'] ?? 0) > $this->memoryThreshold) {
            $issues[] = [
                'type' => 'high_memory_usage',
                'message' => 'High memory usage detected - exceeded configured threshold',
                'severity' => 'high',
            ];
        }

        // Increasing memory trend
        if (($memoryStats['trend'] ?? '') === 'increasing') {
            $issues[] = [
                'type' => 'increasing_memory_trend',
                'message' => 'Memory usage is consistently increasing over time',
                'severity' => 'medium',
            ];
        }

        return $issues;
    }

    /**
     * Format bytes into human-readable format.
     */
    /**
     * @param int $bytes
     * @return string
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2).' '.$units[$pow];
    }

    /**
     * Get profiling summary.
     */
    /**
     * @return array<string, mixed>
     */
    public function getSummary(): array
    {
        if (! $this->memoryTracker->isTracking() && empty($this->memoryTracker->getSamples())) {
            return [];
        }

        $stats = $this->memoryTracker->getStatistics();

        return [
            'peak_memory' => $stats['memory']['peak'] ?? 0,
            'final_memory' => $stats['memory']['current'] ?? 0,
            'memory_difference' => $stats['memory']['difference'] ?? 0,
            'execution_time' => $stats['duration'] ?? 0,
            'sample_count' => $stats['sample_count'] ?? 0,
        ];
    }

    // Getter and setter methods
    public function getSamplingInterval(): int
    {
        return $this->samplingInterval;
    }

    public function setSamplingInterval(int $interval): void
    {
        $this->samplingInterval = $interval;
    }

    public function getOutputDirectory(): string
    {
        return $this->outputDirectory;
    }

    public function setOutputDirectory(string $directory): void
    {
        $this->outputDirectory = $directory;
    }

    public function getReportFormat(): string
    {
        return $this->reportFormat;
    }

    public function setReportFormat(string $format): void
    {
        $this->reportFormat = $format;
    }

    public function setOutputPath(string $path): void
    {
        $this->outputPath = $path;
    }
}
