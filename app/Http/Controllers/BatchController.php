<?php

namespace App\Http\Controllers;

use App\Http\Requests\BatchIndexRequest;
use App\Http\Requests\BatchRequest;
use App\Http\Resources\BatchResource;
use App\Models\Batch;
use App\Models\Item;
use App\Services\BatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class BatchController extends Controller
{
    public function __construct(private readonly BatchService $batches) {}

    public function index(BatchIndexRequest $request): Response
    {
        $filters = $request->validated();
        $batches = Batch::query()
            ->with('item:id,sku,name')
            ->whereHas('item', fn ($query) => $query->where('is_deleted', false))
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $term = '%'.mb_strtolower($search).'%';
                $query->where(function ($query) use ($term): void {
                    $query->whereRaw('LOWER(batch_number) LIKE ?', [$term])
                        ->orWhereHas('item', fn ($itemQuery) => $itemQuery
                            ->whereRaw('LOWER(name) LIKE ?', [$term])
                            ->orWhereRaw('LOWER(sku) LIKE ?', [$term]));
                });
            })
            ->when($filters['item_id'] ?? null, fn ($query, $itemId) => $query->where('item_id', $itemId))
            ->when(($filters['status'] ?? '') === 'expired', fn ($query) => $query->whereDate('expiry_date', '<', today()))
            ->when(($filters['status'] ?? '') === 'near_expiry', fn ($query) => $query->whereBetween('expiry_date', [
                today()->toDateString(),
                today()->addDays(30)->toDateString(),
            ]))
            ->when(($filters['status'] ?? '') === 'safe', fn ($query) => $query->whereDate('expiry_date', '>', today()->addDays(30)))
            ->latest('id')
            ->get()
            ->map(fn (Batch $batch) => (new BatchResource($batch))->resolve($request));

        $items = Item::query()
            ->where('is_deleted', false)
            ->orderBy('name')
            ->get(['id', 'sku', 'name']);

        return Inertia::render('Batches/Index', [
            'batches' => $batches,
            'items' => $items,
            'filters' => [
                'search' => $filters['search'] ?? '',
                'item_id' => $filters['item_id'] ?? null,
                'status' => $filters['status'] ?? '',
            ],
        ]);
    }

    public function create(): RedirectResponse
    {
        return to_route('batches.index');
    }

    public function store(BatchRequest $request): RedirectResponse
    {
        return $this->mutate(
            fn () => $this->batches->create($request->validated(), $request->user()),
            'Batch created successfully.',
            'batches.index',
        );
    }

    public function show(Batch $batch): JsonResponse
    {
        abort_unless($batch->item()->where('is_deleted', false)->exists(), 404);
        $batch->load('item:id,sku,name');

        return (new BatchResource($batch))->response();
    }

    public function edit(Batch $batch): RedirectResponse
    {
        abort_unless($batch->item()->where('is_deleted', false)->exists(), 404);

        return to_route('batches.index');
    }

    public function update(BatchRequest $request, Batch $batch): RedirectResponse
    {
        abort_unless($batch->item()->where('is_deleted', false)->exists(), 404);

        return $this->mutate(
            fn () => $this->batches->update($batch, $request->validated(), $request->user()),
            'Batch updated successfully.',
            'batches.index',
        );
    }

    public function destroy(Batch $batch): RedirectResponse
    {
        return $this->mutate(
            fn () => $this->batches->delete($batch, request()->user()),
            'Batch deleted successfully.',
            'batches.index',
        );
    }
}
