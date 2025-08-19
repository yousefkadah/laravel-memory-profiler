# Troubleshooting Guide

This guide helps you diagnose and resolve common issues when using the Laravel Memory Profiler.

## Installation Issues

### Package Not Found

**Problem**: `composer require` fails with "Package not found"

**Solutions**:
1. Check package name spelling
2. Verify Composer repository access
3. Clear Composer cache: `composer clear-cache`
4. Update Composer: `composer self-update`

### Service Provider Not Registered

**Problem**: `profile:memory` command not available

**Solutions**:
1. Check if auto-discovery is enabled in `composer.json`
2. Manually register the service provider in `config/app.php`
3. Clear application cache: `php artisan config:clear`
4. Verify package installation: `composer show yourname/laravel-memory-profiler`

### Permission Errors

**Problem**: "Permission denied" when creating reports

**Solutions**:
```bash
# Fix directory permissions
chmod 755 storage/memory-profiles

# Create directory if it doesn't exist
mkdir -p storage/memory-profiles
chmod 755 storage/memory-profiles

# Check ownership
chown -R www-data:www-data storage/memory-profiles
```

## Runtime Issues

### Memory Limit Exceeded

**Problem**: PHP fatal error "Allowed memory size exhausted"

**Symptoms**:
- Command crashes during profiling
- Incomplete reports generated
- PHP error logs show memory limit errors

**Solutions**:

1. **Increase PHP Memory Limit**:
```bash
# Temporarily for single command
php -d memory_limit=512M artisan profile:memory your:command

# Permanently in php.ini
memory_limit = 512M
```

2. **Optimize Sampling Interval**:
```bash
# Reduce sampling frequency
php artisan profile:memory your:command --interval=500
```

3. **Use JSON-only Reports**:
```bash
# HTML reports use more memory
php artisan profile:memory your:command --format=json
```

### Command Execution Failures

**Problem**: Target command fails during profiling

**Symptoms**:
- "Command execution failed" error
- Non-zero exit codes
- Incomplete profiling data

**Debugging Steps**:

1. **Test Command Independently**:
```bash
# First, verify the command works without profiling
php artisan your:command

# Then profile it
php artisan profile:memory your:command
```

2. **Check Command Arguments**:
```bash
# Ensure proper argument formatting
php artisan profile:memory your:command --arguments="arg1=value1" --options="--flag"
```

3. **Review Error Logs**:
```bash
# Check Laravel logs
tail -f storage/logs/laravel.log

# Check PHP error logs
tail -f /var/log/php_errors.log
```

### Profiler State Issues

**Problem**: "Profiler is already running" error

**Symptoms**:
- Cannot start new profiling session
- Previous session didn't complete properly

**Solutions**:

1. **Wait for Previous Session**:
   - Check if another profiling command is running
   - Wait for completion or terminate the process

2. **Clear Profiler State**:
```php
// In tinker or a custom command
use YourName\LaravelMemoryProfiler\MemoryProfiler;
$profiler = app(MemoryProfiler::class);
// Force reset if needed (use with caution)
```

3. **Restart PHP Process**:
```bash
# For PHP-FPM
sudo service php8.1-fpm restart

# For development server
# Stop and restart php artisan serve
```

## Report Generation Issues

### Empty or Incomplete Reports

**Problem**: Reports are generated but contain no data

**Symptoms**:
- JSON reports with empty arrays
- HTML reports with no charts
- Zero samples collected

**Debugging**:

1. **Check Sampling Interval**:
```bash
# Very short commands might not collect samples
php artisan profile:memory inspire --interval=10
```

2. **Verify Command Duration**:
```bash
# Add artificial delay to test
php artisan profile:memory inspire
# If command runs too quickly, samples might not be collected
```

3. **Test with Known Long Command**:
```bash
# Use a command that definitely takes time
php artisan profile:memory queue:work --options="--max-jobs=1"
```

### Large Report Files

**Problem**: Report files are extremely large

**Symptoms**:
- Multi-megabyte HTML files
- Slow report generation
- Disk space issues

**Solutions**:

1. **Increase Sampling Interval**:
```bash
# Reduce sample frequency
php artisan profile:memory your:command --interval=1000
```

2. **Use JSON Format Only**:
```bash
# JSON is more compact
php artisan profile:memory your:command --format=json
```

3. **Limit Command Duration**:
```bash
# For queue workers, limit job count
php artisan profile:memory queue:work --options="--max-jobs=10"
```

### Report Access Issues

**Problem**: Cannot open or view generated reports

**Symptoms**:
- HTML files don't display properly
- JSON files are corrupted
- File permission errors

**Solutions**:

1. **Check File Permissions**:
```bash
# Make reports readable
chmod 644 storage/memory-profiles/*.html
chmod 644 storage/memory-profiles/*.json
```

2. **Verify File Integrity**:
```bash
# Check if JSON is valid
jq . storage/memory-profiles/report.json

# Check HTML file size
ls -la storage/memory-profiles/*.html
```

3. **Browser Issues**:
   - Try different browsers
   - Check browser console for JavaScript errors
   - Ensure internet connection for CDN resources

## Performance Issues

### High Profiling Overhead

