<?php

namespace YousefKadah\LaravelMemoryProfiler\Tests\Unit;

use PHPUnit\Framework\TestCase;
use YousefKadah\LaravelMemoryProfiler\Reporters\JsonReporter;
use YousefKadah\LaravelMemoryProfiler\Reporters\HtmlReporter;

class ReportersTest extends TestCase
{
    /** @test */
    public function json_reporter_generates_valid_json()
    {
        $reporter = new JsonReporter();
        
        $sampleData = $this->getSampleReportData();
        $json = $reporter->generate($sampleData);
        
        $this->assertJson($json);
        
        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('command', $decoded);
        $this->assertArrayHasKey('memory', $decoded);
        $this->assertArrayHasKey('samples', $decoded);
    }

    /** @test */
    public function json_reporter_enhances_data_with_statistics()
    {
        $reporter = new JsonReporter();
        
        $sampleData = $this->getSampleReportData();
        $json = $reporter->generate($sampleData);
        $decoded = json_decode($json, true);
        
        // Should have enhanced data
        $this->assertArrayHasKey('statistics', $decoded);
        $this->assertArrayHasKey('performance_metrics', $decoded);
        $this->assertArrayHasKey('efficiency_score', $decoded);
        $this->assertArrayHasKey('recommendations', $decoded);
    }

    /** @test */
    public function json_reporter_calculates_statistics_correctly()
    {
        $reporter = new JsonReporter();
        
        // Create reflection to test protected methods
        $reflection = new \ReflectionClass($reporter);
        $method = $reflection->getMethod('calculateStatistics');
        $method->setAccessible(true);
        
        $sampleData = $this->getSampleReportData();
        $stats = $method->invoke($reporter, $sampleData);
        
        $this->assertArrayHasKey('memory_usage', $stats);
        $this->assertArrayHasKey('memory_differences', $stats);
        $this->assertArrayHasKey('sampling', $stats);
        
        $this->assertArrayHasKey('mean', $stats['memory_usage']);
        $this->assertArrayHasKey('median', $stats['memory_usage']);
        $this->assertArrayHasKey('std_deviation', $stats['memory_usage']);
    }

    /** @test */
    public function json_reporter_calculates_percentiles()
    {
        $reporter = new JsonReporter();
        
        $reflection = new \ReflectionClass($reporter);
        $method = $reflection->getMethod('calculatePercentile');
        $method->setAccessible(true);
        
        $values = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
        
        $percentile25 = $method->invoke($reporter, $values, 25);
        $percentile50 = $method->invoke($reporter, $values, 50);
        $percentile75 = $method->invoke($reporter, $values, 75);
        
        $this->assertLessThan($percentile50, $percentile25);
        $this->assertLessThan($percentile75, $percentile50);
    }

    /** @test */
    public function json_reporter_calculates_median_correctly()
    {
        $reporter = new JsonReporter();
        
        $reflection = new \ReflectionClass($reporter);
        $method = $reflection->getMethod('calculateMedian');
        $method->setAccessible(true);
        
        // Odd number of values
        $oddValues = [1, 3, 5, 7, 9];
        $this->assertEquals(5, $method->invoke($reporter, $oddValues));
        
        // Even number of values
        $evenValues = [1, 2, 3, 4];
        $this->assertEquals(2.5, $method->invoke($reporter, $evenValues));
        
        // Empty array
        $this->assertEquals(0, $method->invoke($reporter, []));
    }

    /** @test */
    public function json_reporter_calculates_standard_deviation()
    {
        $reporter = new JsonReporter();
        
        $reflection = new \ReflectionClass($reporter);
        $method = $reflection->getMethod('calculateStandardDeviation');
        $method->setAccessible(true);
        
        $values = [2, 4, 4, 4, 5, 5, 7, 9];
        $stdDev = $method->invoke($reporter, $values);
        
        $this->assertGreaterThan(0, $stdDev);
        $this->assertIsFloat($stdDev);
        
        // Empty array should return 0
        $this->assertEquals(0, $method->invoke($reporter, []));
    }

    /** @test */
    public function json_reporter_generates_recommendations()
    {
        $reporter = new JsonReporter();
        
        $reflection = new \ReflectionClass($reporter);
        $method = $reflection->getMethod('generateDetailedRecommendations');
        $method->setAccessible(true);
        
        $dataWithLeak = [
            'memory' => ['leak_detected' => true, 'threshold_exceeded' => true],
            'database' => ['total_queries' => 1500],
            'samples' => []
        ];
        
        $recommendations = $method->invoke($reporter, $dataWithLeak);
        
        $this->assertIsArray($recommendations);
        $this->assertNotEmpty($recommendations);
        
        // Should have recommendations for each issue
        $categories = array_column($recommendations, 'category');
        $this->assertContains('memory_leak', $categories);
        $this->assertContains('high_memory', $categories);
        $this->assertContains('database_performance', $categories);
    }

