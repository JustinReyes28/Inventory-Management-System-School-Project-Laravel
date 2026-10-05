<?php
// Category Management View
?>

<div class="card-custom p-6">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h4 class="text-xl font-bold text-slate-800 mb-1">Category Management</h4>
            <p class="text-slate-500 text-xs sm:text-sm mb-0">Organize and manage inventory item categories</p>
        </div>
        <div class="flex items-center gap-3">
            <div class="relative w-full sm:w-64">
                <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input type="text" id="categorySearchInput" class="w-full pl-9 pr-3 py-2 text-sm border rounded-lg bg-slate-50 border-slate-200 text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-teal/30 focus:border-teal" placeholder="Search categories...">
            </div>
            <button type="button" id="openAddCategoryBtn" class="btn-teal py-2 px-4 font-semibold text-sm flex items-center gap-2 shrink-0 cursor-pointer">
                <i class="bi bi-plus-lg"></i>
                <span>Add Category</span>
            </button>
        </div>
    </div>

    <!-- Category Table -->
    <div class="overflow-x-auto rounded-lg border border-slate-200">
        <table class="w-full text-left text-sm text-slate-600">
            <thead class="bg-slate-100/70 text-slate-700 font-semibold border-b border-slate-200">
                <tr>
                    <th class="px-4 py-3 w-20">ID</th>
                    <th class="px-4 py-3">Category Name</th>
                    <th class="px-4 py-3 w-36">Total Products</th>
                    <th class="px-4 py-3 w-32 text-right">Actions</th>
                </tr>
            </thead>
            <tbody id="categoriesTableBody" class="divide-y divide-slate-100">
                <tr>
                    <td colspan="4" class="px-4 py-8 text-center text-slate-400">
                        <i class="bi bi-arrow-repeat spin text-xl block mb-2"></i>
                        Loading categories...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Add / Edit Category Modal -->
<div id="categoryModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden transform transition-all">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
            <h5 id="categoryModalTitle" class="font-bold text-slate-800 text-base mb-0">Add Category</h5>
            <button type="button" class="closeModalBtn text-slate-400 hover:text-slate-600 text-lg cursor-pointer">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <form id="categoryForm" class="p-6">
            <input type="hidden" id="categoryFormId" name="id" value="">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

            <div class="mb-4">
                <label for="categoryNameInput" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                    Category Name <span class="text-red-500">*</span>
                </label>
                <input type="text" id="categoryNameInput" name="category_name" required maxlength="100" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal/30 focus:border-teal" placeholder="e.g. Electronics, Medical Supplies">
            </div>

            <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100">
                <button type="button" class="closeModalBtn px-4 py-2 text-sm font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg cursor-pointer">
                    Cancel
                </button>
                <button type="submit" id="saveCategoryBtn" class="btn-teal px-4 py-2 text-sm font-medium rounded-lg cursor-pointer flex items-center gap-2">
                    <i class="bi bi-check-lg"></i>
                    <span>Save Category</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteCategoryModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm overflow-hidden p-6 text-center">
        <div class="w-12 h-12 rounded-full bg-red-100 text-red-600 flex items-center justify-center mx-auto mb-4 text-2xl">
            <i class="bi bi-exclamation-triangle"></i>
        </div>
        <h5 class="font-bold text-slate-800 text-base mb-1">Delete Category?</h5>
        <p class="text-slate-500 text-xs mb-6" id="deleteCategoryMsg">Are you sure you want to delete this category? This action cannot be undone.</p>

        <div class="flex items-center justify-center gap-3">
            <button type="button" class="closeDeleteCategoryModalBtn px-4 py-2 text-sm font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg cursor-pointer">
                Cancel
            </button>
            <button type="button" id="confirmDeleteCategoryBtn" class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg cursor-pointer">
                Delete
            </button>
        </div>
    </div>
</div>