**Problem**: Profiling significantly slows down command execution

**Symptoms**:
- Commands take much longer when profiled
- High CPU usage during profiling
- System becomes unresponsive

**Solutions**:

1. **Adjust Sampling Rate**:
```bash
# Reduce sampling frequency
php artisan profile:memory your:command --interval=500
```

2. **Disable Database Tracking**:
```php
// config/memory-profiler.php
'track_database_queries' => false,
```

3. **Use Minimal Reporting**:
```bash
# JSON only, less frequent sampling
php artisan profile:memory your:command --format=json --interval=1000
```

### Memory Profiler Memory Usage

**Problem**: The profiler itself uses too much memory

**Symptoms**:
- Memory usage higher than expected
- Profiler crashes due to memory limits
- Inaccurate memory measurements

**Solutions**:

1. **Optimize Configuration**:
```php
// config/memory-profiler.php
return [
    'sampling_interval' => 500, // Less frequent
    'track_database_queries' => false, // Disable if not needed
    'track_garbage_collection' => false, // Disable if not needed
];
```

2. **Clear Query Logs Periodically**:
```php
// In long-running commands
DB::flushQueryLog();
```

## Data Accuracy Issues

### Inconsistent Memory Readings

**Problem**: Memory usage readings seem inaccurate or inconsistent

**Symptoms**:
- Memory usage jumps unexpectedly
- Negative memory differences
- Inconsistent results between runs

**Causes and Solutions**:

1. **Garbage Collection Interference**:
```php
// Force garbage collection for consistent readings
gc_collect_cycles();
```

2. **Other Processes**:
   - Ensure no other heavy processes are running
   - Use dedicated testing environment

3. **PHP Memory Management**:
   - Understand difference between `memory_get_usage(true)` and `memory_get_usage(false)`
   - Consider PHP's memory allocation patterns

### Database Query Tracking Issues

**Problem**: Database queries not being tracked properly

**Symptoms**:
- Zero queries reported when queries are executed
- Incomplete query information
- Missing slow queries

**Solutions**:

1. **Verify Configuration**:
```php
// config/memory-profiler.php
'track_database_queries' => true,
```

2. **Check Database Connection**:
```bash
# Ensure database is connected
php artisan tinker
>>> DB::connection()->getPdo()
```

3. **Test Query Logging**:
```php
// In tinker
DB::enableQueryLog();
User::first();
dd(DB::getQueryLog());
```

## Environment-Specific Issues

### Docker Environment Issues

**Problem**: Profiling doesn't work properly in Docker

**Symptoms**:
- Permission errors
- File path issues
- Memory limit problems

**Solutions**:

1. **Volume Mounting**:
```yaml
# docker-compose.yml
volumes:
  - ./storage:/var/www/html/storage
```

2. **Memory Limits**:
```yaml
# docker-compose.yml
services:
  app:
    deploy:
      resources:
        limits:
          memory: 1G
```

3. **User Permissions**:
```dockerfile
# Dockerfile
RUN chown -R www-data:www-data /var/www/html/storage
```

### Windows Environment Issues

**Problem**: Path or permission issues on Windows

**Solutions**:

1. **Use Forward Slashes**:
```bash
php artisan profile:memory your:command --output=C:/reports/profile
```

2. **Run as Administrator**:
   - Open command prompt as administrator
   - Ensure proper file permissions

### Production Environment Warnings

**Problem**: Package accidentally installed in production

**Symptoms**:
- Performance degradation
- Unexpected disk usage
- Security concerns

**Immediate Actions**:

1. **Remove Package**:
```bash
composer remove yourname/laravel-memory-profiler
```

2. **Clear Caches**:
```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

3. **Review Logs**:
   - Check for any profiling activity
   - Monitor system resources

## Getting Help

### Diagnostic Information

When seeking help, provide:

1. **System Information**:
```bash
php --version
php artisan --version
composer show yourname/laravel-memory-profiler
```

2. **Configuration**:
```bash
php artisan config:show memory-profiler
```

3. **Error Messages**:
   - Complete error messages
   - Stack traces
   - Log entries

4. **Reproduction Steps**:
   - Exact commands used
   - Expected vs actual behavior
   - Environment details

### Common Solutions Checklist

Before seeking help, verify:

- [ ] Package is properly installed
- [ ] Configuration is published and correct
- [ ] Directory permissions are set
- [ ] PHP memory limit is adequate
- [ ] Target command works independently
- [ ] No other profiling sessions are running
- [ ] Laravel logs don't show errors

### Support Resources

1. **Documentation**: Review all documentation files
2. **GitHub Issues**: Check existing issues and solutions
3. **Laravel Community**: Laravel forums and Discord
4. **Stack Overflow**: Search for similar problems

### Reporting Bugs

When reporting bugs, include:

1. **Environment Details**:
   - PHP version
   - Laravel version
   - Operating system
   - Package version

2. **Reproduction Case**:
   - Minimal code example
   - Step-by-step instructions
   - Expected vs actual results

3. **Logs and Errors**:
   - Complete error messages
   - Relevant log entries
   - Configuration files

This troubleshooting guide should help resolve most common issues. If you encounter problems not covered here, please refer to the support resources or report the issue for further assistance.

