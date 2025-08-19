# Usage Guide

## Basic Usage

The Laravel Memory Profiler provides a simple command-line interface to profile any Artisan command. The basic syntax is:

```bash
php artisan profile:memory {command} [options]
```

### Simple Command Profiling

Profile a basic Artisan command:

```bash
php artisan profile:memory inspire
```

This will:
1. Execute the `inspire` command
2. Monitor memory usage throughout execution
3. Generate a detailed report
4. Display a summary in the terminal

### Profiling Commands with Arguments

Profile commands that require arguments:

```bash
php artisan profile:memory make:model --arguments="User" --options="--migration"
```

The `--arguments` and `--options` flags allow you to pass parameters to the target command.

## Advanced Usage

### Custom Output Location

Specify a custom location for the report:

```bash
php artisan profile:memory your:command --output=/path/to/custom/report
```

### Report Format Selection

Choose the report format:

```bash
# Generate only JSON report
php artisan profile:memory your:command --format=json

# Generate only HTML report
php artisan profile:memory your:command --format=html

# Generate both formats (default)
php artisan profile:memory your:command --format=both
```

### Custom Sampling Interval

Adjust the memory sampling frequency:

```bash
# Sample every 50 milliseconds (more detailed)
php artisan profile:memory your:command --interval=50

# Sample every 500 milliseconds (less overhead)
php artisan profile:memory your:command --interval=500
```

## Real-World Examples

### Database Migration Profiling

Profile database migrations to identify memory-intensive operations:

```bash
php artisan profile:memory migrate --options="--force"
```

### Queue Worker Profiling

Profile queue workers for a limited number of jobs:

```bash
php artisan profile:memory queue:work --options="--max-jobs=10" --options="--timeout=60"
```

### Custom Command Profiling

Profile your custom Artisan commands:

```bash
php artisan profile:memory import:users --arguments="file=/path/to/users.csv" --options="--batch-size=1000"
```

### Seeder Profiling

Profile database seeders:

```bash
php artisan profile:memory db:seed --arguments="class=UserSeeder"
```

## Understanding the Output

### Terminal Summary

After profiling, you'll see a summary like:

```
Memory Profiling Summary:
  Peak Memory Usage: 45.2 MB
  Final Memory Usage: 12.8 MB
  Memory Difference: +2.1 MB
  Execution Time: 15.43s
  Samples Collected: 154

⚠️  Potential memory leak detected! Memory increased by 2.1 MB
```

### Report Files

The profiler generates detailed reports in your configured output directory:

- **JSON Report**: Machine-readable data for automated analysis
- **HTML Report**: Human-readable visual report with charts and analysis

## Command Options Reference

### Required Arguments

- `command`: The Artisan command to profile

### Optional Flags

- `--arguments=*`: Arguments to pass to the target command
- `--options=*`: Options to pass to the target command
- `--output=`: Custom output file path
- `--format=`: Report format (json, html, both)
- `--interval=`: Sampling interval in milliseconds

### Argument and Option Formatting

When passing arguments and options to the target command:

```bash
# Single argument
--arguments="value"

# Multiple arguments
--arguments="arg1=value1" --arguments="arg2=value2"

# Boolean options
--options="--force" --options="--verbose"

# Options with values
--options="--timeout=60" --options="--memory=256"
```

## Best Practices

### When to Use Profiling

- **Long-running commands**: Commands that run for more than a few seconds
- **Data processing**: Commands that handle large datasets
- **Memory-intensive operations**: Commands that load significant amounts of data
- **Debugging**: When investigating suspected memory leaks

### Profiling Strategy

1. **Start with defaults**: Use default settings for initial profiling
2. **Adjust sampling**: Increase frequency for detailed analysis, decrease for long-running commands
3. **Multiple runs**: Profile the same command multiple times to identify patterns
4. **Compare results**: Profile before and after optimizations

### Performance Considerations

- Profiling adds minimal overhead (typically <5%)
- Lower sampling intervals increase accuracy but add more overhead
- HTML reports take longer to generate than JSON reports
- Large datasets will produce larger report files

## Troubleshooting

### Common Issues

1. **Command not found**: Ensure the target command exists and is spelled correctly
2. **Permission errors**: Check write permissions for the output directory
3. **Memory errors**: Increase PHP memory limit for memory-intensive commands
4. **Large reports**: Consider increasing sampling interval for long-running commands

### Error Messages

- **"Profiler is already running"**: Another profiling session is active
- **"Command execution failed"**: The target command encountered an error
- **"Output directory not writable"**: Check directory permissions

## Integration with CI/CD

### Automated Testing

Include memory profiling in your testing pipeline:

```bash
# In your CI script
php artisan profile:memory test --format=json --output=reports/memory-test.json
```

### Performance Monitoring

Set up automated alerts for memory usage thresholds:

```bash
# Check if memory usage exceeds threshold
if [ $(jq '.memory.peak_usage' reports/memory-test.json) -gt 134217728 ]; then
    echo "Memory usage exceeded 128MB threshold"
    exit 1
fi
```

## Next Steps

- Explore [Report Analysis](ANALYSIS.md) to understand your results
- Learn about [Configuration Options](CONFIGURATION.md)
- Check out [Advanced Examples](EXAMPLES.md)
- Review [Troubleshooting Guide](TROUBLESHOOTING.md)

