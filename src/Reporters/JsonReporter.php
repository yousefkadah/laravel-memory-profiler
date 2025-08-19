<?php

namespace YousefKadah\LaravelMemoryProfiler\Reporters;

class JsonReporter
{
    /**
     * Generate a JSON report from profiling data.
     *
     * @param  array<string, mixed>  $data
     */
    public function generate(array $data): string
    {
        return json_encode($this->enhanceData($data), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Enhance the data with additional analysis.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function enhanceData(array $data): array
    {
        /** @var array<string, mixed> $enhanced */
        $enhanced = $data;

        // Add statistical analysis
        $enhanced['statistics'] = $this->calculateStatistics($data);

        // Add performance metrics
        $enhanced['performance_metrics'] = $this->calculatePerformanceMetrics($data);

        // Add memory efficiency score
        $enhanced['efficiency_score'] = $this->calculateEfficiencyScore($data);

        // Add detailed recommendations
        $enhanced['recommendations'] = $this->generateDetailedRecommendations($data);

        return $enhanced;
    }

    /**
     * Calculate statistical analysis of the memory data.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function calculateStatistics(array $data): array
    {
        if (empty($data['samples'])) {
            return [];
        }

        /** @var array<int, int> $memoryUsages */
        $memoryUsages = array_column($data['samples'], 'memory_usage');
        /** @var array<int, int> $memoryDifferences */
        $memoryDifferences = array_column($data['samples'], 'memory_difference');
        /** @var array<int, float> $elapsedTimes */
        $elapsedTimes = array_column($data['samples'], 'elapsed_time');

        return [
            'memory_usage' => [
                'mean' => array_sum($memoryUsages) / count($memoryUsages),
                'median' => $this->calculateMedian($memoryUsages),
                'std_deviation' => $this->calculateStandardDeviation($memoryUsages),
                'min' => min($memoryUsages),
                'max' => max($memoryUsages),
                'range' => max($memoryUsages) - min($memoryUsages),
                'percentiles' => [
                    '25th' => $this->calculatePercentile($memoryUsages, 25),
                    '75th' => $this->calculatePercentile($memoryUsages, 75),
                    '90th' => $this->calculatePercentile($memoryUsages, 90),
                    '95th' => $this->calculatePercentile($memoryUsages, 95),
                ],
            ],
            'memory_differences' => [
                'mean' => array_sum($memoryDifferences) / count($memoryDifferences),
                'median' => $this->calculateMedian($memoryDifferences),
                'positive_count' => count(array_filter($memoryDifferences, fn ($d) => $d > 0)),
                'negative_count' => count(array_filter($memoryDifferences, fn ($d) => $d < 0)),
                'zero_count' => count(array_filter($memoryDifferences, fn ($d) => $d === 0)),
            ],
            'sampling' => [
                'total_samples' => count($data['samples']),
                'duration' => max($elapsedTimes) - min($elapsedTimes),
                'average_interval' => count($elapsedTimes) > 1 ?
                    (max($elapsedTimes) - min($elapsedTimes)) / (count($elapsedTimes) - 1) : 0,
            ],
        ];
    }

    /**
     * Calculate performance metrics.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function calculatePerformanceMetrics(array $data): array
    {
        /** @var array<string, mixed> $metrics */
        $metrics = [
            'memory_efficiency' => $this->calculateMemoryEfficiency($data),
            'stability_score' => $this->calculateStabilityScore($data),
            'leak_probability' => $this->calculateLeakProbability($data),
            'performance_grade' => 'A', // Will be calculated
        ];

        // Calculate overall performance grade
        $metrics['performance_grade'] = $this->calculatePerformanceGrade($metrics);

        return $metrics;
    }

    /**
     * Calculate memory efficiency score (0-100).
     *
     * @param  array<string, mixed>  $data
     */
    protected function calculateMemoryEfficiency(array $data): float
    {
        if (empty($data['samples'])) {
            return 0;
        }

        /** @var array<int, int> $memoryUsages */
        $memoryUsages = array_column($data['samples'], 'memory_usage');
        $peakMemory = max($memoryUsages);
        $avgMemory = array_sum($memoryUsages) / count($memoryUsages);

        // Efficiency is higher when average usage is closer to peak (less waste)
        $efficiency = ($avgMemory / $peakMemory) * 100;

        // Penalize for high absolute memory usage
        $threshold = 128 * 1024 * 1024; // 128MB
        if ($peakMemory > $threshold) {
            $penalty = min(50, ($peakMemory - $threshold) / $threshold * 50);
            $efficiency -= $penalty;
        }

        return max(0, min(100, $efficiency));
    }

