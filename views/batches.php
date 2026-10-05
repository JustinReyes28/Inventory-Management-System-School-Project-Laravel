<?php
// Batch & Expiry Tracking View
?>

<div class="card-custom p-6">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h4 class="text-xl font-bold text-slate-800 mb-1">Batch & Expiry Tracking</h4>
            <p class="text-slate-500 text-xs sm:text-sm mb-0">Track product batches, quantities, and expiry dates</p>
        </div>
        <button type="button" id="openAddBatchBtn" class="btn-teal py-2 px-4 font-semibold text-sm flex items-center gap-2 shrink-0 cursor-pointer self-start sm:self-auto">
            <i class="bi bi-plus-lg"></i>
            <span>New Batch</span>
        </button>
    </div>

    <!-- Filters Section -->
    <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3 mb-6 bg-slate-50 p-3 rounded-lg border border-slate-200/80">
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 flex-grow">
            <!-- Item Dropdown Filter -->
            <div class="w-full sm:w-56 shrink-0">
                <select id="batchItemFilter" class="w-full px-3 py-2 text-sm border rounded-lg bg-white border-slate-200 text-slate-700 focus:outline-none focus:ring-2 focus:ring-teal/30 focus:border-teal">
                    <option value="">All Items</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Batches Table -->
    <div class="overflow-x-auto rounded-lg border border-slate-200">
        <table class="w-full text-left text-sm text-slate-600">
            <thead class="bg-slate-100/70 text-slate-700 font-semibold border-b border-slate-200">
                <tr>
                    <th class="px-4 py-3">#</th>
                    <th class="px-4 py-3">Batch No.</th>
                    <th class="px-4 py-3">Item</th>
                    <th class="px-4 py-3">Quantity</th>
                    <th class="px-4 py-3">Expiry Date</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Created At</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody id="batchesTableBody" class="divide-y divide-slate-100">
                <tr>
                    <td colspan="8" class="px-4 py-8 text-center text-slate-400">
                        <i class="bi bi-arrow-repeat spin text-xl block mb-2"></i>
                        Loading batches...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Add / Edit Batch Modal -->
<div id="batchModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg overflow-hidden transform transition-all my-8">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
            <h5 id="batchModalTitle" class="font-bold text-slate-800 text-base mb-0">Add Batch</h5>
            <button type="button" class="closeBatchModalBtn text-slate-400 hover:text-slate-600 text-lg cursor-pointer">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <form id="batchForm" class="p-6">
            <input type="hidden" id="batchFormId" name="id" value="">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

            <div class="mb-4">
                <label for="batchItemSelect" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                    Item <span class="text-red-500">*</span>
                </label>
                <select id="batchItemSelect" name="item_id" required class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal/30 focus:border-teal">
                    <option value="">Select Item</option>
                </select>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="batchNumberInput" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                        Batch Number <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="batchNumberInput" name="batch_number" required maxlength="50" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal/30 focus:border-teal font-mono" placeholder="e.g. LOT-001">
                </div>

                <div>
                    <label for="batchQuantityInput" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                        Quantity <span class="text-red-500">*</span>
                    </label>
                    <input type="number" min="0" id="batchQuantityInput" name="quantity" required class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal/30 focus:border-teal" placeholder="0">
                </div>
            </div>

            <div class="mb-4">
                <label for="batchExpiryInput" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                    Expiry Date <span class="text-red-500">*</span>
                </label>
                <input type="date" id="batchExpiryInput" name="expiry_date" required class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal/30 focus:border-teal">
            </div>

            <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100">
                <button type="button" class="closeBatchModalBtn px-4 py-2 text-sm font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg cursor-pointer">
                    Cancel
                </button>
                <button type="submit" id="saveBatchBtn" class="btn-teal px-4 py-2 text-sm font-medium rounded-lg cursor-pointer flex items-center gap-2">
                    <i class="bi bi-check-lg"></i>
                    <span>Save Batch</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteBatchModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm overflow-hidden p-6 text-center">
        <div class="w-12 h-12 rounded-full bg-red-100 text-red-600 flex items-center justify-center mx-auto mb-4 text-2xl">
            <i class="bi bi-exclamation-triangle"></i>
        </div>
        <h5 class="font-bold text-slate-800 text-base mb-1">Delete Batch?</h5>
        <p class="text-slate-500 text-xs mb-6" id="deleteBatchMsg">Are you sure you want to delete this batch? This action cannot be undone.</p>

        <div class="flex items-center justify-center gap-3">
            <button type="button" class="closeDeleteBatchModalBtn px-4 py-2 text-sm font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg cursor-pointer">
                Cancel
            </button>
            <button type="button" id="confirmDeleteBatchBtn" class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg cursor-pointer">
                Delete
            </button>
        </div>
    </div>
</div>
