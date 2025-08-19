<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Memory Profiler Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration options for the Laravel Memory
    | Profiler package. You can customize the behavior of the profiler
    | by modifying these settings.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Output Directory
    |--------------------------------------------------------------------------
    |
    | The directory where profiling reports will be saved. This should be
    | a writable directory. By default, reports are saved in the storage
    | directory under 'memory-profiles'.
    |
    */
    'output_directory' => storage_path('memory-profiles'),

    /*
    |--------------------------------------------------------------------------
    | Sampling Interval
    |--------------------------------------------------------------------------
    |
    | The interval (in milliseconds) at which memory usage will be sampled
    | during command execution. Lower values provide more detailed profiling
    | but may impact performance. Default is 100ms.
    |
    */
    'sampling_interval' => 100,

    /*
    |--------------------------------------------------------------------------
    | Memory Threshold
    |--------------------------------------------------------------------------
    |
    | The memory usage threshold (in bytes) that triggers a warning in the
    | profiling report. Commands that exceed this threshold will be flagged
    | as potentially problematic. Default is 128MB.
    |
    */
    'memory_threshold' => 128 * 1024 * 1024, // 128MB

    /*
    |--------------------------------------------------------------------------
    | Report Format
    |--------------------------------------------------------------------------
    |
    | The format for the profiling reports. Supported formats:
    | - 'json': JSON format for programmatic analysis
    | - 'html': HTML format for visual analysis
    | - 'both': Generate both JSON and HTML reports
    |
    */
    'report_format' => 'both',

    /*
    |--------------------------------------------------------------------------
    | Track Database Queries
    |--------------------------------------------------------------------------
    |
    | Whether to track database queries during profiling. This can help
    | identify memory leaks caused by query logging or inefficient queries.
    |
    */
    'track_database_queries' => true,

    /*
    |--------------------------------------------------------------------------
    | Track Garbage Collection
    |--------------------------------------------------------------------------
    |
    | Whether to track garbage collection cycles during profiling. This can
    | help identify memory management issues.
    |
    */
    'track_garbage_collection' => true,

    /*
    |--------------------------------------------------------------------------
    | Auto-cleanup Reports
    |--------------------------------------------------------------------------
    |
    | Automatically delete old profiling reports after the specified number
    | of days. Set to null to disable auto-cleanup.
    |
    */
    'auto_cleanup_days' => 30,
];