    /**
     * Calculate stability score (0-100).
     *
     * @param  array<string, mixed>  $data
     */
    protected function calculateStabilityScore(array $data): float
    {
        if (empty($data['samples'])) {
            return 0;
        }

        /** @var array<int, int> $memoryUsages */
        $memoryUsages = array_column($data['samples'], 'memory_usage');
        $stdDev = $this->calculateStandardDeviation($memoryUsages);
        $mean = array_sum($memoryUsages) / count($memoryUsages);

        // Lower coefficient of variation = higher stability
        $coefficientOfVariation = $mean > 0 ? $stdDev / $mean : 1;
        $stability = max(0, 100 - ($coefficientOfVariation * 100));

        return min(100, $stability);
    }

    /**
     * Calculate leak probability (0-100).
     *
     * @param  array<string, mixed>  $data
     */
    protected function calculateLeakProbability(array $data): float
    {
        if (empty($data['samples'])) {
            return 0;
        }

        /** @var array<int, int> $memoryDifferences */
        $memoryDifferences = array_column($data['samples'], 'memory_difference');
        $positiveCount = count(array_filter($memoryDifferences, fn ($d) => $d > 0));
        $totalCount = count($memoryDifferences);

        $positiveRatio = $positiveCount / $totalCount;

        // Check for consistent increase
        $finalDifference = end($memoryDifferences);
        $consistentIncrease = $finalDifference > 0 ? 50 : 0;

        // Check trend
        $trendPenalty = ($data['memory']['trend'] ?? '') === 'increasing' ? 30 : 0;

        return min(100, ($positiveRatio * 50) + $consistentIncrease + $trendPenalty);
    }

    /**
     * Calculate overall performance grade.
     *
     * @param  array<string, mixed>  $metrics
     */
    protected function calculatePerformanceGrade(array $metrics): string
    {
        $efficiency = $metrics['memory_efficiency'];
        $stability = $metrics['stability_score'];
        $leakProb = $metrics['leak_probability'];

        $overallScore = ($efficiency + $stability + (100 - $leakProb)) / 3;

        if ($overallScore >= 90) {
            return 'A+';
        }
        if ($overallScore >= 80) {
            return 'A';
        }
        if ($overallScore >= 70) {
            return 'B';
        }
        if ($overallScore >= 60) {
            return 'C';
        }
        if ($overallScore >= 50) {
            return 'D';
        }

        return 'F';
    }

    /**
     * Calculate efficiency score (0-100).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function calculateEfficiencyScore(array $data): array
    {
        $memoryScore = $this->calculateMemoryEfficiency($data);
        $stabilityScore = $this->calculateStabilityScore($data);
        $leakScore = 100 - $this->calculateLeakProbability($data);

        $databaseScore = 100;
        if (! empty($data['database'])) {
            $queryCount = $data['database']['total_queries'] ?? 0;
            $avgQueryTime = $data['database']['average_time'] ?? 0;

            // Penalize for too many queries or slow queries
            if ($queryCount > 1000) {
                $databaseScore -= min(50, ($queryCount - 1000) / 100);
            }
            if ($avgQueryTime > 100) {
                $databaseScore -= min(30, ($avgQueryTime - 100) / 10);
            }
        }

        $overallScore = ($memoryScore + $stabilityScore + $leakScore + $databaseScore) / 4;

        return [
            'overall' => round($overallScore, 2),
            'memory_efficiency' => round($memoryScore, 2),
            'stability' => round($stabilityScore, 2),
            'leak_resistance' => round($leakScore, 2),
            'database_efficiency' => round($databaseScore, 2),
            'grade' => $this->scoreToGrade($overallScore),
        ];
    }

    /**
     * Convert score to letter grade.
     */
    protected function scoreToGrade(float $score): string
    {
        if ($score >= 95) {
            return 'A+';
        }
        if ($score >= 90) {
            return 'A';
        }
        if ($score >= 85) {
            return 'A-';
        }
        if ($score >= 80) {
            return 'B+';
        }
        if ($score >= 75) {
            return 'B';
        }
        if ($score >= 70) {
            return 'B-';
        }
        if ($score >= 65) {
            return 'C+';
        }
        if ($score >= 60) {
            return 'C';
        }
        if ($score >= 55) {
            return 'C-';
        }
        if ($score >= 50) {
            return 'D';
        }

        return 'F';
    }

