<?php

namespace App\Http\Controllers;

use App\Services\Cms;
use App\Services\OperatingAccess;
use App\Services\OperatingCatalog;
use App\Services\OperatingMarketing;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MarketingController extends Controller
{
    public function __construct(private OperatingMarketing $marketing, private OperatingAccess $access, private OperatingCatalog $catalog) {}

    public function performance(Request $request): Response
    {
        abort_unless($this->access->can($request->user(), 'campaigns'), 403);
        $channels = collect($this->catalog->entity('campaigns')['fields'])->firstWhere('key', 'channel')['options'];
        $input = $request->validate(['from' => 'nullable|date_format:Y-m-d', 'to' => 'nullable|date_format:Y-m-d|after_or_equal:from', 'channel' => 'nullable|in:'.implode(',', array_keys($channels)), 'format' => 'nullable|in:csv']);
        $report = $this->marketing->performance($request->user(), $input);
        if (($input['format'] ?? null) === 'csv') {
            return response()->streamDownload(function () use ($report): void {
                $out = fopen('php://output', 'w');
                fwrite($out, "\xEF\xBB\xBF");
                fputcsv($out, ['Campaign', 'Status', 'Channel', 'Currency', 'Spend', 'Impressions', 'Clicks', 'CTR %', 'Leads (CRM)', 'Leads (reported)', 'Qualified', 'Won', 'Revenue', 'CPL', 'Conversion %', 'ROI %'], ',', '"', '');
                foreach ($report['rows'] as $row) {
                    fputcsv($out, [preg_match('/^[=+\-@\t\r]/', $row['title']) ? "'".$row['title'] : $row['title'], $row['status'], $row['channel'] ?? '', $row['currency'], $row['spend'], $row['impressions'], $row['clicks'], $row['ctr'] ?? '', $row['leads'], $row['reported_leads'], $row['qualified'], $row['won'], $row['revenue'], $row['cpl'] ?? '', $row['conversion'] ?? '', $row['roi'] ?? ''], ',', '"', '');
                }
                fclose($out);
            }, 'marketing-performance-'.$report['from'].'-to-'.$report['to'].'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        return response()->view('admin.operating.marketing-performance', $report + [
            'filters' => $input,
            'channelOptions' => $channels,
            'sourceOptions' => collect($this->catalog->entity('opportunities')['fields'])->firstWhere('key', 'lead_source')['options'],
            'collections' => Cms::COLLECTIONS,
            'catalog' => $this->catalog,
            'access' => $this->access,
        ]);
    }
}
