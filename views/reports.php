<?php
// Reports View - Analytics & Reporting Hub
?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h5 class="font-bold mb-1">Analytics & Reports</h5>
            <p class="text-slate-500 text-xs mb-0">Low stock alerts, expiry tracking, and user activity summaries</p>
        </div>
    </div>

    <!-- Summary Metrics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4" id="reportSummaryCards">
        <div class="card-metric bg-red-gradient">
            <div class="d-flex align-items-center gap-3">
                <div>
                    <p class="text-white/70 text-xs font-medium mb-1 uppercase tracking-wider">Low Stock Items</p>
                    <h3 class="text-white font-bold text-2xl mb-0" id="metricLowStockCount">--</h3>
                </div>
                <i class="bi bi-exclamation-triangle-fill metric-icon"></i>
            </div>
        </div>
        <div class="card-metric bg-amber-gradient">
            <div class="d-flex align-items-center gap-3">
                <div>
                    <p class="text-white/70 text-xs font-medium mb-1 uppercase tracking-wider">Expiring (30 Days)</p>
                    <h3 class="text-white font-bold text-2xl mb-0" id="metricExpiringCount">--</h3>
                </div>
                <i class="bi bi-calendar-event metric-icon"></i>
            </div>
        </div>
        <div class="card-metric bg-teal" style="background: var(--gradient-purple);">
            <div class="d-flex align-items-center gap-3">
                <div>
                    <p class="text-white/70 text-xs font-medium mb-1 uppercase tracking-wider">Total Actions</p>
                    <h3 class="text-white font-bold text-2xl mb-0" id="metricTotalActions">--</h3>
                </div>
                <i class="bi bi-activity metric-icon"></i>
            </div>
        </div>
    </div>

    <!-- Tab Navigation -->
    <div class="border-b border-gray-200">
        <div class="flex gap-0 -mb-px">
            <button type="button" class="report-tab-btn px-5 py-3 text-sm font-semibold border-b-2 border-teal text-teal transition-colors cursor-pointer" data-tab="low_stock">
                <i class="bi bi-box-seam me-1.5"></i>Low Stock Report
            </button>
            <button type="button" class="report-tab-btn px-5 py-3 text-sm font-semibold border-b-2 border-transparent text-slate-500 hover:text-slate-700 transition-colors cursor-pointer" data-tab="expiry">
                <i class="bi bi-calendar-range me-1.5"></i>Expiry Report
            </button>
            <button type="button" class="report-tab-btn px-5 py-3 text-sm font-semibold border-b-2 border-transparent text-slate-500 hover:text-slate-700 transition-colors cursor-pointer" data-tab="activity_summary">
                <i class="bi bi-people me-1.5"></i>Activity Summary
            </button>
        </div>
    </div>

    <!-- Tab 1: Low Stock Report -->
    <div id="tabLowStock" class="report-tab-content">
        <div class="card-custom p-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                <div class="flex items-center gap-3">
                    <h6 class="font-bold mb-0">Items Below Threshold</h6>
                    <select id="lowStockCategoryFilter" class="text-xs border border-gray-200 rounded-lg px-2.5 py-1.5 bg-white text-slate-700 focus:outline-none focus:ring-2 focus:ring-teal-500/30">
                        <option value="">All Categories</option>
                    </select>
                </div>
            </div>
            <div class="table-responsive-wrapper">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-slate-500 text-xs uppercase tracking-wider">
                            <th class="text-left px-4 py-3 font-semibold">SKU</th>
                            <th class="text-left px-4 py-3 font-semibold">Item Name</th>
                            <th class="text-left px-4 py-3 font-semibold">Category</th>
                            <th class="text-right px-4 py-3 font-semibold">Price</th>
                            <th class="text-right px-4 py-3 font-semibold">Qty</th>
                            <th class="text-right px-4 py-3 font-semibold">Threshold</th>
                            <th class="text-right px-4 py-3 font-semibold">Stock Value</th>
                            <th class="text-center px-4 py-3 font-semibold">Status</th>
                        </tr>
                    </thead>
                    <tbody id="lowStockTableBody">
                        <tr><td colspan="8" class="px-4 py-6 text-center text-slate-400 text-sm">Loading...</td></tr>
                    </tbody>
                    <tfoot id="lowStockTableFoot" class="border-t border-gray-100 bg-slate-50/50">
                        <tr>
                            <td colspan="6" class="px-4 py-3 text-right text-slate-600 text-xs font-semibold">Total Stock Value:</td>
                            <td class="px-4 py-3 text-right font-bold text-slate-800" id="lowStockTotalValue">--</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Tab 2: Expiry Report -->
    <div id="tabExpiry" class="report-tab-content hidden">
        <div class="card-custom p-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                <div class="flex items-center gap-2">
                    <h6 class="font-bold mb-0">Expiring Batches</h6>
                    <div class="flex gap-1 ms-2" id="expiryDayPills">
                        <button type="button" class="expiry-pill px-3 py-1 text-xs font-semibold rounded-full border cursor-pointer bg-teal text-white border-teal" data-days="30">30 Days</button>
                        <button type="button" class="expiry-pill px-3 py-1 text-xs font-semibold rounded-full border cursor-pointer bg-white text-slate-600 border-slate-200 hover:bg-slate-50" data-days="60">60 Days</button>
                        <button type="button" class="expiry-pill px-3 py-1 text-xs font-semibold rounded-full border cursor-pointer bg-white text-slate-600 border-slate-200 hover:bg-slate-50" data-days="90">90 Days</button>
                    </div>
                </div>
            </div>
            <div class="table-responsive-wrapper">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-slate-500 text-xs uppercase tracking-wider">
                            <th class="text-left px-4 py-3 font-semibold">SKU</th>
                            <th class="text-left px-4 py-3 font-semibold">Item Name</th>
                            <th class="text-left px-4 py-3 font-semibold">Batch No.</th>
                            <th class="text-left px-4 py-3 font-semibold">Category</th>
                            <th class="text-right px-4 py-3 font-semibold">Qty</th>
                            <th class="text-left px-4 py-3 font-semibold">Expiry Date</th>
                            <th class="text-right px-4 py-3 font-semibold">Days Left</th>
                            <th class="text-center px-4 py-3 font-semibold">Urgency</th>
                        </tr>
                    </thead>
                    <tbody id="expiryTableBody">
                        <tr><td colspan="8" class="px-4 py-6 text-center text-slate-400 text-sm">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Tab 3: Activity Summary -->
    <div id="tabActivitySummary" class="report-tab-content hidden">
        <div class="card-custom p-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                <div class="flex items-center gap-3">
                    <h6 class="font-bold mb-0">User Activity Breakdown</h6>
                </div>
            </div>
            <div class="table-responsive-wrapper">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-slate-500 text-xs uppercase tracking-wider">
                            <th class="text-left px-4 py-3 font-semibold">User</th>
                            <th class="text-left px-4 py-3 font-semibold">Role</th>
                            <th class="text-center px-4 py-3 font-semibold">Create</th>
                            <th class="text-center px-4 py-3 font-semibold">Update</th>
                            <th class="text-center px-4 py-3 font-semibold">Delete</th>
                            <th class="text-center px-4 py-3 font-semibold">Total</th>
                        </tr>
                    </thead>
                    <tbody id="activitySummaryTableBody">
                        <tr><td colspan="8" class="px-4 py-6 text-center text-slate-400 text-sm">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
