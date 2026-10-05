<?php

namespace App\Services;

use App\Enums\ActivityAction;
use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CategoryService
{
    public function __construct(
        private readonly ActivityLogService $activityLog,
    ) {}

    public function create(string $name, ?User $actor = null): Category
    {
        $name = $this->normalizeName($name);

        return DB::transaction(function () use ($name, $actor): Category {
            $this->ensureNameIsAvailable($name);

            $category = Category::query()->create(['category_name' => $name]);

            $this->activityLog->log(
                $actor,
                null,
                ActivityAction::CREATE,
                description: "Created category: {$name}",
            );

            return $category;
        }, 3);
    }

    public function update(Category $category, string $name, ?User $actor = null): Category
    {
        $name = $this->normalizeName($name);

        return DB::transaction(function () use ($category, $name, $actor): Category {
            $locked = Category::query()
                ->whereKey($category->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $oldName = $locked->category_name;

            $this->ensureNameIsAvailable($name, $locked->getKey());
            $locked->category_name = $name;
            $locked->save();

            $this->activityLog->log(
                $actor,
                null,
                ActivityAction::UPDATE,
                description: "Updated category #{$locked->getKey()} from '{$oldName}' to '{$name}'",
            );

            return $locked;
        }, 3);
    }

    public function delete(Category $category, ?User $actor = null): void
    {
        DB::transaction(function () use ($category, $actor): void {
            $locked = Category::query()
                ->whereKey($category->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $itemCount = Item::withDeleted()
                ->where('category_id', $locked->getKey())
                ->count();

            if ($itemCount > 0) {
                throw new DomainException(
                    "Cannot delete category. {$itemCount} product record(s), including archived products, are linked to it."
                );
            }

            $name = $locked->category_name;
            $locked->delete();

            $this->activityLog->log(
                $actor,
                null,
                ActivityAction::DELETE,
                description: "Deleted category: {$name}",
            );
        }, 3);
    }

    private function normalizeName(string $name): string
    {
        $name = trim($name);

        if ($name === '' || mb_strlen($name) > 100) {
            throw new InvalidArgumentException('Category name must contain 1 to 100 characters.');
        }

        return $name;
    }

    private function ensureNameIsAvailable(string $name, ?int $exceptId = null): void
    {
        $query = Category::query()
            ->whereRaw('LOWER(category_name) = ?', [mb_strtolower($name)]);

        if ($exceptId !== null) {
            $query->whereKeyNot($exceptId);
        }

        if ($query->exists()) {
            throw new DomainException("Category [{$name}] already exists.");
        }
    }
}
