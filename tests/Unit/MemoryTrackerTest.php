<?php

namespace YousefKadah\LaravelMemoryProfiler\Tests\Unit;

use PHPUnit\Framework\TestCase;
use YousefKadah\LaravelMemoryProfiler\Trackers\MemoryTracker;

class MemoryTrackerTest extends TestCase
{
    /** @test */
    public function it_can_start_and_stop_tracking()
    {
        $tracker = new MemoryTracker(100);

        $this->assertFalse($tracker->isTracking());

        $tracker->start();
        $this->assertTrue($tracker->isTracking());

        $samples = $tracker->stop();
        $this->assertFalse($tracker->isTracking());
        $this->assertIsArray($samples);
        $this->assertNotEmpty($samples);
    }

    /** @test */
    public function it_collects_memory_samples()
    {
        $tracker = new MemoryTracker(100);
        $tracker->start();

        // Simulate some memory usage
        $data = array_fill(0, 1000, 'test');

        $sample = $tracker->collectSample();

        $this->assertArrayHasKey('timestamp', $sample);
        $this->assertArrayHasKey('memory_usage', $sample);
        $this->assertArrayHasKey('peak_memory', $sample);
        $this->assertArrayHasKey('elapsed_time', $sample);

        unset($data);
        $tracker->stop();
    }

    /** @test */
    public function it_calculates_statistics()
    {
        $tracker = new MemoryTracker(100);
        $tracker->start();

        // Collect a few samples
        $tracker->collectSample();
        usleep(10000); // 10ms
        $tracker->collectSample();
        usleep(10000); // 10ms
        $tracker->collectSample();

        $tracker->stop();
        $stats = $tracker->getStatistics();

        $this->assertArrayHasKey('sample_count', $stats);
        $this->assertArrayHasKey('duration', $stats);
        $this->assertArrayHasKey('memory', $stats);
        $this->assertArrayHasKey('trend', $stats);

        $this->assertGreaterThanOrEqual(3, $stats['sample_count']);
        $this->assertGreaterThan(0, $stats['duration']);
    }

    /** @test */
    public function it_formats_bytes_correctly()
    {
        $tracker = new MemoryTracker(100);

        $this->assertEquals('1.00 KB', $tracker->formatBytes(1024));
        $this->assertEquals('1.00 MB', $tracker->formatBytes(1024 * 1024));
        $this->assertEquals('1.00 GB', $tracker->formatBytes(1024 * 1024 * 1024));
    }

    /** @test */
    public function it_handles_checkpoints()
    {
        $tracker = new MemoryTracker(100);
        $tracker->start();

        $checkpoint = $tracker->checkpoint('test-checkpoint');

        $this->assertArrayHasKey('label', $checkpoint);
        $this->assertEquals('test-checkpoint', $checkpoint['label']);

        $tracker->stop();
    }

    /** @test */
    public function it_throws_exception_when_starting_already_active_tracker()
    {
        $tracker = new MemoryTracker(100);
        $tracker->start();

        $this->expectException(\YousefKadah\LaravelMemoryProfiler\Exceptions\TrackerException::class);
        $tracker->start();

        $tracker->stop();
    }
}
