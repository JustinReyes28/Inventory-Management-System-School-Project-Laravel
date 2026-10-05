<?php
// Item Inventory View
?>

<div class="card-custom p-6">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h4 class="text-xl font-bold text-slate-800 mb-1">Item Inventory</h4>
            <p class="text-slate-500 text-xs sm:text-sm mb-0">Manage inventory items, pricing, categories, and stock thresholds</p>
        </div>
        <button type="button" id="openAddItemBtn" class="btn-teal py-2 px-4 font-semibold text-sm flex items-center gap-2 shrink-0 cursor-pointer self-start sm:self-auto">
            <i class="bi bi-plus-lg"></i>
            <span>Add Item</span>
        </button>
    </div>

    <!-- Filters Section -->
    <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3 mb-6 bg-slate-50 p-3 rounded-lg border border-slate-200/80">
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 flex-grow">
            <!-- Search -->
            <div class="relative flex-grow max-w-md">
                <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input type="text" id="itemSearchInput" class="w-full pl-9 pr-3 py-2 text-sm border rounded-lg bg-white border-slate-200 text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-teal/30 focus:border-teal" placeholder="Search by SKU or item name...">
            </div>

            <!-- Category Dropdown Filter -->
            <div class="w-full sm:w-48 shrink-0">
                <select id="itemCategoryFilter" class="w-full px-3 py-2 text-sm border rounded-lg bg-white border-slate-200 text-slate-700 focus:outline-none focus:ring-2 focus:ring-teal/30 focus:border-teal">
                    <option value="">All Categories</option>
                </select>
            </div>
        </div>

        <!-- Low Stock Filter Checkbox -->
        <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-600 cursor-pointer select-none px-2 py-1 bg-white rounded border border-slate-200 shadow-sm shrink-0">
            <input type="checkbox" id="itemLowStockFilter" class="rounded border-slate-300 text-teal focus:ring-teal cursor-pointer">
            <span>Show Low Stock Only</span>
        </label>
    </div>

    <!-- Items Table -->
    <div class="overflow-x-auto rounded-lg border border-slate-200">
        <table class="w-full text-left text-sm text-slate-600">
            <thead class="bg-slate-100/70 text-slate-700 font-semibold border-b border-slate-200">
                <tr>
                    <th class="px-4 py-3">SKU</th>
                    <th class="px-4 py-3">Item Name</th>
                    <th class="px-4 py-3">Category</th>
                    <th class="px-4 py-3">Price</th>
                    <th class="px-4 py-3">Quantity</th>
                    <th class="px-4 py-3">Threshold</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody id="itemsTableBody" class="divide-y divide-slate-100">
                <tr>
                    <td colspan="8" class="px-4 py-8 text-center text-slate-400">
                        <i class="bi bi-arrow-repeat spin text-xl block mb-2"></i>
                        Loading inventory items...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Add / Edit Item Modal -->
<div id="itemModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg overflow-hidden transform transition-all my-8">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
            <h5 id="itemModalTitle" class="font-bold text-slate-800 text-base mb-0">Add Item</h5>
            <button type="button" class="closeItemModalBtn text-slate-400 hover:text-slate-600 text-lg cursor-pointer">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <form id="itemForm" class="p-6">
            <input type="hidden" id="itemFormId" name="id" value="">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="itemSkuInput" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                        SKU <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="itemSkuInput" name="sku" required maxlength="50" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal/30 focus:border-teal font-mono" placeholder="e.g. ELEC-1001">
                </div>

                <div>
                    <label for="itemNameInput" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                        Item Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="itemNameInput" name="name" required maxlength="150" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal/30 focus:border-teal" placeholder="e.g. Wireless Ergonomic Mouse">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="itemCategorySelect" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                        Category <span class="text-red-500">*</span>
                    </label>
                    <select id="itemCategorySelect" name="category_id" required class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal/30 focus:border-teal">
                        <option value="">Select Category</option>
                    </select>
                </div>

                <div>
                    <label for="itemPriceInput" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                        Price ($) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" step="0.01" min="0" id="itemPriceInput" name="price" required class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal/30 focus:border-teal" placeholder="0.00">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="itemQuantityInput" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                        Quantity <span class="text-red-500">*</span>
                    </label>
                    <input type="number" min="0" id="itemQuantityInput" name="quantity" required class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal/30 focus:border-teal" placeholder="0">
                </div>

                <div>
                    <label for="itemThresholdInput" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                        Low Stock Threshold <span class="text-red-500">*</span>
                    </label>
                    <input type="number" min="0" id="itemThresholdInput" name="low_stock_threshold" required value="10" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal/30 focus:border-teal" placeholder="10">
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100">
                <button type="button" class="closeItemModalBtn px-4 py-2 text-sm font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg cursor-pointer">
                    Cancel
                </button>
                <button type="submit" id="saveItemBtn" class="btn-teal px-4 py-2 text-sm font-medium rounded-lg cursor-pointer flex items-center gap-2">
                    <i class="bi bi-check-lg"></i>
                    <span>Save Item</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteItemModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm overflow-hidden p-6 text-center">
        <div class="w-12 h-12 rounded-full bg-red-100 text-red-600 flex items-center justify-center mx-auto mb-4 text-2xl">
            <i class="bi bi-exclamation-triangle"></i>
        </div>
        <h5 class="font-bold text-slate-800 text-base mb-1">Delete Item?</h5>
        <p class="text-slate-500 text-xs mb-6" id="deleteItemMsg">Are you sure you want to delete this item? This action will archive the product.</p>

        <div class="flex items-center justify-center gap-3">
            <button type="button" class="closeDeleteItemModalBtn px-4 py-2 text-sm font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg cursor-pointer">
                Cancel
            </button>
            <button type="button" id="confirmDeleteItemBtn" class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg cursor-pointer">
                Delete
            </button>
        </div>
    </div>
</div>
