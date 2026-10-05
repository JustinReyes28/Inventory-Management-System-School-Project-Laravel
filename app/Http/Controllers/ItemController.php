<?php

namespace App\Http\Controllers;

use App\Http\Requests\ItemIndexRequest;
use App\Http\Requests\ItemRequest;
use App\Http\Resources\ItemResource;
use App\Models\Category;
use App\Models\Item;
use App\Services\ItemService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ItemController extends Controller
{
    public function __construct(private readonly ItemService $items) {}

    public function index(ItemIndexRequest $request): Response
    {
        $filters = $request->validated();
        $items = Item::query()
            ->with('category:id,category_name')
            ->where('is_deleted', false)
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $search = '%'.mb_strtolower($search).'%';
                $query->where(function ($query) use ($search): void {
                    $query->whereRaw('LOWER(sku) LIKE ?', [$search])
                        ->orWhereRaw('LOWER(name) LIKE ?', [$search]);
                });
            })
            ->when($filters['category_id'] ?? null, fn ($query, $categoryId) => $query->where('category_id', $categoryId))
            ->when($filters['low_stock'] ?? false, fn ($query) => $query->whereColumn('quantity', '<=', 'low_stock_threshold'))
            ->latest('id')
            ->get()
            ->map(fn (Item $item) => (new ItemResource($item))->resolve($request));

        $categories = Category::query()
            ->orderBy('category_name')
            ->get(['id', 'category_name']);

        return Inertia::render('Items/Index', [
            'items' => $items,
            'categories' => $categories,
            'filters' => [
                'search' => $filters['search'] ?? '',
                'category_id' => $filters['category_id'] ?? null,
                'low_stock' => (bool) ($filters['low_stock'] ?? false),
            ],
        ]);
    }

    public function create(): RedirectResponse
    {
        return to_route('items.index');
    }

    public function store(ItemRequest $request): RedirectResponse
    {
        return $this->mutate(
            fn () => $this->items->create($request->validated(), $request->user()),
            'Item created successfully.',
            'items.index',
        );
    }

    public function show(Item $item): JsonResponse
    {
        abort_if((bool) $item->is_deleted, 404);

        $item->load('category:id,category_name');

        return (new ItemResource($item))->response();
    }

    public function edit(Item $item): RedirectResponse
    {
        abort_if((bool) $item->is_deleted, 404);

        return to_route('items.index');
    }

    public function update(ItemRequest $request, Item $item): RedirectResponse
    {
        abort_if((bool) $item->is_deleted, 404);

        return $this->mutate(
            fn () => $this->items->update($item, $request->validated(), $request->user()),
            'Item updated successfully.',
            'items.index',
        );
    }

    public function archive(Item $item): RedirectResponse
    {
        abort_if((bool) $item->is_deleted, 404);

        return $this->mutate(
            fn () => $this->items->delete($item, request()->user()),
            'Item archived successfully.',
            'items.index',
        );
    }

    public function destroy(Item $item): RedirectResponse
    {
        abort_if((bool) $item->is_deleted, 404);

        return $this->mutate(
            fn () => $this->items->delete($item, request()->user()),
            'Item archived successfully.',
            'items.index',
        );
    }
}
