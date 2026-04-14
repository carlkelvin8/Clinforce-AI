<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Csv;

class ReportBuilderService
{
    /**
     * Execute a custom report and return results
     */
    public function executeReport(object $report, array $parameters = []): array
    {
        $config = json_decode($report->data_sources, true);
        $filters = json_decode($report->filters, true) ?? [];
        $grouping = json_decode($report->grouping, true) ?? [];
        $metrics = json_decode($report->metrics, true);

        // Apply runtime parameters
        $filters = $this->applyParameters($filters, $parameters);

        switch ($report->report_type) {
            case 'hiring_analytics':
                return $this->executeHiringAnalyticsReport($config, $filters, $grouping, $metrics);
            case 'market_intelligence':
                return $this->executeMarketIntelligenceReport($config, $filters, $grouping, $metrics);
            case 'custom_query':
                return $this->executeCustomQueryReport($config, $filters, $grouping, $metrics);
            default:
                throw new \Exception('Unknown report type: ' . $report->report_type);
        }
    }

    /**
     * Export report results to specified format
     */
    public function exportReport(array $data, string $format, string $reportName): string
    {
        $fileName = $this->sanitizeFileName($reportName) . '_' . date('Y-m-d_H-i-s');
        
        switch ($format) {
            case 'excel':
                return $this->exportToExcel($data, $fileName);
            case 'csv':
                return $this->exportToCsv($data, $fileName);
            case 'pdf':
                return $this->exportToPdf($data, $fileName);
            default:
                throw new \Exception('Unsupported export format: ' . $format);
        }
    }

    /**
     * Generate scheduled report
     */
    public function generateScheduledReport(int $reportId): array
    {
        $report = DB::table('custom_reports')->where('id', $reportId)->first();
        if (!$report) {
            throw new \Exception('Report not found');
        }

        $scheduleConfig = json_decode($report->schedule_config, true) ?? [];
        $results = $this->executeReport($report);

        // Send to configured recipients
        if (!empty($scheduleConfig['recipients'])) {
            $this->sendReportToRecipients($results, $scheduleConfig['recipients'], $report);
        }

        // Update last generated timestamp
        DB::table('custom_reports')
            ->where('id', $reportId)
            ->update(['last_generated_at' => now()]);

        return $results;
    }

    // ── Report Execution Methods ─────────────────────────────────────────
    private function executeHiringAnalyticsReport(array $config, array $filters, array $grouping, array $metrics): array
    {
        $query = $this->buildHiringAnalyticsQuery($config, $filters, $grouping);
        $results = $query->get();

        return [
            'data' => $results->toArray(),
            'summary' => $this->calculateSummaryMetrics($results, $metrics),
            'metadata' => [
                'total_records' => $results->count(),
                'generated_at' => now()->toISOString(),
            ],
        ];
    }

    private function executeMarketIntelligenceReport(array $config, array $filters, array $grouping, array $metrics): array
    {
        $results = [];
        
        // Combine data from multiple market intelligence sources
        if (in_array('salary_benchmarks', $config)) {
            $results['salary_data'] = $this->getSalaryBenchmarkData($filters, $grouping);
        }
        
        if (in_array('supply_demand', $config)) {
            $results['supply_demand_data'] = $this->getSupplyDemandData($filters, $grouping);
        }
        
        if (in_array('competitor_intelligence', $config)) {
            $results['competitor_data'] = $this->getCompetitorData($filters, $grouping);
        }
        
        if (in_array('trending_skills', $config)) {
            $results['skills_data'] = $this->getTrendingSkillsData($filters, $grouping);
        }

        return [
            'data' => $results,
            'summary' => $this->calculateMarketSummary($results, $metrics),
            'metadata' => [
                'data_sources' => $config,
                'generated_at' => now()->toISOString(),
            ],
        ];
    }

    private function executeCustomQueryReport(array $config, array $filters, array $grouping, array $metrics): array
    {
        // For custom queries, config contains the raw SQL or query builder instructions
        $query = $this->buildCustomQuery($config, $filters, $grouping);
        $results = $query->get();

        return [
            'data' => $results->toArray(),
            'summary' => $this->calculateCustomMetrics($results, $metrics),
            'metadata' => [
                'query_type' => 'custom',
                'generated_at' => now()->toISOString(),
            ],
        ];
    }

