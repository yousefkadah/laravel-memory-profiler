# Installation Guide

## System Requirements

Before installing the Laravel Memory Profiler package, ensure your system meets the following requirements:

- **PHP**: 8.0 or higher
- **Laravel**: 9.0, 10.0, or 11.0
- **Memory**: At least 256MB available for PHP
- **Disk Space**: Sufficient space for report generation (reports can range from 100KB to several MB)
- **Extensions**: No additional PHP extensions required (works without Xdebug)

## Installation Methods

### Method 1: Composer Installation (Recommended)

Install the package via Composer in your Laravel project:

```bash
composer require yourname/laravel-memory-profiler --dev
```

The `--dev` flag ensures the package is only installed in development environments, which is recommended since memory profiling is typically used during development and testing phases.

### Method 2: Manual Installation

If you prefer to install manually or need to customize the package:

1. Download the package source code
2. Place it in your project's `packages` directory
3. Add the local package to your `composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "./packages/laravel-memory-profiler"
        }
    ],
    "require-dev": {
        "yourname/laravel-memory-profiler": "*"
    }
}
```

4. Run `composer install`

## Service Provider Registration

The package uses Laravel's auto-discovery feature, so the service provider will be automatically registered. If you have disabled auto-discovery, manually add the service provider to your `config/app.php`:

```php
'providers' => [
    // Other providers...
    YourName\LaravelMemoryProfiler\MemoryProfilerServiceProvider::class,
],
```

## Configuration

### Publishing Configuration

Publish the configuration file to customize the package behavior:

```bash
php artisan vendor:publish --tag=memory-profiler-config
```

This creates a `config/memory-profiler.php` file with the following default settings:

```php
<?php

return [
    'output_directory' => storage_path('memory-profiles'),
    'sampling_interval' => 100, // milliseconds
    'memory_threshold' => 128 * 1024 * 1024, // 128MB
    'report_format' => 'both', // 'json', 'html', or 'both'
    'track_database_queries' => true,
    'track_garbage_collection' => true,
    'auto_cleanup_days' => 30,
];
```

### Configuration Options Explained

- **output_directory**: Where profiling reports are saved
- **sampling_interval**: How often memory usage is sampled (in milliseconds)
- **memory_threshold**: Memory usage threshold for warnings (in bytes)
- **report_format**: Format of generated reports
- **track_database_queries**: Whether to monitor database queries
- **track_garbage_collection**: Whether to track PHP garbage collection
- **auto_cleanup_days**: Automatically delete reports older than specified days

### Directory Permissions

Ensure the output directory is writable:

```bash
chmod 755 storage/memory-profiles
```

If the directory doesn't exist, the package will create it automatically with proper permissions.

## Verification

Verify the installation by running:

```bash
php artisan list | grep profile
```

You should see the `profile:memory` command listed.

## Environment-Specific Installation

### Development Environment

For development, install with all features enabled:

```bash
composer require yourname/laravel-memory-profiler --dev
```

### Testing Environment

In testing environments, you might want to disable certain features:

```php
// config/memory-profiler.php
return [
    'track_database_queries' => false, // Disable for faster tests
    'report_format' => 'json', // JSON only for automated analysis
    'sampling_interval' => 200, // Less frequent sampling
];
```

### Production Environment

**Important**: This package is designed for development and testing. Do not install in production environments as it can impact performance and generate large amounts of data.

## Troubleshooting Installation

### Common Issues

1. **Permission Denied**: Ensure proper directory permissions
2. **Memory Limit**: Increase PHP memory limit if needed
3. **Composer Conflicts**: Check for package version conflicts

### Getting Help

If you encounter installation issues:

1. Check the system requirements
2. Review Laravel logs for error messages
3. Ensure proper file permissions
4. Verify Composer dependencies

## Next Steps

After successful installation:

1. Review the [Usage Guide](USAGE.md)
2. Explore [Configuration Options](CONFIGURATION.md)
3. Try the [Quick Start Examples](EXAMPLES.md)

