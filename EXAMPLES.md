# Examples and Use Cases

This guide provides practical examples of using the Laravel Memory Profiler in real-world scenarios.

## Basic Examples

### Example 1: Profiling a Simple Command

Let's start with profiling Laravel's built-in `inspire` command:

```bash
php artisan profile:memory inspire
```

**Expected Output:**
```
Starting memory profiling for command: inspire
Profiling configuration:
  - Sampling interval: 100ms
  - Output directory: /path/to/storage/memory-profiles
  - Report format: both

Command executed with exit code: 0
Memory profiling completed!
Report saved to: /path/to/storage/memory-profiles/inspire_2024-01-15_14-30-25.html, /path/to/storage/memory-profiles/inspire_2024-01-15_14-30-25.json

Memory Profiling Summary:
  Peak Memory Usage: 12.5 MB
  Final Memory Usage: 12.1 MB
  Memory Difference: +0.1 MB
  Execution Time: 0.05s
  Samples Collected: 1

✅ No memory leak detected.
```

### Example 2: Profiling with Custom Options

Profile a command with specific formatting and output:

```bash
php artisan profile:memory inspire --format=html --interval=50 --output=/tmp/inspire-profile
```

This command:
- Generates only an HTML report
- Samples memory every 50ms for higher precision
- Saves the report to a custom location

## Database-Related Examples

### Example 3: Profiling Database Migrations

Profile a database migration to identify memory usage patterns:

```bash
php artisan profile:memory migrate --options="--force"
```

**Sample Report Analysis:**
- **Peak Memory**: 45.2 MB
- **Database Queries**: 127 queries
- **Potential Issues**: High query count detected
- **Recommendations**: Consider batching operations

### Example 4: Profiling Database Seeders

Profile a large database seeder:

```bash
php artisan profile:memory db:seed --arguments="class=UserSeeder"
```

**Common Issues Found:**
- Memory leaks from loading all records at once
- Excessive database query logging
- Inefficient data processing

**Optimization Suggestions:**
```php
// Before (problematic)
$users = User::all();
foreach ($users as $user) {
    // Process user
}

// After (optimized)
User::chunk(100, function ($users) {
    foreach ($users as $user) {
        // Process user
    }
});
```

## Queue and Job Examples

### Example 5: Profiling Queue Workers

Profile queue workers for a limited duration:

```bash
php artisan profile:memory queue:work --options="--max-jobs=50" --options="--timeout=300"
```

**Key Metrics to Monitor:**
- Memory growth per job processed
- Peak memory usage
- Memory cleanup between jobs

### Example 6: Profiling Specific Job Classes

Create a test command to profile specific job types:

```php
// app/Console/Commands/ProfileJobCommand.php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\ProcessLargeDataset;

class ProfileJobCommand extends Command
{
    protected $signature = 'profile:job {count=10}';
    protected $description = 'Profile job execution';

    public function handle()
    {
        $count = $this->argument('count');
        
        for ($i = 0; $i < $count; $i++) {
            ProcessLargeDataset::dispatch($i);
            $this->info("Dispatched job {$i}");
        }
    }
}
```

Then profile it:

```bash
php artisan profile:memory profile:job --arguments="count=25"
```

## Data Processing Examples

### Example 7: Profiling CSV Import

Profile a CSV import command:

```bash
php artisan profile:memory import:csv --arguments="file=/path/to/large-file.csv" --options="--batch-size=1000"
```

**Memory Optimization Techniques:**

```php
// Problematic approach
$data = file_get_contents($csvFile);
$lines = explode("\n", $data); // Loads entire file into memory

// Optimized approach
$handle = fopen($csvFile, 'r');
while (($line = fgetcsv($handle)) !== false) {
    // Process line by line
    $this->processLine($line);
}
fclose($handle);
```

### Example 8: Profiling API Data Sync

Profile an API synchronization command:

```bash
php artisan profile:memory sync:api-data --options="--source=external-api" --options="--limit=10000"
```

**Common Memory Issues:**
- Loading all API responses into memory
- Not clearing processed data
- Accumulating error logs

## Custom Command Examples

### Example 9: Creating a Memory-Intensive Test Command

Create a command specifically for testing memory behavior:

```php
// app/Console/Commands/MemoryTestCommand.php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MemoryTestCommand extends Command
{
    protected $signature = 'test:memory {type=leak}';
    protected $description = 'Test different memory scenarios';

    public function handle()
    {
        $type = $this->argument('type');
        
        switch ($type) {
            case 'leak':
                $this->simulateMemoryLeak();
                break;
            case 'spike':
                $this->simulateMemorySpike();
                break;
            case 'stable':
                $this->simulateStableUsage();
                break;
        }
    }

    private function simulateMemoryLeak()
    {
        $data = [];
        for ($i = 0; $i < 10000; $i++) {
            $data[] = str_repeat('x', 1000); // Accumulate data
            usleep(1000); // 1ms delay
        }
        // Data is never freed - simulates memory leak
    }

    private function simulateMemorySpike()
    {
        $largeArray = array_fill(0, 1000000, 'data'); // Sudden spike
        unset($largeArray); // Immediate cleanup
        sleep(2);
    }

    private function simulateStableUsage()
    {
        for ($i = 0; $i < 100; $i++) {
            $data = array_fill(0, 10000, 'temp');
            unset($data); // Clean up each iteration
            usleep(10000); // 10ms delay
        }
    }
}
```