    /**
     * Generate detailed recommendations.
     *
     * @param  array<string, mixed>  $data
     * @return array<int, array<string, mixed>>
     */
    protected function generateDetailedRecommendations(array $data): array
    {
        /** @var array<int, array<string, mixed>> $recommendations */
        $recommendations = [];

        // Memory-based recommendations
        if ($data['memory']['leak_detected'] ?? false) {
            $recommendations[] = [
                'category' => 'memory_leak',
                'priority' => 'high',
                'title' => 'Memory Leak Detected',
                'description' => 'Your command shows signs of a memory leak.',
                'suggestions' => [
                    'Use unset() to explicitly free large variables',
                    'Implement chunking for large dataset processing',
                    'Clear static caches periodically in long-running processes',
                    'Consider using generators instead of loading all data into memory',
                    'Review object references for circular dependencies',
                ],
                'code_examples' => [
                    'chunking' => 'User::chunk(100, function ($users) { /* process */ });',
                    'unset' => 'unset($largeVariable, $anotherLargeVariable);',
                    'generator' => 'function getUsers() { foreach (User::cursor() as $user) yield $user; }',
                ],
            ];
        }

        // High memory usage recommendations
        if ($data['memory']['threshold_exceeded'] ?? false) {
            $recommendations[] = [
                'category' => 'high_memory',
                'priority' => 'medium',
                'title' => 'High Memory Usage',
                'description' => 'Your command uses more memory than the recommended threshold.',
                'suggestions' => [
                    'Process data in smaller batches',
                    'Use database cursors for large datasets',
                    'Optimize data structures and algorithms',
                    'Consider streaming data instead of loading everything',
                    'Increase memory limit if processing large datasets is necessary',
                ],
            ];
        }

        // Database recommendations
        if (($data['database']['total_queries'] ?? 0) > 1000) {
            $recommendations[] = [
                'category' => 'database_performance',
                'priority' => 'high',
                'title' => 'High Database Query Count',
                'description' => 'Your command executes a large number of database queries.',
                'suggestions' => [
                    'Use eager loading to reduce N+1 query problems',
                    'Implement query result caching',
                    'Batch database operations where possible',
                    'Review and optimize slow queries',
                    'Consider using raw queries for complex operations',
                ],
                'code_examples' => [
                    'eager_loading' => 'User::with([\'posts\', \'comments\'])->get();',
                    'chunking' => 'User::chunk(100, function ($users) { /* process */ });',
                ],
            ];
        }

        // Stability recommendations
        $stabilityScore = $this->calculateStabilityScore($data);
        if ($stabilityScore < 70) {
            $recommendations[] = [
                'category' => 'stability',
                'priority' => 'medium',
                'title' => 'Memory Usage Instability',
                'description' => 'Your command shows inconsistent memory usage patterns.',
                'suggestions' => [
                    'Review algorithms for consistent memory allocation',
                    'Implement proper resource cleanup',
                    'Consider using memory pools for frequent allocations',
                    'Profile specific code sections to identify instability sources',
                ],
            ];
        }

        return $recommendations;
    }

    /**
     * Calculate median of an array.
     *
     * @param  array<int, int|float>  $values
     */
    protected function calculateMedian(array $values): float
    {
        sort($values);
        $count = count($values);

        if ($count === 0) {
            return 0;
        }

        if ($count % 2 === 0) {
            return ($values[$count / 2 - 1] + $values[$count / 2]) / 2;
        } else {
            return $values[intval($count / 2)];
        }
    }

    /**
     * Calculate standard deviation.
     *
     * @param  array<int, int|float>  $values
     */
    protected function calculateStandardDeviation(array $values): float
    {
        $count = count($values);
        if ($count === 0) {
            return 0;
        }

        $mean = array_sum($values) / $count;
        $variance = array_sum(array_map(fn ($x) => pow($x - $mean, 2), $values)) / $count;

        return sqrt($variance);
    }

    /**
     * Calculate percentile.
     *
     * @param  array<int, int|float>  $values
     */
    protected function calculatePercentile(array $values, int $percentile): float
    {
        sort($values);
        $count = count($values);

        if ($count === 0) {
            return 0;
        }

        $index = ($percentile / 100) * ($count - 1);
        $lower = (int) floor($index);
        $upper = (int) ceil($index);

        if ($lower === $upper) {
            return $values[$lower];
        }

        $weight = $index - $lower;

        return $values[$lower] * (1 - $weight) + $values[$upper] * $weight;
    }
}