    /** @test */
    public function html_reporter_generates_valid_html()
    {
        $reporter = new HtmlReporter();
        
        $sampleData = $this->getSampleReportData();
        $html = $reporter->generate($sampleData);
        
        $this->assertStringContainsString('<!DOCTYPE html>', $html);
        $this->assertStringContainsString('<html', $html);
        $this->assertStringContainsString('</html>', $html);
        $this->assertStringContainsString('Memory Profile Report', $html);
    }

    /** @test */
    public function html_reporter_includes_required_sections()
    {
        $reporter = new HtmlReporter();
        
        $sampleData = $this->getSampleReportData();
        $html = $reporter->generate($sampleData);
        
        // Should include all major sections
        $this->assertStringContainsString('Performance Summary', $html);
        $this->assertStringContainsString('Memory Analysis', $html);
        $this->assertStringContainsString('Sample Data', $html);
        
        // Should include Chart.js
        $this->assertStringContainsString('chart.js', $html);
        $this->assertStringContainsString('memoryChart', $html);
    }

    /** @test */
    public function html_reporter_formats_bytes_correctly()
    {
        $reporter = new HtmlReporter();
        
        $reflection = new \ReflectionClass($reporter);
        $method = $reflection->getMethod('formatBytes');
        $method->setAccessible(true);
        
        $this->assertEquals('1 KB', $method->invoke($reporter, 1024));
        $this->assertEquals('1 MB', $method->invoke($reporter, 1024 * 1024));
        $this->assertEquals('1 GB', $method->invoke($reporter, 1024 * 1024 * 1024));
    }

    /** @test */
    public function html_reporter_handles_empty_issues()
    {
        $reporter = new HtmlReporter();
        
        $dataWithoutIssues = $this->getSampleReportData();
        $dataWithoutIssues['analysis']['potential_issues'] = [];
        
        $html = $reporter->generate($dataWithoutIssues);
        
        $this->assertStringContainsString('No significant issues detected', $html);
        $this->assertStringContainsString('memory-efficient', $html);
    }

    /**
     * Generate sample report data for testing
     */
    private function getSampleReportData(): array
    {
        return [
            'command' => [
                'name' => 'test:command',
                'arguments' => [],
                'options' => []
            ],
            'memory' => [
                'initial_usage' => 10 * 1024 * 1024, // 10MB
                'peak_usage' => 15 * 1024 * 1024,    // 15MB
                'final_usage' => 12 * 1024 * 1024,   // 12MB
                'difference' => 2 * 1024 * 1024,     // 2MB
                'leak_detected' => false,
                'threshold_exceeded' => false,
                'trend' => 'stable'
            ],
            'execution' => [
                'start_time' => time() - 60,
                'end_time' => time(),
                'duration' => 60.0
            ],
            'samples' => [
                [
                    'timestamp' => time() - 60,
                    'memory_usage' => 10 * 1024 * 1024,
                    'peak_memory' => 10 * 1024 * 1024,
                    'memory_difference' => 0,
                    'elapsed_time' => 0.0
                ],
                [
                    'timestamp' => time() - 30,
                    'memory_usage' => 12 * 1024 * 1024,
                    'peak_memory' => 12 * 1024 * 1024,
                    'memory_difference' => 2 * 1024 * 1024,
                    'elapsed_time' => 30.0
                ],
                [
                    'timestamp' => time(),
                    'memory_usage' => 12 * 1024 * 1024,
                    'peak_memory' => 15 * 1024 * 1024,
                    'memory_difference' => 0,
                    'elapsed_time' => 60.0
                ]
            ],
            'database' => [
                'total_queries' => 25,
                'total_time' => 150.5,
                'average_time' => 6.02,
                'query_types' => [
                    'SELECT' => ['count' => 20, 'total_time' => 120.0],
                    'INSERT' => ['count' => 3, 'total_time' => 20.5],
                    'UPDATE' => ['count' => 2, 'total_time' => 10.0]
                ],
                'queries' => []
            ],
            'analysis' => [
                'potential_issues' => []
            ],
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }
}
