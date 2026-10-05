<?php
// Dashboard View Template
?>
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-4">
    <!-- Total Items Metric Card -->
    <div class="card-metric bg-teal-gradient">
        <div class="flex flex-col">
            <span class="text-xs text-white/50 uppercase font-semibold mb-1">Total Items</span>
            <h2 id="metricTotalProducts" class="font-bold mb-0 text-3xl">--</h2>
        </div>
        <i class="bi bi-box-seam metric-icon"></i>
    </div>

    <!-- Total Value Metric Card -->
    <div class="card-metric bg-green-gradient">
        <div class="flex flex-col">
            <span class="text-xs text-white/50 uppercase font-semibold mb-1">Total Value</span>
            <h2 id="metricTotalValue" class="font-bold mb-0 text-3xl">--</h2>
        </div>
        <i class="bi bi-currency-dollar metric-icon"></i>
    </div>

    <!-- Low Stock Metric Card -->
    <div class="card-metric bg-amber-gradient">
        <div class="flex flex-col">
            <span class="text-xs text-white/50 uppercase font-semibold mb-1">Low Stock Alerts</span>
            <h2 id="metricLowStock" class="font-bold mb-0 text-3xl">--</h2>
        </div>
        <i class="bi bi-exclamation-triangle metric-icon"></i>
    </div>

    <!-- Expiring Soon Metric Card -->
    <div class="card-metric bg-red-gradient">
        <div class="flex flex-col">
            <span class="text-xs text-white/50 uppercase font-semibold mb-1">Expiring Batches</span>
            <h2 id="metricNearExpiry" class="font-bold mb-0 text-3xl">--</h2>
        </div>
        <i class="bi bi-clock-history metric-icon"></i>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
    <div class="lg:col-span-2">
        <div class="card-custom p-4">
            <h6 class="font-bold mb-3">Category Stock Distribution</h6>
            <canvas id="categoryStockChart" height="200"></canvas>
        </div>
    </div>

    <div class="lg:col-span-1">
        <div class="card-custom p-4">
            <div class="flex items-center justify-between mb-3">
                <h6 class="font-bold mb-0">Recent Activity</h6>
                <a href="index.php?page=activity_log" class="no-underline text-xs text-teal-500 font-semibold hover:text-teal-600">View All</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="px-4 py-3 font-semibold text-slate-700">User</th>
                            <th class="px-4 py-3 font-semibold text-slate-700">Action</th>
                            <th class="px-4 py-3 font-semibold text-slate-700">Item</th>
                            <th class="px-4 py-3 font-semibold text-slate-700">Qty Change</th>
                            <th class="px-4 py-3 font-semibold text-slate-700">Time</th>
                        </tr>
                    </thead>
                    <tbody id="recentActivityTableBody" class="divide-y divide-gray-200">
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 gap-4">
    <div class="card-custom p-4">
        <div class="flex items-center justify-between mb-3">
            <h6 class="font-bold mb-0">Batch & Expiry Watchlist</h6>
            <a href="index.php?page=batches" class="no-underline text-xs text-teal-500 font-semibold hover:text-teal-600">View All</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="px-4 py-3 font-semibold text-slate-700">SKU</th>
                        <th class="px-4 py-3 font-semibold text-slate-700">Item</th>
                        <th class="px-4 py-3 font-semibold text-slate-700">Batch #</th>
                        <th class="px-4 py-3 font-semibold text-slate-700">Qty</th>
                        <th class="px-4 py-3 font-semibold text-slate-700">Expiry Date</th>
                        <th class="px-4 py-3 font-semibold text-slate-700">Status</th>
                    </tr>
                </thead>
                <tbody id="expiryWatchlistTableBody" class="divide-y divide-gray-200">
                </tbody>
            </table>
        </div>
    </div>
</div>
