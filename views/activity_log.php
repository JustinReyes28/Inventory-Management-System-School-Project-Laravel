<?php
// Activity Log View
?>

<div class="card-custom p-6">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h4 class="text-xl font-bold text-slate-800 mb-1">System Activity Logs</h4>
            <p class="text-slate-500 text-xs sm:text-sm mb-0">Audit trail of all inventory modifications and system events</p>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="flex flex-col md:flex-row items-stretch md:items-end justify-between gap-3 mb-6 bg-slate-50 p-3 rounded-lg border border-slate-200/80">
        <div class="flex flex-col sm:flex-row items-stretch sm:items-end gap-3 flex-grow flex-wrap">
            <!-- User Filter -->
            <div class="w-full sm:w-44">
                <label class="block text-xs font-semibold text-slate-600 mb-1">User</label>
                <select id="logUserFilter" class="w-full px-3 py-2 text-sm border rounded-lg bg-white border-slate-200 text-slate-700 focus:outline-none focus:ring-2 focus:ring-teal/30 focus:border-teal">
                    <option value="">All Users</option>
                </select>
            </div>

            <!-- Action Type Filter -->
            <div class="w-full sm:w-40">
                <label class="block text-xs font-semibold text-slate-600 mb-1">Action Type</label>
                <select id="logActionFilter" class="w-full px-3 py-2 text-sm border rounded-lg bg-white border-slate-200 text-slate-700 focus:outline-none focus:ring-2 focus:ring-teal/30 focus:border-teal">
                    <option value="">All Actions</option>
                    <option value="create">Create</option>
                    <option value="update">Update</option>
                    <option value="delete">Delete</option>
                    <option value="stock_in">Stock In</option>
                    <option value="stock_out">Stock Out</option>
                </select>
            </div>

            <!-- Date From -->
            <div class="w-full sm:w-40">
                <label class="block text-xs font-semibold text-slate-600 mb-1">Date From</label>
                <input type="date" id="logDateFrom" class="w-full px-3 py-2 text-sm border rounded-lg bg-white border-slate-200 text-slate-700 focus:outline-none focus:ring-2 focus:ring-teal/30 focus:border-teal">
            </div>

            <!-- Date To -->
            <div class="w-full sm:w-40">
                <label class="block text-xs font-semibold text-slate-600 mb-1">Date To</label>
                <input type="date" id="logDateTo" class="w-full px-3 py-2 text-sm border rounded-lg bg-white border-slate-200 text-slate-700 focus:outline-none focus:ring-2 focus:ring-teal/30 focus:border-teal">
            </div>

            <!-- Buttons -->
            <div class="flex items-center gap-2">
                <button type="button" id="applyLogFilters" class="btn-teal px-4 py-2 text-sm font-medium rounded-lg cursor-pointer">
                    <i class="bi bi-funnel"></i> Apply
                </button>
                <button type="button" id="resetLogFilters" class="px-4 py-2 text-sm font-medium text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 rounded-lg cursor-pointer">
                    Reset
                </button>
            </div>
        </div>
    </div>

    <!-- Activity Log Table -->
    <div class="overflow-x-auto rounded-lg border border-slate-200">
        <table class="w-full text-left text-sm text-slate-600">
            <thead class="bg-slate-100/70 text-slate-700 font-semibold border-b border-slate-200">
                <tr>
                    <th class="px-4 py-3">#</th>
                    <th class="px-4 py-3">User</th>
                    <th class="px-4 py-3">Item</th>
                    <th class="px-4 py-3">Action</th>
                    <th class="px-4 py-3">Old Qty</th>
                    <th class="px-4 py-3">New Qty</th>
                    <th class="px-4 py-3">Description</th>
                    <th class="px-4 py-3">Timestamp</th>
                </tr>
            </thead>
            <tbody id="activityLogTableBody" class="divide-y divide-slate-100">
                <tr>
                    <td colspan="8" class="px-4 py-8 text-center text-slate-400">
                        <i class="bi bi-arrow-repeat spin text-xl block mb-2"></i>
                        Loading activity logs...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Pagination Controls -->
    <div id="logPagination" class="hidden flex items-center justify-between mt-4 text-sm">
        <div>
            <span class="text-slate-500" id="logPaginationInfo">Page 1 of 1</span>
        </div>
        <div class="flex items-center gap-1">
            <button type="button" id="logPrevPage" class="px-3 py-1.5 text-sm font-medium text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer" disabled>
                <i class="bi bi-chevron-left"></i> Previous
            </button>
            <div id="logPageNumbers" class="flex items-center gap-1"></div>
            <button type="button" id="logNextPage" class="px-3 py-1.5 text-sm font-medium text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer" disabled>
                Next <i class="bi bi-chevron-right"></i>
            </button>
        </div>
    </div>
</div>
