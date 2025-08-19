<?php

namespace YousefKadah\LaravelMemoryProfiler\Trackers;

use YousefKadah\LaravelMemoryProfiler\Exceptions\TrackerException;

class MemoryTracker
{
    /**
     * Tracking state.
     */
    protected bool $isTracking = false;

    protected float $startTime;

    protected int $startMemory;

    /**
     * @var array<int, array<string, mixed>>
     */
    protected array $samples = [];

    protected int $samplingInterval;

    /**
     * @var resource|null
     */
    protected $backgroundProcess = null;

    /**
     * Create a new memory tracker instance.
     */
    public function __construct(int $samplingInterval = 100)
    {
        $this->samplingInterval = $samplingInterval;
    }

    /**
     * Start memory tracking.
     */
    public function start(): void
    {
        if ($this->isTracking) {
            throw new TrackerException('Memory tracking is already active');
        }

        $this->isTracking = true;
        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage(true);
        $this->samples = [];

        // Collect initial sample
        $this->collectSample();

        // Start background sampling if possible
        $this->startBackgroundSampling();
    }

    /**
     * Stop memory tracking.
     *
     * @return array<int, array<string, mixed>>
     */
    public function stop(): array
    {
        if (! $this->isTracking) {
            throw new TrackerException('Memory tracking is not active');
        }

        // Collect final sample
        $this->collectSample();

        $this->isTracking = false;
        $this->stopBackgroundSampling();

        return $this->samples;
    }

    /**
     * Collect a memory usage sample.
     *
     * @return array<string, mixed>
     */
    public function collectSample(): array
    {
        if (! $this->isTracking) {
            return [];
        }

        $currentTime = microtime(true);
        $currentMemory = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);

        $sample = [
            'timestamp' => $currentTime,
            'elapsed_time' => $currentTime - $this->startTime,
            'memory_usage' => $currentMemory,
            'peak_memory' => $peakMemory,
            'memory_difference' => $currentMemory - $this->startMemory,
            'allocated_memory' => memory_get_usage(false),
            'allocated_peak' => memory_get_peak_usage(false),
        ];

        // Add garbage collection stats if available
        if (function_exists('gc_status')) {
            $sample['gc_stats'] = gc_status();
        }

        // Add memory allocation details if available
        if (function_exists('memory_get_usage')) {
            $sample['real_memory'] = memory_get_usage(true);
            $sample['emalloc_memory'] = memory_get_usage(false);
        }

        $this->samples[] = $sample;

        return $sample;
    }

    /**
     * Start background sampling using a timer-based approach.
     */
    protected function startBackgroundSampling(): void
    {
        // Register a tick function for periodic sampling
        if (function_exists('register_tick_function')) {
            declare(ticks=1);
            // @phpstan-ignore-next-line argument.type
            register_tick_function([$this, 'collectSample']);
        }

        // Also register shutdown function to ensure final sample
        register_shutdown_function(function () {
            if ($this->isTracking) {
                $this->collectSample();
            }
        });
    }

    /**
     * Stop background sampling.
     */
    protected function stopBackgroundSampling(): void
    {
        if (function_exists('unregister_tick_function')) {
            unregister_tick_function([$this, 'collectSample']);
        }
    }

    /**
     * Force a sample collection (useful for manual checkpoints).
     *
     * @return array<string, mixed>
     */
    public function checkpoint(?string $label = null): array
    {
        $sample = $this->collectSample();

        if ($label) {
            $sample['label'] = $label;
            // Update the last sample with the label
            $this->samples[count($this->samples) - 1]['label'] = $label;
        }

        return $sample;
    }

    /**
     * Get all collected samples.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getSamples(): array
    {
        return $this->samples;
    }

    /**
     * Get the latest sample.
     *
     * @return array<string, mixed>|null
     */
    public function getLatestSample(): ?array
    {
        return end($this->samples) ?: null;
    }

    /**
     * Get tracking statistics.
     */
    public function getStatistics(): array
    {
        if (empty($this->samples)) {
            return [];
        }

        $memoryUsages = array_column($this->samples, 'memory_usage');
        $peakMemories = array_column($this->samples, 'peak_memory');
        $memoryDifferences = array_column($this->samples, 'memory_difference');

        return [
            'sample_count' => count($this->samples),
            'duration' => $this->isTracking ? microtime(true) - $this->startTime : end($this->samples)['elapsed_time'],
            'memory' => [
                'initial' => $this->startMemory,
                'current' => end($memoryUsages),
                'peak' => max($peakMemories),
                'min' => min($memoryUsages),
                'max' => max($memoryUsages),
                'average' => array_sum($memoryUsages) / count($memoryUsages),
                'difference' => end($memoryDifferences),
            ],
            'trend' => $this->calculateTrend($memoryUsages),
            'leak_detected' => end($memoryDifferences) > 0,
        ];
    }

    /**
     * Calculate memory usage trend.
     */
    protected function calculateTrend(array $memoryUsages): string
    {
        if (count($memoryUsages) < 2) {
            return 'insufficient_data';
        }

        $first = reset($memoryUsages);
        $last = end($memoryUsages);
        $threshold = 0.05; // 5% threshold

        $change = ($last - $first) / $first;

        if ($change > $threshold) {
            return 'increasing';
        } elseif ($change < -$threshold) {
            return 'decreasing';
        } else {
            return 'stable';
        }
    }

    /**
     * Check if tracking is active.
     */
    public function isTracking(): bool
    {
        return $this->isTracking;
    }

    /**
     * Get the sampling interval.
     */
    public function getSamplingInterval(): int
    {
        return $this->samplingInterval;
    }

    /**
     * Set the sampling interval.
     */
    public function setSamplingInterval(int $interval): void
    {
        $this->samplingInterval = $interval;
    }

    /**
     * Reset the tracker state.
     */
    public function reset(): void
    {
        $this->stop();
        $this->samples = [];
    }

    /**
     * Get memory usage in human-readable format.
     */
    public function formatBytes(int $bytes): string
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
