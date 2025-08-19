<?php

namespace YousefKadah\LaravelMemoryProfiler\Trackers;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

class DatabaseTracker
{
    /**
     * Tracking state.
     */
    protected bool $isTracking = false;

    /** @var array<int, array<string, mixed>> */
    protected array $queries = [];

    /** @var array<string, array<string, mixed>> */
    protected array $queryStats = [];

    protected int $queryCount = 0;

    protected float $totalQueryTime = 0;

    protected bool $wasQueryLogEnabled = false;

    /**
     * Start database tracking.
     */
    public function start(): void
    {
        if ($this->isTracking) {
            return;
        }

        $this->isTracking = true;
        $this->queries = [];
        $this->queryStats = [];
        $this->queryCount = 0;
        $this->totalQueryTime = 0;

        // Check if query log was already enabled
        $this->wasQueryLogEnabled = ! empty(DB::getQueryLog());

        // Enable query logging
        DB::enableQueryLog();

        // Listen for query events
        Event::listen(QueryExecuted::class, [$this, 'recordQuery']);
    }

    /**
     * Stop database tracking.
     *
     * @return array<string, mixed>
     */
    public function stop(): array
    {
        if (! $this->isTracking) {
            return [];
        }

        $this->isTracking = false;

        // Stop listening for query events
        Event::forget(QueryExecuted::class);

        // Get final query log
        $this->queries = DB::getQueryLog();

        // Restore original query log state
        if (! $this->wasQueryLogEnabled) {
            DB::disableQueryLog();
        }

        return $this->getStatistics();
    }

    /**
     * Record a query execution.
     */
    public function recordQuery(QueryExecuted $event): void
    {
        if (! $this->isTracking) {
            return;
        }

        $this->queryCount++;
        $this->totalQueryTime += $event->time;

        // Track query patterns
        $queryType = $this->getQueryType($event->sql);

        if (! isset($this->queryStats[$queryType])) {
            $this->queryStats[$queryType] = [
                'count' => 0,
                'total_time' => 0,
                'average_time' => 0,
                'examples' => [],
            ];
        }

        $this->queryStats[$queryType]['count']++;
        $this->queryStats[$queryType]['total_time'] += $event->time;
        $this->queryStats[$queryType]['average_time'] =
            $this->queryStats[$queryType]['total_time'] / $this->queryStats[$queryType]['count'];

        // Store a few examples of each query type
        if (count($this->queryStats[$queryType]['examples']) < 3) {
            $this->queryStats[$queryType]['examples'][] = [
                'sql' => $event->sql,
                'bindings' => $event->bindings,
                'time' => $event->time,
            ];
        }
    }

    /**
     * Get query type from SQL.
     */
    protected function getQueryType(string $sql): string
    {
        $sql = trim(strtoupper($sql));

        if (strpos($sql, 'SELECT') === 0) {
            return 'SELECT';
        } elseif (strpos($sql, 'INSERT') === 0) {
            return 'INSERT';
        } elseif (strpos($sql, 'UPDATE') === 0) {
            return 'UPDATE';
        } elseif (strpos($sql, 'DELETE') === 0) {
            return 'DELETE';
        } elseif (strpos($sql, 'CREATE') === 0) {
            return 'CREATE';
        } elseif (strpos($sql, 'ALTER') === 0) {
            return 'ALTER';
        } elseif (strpos($sql, 'DROP') === 0) {
            return 'DROP';
        } else {
            return 'OTHER';
        }
    }

    /**
     * Get database tracking statistics.
     *
     * @return array<string, mixed>
     */
    public function getStatistics(): array
    {
        return [
            'total_queries' => $this->queryCount,
            'total_time' => $this->totalQueryTime,
            'average_time' => $this->queryCount > 0 ? $this->totalQueryTime / $this->queryCount : 0,
            'query_types' => $this->queryStats,
            'queries' => $this->queries,
            'potential_issues' => $this->analyzePotentialIssues(),
        ];
    }

    /**
     * Analyze potential database-related issues.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function analyzePotentialIssues(): array
    {
        /** @var array<int, array<string, mixed>> $issues */
        $issues = [];

        // Check for excessive queries
        if ($this->queryCount > 1000) {
            $issues[] = [
                'type' => 'excessive_queries',
                'message' => "High number of queries ({$this->queryCount}). Potential N+1 problem.",
                'severity' => 'high',
            ];
        } elseif ($this->queryCount > 500) {
            $issues[] = [
                'type' => 'high_query_count',
                'message' => "Moderate number of queries ({$this->queryCount}). Consider optimization.",
                'severity' => 'medium',
            ];
        }

        // Check for slow queries
        $slowQueries = array_filter($this->queries, function ($query) {
            return $query['time'] > 1000; // 1 second
        });

        if (! empty($slowQueries)) {
            $issues[] = [
                'type' => 'slow_queries',
                'message' => count($slowQueries).' slow queries detected (>1s).',
                'severity' => 'high',
                'examples' => array_slice($slowQueries, 0, 3),
            ];
        }

        // Check for repetitive queries
        /** @var array<string, int> $queryHashes */
        $queryHashes = [];
        foreach ($this->queries as $query) {
            $hash = md5($query['query']);
            $queryHashes[$hash] = ($queryHashes[$hash] ?? 0) + 1;
        }

        $repetitiveQueries = array_filter($queryHashes, function ($count) {
            return $count > 50;
        });

        if (! empty($repetitiveQueries)) {
            $issues[] = [
                'type' => 'repetitive_queries',
                'message' => 'Repetitive queries detected. Consider caching or optimization.',
                'severity' => 'medium',
                'count' => count($repetitiveQueries),
            ];
        }

        // Check query log size (potential memory issue)
        $queryLogSize = count($this->queries);
        if ($queryLogSize > 10000) {
            $issues[] = [
                'type' => 'large_query_log',
                'message' => "Large query log ({$queryLogSize} queries) may cause memory issues.",
                'severity' => 'high',
            ];
        }

        return $issues;
    }

    /**
     * Get query log size in bytes (approximate).
     */
    public function getQueryLogSize(): int
    {
        $size = 0;
        foreach ($this->queries as $query) {
            $size += strlen($query['query']);
            $size += strlen(serialize($query['bindings']));
        }

        return $size;
    }

    /**
     * Clear the query log to free memory.
     */
    public function clearQueryLog(): void
    {
        DB::flushQueryLog();
        $this->queries = [];
    }

    /**
     * Check if tracking is active.
     */
    public function isTracking(): bool
    {
        return $this->isTracking;
    }

    /**
     * Get the current query count.
     */
    public function getQueryCount(): int
    {
        return $this->queryCount;
    }

    /**
     * Get the total query time.
     */
    public function getTotalQueryTime(): float
    {
        return $this->totalQueryTime;
    }

    /**
     * Get all recorded queries.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getQueries(): array
    {
        return $this->queries;
    }

    /**
     * Get query statistics by type.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getQueryStats(): array
    {
        return $this->queryStats;
    }
}