    // ── Query Building Methods ───────────────────────────────────────────
    private function buildHiringAnalyticsQuery(array $config, array $filters, array $grouping)
    {
        $query = DB::table('hiring_metrics as hm');

        // Join additional tables based on config
        if (in_array('job_applications', $config)) {
            $query->leftJoin('job_applications as ja', 'ja.id', '=', 'hm.job_id');
        }
        
        if (in_array('jobs', $config)) {
            $query->leftJoin('jobs_table as j', 'j.id', '=', 'hm.job_id');
        }

        // Apply filters
        foreach ($filters as $filter) {
            $this->applyFilter($query, $filter);
        }

        // Apply grouping
        if (!empty($grouping)) {
            foreach ($grouping as $group) {
                $query->groupBy($group['field']);
            }
        }

        // Select appropriate fields
        $selectFields = $this->buildSelectFields($config, $grouping);
        $query->select($selectFields);

        return $query;
    }

    private function buildSelectFields(array $config, array $grouping): array
    {
        $fields = ['hm.*'];

        // Add grouping fields
        foreach ($grouping as $group) {
            if (!in_array($group['field'], $fields)) {
                $fields[] = $group['field'];
            }
        }

        // Add aggregation fields based on config
        if (in_array('time_to_hire', $config)) {
            $fields[] = DB::raw('AVG(CASE WHEN hm.metric_type = "time_to_hire" THEN hm.metric_value END) as avg_time_to_hire');
        }
        
        if (in_array('cost_per_hire', $config)) {
            $fields[] = DB::raw('AVG(CASE WHEN hm.metric_type = "cost_per_hire" THEN hm.metric_value END) as avg_cost_per_hire');
        }

        return $fields;
    }

    private function applyFilter($query, array $filter)
    {
        $field = $filter['field'];
        $operator = $filter['operator'] ?? '=';
        $value = $filter['value'];

        switch ($operator) {
            case '=':
                $query->where($field, $value);
                break;
            case '!=':
                $query->where($field, '!=', $value);
                break;
            case '>':
                $query->where($field, '>', $value);
                break;
            case '<':
                $query->where($field, '<', $value);
                break;
            case 'between':
                $query->whereBetween($field, $value);
                break;
            case 'in':
                $query->whereIn($field, $value);
                break;
            case 'like':
                $query->where($field, 'like', "%{$value}%");
                break;
        }
    }

    // ── Data Retrieval Methods ───────────────────────────────────────────
    private function getSalaryBenchmarkData(array $filters, array $grouping): array
    {
        $query = DB::table('salary_benchmarks');
        
        foreach ($filters as $filter) {
            $this->applyFilter($query, $filter);
        }

        return $query->get()->toArray();
    }

    private function getSupplyDemandData(array $filters, array $grouping): array
    {
        $query = DB::table('supply_demand_metrics');
        
        foreach ($filters as $filter) {
            $this->applyFilter($query, $filter);
        }

        return $query->get()->toArray();
    }

    private function getCompetitorData(array $filters, array $grouping): array
    {
        $query = DB::table('competitor_intelligence');
        
        foreach ($filters as $filter) {
            $this->applyFilter($query, $filter);
        }

        return $query->get()->toArray();
    }

    private function getTrendingSkillsData(array $filters, array $grouping): array
    {
        $query = DB::table('trending_skills');
        
        foreach ($filters as $filter) {
            $this->applyFilter($query, $filter);
        }

        return $query->get()->toArray();
    }

    // ── Export Methods ───────────────────────────────────────────────────
    private function exportToExcel(array $data, string $fileName): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Add headers
        if (!empty($data['data'])) {
            $headers = array_keys((array) $data['data'][0]);
            $sheet->fromArray($headers, null, 'A1');
            
            // Add data
            $rowData = array_map(function($row) {
                return array_values((array) $row);
            }, $data['data']);
            
            $sheet->fromArray($rowData, null, 'A2');
        }

        $writer = new Xlsx($spreadsheet);
        $filePath = "reports/{$fileName}.xlsx";
        
        $writer->save(storage_path("app/{$filePath}"));
        
