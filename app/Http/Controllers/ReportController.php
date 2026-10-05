<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportIndexRequest;
use App\Models\Category;
use App\Services\ReportService;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reports) {}

    public function index(ReportIndexRequest $request): Response
    {
        $validated = $request->validated();
        $internalTab = $validated['tab'];
        $uiTab = match ($internalTab) {
            'expiry' => 'expiry',
            'activity_summary' => 'activity',
            default => 'low-stock',
        };

        $overview = $this->reports->overview();
        $lowStock = $this->reports->lowStock($validated['category_id'] ?? null);
        $expiry = $this->reports->expiry((int) $validated['days']);
        $activity = $this->reports->activitySummary(
            $validated['date_from'] ?? null,
            $validated['date_to'] ?? null,
        );

        $activeReport = match ($internalTab) {
            'expiry' => $expiry,
            'activity_summary' => $activity,
            default => $lowStock,
        };

        $filters = [
            'tab' => $uiTab,
            'category_id' => $validated['category_id'] ?? null,
            'days' => (int) $validated['days'],
            'date_from' => $validated['date_from'] ?? null,
            'date_to' => $validated['date_to'] ?? null,
        ];

        return Inertia::render('Reports/Index', [
            'tab' => $uiTab,
            'activeTab' => $uiTab,
            'filters' => $filters,
            'categories' => Category::query()
                ->orderBy('category_name')
                ->get(['id', 'category_name']),
            'overview' => $overview,
            'lowStock' => $lowStock['data'],
            'expiry' => $expiry['data'],
            'activitySummary' => $activity['data'],
            'report' => $activeReport,
            'reportData' => $activeReport['data'],
            'reportMeta' => $activeReport['meta'],
        ]);
    }
}
