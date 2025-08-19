<?php

namespace YousefKadah\LaravelMemoryProfiler\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Orchestra\Testbench\TestCase;
use YousefKadah\LaravelMemoryProfiler\MemoryProfilerServiceProvider;

class MemoryProfilerTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            MemoryProfilerServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        // Setup test environment
        $app['config']->set('memory-profiler.output_directory', storage_path('memory-profiles-test'));
        $app['config']->set('memory-profiler.sampling_interval', 100);
        $app['config']->set('memory-profiler.report_format', 'json');
    }

    /** @test */
    public function it_can_profile_a_simple_command()
    {
        $exitCode = Artisan::call('profile:memory', [
            'profiled_command' => 'list',
            '--format' => 'json',
        ]);

        $this->assertEquals(0, $exitCode);
    }

    /** @test */
    public function it_generates_report_files()
    {
        Artisan::call('profile:memory', [
            'profiled_command' => 'list',
            '--format' => 'json',
        ]);

        $outputDir = storage_path('memory-profiles-test');
        $files = glob($outputDir.'/*.json');

        $this->assertNotEmpty($files, 'No JSON report files were generated');

        $reportContent = file_get_contents($files[0]);
        $reportData = json_decode($reportContent, true);

        $this->assertArrayHasKey('command', $reportData);
        $this->assertArrayHasKey('memory', $reportData);
        $this->assertArrayHasKey('samples', $reportData);
    }

    /** @test */
    public function it_handles_invalid_commands_gracefully()
    {
        $exitCode = Artisan::call('profile:memory', [
            'profiled_command' => 'nonexistent:command',
        ]);

        $this->assertEquals(1, $exitCode);
    }

    /** @test */
    public function it_respects_configuration_options()
    {
        config(['memory-profiler.sampling_interval' => 200]);

        $exitCode = Artisan::call('profile:memory', [
            'profiled_command' => 'list',
            '--format' => 'json',
        ]);

        $this->assertEquals(0, $exitCode);
    }

    protected function tearDown(): void
    {
        // Clean up test files
        $testDir = storage_path('memory-profiles-test');
        if (is_dir($testDir)) {
            $files = glob($testDir.'/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($testDir);
        }

        parent::tearDown();
    }
}