Profile different scenarios:

```bash
# Test memory leak scenario
php artisan profile:memory test:memory --arguments="type=leak"

# Test memory spike scenario
php artisan profile:memory test:memory --arguments="type=spike"

# Test stable usage scenario
php artisan profile:memory test:memory --arguments="type=stable"
```

## Advanced Profiling Scenarios

### Example 10: Profiling with Multiple Sampling Rates

Compare the same command with different sampling rates:

```bash
# High precision (every 25ms)
php artisan profile:memory your:command --interval=25 --output=reports/high-precision

# Standard precision (every 100ms)
php artisan profile:memory your:command --interval=100 --output=reports/standard

# Low precision (every 500ms)
php artisan profile:memory your:command --interval=500 --output=reports/low-precision
```

### Example 11: Profiling Long-Running Commands

For commands that run for hours:

```bash
php artisan profile:memory long:running:command --interval=1000 --format=json
```

**Considerations for Long-Running Commands:**
- Use larger sampling intervals to reduce overhead
- Monitor disk space for report files
- Consider splitting into smaller batches

## Automated Testing Examples

### Example 12: CI/CD Integration

Create a test script for continuous integration:

```bash
#!/bin/bash
# scripts/memory-test.sh

echo "Running memory profiling tests..."

# Test critical commands
php artisan profile:memory migrate --format=json --output=reports/migrate.json
php artisan profile:memory db:seed --format=json --output=reports/seed.json

# Check for memory leaks
MIGRATE_LEAK=$(jq '.memory.leak_detected' reports/migrate.json)
SEED_LEAK=$(jq '.memory.leak_detected' reports/seed.json)

if [ "$MIGRATE_LEAK" = "true" ] || [ "$SEED_LEAK" = "true" ]; then
    echo "Memory leak detected!"
    exit 1
fi

echo "All memory tests passed!"
```

### Example 13: Performance Regression Testing

Compare memory usage between versions:

```bash
# Before optimization
git checkout main
php artisan profile:memory your:command --format=json --output=before.json

# After optimization
git checkout feature/optimization
php artisan profile:memory your:command --format=json --output=after.json

# Compare results
BEFORE_PEAK=$(jq '.memory.peak_usage' before.json)
AFTER_PEAK=$(jq '.memory.peak_usage' after.json)

echo "Memory usage before: $BEFORE_PEAK bytes"
echo "Memory usage after: $AFTER_PEAK bytes"
```

## Troubleshooting Examples

### Example 14: Debugging Memory Issues

When you suspect a memory leak:

```bash
# Profile with high precision
php artisan profile:memory suspected:command --interval=10 --format=both

# Check the HTML report for:
# - Consistently increasing memory trend
# - High memory difference
# - Database query accumulation
```

### Example 15: Optimizing Based on Reports

After identifying issues, implement fixes:

```php
// Before: Memory leak in loop
class ProblematicCommand extends Command
{
    public function handle()
    {
        $results = [];
        foreach (User::all() as $user) {
            $results[] = $this->processUser($user); // Accumulates in memory
        }
    }
}

// After: Memory-efficient processing
class OptimizedCommand extends Command
{
    public function handle()
    {
        User::chunk(100, function ($users) {
            foreach ($users as $user) {
                $this->processUser($user);
                // Process immediately, don't accumulate
            }
        });
    }
}
```

## Best Practices from Examples

### Memory Management Patterns

1. **Use Chunking**: Process large datasets in smaller chunks
2. **Explicit Cleanup**: Use `unset()` for large variables
3. **Avoid Accumulation**: Don't store all results in memory
4. **Monitor Queries**: Watch for N+1 query problems

### Profiling Strategies

1. **Baseline First**: Profile before making changes
2. **Multiple Runs**: Run several times to identify patterns
3. **Different Scenarios**: Test with various data sizes
4. **Document Results**: Keep records of profiling results

### Report Analysis

1. **Focus on Trends**: Look for consistent patterns
2. **Check Database Impact**: Monitor query counts and times
3. **Identify Spikes**: Look for sudden memory increases
4. **Validate Fixes**: Re-profile after optimizations

## Next Steps

- Learn about [Report Analysis](ANALYSIS.md)
- Explore [Configuration Options](CONFIGURATION.md)
- Review [Troubleshooting Guide](TROUBLESHOOTING.md)
- Check [Performance Optimization Tips](OPTIMIZATION.md)