        return $filePath;
    }

    private function exportToCsv(array $data, string $fileName): string
    {
        $filePath = "reports/{$fileName}.csv";
        $fullPath = storage_path("app/{$filePath}");
        
        // Ensure directory exists
        $directory = dirname($fullPath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $file = fopen($fullPath, 'w');
        
        if (!empty($data['data'])) {
            // Add headers
            $headers = array_keys((array) $data['data'][0]);
            fputcsv($file, $headers);
            
            // Add data
            foreach ($data['data'] as $row) {
                fputcsv($file, array_values((array) $row));
            }
        }
        
        fclose($file);
        
        return $filePath;
    }

    private function exportToPdf(array $data, string $fileName): string
    {
        // For PDF export, you would typically use a library like TCPDF or DOMPDF
        // For now, we'll create a simple HTML file that can be converted to PDF
        
        $html = $this->generateReportHtml($data);
        $filePath = "reports/{$fileName}.html";
        
        Storage::put($filePath, $html);
        
        return $filePath;
    }

    private function generateReportHtml(array $data): string
    {
        $html = '<html><head><title>Report</title><style>
            table { border-collapse: collapse; width: 100%; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; }
        </style></head><body>';
        
        $html .= '<h1>Analytics Report</h1>';
        $html .= '<p>Generated on: ' . date('Y-m-d H:i:s') . '</p>';
        
        if (!empty($data['data'])) {
            $html .= '<table>';
            
            // Headers
            $headers = array_keys((array) $data['data'][0]);
            $html .= '<tr>';
            foreach ($headers as $header) {
                $html .= '<th>' . htmlspecialchars($header) . '</th>';
            }
            $html .= '</tr>';
            
            // Data
            foreach ($data['data'] as $row) {
                $html .= '<tr>';
                foreach (array_values((array) $row) as $value) {
                    $html .= '<td>' . htmlspecialchars($value) . '</td>';
                }
                $html .= '</tr>';
            }
            
            $html .= '</table>';
        }
        
        $html .= '</body></html>';
        
        return $html;
    }

    // ── Helper Methods ───────────────────────────────────────────────────
    private function applyParameters(array $filters, array $parameters): array
    {
        foreach ($filters as &$filter) {
            if (isset($filter['value']) && is_string($filter['value']) && strpos($filter['value'], '{{') !== false) {
                // Replace parameter placeholders
                foreach ($parameters as $key => $value) {
                    $filter['value'] = str_replace('{{' . $key . '}}', $value, $filter['value']);
                }
            }
        }
        
        return $filters;
    }

    private function calculateSummaryMetrics(object $results, array $metrics): array
    {
        $summary = [];
        
        foreach ($metrics as $metric) {
            switch ($metric['type']) {
                case 'count':
                    $summary[$metric['name']] = $results->count();
                    break;
                case 'sum':
                    $summary[$metric['name']] = $results->sum($metric['field']);
                    break;
                case 'average':
                    $summary[$metric['name']] = $results->avg($metric['field']);
                    break;
                case 'min':
                    $summary[$metric['name']] = $results->min($metric['field']);
                    break;
                case 'max':
                    $summary[$metric['name']] = $results->max($metric['field']);
                    break;
            }
        }
        
        return $summary;
    }

    private function calculateMarketSummary(array $results, array $metrics): array
    {
        // Calculate summary metrics across different market intelligence data sources
        $summary = [];
        
        if (isset($results['salary_data'])) {
            $salaryData = collect($results['salary_data']);
            $summary['avg_salary'] = $salaryData->avg('salary_median');
            $summary['salary_range'] = [
                'min' => $salaryData->min('salary_min'),
                'max' => $salaryData->max('salary_max'),
            ];
        }
        
        if (isset($results['supply_demand_data'])) {
            $supplyDemandData = collect($results['supply_demand_data']);
            $summary['avg_supply_demand_ratio'] = $supplyDemandData->avg('supply_demand_ratio');
            $summary['avg_time_to_fill'] = $supplyDemandData->avg('avg_time_to_fill');
        }
        
        return $summary;
    }

    private function calculateCustomMetrics(object $results, array $metrics): array
    {
        return $this->calculateSummaryMetrics($results, $metrics);
    }

    private function buildCustomQuery(array $config, array $filters, array $grouping)
    {
        // Build query based on custom configuration
        $tableName = $config['table'] ?? 'hiring_metrics';
        $query = DB::table($tableName);
        
        // Apply joins if specified
        if (isset($config['joins'])) {
            foreach ($config['joins'] as $join) {
                $query->leftJoin($join['table'], $join['on'][0], $join['on'][1], $join['on'][2]);
            }
        }
        
        // Apply filters
        foreach ($filters as $filter) {
            $this->applyFilter($query, $filter);
        }
        
        // Apply grouping
        if (!empty($grouping)) {
            foreach ($grouping as $group) {
                $query->groupBy($group['field']);
            }
        }
        
        return $query;
    }

    private function sanitizeFileName(string $name): string
    {
        return preg_replace('/[^a-zA-Z0-9_-]/', '_', $name);
    }

    private function sendReportToRecipients(array $results, array $recipients, object $report)
    {
        // Implementation would send email with report results
        // For now, just log the action
        \Log::info('Scheduled report sent', [
            'report_id' => $report->id,
            'recipients' => $recipients,
            'data_points' => count($results['data'] ?? []),
        ]);
    }
}