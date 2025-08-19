# Laravel Memory Profiler

A Laravel package that provides memory profiling capabilities for Artisan commands without requiring Xdebug. This package helps developers identify memory leaks and optimize memory usage in their Laravel applications.

## Features

- 🔍 **Memory Leak Detection**: Automatically detect memory leaks in long-running commands
- 📊 **Visual Reports**: Generate HTML reports with interactive charts
- 📈 **Real-time Monitoring**: Track memory usage over time during command execution
- 🗄️ **Database Query Tracking**: Monitor database queries that might cause memory issues
- ⚡ **No Xdebug Required**: Works without any external profiling extensions
- 🎯 **Easy Integration**: Simple flag-based profiling for any Artisan command
- 📋 **Multiple Formats**: Generate reports in JSON, HTML, or both formats

## Installation

Install the package via Composer:

```bash
composer require yousefkadah/laravel-memory-profiler --dev
```

The package will automatically register its service provider.

Publish the configuration file (optional):

```bash
php artisan vendor:publish --tag=memory-profiler-config
```

## Usage

### Basic Usage

Profile any Artisan command by prefixing it with `profile:memory`:

```bash
# Profile a simple command
php artisan profile:memory your:command

# Profile a command with arguments
php artisan profile:memory your:command --arguments="arg1=value1" --arguments="arg2=value2"

# Profile a command with options
php artisan profile:memory your:command --options="--force" --options="--verbose"
```

### Advanced Usage

```bash
# Custom output location
php artisan profile:memory your:command --output=/path/to/custom/report

# Specify report format
php artisan profile:memory your:command --format=html

# Custom sampling interval (in milliseconds)
php artisan profile:memory your:command --interval=50
```

### Real-world Examples

```bash
# Profile a migration command
php artisan profile:memory migrate --options="--force"

# Profile a queue worker (for a short duration)
php artisan profile:memory queue:work --options="--max-jobs=10"

# Profile a custom command with arguments
php artisan profile:memory import:users --arguments="file=/path/to/users.csv" --options="--batch-size=1000"

# Profile a seeder
php artisan profile:memory db:seed --arguments="class=UserSeeder"
```

## Understanding the Reports

### HTML Report

The HTML report includes:

- **Summary Section**: Key metrics like execution time, peak memory, and memory difference
- **Memory Chart**: Interactive chart showing memory usage over time
- **Potential Issues**: Automated analysis highlighting possible problems
- **Sample Data**: Detailed breakdown of memory usage at different time points

### JSON Report

The JSON report contains:
- Command information and arguments
- Execution timing data
- Memory usage samples
- Database query logs (if enabled)
- Garbage collection statistics
- Automated analysis results

### Key Metrics

- **Peak Memory**: Maximum memory usage during execution
- **Memory Difference**: Difference between final and initial memory usage
- **Memory Trend**: Whether memory usage is increasing, decreasing, or stable
- **Potential Issues**: Automated detection of memory leaks and high usage

## Configuration

The package can be configured via the `config/memory-profiler.php` file:

```php
return [
    // Output directory for reports
    'output_directory' => storage_path('memory-profiles'),
    
    // Sampling interval in milliseconds
    'sampling_interval' => 100,
    
    // Memory threshold for warnings (in bytes)
    'memory_threshold' => 128 * 1024 * 1024, // 128MB
    
    // Report format: 'json', 'html', or 'both'
    'report_format' => 'both',
    
    // Track database queries
    'track_database_queries' => true,
    
    // Track garbage collection
    'track_garbage_collection' => true,
    
    // Auto-cleanup old reports (days)
    'auto_cleanup_days' => 30,
];
```

## Detecting Memory Leaks

### Common Signs of Memory Leaks

1. **Positive Memory Difference**: Final memory usage is significantly higher than initial
2. **Increasing Memory Trend**: Memory usage consistently grows over time
3. **High Peak Memory**: Memory usage exceeds reasonable thresholds

### Common Causes and Solutions

#### 1. Eloquent Model Accumulation
```php
// ❌ Problematic: Loading all records at once
$users = User::all();
foreach ($users as $user) {
    // Process user
}

// ✅ Better: Use chunking
User::chunk(100, function ($users) {
    foreach ($users as $user) {
        // Process user
    }
});
```

#### 2. Database Query Log Growth
```php
// ❌ Problematic: Query log grows indefinitely
DB::enableQueryLog();
// ... many queries ...

// ✅ Better: Disable or clear query log
DB::disableQueryLog();
// or
DB::flushQueryLog();
```

#### 3. Static Variable Accumulation
```php
// ❌ Problematic: Static arrays that grow
class MyService {
    public static $cache = [];
    
    public function process($item) {
        self::$cache[] = $item; // Grows forever
    }
}

// ✅ Better: Clear static variables periodically
MyService::$cache = [];
```

## Best Practices

1. **Profile Long-running Commands**: Focus on commands that run for extended periods
2. **Use Chunking**: Process large datasets in smaller chunks
3. **Monitor Database Queries**: Watch for N+1 queries and excessive query logging
4. **Clear Static Variables**: Reset static caches in long-running processes
5. **Force Garbage Collection**: Use `gc_collect_cycles()` if needed
6. **Set Memory Limits**: Use `--memory` option for queue workers

## Troubleshooting

### High Memory Usage

If you see high memory usage:

1. Check the database query count in the report
2. Look for increasing memory trends
3. Examine the timing of peak memory usage
4. Review your code for static variable accumulation

### Inaccurate Results

If results seem inaccurate:

1. Reduce the sampling interval for more precision
2. Ensure no other processes are affecting memory
3. Run the profiler multiple times for consistency
4. Check if garbage collection is affecting measurements

### Performance Impact

The profiler has minimal performance impact, but you can:

1. Increase sampling interval to reduce overhead
2. Disable database query tracking if not needed
3. Use JSON format only for faster report generation

## Requirements

- PHP 7.4 or higher
- Laravel 8.0, 9.0, 10.0, or 11.0
- Sufficient disk space for reports

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## Support

If you discover any security vulnerabilities or bugs, please send an e-mail to [your.email@example.com](mailto:your.email@example.com).

## Changelog

### v1.0.0
- Initial release
- Basic memory profiling functionality
- HTML and JSON report generation
- Database query tracking
- Garbage collection monitoring

