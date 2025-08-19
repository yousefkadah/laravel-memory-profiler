<?php

namespace YousefKadah\LaravelMemoryProfiler\Reporters;

class HtmlReporter
{
    /**
     * Generate an HTML report from profiling data.
     *
     * @param  array<string, mixed>  $data
     */
    public function generate(array $data): string
    {
        return $this->buildHtml($data);
    }

    /**
     * Build the complete HTML report.
     *
     * @param  array<string, mixed>  $data
     */
    protected function buildHtml(array $data): string
    {
        return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Memory Profile Report - '.htmlspecialchars($data['command']['name']).'</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
    <style>
        '.$this->getStyles().'
    </style>
</head>
<body>
    <div class="container">
        '.$this->buildHeader($data).'
        '.$this->buildSummary($data).'
        '.$this->buildIssues($data).'
        '.$this->buildCharts($data).'
        '.$this->buildDatabaseSection($data).'
        '.$this->buildSampleData($data).'
        '.$this->buildRecommendations($data).'
    </div>
    
    <script>
        '.$this->getJavaScript($data).'
    </script>
</body>
</html>';
    }

    /**
     * Get CSS styles for the report.
     */
    protected function getStyles(): string
    {
        return '
        * { box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            margin: 0; 
            padding: 20px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container { 
            max-width: 1200px; 
            margin: 0 auto; 
            background: white; 
            border-radius: 12px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .header { 
            background: linear-gradient(135deg, #007cba 0%, #0056b3 100%);
            color: white; 
            padding: 30px; 
            text-align: center;
        }
        .header h1 { margin: 0 0 10px 0; font-size: 2.5em; font-weight: 300; }
        .header p { margin: 5px 0; opacity: 0.9; }
        .section { 
            padding: 30px; 
            border-bottom: 1px solid #eee; 
        }
        .section:last-child { border-bottom: none; }
        .section h3 { 
            color: #333; 
            margin: 0 0 20px 0; 
            font-size: 1.5em; 
            font-weight: 500;
            display: flex;
            align-items: center;
        }
        .section h3::before {
            content: "";
            width: 4px;
            height: 24px;
            background: #007cba;
            margin-right: 12px;
            border-radius: 2px;
        }
        .metrics { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
            gap: 20px; 
            margin: 20px 0; 
        }
        .metric { 
            background: #f8f9fa; 
            padding: 20px; 
            border-radius: 8px; 
            text-align: center;
            border-left: 4px solid #007cba;
        }
        .metric-label { 
            font-weight: 600; 
            color: #666; 
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .metric-value { 
            font-size: 1.8em; 
            color: #007cba; 
            font-weight: 700;
            margin-top: 8px;
        }
        .metric.warning .metric-value { color: #dc3545; }
        .metric.success .metric-value { color: #28a745; }
        .chart-container { 
            margin: 30px 0; 
            height: 400px; 
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
        }
        .chart-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin: 30px 0;
        }
        .chart-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            height: 300px;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 20px 0;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        th, td { 
            padding: 12px 16px; 
            text-align: left; 
            border-bottom: 1px solid #eee; 
        }
        th { 
            background: #f8f9fa; 
            font-weight: 600; 
            color: #333;
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        tr:hover { background: #f8f9fa; }
        .issue { 
            padding: 16px; 
            margin: 12px 0; 
            border-radius: 8px; 
            border-left: 4px solid;
            display: flex;
            align-items: flex-start;
        }
        .issue-icon {
            margin-right: 12px;
            font-size: 1.2em;
        }
        .issue.high { 
            background: #f8d7da; 
            border-color: #dc3545; 
            color: #721c24;
        }
        .issue.medium { 
            background: #fff3cd; 
            border-color: #ffc107; 
            color: #856404;
        }
        .issue.low { 
            background: #d1ecf1; 
            border-color: #17a2b8; 
            color: #0c5460;
        }
        .recommendation {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 8px;
            padding: 16px;
            margin: 12px 0;
            border-left: 4px solid #28a745;
        }
        .recommendation-title {
            font-weight: 600;
            color: #155724;
            margin-bottom: 8px;
        }
        .recommendation-content {
            color: #155724;
            line-height: 1.5;
        }
        .tabs {
            display: flex;
            border-bottom: 2px solid #eee;
            margin-bottom: 20px;
        }
        .tab {
            padding: 12px 24px;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .tab.active {
            border-bottom-color: #007cba;
            color: #007cba;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .progress-bar {
            background: #eee;
            border-radius: 10px;
            height: 8px;
            overflow: hidden;
            margin: 8px 0;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #28a745, #20c997);
            transition: width 0.3s ease;
        }
        @media (max-width: 768px) {
            .container { margin: 10px; border-radius: 8px; }
            .metrics { grid-template-columns: 1fr; }
            .chart-grid { grid-template-columns: 1fr; }
            .header h1 { font-size: 2em; }
            .section { padding: 20px; }
        }
        ';
    }

    /**
     * Build the header section.
     *
     * @param  array<string, mixed>  $data
     */
    protected function buildHeader(array $data): string
    {
        $commandName = htmlspecialchars($data['command']['name']);
        $generatedAt = $data['generated_at'];
        $duration = number_format($data['execution']['duration'], 2);

        return '<div class="header">
            <h1>Memory Profile Report</h1>
            <p><strong>Command:</strong> '.$commandName.'</p>
            <p><strong>Duration:</strong> '.$duration.'s | <strong>Generated:</strong> '.$generatedAt.'</p>
        </div>';
    }

    /**
     * Build the summary metrics section.
     *
     * @param  array<string, mixed>  $data
     */
    protected function buildSummary(array $data): string
    {
        $peakMemory = $this->formatBytes($data['memory']['peak_usage']);
        $memoryDiff = $data['memory']['difference'];
        $memoryDiffFormatted = ($memoryDiff > 0 ? '+' : '').$this->formatBytes($memoryDiff);
        $memoryDiffClass = $memoryDiff > 0 ? 'warning' : 'success';
        $sampleCount = count($data['samples']);
        $queryCount = $data['database']['total_queries'] ?? 0;
        $avgQueryTime = number_format($data['database']['average_time'] ?? 0, 2);

        return '<div class="section">
            <h3>Performance Summary</h3>
            <div class="metrics">
                <div class="metric">
                    <div class="metric-label">Peak Memory Usage</div>
                    <div class="metric-value">'.$peakMemory.'</div>
                </div>
                <div class="metric '.$memoryDiffClass.'">
                    <div class="metric-label">Memory Difference</div>
                    <div class="metric-value">'.$memoryDiffFormatted.'</div>
                </div>
                <div class="metric">
                    <div class="metric-label">Samples Collected</div>
                    <div class="metric-value">'.$sampleCount.'</div>
                </div>
                <div class="metric">
                    <div class="metric-label">Database Queries</div>
                    <div class="metric-value">'.$queryCount.'</div>
                </div>
                <div class="metric">
                    <div class="metric-label">Avg Query Time</div>
                    <div class="metric-value">'.$avgQueryTime.'ms</div>
                </div>
                <div class="metric">
                    <div class="metric-label">Memory Trend</div>
                    <div class="metric-value">'.ucfirst($data['memory']['trend']).'</div>
                </div>
            </div>
        </div>';
    }

    /**
     * Build the issues section.
     *
     * @param  array<string, mixed>  $data
     */
    protected function buildIssues(array $data): string
    {
        if (empty($data['analysis']['potential_issues'])) {
            return '<div class="section">
                <h3>Analysis Results</h3>
                <div class="issue low">
                    <span class="issue-icon">✅</span>
                    <div>No significant issues detected. Your command appears to be memory-efficient!</div>
                </div>
            </div>';
        }

        $html = '<div class="section">
            <h3>Potential Issues</h3>';

        foreach ($data['analysis']['potential_issues'] as $issue) {
            $severity = is_array($issue) ? ($issue['severity'] ?? 'medium') : 'medium';
            $message = is_array($issue) ? ($issue['message'] ?? $issue) : $issue;
            $icon = $this->getIssueIcon($severity);

            $html .= '<div class="issue '.$severity.'">
                <span class="issue-icon">'.$icon.'</span>
                <div>'.htmlspecialchars($message).'</div>
            </div>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Build the charts section.
     *
     * @param  array<string, mixed>  $data
     */
    protected function buildCharts(array $data): string
    {
        return '<div class="section">
            <h3>Memory Analysis</h3>
            <div class="chart-container">
                <canvas id="memoryChart"></canvas>
            </div>
            <div class="chart-grid">
                <div class="chart-item">
                    <canvas id="memoryTrendChart"></canvas>
                </div>
                <div class="chart-item">
                    <canvas id="memoryDistributionChart"></canvas>
                </div>
            </div>
        </div>';
    }

    /**
     * Build the database section.
     *
     * @param  array<string, mixed>  $data
     */
    protected function buildDatabaseSection(array $data): string
    {
        if (empty($data['database'])) {
            return '';
        }

        $db = $data['database'];
        $html = '<div class="section">
            <h3>Database Analysis</h3>
            <div class="metrics">
                <div class="metric">
                    <div class="metric-label">Total Queries</div>
                    <div class="metric-value">'.($db['total_queries'] ?? 0).'</div>
                </div>
                <div class="metric">
                    <div class="metric-label">Total Time</div>
                    <div class="metric-value">'.number_format($db['total_time'] ?? 0, 2).'ms</div>
                </div>
                <div class="metric">
                    <div class="metric-label">Average Time</div>
                    <div class="metric-value">'.number_format($db['average_time'] ?? 0, 2).'ms</div>
                </div>
            </div>';

        if (! empty($db['query_types'])) {
            $html .= '<div class="chart-item">
                <canvas id="queryTypesChart"></canvas>
            </div>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Build the sample data section.
     *
     * @param  array<string, mixed>  $data
     */
    protected function buildSampleData(array $data): string
    {
        $html = '<div class="section">
            <h3>Sample Data</h3>
            <div class="tabs">
                <div class="tab active" onclick="showTab(\'samples\')">Memory Samples</div>
                <div class="tab" onclick="showTab(\'queries\')">Database Queries</div>
            </div>
            
            <div id="samples-content" class="tab-content active">
                <table>
                    <thead>
                        <tr>
                            <th>Time (s)</th>
                            <th>Memory Usage</th>
                            <th>Peak Memory</th>
                            <th>Difference</th>
                            <th>Trend</th>
                        </tr>
                    </thead>
                    <tbody>';

        foreach (array_slice($data['samples'], 0, 50) as $i => $sample) {
            $trend = $i > 0 ? ($sample['memory_usage'] > $data['samples'][$i - 1]['memory_usage'] ? '↗️' : '↘️') : '➡️';
            $html .= '<tr>
                <td>'.number_format($sample['elapsed_time'], 2).'</td>
                <td>'.$this->formatBytes($sample['memory_usage']).'</td>
                <td>'.$this->formatBytes($sample['peak_memory']).'</td>
                <td>'.($sample['memory_difference'] > 0 ? '+' : '').$this->formatBytes($sample['memory_difference']).'</td>
                <td>'.$trend.'</td>
            </tr>';
        }

        $html .= '</tbody>
                </table>
            </div>
            
            <div id="queries-content" class="tab-content">';

        if (! empty($data['database']['queries'])) {
            $html .= '<table>
                <thead>
                    <tr>
                        <th>Query</th>
                        <th>Time (ms)</th>
                        <th>Bindings</th>
                    </tr>
                </thead>
                <tbody>';

            foreach (array_slice($data['database']['queries'], 0, 20) as $query) {
                $html .= '<tr>
                    <td><code>'.htmlspecialchars(substr($query['query'], 0, 100)).'...</code></td>
                    <td>'.number_format($query['time'], 2).'</td>
                    <td>'.count($query['bindings']).'</td>
                </tr>';
            }

            $html .= '</tbody></table>';
        } else {
            $html .= '<p>No database queries recorded.</p>';
        }

        $html .= '</div>
        </div>';

        return $html;
    }

    /**
     * Build the recommendations section.
     *
     * @param  array<string, mixed>  $data
     */
    protected function buildRecommendations(array $data): string
    {
        $recommendations = $this->generateRecommendations($data);

        if (empty($recommendations)) {
            return '';
        }

        $html = '<div class="section">
            <h3>Recommendations</h3>';

        foreach ($recommendations as $recommendation) {
            $html .= '<div class="recommendation">
                <div class="recommendation-title">'.htmlspecialchars($recommendation['title']).'</div>
                <div class="recommendation-content">'.htmlspecialchars($recommendation['content']).'</div>
            </div>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Generate recommendations based on the analysis.
     *
     * @param  array<string, mixed>  $data
     * @return array<int, array<string, string>>
     */
    protected function generateRecommendations(array $data): array
    {
        /** @var array<int, array<string, string>> $recommendations */
        $recommendations = [];

        // Memory leak recommendations
        if ($data['memory']['leak_detected']) {
            $recommendations[] = [
                'title' => 'Memory Leak Detected',
                'content' => 'Consider using unset() for large variables, implementing chunking for large datasets, or clearing static caches periodically.',
            ];
        }

        // High memory usage recommendations
        if ($data['memory']['threshold_exceeded']) {
            $recommendations[] = [
                'title' => 'High Memory Usage',
                'content' => 'Consider processing data in smaller chunks, optimizing database queries, or increasing the memory limit if necessary.',
            ];
        }

        // Database query recommendations
        if (($data['database']['total_queries'] ?? 0) > 1000) {
            $recommendations[] = [
                'title' => 'High Query Count',
                'content' => 'Consider using eager loading, caching frequently accessed data, or optimizing your database queries to reduce the total number of queries.',
            ];
        }

        // Memory trend recommendations
        if ($data['memory']['trend'] === 'increasing') {
            $recommendations[] = [
                'title' => 'Increasing Memory Trend',
                'content' => 'Monitor for potential memory leaks. Consider implementing periodic garbage collection or reviewing your data processing logic.',
            ];
        }

        return $recommendations;
    }

    /**
     * Get JavaScript for the report.
     *
     * @param  array<string, mixed>  $data
     */
    protected function getJavaScript(array $data): string
    {
        return '
        // Memory usage chart
        const memoryCtx = document.getElementById("memoryChart").getContext("2d");
        const samples = '.json_encode($data['samples']).';
        
        new Chart(memoryCtx, {
            type: "line",
            data: {
                labels: samples.map(s => s.elapsed_time.toFixed(2)),
                datasets: [{
                    label: "Memory Usage (MB)",
                    data: samples.map(s => (s.memory_usage / 1024 / 1024).toFixed(2)),
                    borderColor: "#007cba",
                    backgroundColor: "rgba(0, 124, 186, 0.1)",
                    tension: 0.1,
                    fill: true
                }, {
                    label: "Peak Memory (MB)",
                    data: samples.map(s => (s.peak_memory / 1024 / 1024).toFixed(2)),
                    borderColor: "#dc3545",
                    backgroundColor: "rgba(220, 53, 69, 0.1)",
                    tension: 0.1,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { title: { display: true, text: "Time (seconds)" } },
                    y: { title: { display: true, text: "Memory Usage (MB)" } }
                },
                plugins: {
                    legend: { position: "top" },
                    title: { display: true, text: "Memory Usage Over Time" }
                }
            }
        });

        // Memory trend chart
        const trendCtx = document.getElementById("memoryTrendChart").getContext("2d");
        const memoryDiffs = samples.map(s => s.memory_difference / 1024 / 1024);
        
        new Chart(trendCtx, {
            type: "bar",
            data: {
                labels: samples.map((s, i) => i),
                datasets: [{
                    label: "Memory Difference (MB)",
                    data: memoryDiffs,
                    backgroundColor: memoryDiffs.map(d => d > 0 ? "rgba(220, 53, 69, 0.6)" : "rgba(40, 167, 69, 0.6)"),
                    borderColor: memoryDiffs.map(d => d > 0 ? "#dc3545" : "#28a745"),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: { display: true, text: "Memory Difference Trend" },
                    legend: { display: false }
                },
                scales: {
                    y: { title: { display: true, text: "Memory Difference (MB)" } }
                }
            }
        });

        // Memory distribution chart
        const distCtx = document.getElementById("memoryDistributionChart").getContext("2d");
        const memoryUsages = samples.map(s => s.memory_usage / 1024 / 1024);
        const min = Math.min(...memoryUsages);
        const max = Math.max(...memoryUsages);
        const range = max - min;
        const buckets = 10;
        const bucketSize = range / buckets;
        const distribution = new Array(buckets).fill(0);
        
        memoryUsages.forEach(usage => {
            const bucketIndex = Math.min(Math.floor((usage - min) / bucketSize), buckets - 1);
            distribution[bucketIndex]++;
        });
        
        new Chart(distCtx, {
            type: "doughnut",
            data: {
                labels: distribution.map((_, i) => `${(min + i * bucketSize).toFixed(1)}-${(min + (i + 1) * bucketSize).toFixed(1)} MB`),
                datasets: [{
                    data: distribution,
                    backgroundColor: [
                        "#FF6384", "#36A2EB", "#FFCE56", "#4BC0C0", "#9966FF",
                        "#FF9F40", "#FF6384", "#C9CBCF", "#4BC0C0", "#FF6384"
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: { display: true, text: "Memory Usage Distribution" },
                    legend: { position: "bottom" }
                }
            }
        });

        // Query types chart (if data available)
        '.$this->getQueryTypesChart($data).'

        // Tab functionality
        function showTab(tabName) {
            document.querySelectorAll(".tab").forEach(tab => tab.classList.remove("active"));
            document.querySelectorAll(".tab-content").forEach(content => content.classList.remove("active"));
            
            event.target.classList.add("active");
            document.getElementById(tabName + "-content").classList.add("active");
        }
        ';
    }

    /**
     * Get query types chart JavaScript.
     *
     * @param  array<string, mixed>  $data
     */
    protected function getQueryTypesChart(array $data): string
    {
        if (empty($data['database']['query_types'])) {
            return '';
        }

        $queryTypes = $data['database']['query_types'];

        return '
        const queryTypesCtx = document.getElementById("queryTypesChart");
        if (queryTypesCtx) {
            const queryTypesData = '.json_encode($queryTypes).';
            
            new Chart(queryTypesCtx, {
                type: "pie",
                data: {
                    labels: Object.keys(queryTypesData),
                    datasets: [{
                        data: Object.values(queryTypesData).map(q => q.count),
                        backgroundColor: ["#FF6384", "#36A2EB", "#FFCE56", "#4BC0C0", "#9966FF"]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: { display: true, text: "Query Types Distribution" }
                    }
                }
            });
        }
        ';
    }

    /**
     * Get issue icon based on severity.
     */
    protected function getIssueIcon(string $severity): string
    {
        switch ($severity) {
            case 'high':
                return '🚨';
            case 'medium':
                return '⚠️';
            case 'low':
                return 'ℹ️';
            default:
                return '⚠️';
        }
    }

    /**
     * Format bytes into human-readable format.
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2).' '.$units[$pow];
    }
}
