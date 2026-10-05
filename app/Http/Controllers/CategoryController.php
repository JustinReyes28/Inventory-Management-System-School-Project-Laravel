<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryIndexRequest;
use App\Http\Requests\CategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function __construct(private readonly CategoryService $categories) {}

    public function index(CategoryIndexRequest $request): Response
    {
        $filters = $request->validated();
        $categories = Category::query()
            ->withCount([
                'items' => fn ($query) => $query->where('is_deleted', false),
            ])
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->whereRaw('LOWER(category_name) LIKE ?', ['%'.mb_strtolower($search).'%']);
            })
            ->orderBy('category_name')
            ->get()
            ->map(fn (Category $category) => (new CategoryResource($category))->resolve($request));

        return Inertia::render('Categories/Index', [
            'categories' => $categories,
            'filters' => [
                'search' => $filters['search'] ?? '',
            ],
        ]);
    }

    public function create(): RedirectResponse
    {
        return to_route('categories.index');
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        return $this->mutate(
            fn () => $this->categories->create($request->validated('category_name'), $request->user()),
            'Category created successfully.',
            'categories.index',
        );
    }

    public function show(Category $category): JsonResponse
    {
        $category->loadCount([
            'items' => fn ($query) => $query->where('is_deleted', false),
        ]);

        return (new CategoryResource($category))->response();
    }

    public function edit(Category $category): RedirectResponse
    {
        return to_route('categories.index');
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        return $this->mutate(
            fn () => $this->categories->update($category, $request->validated('category_name'), $request->user()),
            'Category updated successfully.',
            'categories.index',
        );
    }

    public function destroy(Category $category): RedirectResponse
    {
        return $this->mutate(
            fn () => $this->categories->delete($category, request()->user()),
            'Category deleted successfully.',
            'categories.index',
        );
    }
}
