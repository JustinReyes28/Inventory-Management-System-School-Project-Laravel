/**
 * InvControl Frontend Application JavaScript
 */

document.addEventListener('DOMContentLoaded', () => {
    // ============================================================
    // 1. Sidebar Toggle & Offcanvas (Mobile < 992px)
    // ============================================================
    const sidebarToggleBtn = document.getElementById('sidebarToggle');
    const sidebarBackdrop = document.getElementById('sidebarBackdrop');
    const body = document.body;
    const MOBILE_BREAKPOINT = 992;

    function isMobile() {
        return window.innerWidth < MOBILE_BREAKPOINT;
    }

    function openSidebar() {
        body.classList.add('sidebar-open');
        if (sidebarBackdrop) {
            sidebarBackdrop.classList.remove('hidden');
        }
    }

    function closeSidebar() {
        body.classList.remove('sidebar-open');
        if (sidebarBackdrop) {
            sidebarBackdrop.classList.add('hidden');
        }
    }

    function toggleSidebar() {
        if (isMobile()) {
            if (body.classList.contains('sidebar-open')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        } else {
            body.classList.toggle('sidebar-collapsed');
            const isCollapsed = body.classList.contains('sidebar-collapsed');
            localStorage.setItem('inv_sidebar_collapsed', isCollapsed);
        }
    }

    // Ensure no stale sidebar state on mobile; restore on desktop
    if (isMobile()) {
        closeSidebar();
    } else {
        const savedSidebarState = localStorage.getItem('inv_sidebar_collapsed');
        if (savedSidebarState === 'true') {
            body.classList.add('sidebar-collapsed');
        }
    }

    if (sidebarToggleBtn) {
        sidebarToggleBtn.addEventListener('click', (e) => {
            e.preventDefault();
            toggleSidebar();
        });
    }

    // Backdrop click to close offcanvas
    if (sidebarBackdrop) {
        sidebarBackdrop.addEventListener('click', () => {
            closeSidebar();
        });
    }

    // Close sidebar when a nav link is clicked on mobile
    document.querySelectorAll('.nav-link-custom').forEach(link => {
        link.addEventListener('click', () => {
            if (isMobile()) {
                closeSidebar();
            }
        });
    });

    // Handle window resize crossing the breakpoint
    let wasMobile = isMobile();
    window.addEventListener('resize', () => {
        const nowMobile = isMobile();
        if (wasMobile !== nowMobile) {
            wasMobile = nowMobile;
            if (!nowMobile) {
                closeSidebar();
                const saved = localStorage.getItem('inv_sidebar_collapsed');
                if (saved === 'true') {
                    body.classList.add('sidebar-collapsed');
                }
            } else {
                closeSidebar();
                body.classList.remove('sidebar-collapsed');
            }
        }
    });

    // ============================================================
    // 2. User Dropdown Toggle
    // ============================================================
    const userDropdownBtn = document.getElementById('userDropdownToggle');
    const userDropdownMenu = document.getElementById('userDropdownMenu');

    if (userDropdownBtn && userDropdownMenu) {
        userDropdownBtn.addEventListener('click', (e) => {
            e.preventDefault();
            userDropdownMenu.classList.toggle('hidden');
            userDropdownMenu.classList.toggle('block');
        });

        document.addEventListener('click', (e) => {
            if (!userDropdownBtn.contains(e.target) && !userDropdownMenu.contains(e.target)) {
                userDropdownMenu.classList.add('hidden');
                userDropdownMenu.classList.remove('block');
            }
        });
    }

// ============================================================
// 3. Notification System
// ============================================================
function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

const notifContainer = document.getElementById('notifContainer');
const notifBellBtn = document.getElementById('notifBellBtn');
const notifDropdown = document.getElementById('notifDropdown');
const notifBadge = document.getElementById('notifBadge');
const notifList = document.getElementById('notifList');
const notifMarkAllRead = document.getElementById('notifMarkAllRead');

let notifData = null;

const NOTIF_ICONS = {
    'low_stock': 'bi-exclamation-triangle-fill text-amber-500',
    'near_expiry': 'bi-clock-fill text-orange-500',
    'stock_in': 'bi-box-arrow-in-down text-green-500',
    'stock_out': 'bi-box-arrow-up text-red-500',
    'create': 'bi-plus-circle-fill text-teal-500',
    'delete': 'bi-trash-fill text-red-500',
    'update': 'bi-pencil-fill text-blue-500',
    'system': 'bi-info-circle-fill text-slate-500',
};

function getNotifIcon(type) {
    return NOTIF_ICONS[type] || 'bi-bell-fill text-slate-500';
}

function updateNotifBadge(count) {
    if (count > 0) {
        notifBadge.classList.remove('hidden');
        notifBadge.textContent = count > 9 ? '9+' : count;
    } else {
        notifBadge.classList.add('hidden');
    }
}

function renderNotifications(notifications) {
    if (!notifications || notifications.length === 0) {
        notifList.innerHTML = '<div class="flex flex-col items-center justify-center py-10 text-slate-400"><i class="bi bi-check2-circle text-3xl mb-2"></i><p class="text-sm font-medium">You\'re all caught up</p><p class="text-xs mt-0.5">No new notifications</p></div>';
        return;
    }
    notifList.innerHTML = notifications.map(function (n) {
        var icon = getNotifIcon(n.type);
        var isUnread = parseInt(n.is_read) === 0;
        return '<div class="flex items-start gap-3 px-4 py-3 border-b border-gray-50 cursor-pointer hover:bg-gray-50 transition-colors notif-item ' + (isUnread ? 'bg-teal-50/40' : '') + '" data-id="' + n.id + '" data-link="' + escapeHtml(n.link || '') + '"><div class="flex-shrink-0 mt-0.5"><i class="bi ' + icon + ' text-base"></i></div><div class="flex-1 min-w-0"><p class="text-sm ' + (isUnread ? 'font-semibold text-slate-800' : 'font-medium text-slate-600') + ' truncate">' + escapeHtml(n.title) + '</p><p class="text-xs text-slate-400 truncate">' + escapeHtml(n.message || '') + '</p><p class="text-[11px] text-slate-400 mt-0.5">' + escapeHtml(n.timeago || '') + '</p></div>' + (isUnread ? '<span class="flex-shrink-0 w-2 h-2 bg-teal-500 rounded-full mt-2"></span>' : '') + '</div>';
    }).join('');
    document.querySelectorAll('.notif-item').forEach(function (el) {
        el.addEventListener('click', function () {
            handleNotifClick(this.dataset.id, this.dataset.link);
        });
    });
}

function renderNotifLoading() {
    notifList.innerHTML = '<div class="flex flex-col items-center justify-center py-10 text-slate-400"><i class="bi bi-arrow-repeat text-2xl animate-spin"></i><p class="text-sm mt-2">Loading...</p></div>';
}

async function fetchNotifications() {
    try {
        var response = await fetch('ajax/notifications.php?action=list');
        if (!response.ok) throw new Error('Network error');
        var result = await response.json();
        if (result.success) {
            notifData = result;
            updateNotifBadge(result.unread_count);
            if (notifDropdown && !notifDropdown.classList.contains('hidden')) {
                renderNotifications(result.notifications);
            }
        }
    } catch (err) {
        console.error('Failed to fetch notifications:', err);
    }
}

function handleNotifClick(id, link) {
    var csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    fetch('ajax/notifications.php?action=mark_read', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id, csrf_token: csrfToken }),
    }).then(function (r) { return r.json(); }).then(function () {
        fetchNotifications();
        if (link) window.location.href = link;
    }).catch(function () {});
}

function handleNotifMarkAllRead() {
    var csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    fetch('ajax/notifications.php?action=mark_all_read', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ csrf_token: csrfToken }),
    }).then(function (r) { return r.json(); }).then(function () {
        fetchNotifications();
    }).catch(function () {});
}

if (notifContainer && notifBellBtn && notifDropdown) {
    renderNotifLoading();
    fetchNotifications();

    notifBellBtn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var isHidden = notifDropdown.classList.contains('hidden');
        notifDropdown.classList.toggle('hidden');
        notifDropdown.classList.toggle('block');
        if (isHidden && notifData) {
            renderNotifications(notifData.notifications);
        } else if (isHidden) {
            fetchNotifications().then(function () {
                if (notifData) renderNotifications(notifData.notifications);
            });
        }
    });

    document.addEventListener('click', function (e) {
        if (!notifContainer.contains(e.target)) {
            notifDropdown.classList.add('hidden');
            notifDropdown.classList.remove('block');
        }
    });

    if (notifMarkAllRead) {
        notifMarkAllRead.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            handleNotifMarkAllRead();
        });
    }
}

setInterval(function () {
    if (document.querySelector('.top-navbar')) {
        fetchNotifications();
    }
}, 30000);

// ============================================================
// 4. Dismissible Alert
// ============================================================
const alertCloseBtn = document.getElementById('alertCloseBtn');
    if (alertCloseBtn) {
        alertCloseBtn.addEventListener('click', () => {
            const alert = alertCloseBtn.closest('.alert-container');
            if (alert) {
                alert.remove();
            }
        });
    }

// ============================================================
// 5. Dashboard Initialization
// ============================================================
// --- Shared Utility Functions ---
    function formatCurrency(value) {
        return '$' + Number(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    const chartCanvas = document.getElementById('categoryStockChart');
    if (chartCanvas) {
        let categoryChart = null;
        let dashboardInterval = null;

        function getActionBadge(actionType) {
            const badges = {
                'stock_in': 'bg-green-500',
                'stock_out': 'bg-red-500',
                'adjustment': 'bg-blue-500',
                'create': 'bg-teal-500',
                'update': 'bg-amber-500',
                'delete': 'bg-red-600',
            };
            const color = badges[actionType] || 'bg-gray-500';
            const label = actionType.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
            return `<span class="inline-block ${color} text-white text-xs font-semibold rounded-full px-2 py-0.5">${label}</span>`;
        }

        function getExpiryBadge(daysUntil) {
            if (daysUntil <= 7) {
                return `<span class="inline-block bg-red-100 text-red-700 text-xs font-semibold rounded-full px-2 py-0.5">${daysUntil} days</span>`;
            } else if (daysUntil <= 14) {
                return `<span class="inline-block bg-amber-100 text-amber-700 text-xs font-semibold rounded-full px-2 py-0.5">${daysUntil} days</span>`;
            }
            return `<span class="inline-block bg-green-100 text-green-700 text-xs font-semibold rounded-full px-2 py-0.5">${daysUntil} days</span>`;
        }

        function formatTimeAgo(dateStr) {
            const now = new Date();
            const date = new Date(dateStr.replace(' ', 'T'));
            const diffMs = now - date;
            const diffMins = Math.floor(diffMs / 60000);
            if (diffMins < 1) return 'Just now';
            if (diffMins < 60) return `${diffMins} min ago`;
            const diffHours = Math.floor(diffMins / 60);
            if (diffHours < 24) return `${diffHours} hr ago`;
            const diffDays = Math.floor(diffHours / 24);
            if (diffDays < 7) return `${diffDays}d ago`;
            return date.toLocaleDateString();
        }

        function updateMetrics(metrics) {
            document.getElementById('metricTotalProducts').textContent = Number(metrics.total_products).toLocaleString();
            document.getElementById('metricTotalValue').textContent = formatCurrency(metrics.total_value);
            document.getElementById('metricLowStock').textContent = Number(metrics.low_stock_count).toLocaleString();
            document.getElementById('metricNearExpiry').textContent = Number(metrics.near_expiry_count).toLocaleString();
        }

        function renderChart(labels, data) {
            if (categoryChart) {
                categoryChart.data.labels = labels;
                categoryChart.data.datasets[0].data = data;
                categoryChart.update('none');
                return;
            }

            const ctx = chartCanvas.getContext('2d');
            categoryChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Stock Quantity',
                        data: data,
                        backgroundColor: 'rgba(32, 201, 166, 0.7)',
                        borderColor: 'rgba(32, 201, 166, 1)',
                        borderWidth: 1,
                        borderRadius: 4,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: { display: false },
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0 },
                            grid: { color: 'rgba(0,0,0,0.05)' },
                        },
                        x: {
                            grid: { display: false },
                        },
                    },
                },
            });
        }

        function populateActivityTable(activities) {
            const tbody = document.getElementById('recentActivityTableBody');
            if (!activities || activities.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="px-4 py-6 text-center text-slate-400 text-sm">No recent activity found.</td></tr>';
                return;
            }
            tbody.innerHTML = activities.map(a => {
                const qtyChange = a.new_quantity !== null && a.old_quantity !== null
                    ? (a.new_quantity - a.old_quantity)
                    : '';
                const qtyDisplay = qtyChange !== '' ? (qtyChange >= 0 ? `+${qtyChange}` : `${qtyChange}`) : '--';
                return `<tr class="hover:bg-gray-50">
                    <td class="px-4 py-3"><span class="font-semibold text-slate-700">${escapeHtml(a.user_name || 'System')}</span></td>
                    <td class="px-4 py-3">${getActionBadge(a.action_type)}</td>
                    <td class="px-4 py-3 text-slate-600">${escapeHtml(a.item_name || '--')}</td>
                    <td class="px-4 py-3 text-slate-600">${qtyDisplay}</td>
                    <td class="px-4 py-3 text-slate-400 text-xs">${formatTimeAgo(a.created_at)}</td>
                </tr>`;
            }).join('');
        }

        function populateExpiryTable(batches) {
            const tbody = document.getElementById('expiryWatchlistTableBody');
            if (!batches || batches.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="px-4 py-6 text-center text-slate-400 text-sm">No batches expiring soon.</td></tr>';
                return;
            }
            tbody.innerHTML = batches.map(b => {
                const expiryDate = new Date(b.expiry_date.replace(' ', 'T'));
                const formattedDate = expiryDate.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
                return `<tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-mono text-xs text-slate-600">${escapeHtml(b.sku)}</td>
                    <td class="px-4 py-3 text-slate-700 font-medium">${escapeHtml(b.name)}</td>
                    <td class="px-4 py-3 text-slate-600">${escapeHtml(b.batch_number)}</td>
                    <td class="px-4 py-3 text-slate-600">${Number(b.quantity).toLocaleString()}</td>
                    <td class="px-4 py-3 text-slate-600 text-xs">${formattedDate}</td>
                    <td class="px-4 py-3">${getExpiryBadge(b.days_until_expiry)}</td>
                </tr>`;
            }).join('');
        }

        async function fetchDashboardData() {
            try {
                const response = await fetch('ajax/dashboard.php');
                if (!response.ok) throw new Error('Network response was not ok');
                const data = await response.json();

                updateMetrics(data.metrics);
                renderChart(data.chart.labels, data.chart.data);
                populateActivityTable(data.recent_activity);
                populateExpiryTable(data.expiring_batches);
            } catch (error) {
                console.error('Dashboard data fetch failed:', error);
            }
        }

        fetchDashboardData();
        dashboardInterval = setInterval(fetchDashboardData, 30000);
    }

// ============================================================
// 6. Reports Module
// ============================================================
    const reportTabBtns = document.querySelectorAll('.report-tab-btn');
    if (reportTabBtns.length > 0) {
        let currentDays = 30;
        let lowStockData = [];
        let expiryData = [];
        let activityData = [];

        // --- Tab Switching ---
        function switchTab(tabId) {
            document.querySelectorAll('.report-tab-content').forEach(el => el.classList.add('hidden'));
            const targetId = 'tab' + tabId.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join('');
            const target = document.getElementById(targetId);
            if (target) target.classList.remove('hidden');

            reportTabBtns.forEach(btn => {
                const isActive = btn.dataset.tab === tabId;
                btn.classList.toggle('text-teal', isActive);
                btn.classList.toggle('border-teal', isActive);
                btn.classList.toggle('text-slate-500', !isActive);
                btn.classList.toggle('border-transparent', !isActive);
            });

            if (tabId === 'low_stock' && lowStockData.length === 0) {
                fetchLowStockReport();
            } else if (tabId === 'low_stock' && lowStockData.length > 0) {
                renderLowStockTable(lowStockData);
            }
            if (tabId === 'expiry' && expiryData.length === 0) {
                fetchExpiryReport(currentDays);
            } else if (tabId === 'expiry' && expiryData.length > 0) {
                renderExpiryTable(expiryData);
            }
            if (tabId === 'activity_summary' && activityData.length === 0) {
                fetchActivitySummary();
            } else if (tabId === 'activity_summary' && activityData.length > 0) {
                renderActivitySummaryTable(activityData);
            }
        }

        reportTabBtns.forEach(btn => {
            btn.addEventListener('click', () => switchTab(btn.dataset.tab));
        });

        // --- Fetch Overview Metrics ---
        async function fetchOverview() {
            try {
                const res = await fetch('ajax/reports.php?action=overview');
                const result = await res.json();
                if (result.success && result.data) {
                    const m = result.data;
                    document.getElementById('metricLowStockCount').textContent = Number(m.low_stock_count).toLocaleString();
                    document.getElementById('metricExpiringCount').textContent = Number(m.expiring_30).toLocaleString();
                    document.getElementById('metricTotalActions').textContent = Number(m.total_actions).toLocaleString();
                }
            } catch (err) {
                console.error('Failed to fetch report overview:', err);
            }
        }

        // --- Low Stock Report ---
        async function fetchLowStockReport() {
            const categoryId = document.getElementById('lowStockCategoryFilter')?.value || '';
            const params = new URLSearchParams({ action: 'low_stock' });
            if (categoryId) params.append('category_id', categoryId);

            const tbody = document.getElementById('lowStockTableBody');
            tbody.innerHTML = '<tr><td colspan="8" class="px-4 py-6 text-center text-slate-400 text-sm">Loading...</td></tr>';

            try {
                const res = await fetch('ajax/reports.php?' + params.toString());
                const result = await res.json();
                if (result.success) {
                    lowStockData = result.data || [];
                    renderLowStockTable(lowStockData);
                    const totalVal = result.meta?.total_value || 0;
                    document.getElementById('lowStockTotalValue').textContent = formatCurrency(totalVal);
                } else {
                    tbody.innerHTML = `<tr><td colspan="8" class="px-4 py-6 text-center text-red-500 text-sm">${escapeHtml(result.message)}</td></tr>`;
                }
            } catch (err) {
                tbody.innerHTML = '<tr><td colspan="8" class="px-4 py-6 text-center text-red-500 text-sm">Failed to load data.</td></tr>';
            }
        }

        function getLowStockBadge(quantity) {
            if (quantity === 0) {
                return '<span class="badge-action-count badge-out-of-stock">Out of Stock</span>';
            }
            return '<span class="badge-action-count badge-low-stock">Low Stock</span>';
        }

        function renderLowStockTable(items) {
            const tbody = document.getElementById('lowStockTableBody');
            if (items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="px-4 py-8 text-center text-slate-400 text-sm">No low stock items found.</td></tr>';
                return;
            }
            tbody.innerHTML = items.map(item => `
                <tr class="hover:bg-slate-50/80 transition-colors">
                    <td class="px-4 py-3 font-mono text-xs font-semibold text-slate-600">${escapeHtml(item.sku)}</td>
                    <td class="px-4 py-3 font-semibold text-slate-800">${escapeHtml(item.name)}</td>
                    <td class="px-4 py-3 text-slate-600">${escapeHtml(item.category_name)}</td>
                    <td class="px-4 py-3 text-right font-medium text-slate-700">${formatCurrency(item.price)}</td>
                    <td class="px-4 py-3 text-right font-bold text-slate-800">${Number(item.quantity).toLocaleString()}</td>
                    <td class="px-4 py-3 text-right text-slate-500">${item.low_stock_threshold}</td>
                    <td class="px-4 py-3 text-right font-medium text-slate-700">${formatCurrency(item.stock_value)}</td>
                    <td class="px-4 py-3 text-center">${getLowStockBadge(Number(item.quantity))}</td>
                </tr>
            `).join('');
        }

        // --- Low Stock Category Filter ---
        const lowStockCategoryFilter = document.getElementById('lowStockCategoryFilter');
        if (lowStockCategoryFilter) {
            // Populate categories
            fetch('ajax/categories.php?action=list')
                .then(r => r.json())
                .then(result => {
                    if (result.success) {
                        const cats = result.data || [];
                        lowStockCategoryFilter.innerHTML = '<option value="">All Categories</option>' +
                            cats.map(c => `<option value="${c.id}">${escapeHtml(c.category_name)}</option>`).join('');
                    }
                })
                .catch(() => {});

            lowStockCategoryFilter.addEventListener('change', () => {
                lowStockData = [];
                fetchLowStockReport();
            });
        }

        // --- Expiry Report ---
        async function fetchExpiryReport(days) {
            currentDays = days;
            const params = new URLSearchParams({ action: 'expiry', days: days });

            const tbody = document.getElementById('expiryTableBody');
            tbody.innerHTML = '<tr><td colspan="8" class="px-4 py-6 text-center text-slate-400 text-sm">Loading...</td></tr>';

            try {
                const res = await fetch('ajax/reports.php?' + params.toString());
                const result = await res.json();
                if (result.success) {
                    expiryData = result.data || [];
                    renderExpiryTable(expiryData);
                } else {
                    tbody.innerHTML = `<tr><td colspan="8" class="px-4 py-6 text-center text-red-500 text-sm">${escapeHtml(result.message)}</td></tr>`;
                }
            } catch (err) {
                tbody.innerHTML = '<tr><td colspan="8" class="px-4 py-6 text-center text-red-500 text-sm">Failed to load data.</td></tr>';
            }
        }

        function getUrgencyBadge(status) {
            const badges = {
                'Expired': 'badge-expired',
                'Critical': 'badge-critical',
                'Warning': 'badge-warning',
                'Notice': 'badge-notice',
            };
            const cls = badges[status] || 'bg-slate-100 text-slate-700';
            return `<span class="badge-action-count ${cls}">${escapeHtml(status)}</span>`;
        }

        function formatDate(dateStr) {
            if (!dateStr) return '--';
            const d = new Date(dateStr + 'T00:00:00');
            return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
        }

        function renderExpiryTable(batches) {
            const tbody = document.getElementById('expiryTableBody');
            if (batches.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="px-4 py-8 text-center text-slate-400 text-sm">No expiring batches found for the selected period.</td></tr>';
                return;
            }
            tbody.innerHTML = batches.map(b => `
                <tr class="hover:bg-slate-50/80 transition-colors">
                    <td class="px-4 py-3 font-mono text-xs font-semibold text-slate-600">${escapeHtml(b.sku)}</td>
                    <td class="px-4 py-3 font-semibold text-slate-800">${escapeHtml(b.item_name)}</td>
                    <td class="px-4 py-3 font-mono text-xs text-slate-600">${escapeHtml(b.batch_number)}</td>
                    <td class="px-4 py-3 text-slate-600">${escapeHtml(b.category_name)}</td>
                    <td class="px-4 py-3 text-right font-bold">${Number(b.quantity).toLocaleString()}</td>
                    <td class="px-4 py-3 text-slate-600">${formatDate(b.expiry_date)}</td>
                    <td class="px-4 py-3 text-right font-mono text-sm ${b.days_until_expiry < 0 ? 'text-red-600' : 'text-slate-700'}">${b.days_until_expiry < 0 ? 'Expired' : b.days_until_expiry}</td>
                    <td class="px-4 py-3 text-center">${getUrgencyBadge(b.urgency_status)}</td>
                </tr>
            `).join('');
        }

        // --- Expiry Day Pills ---
        document.querySelectorAll('.expiry-pill').forEach(pill => {
            pill.addEventListener('click', () => {
                document.querySelectorAll('.expiry-pill').forEach(p => {
                    p.classList.remove('bg-teal', 'text-white', 'border-teal');
                    p.classList.add('bg-white', 'text-slate-600', 'border-slate-200');
                });
                pill.classList.remove('bg-white', 'text-slate-600', 'border-slate-200');
                pill.classList.add('bg-teal', 'text-white', 'border-teal');
                fetchExpiryReport(parseInt(pill.dataset.days));
            });
        });

        // --- Activity Summary ---
        async function fetchActivitySummary() {
            const tbody = document.getElementById('activitySummaryTableBody');
            tbody.innerHTML = '<tr><td colspan="8" class="px-4 py-6 text-center text-slate-400 text-sm">Loading...</td></tr>';

            try {
                const res = await fetch('ajax/reports.php?action=activity_summary');
                const result = await res.json();
                if (result.success) {
                    activityData = result.data || [];
                    renderActivitySummaryTable(activityData);
                } else {
                    tbody.innerHTML = `<tr><td colspan="8" class="px-4 py-6 text-center text-red-500 text-sm">${escapeHtml(result.message)}</td></tr>`;
                }
            } catch (err) {
                tbody.innerHTML = '<tr><td colspan="8" class="px-4 py-6 text-center text-red-500 text-sm">Failed to load data.</td></tr>';
            }
        }

        function renderActivitySummaryTable(users) {
            const tbody = document.getElementById('activitySummaryTableBody');
            if (users.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="px-4 py-8 text-center text-slate-400 text-sm">No activity data found.</td></tr>';
                return;
            }
            tbody.innerHTML = users.map(u => `
                <tr class="hover:bg-slate-50/80 transition-colors">
                    <td class="px-4 py-3 font-semibold text-slate-800">${escapeHtml(u.full_name)}</td>
                    <td class="px-4 py-3 text-slate-600 text-xs">${escapeHtml(u.role_name)}</td>
                    <td class="px-4 py-3 text-center"><span class="badge-action-count badge-action-create">${u.create}</span></td>
                    <td class="px-4 py-3 text-center"><span class="badge-action-count badge-action-update">${u.update}</span></td>
                    <td class="px-4 py-3 text-center"><span class="badge-action-count badge-action-delete">${u.delete}</span></td>
                    <td class="px-4 py-3 text-center"><span class="badge-action-count badge-action-stock_in">${u.stock_in}</span></td>
                    <td class="px-4 py-3 text-center"><span class="badge-action-count badge-action-stock_out">${u.stock_out}</span></td>
                    <td class="px-4 py-3 text-center"><span class="badge-action-count badge-action-total">${u.total}</span></td>
                </tr>
            `).join('');
        }

        // --- Init Reports ---
        fetchOverview();
        fetchLowStockReport();
    }

    // ============================================================
    // 7. Full Notifications Page
    // ============================================================
    var notifPageContainer = document.getElementById('notifPageContainer');
    if (notifPageContainer) {
        var notifPage = 1;
        var notifPageLimit = 20;
        var notifPagePrev = document.getElementById('notifPagePrev');
        var notifPageNext = document.getElementById('notifPageNext');
        var notifPageInfo = document.getElementById('notifPageInfo');
        var notifPagePagination = document.getElementById('notifPagePagination');

        function fetchNotifPage() {
            notifPageContainer.innerHTML = '<div class="text-center py-12 text-slate-400"><i class="bi bi-arrow-repeat text-3xl animate-spin inline-block"></i><p class="mt-2 text-sm">Loading notifications...</p></div>';
            fetch('ajax/notifications.php?action=list_all&page=' + notifPage + '&limit=' + notifPageLimit)
                .then(function (r) { return r.json(); })
                .then(function (result) {
                    if (result.success) {
                        renderNotifPage(result.notifications, result.pagination);
                    } else {
                        notifPageContainer.innerHTML = '<div class="text-center py-12 text-red-400 text-sm">' + escapeHtml(result.message) + '</div>';
                    }
                })
                .catch(function () {
                    notifPageContainer.innerHTML = '<div class="text-center py-12 text-red-400 text-sm">Failed to load notifications.</div>';
                });
        }

        function renderNotifPage(notifications, pagination) {
            if (!notifications || notifications.length === 0) {
                notifPageContainer.innerHTML = '<div class="text-center py-16 text-slate-400"><i class="bi bi-check2-circle text-5xl mb-3"></i><p class="text-lg font-medium">You\'re all caught up</p><p class="text-sm mt-1">No notifications yet</p></div>';
                if (notifPagePagination) notifPagePagination.classList.add('hidden');
                return;
            }
            var iconMap = {
                'low_stock': 'bi-exclamation-triangle-fill text-amber-500',
                'near_expiry': 'bi-clock-fill text-orange-500',
                'stock_in': 'bi-box-arrow-in-down text-green-500',
                'stock_out': 'bi-box-arrow-up text-red-500',
                'create': 'bi-plus-circle-fill text-teal-500',
                'delete': 'bi-trash-fill text-red-500',
                'update': 'bi-pencil-fill text-blue-500',
                'system': 'bi-info-circle-fill text-slate-500',
            };
            var html = '<div class="space-y-2">';
            notifications.forEach(function (n) {
                var icon = iconMap[n.type] || 'bi-bell-fill text-slate-500';
                var isUnread = parseInt(n.is_read) === 0;
                html += '<div class="flex items-start gap-3 p-4 bg-white border border-gray-100 rounded-xl hover:shadow-sm transition-shadow cursor-pointer notif-page-item ' + (isUnread ? 'border-l-4 border-l-teal' : '') + '" data-id="' + n.id + '" data-link="' + escapeHtml(n.link || '') + '"><div class="flex-shrink-0 mt-0.5"><i class="bi ' + icon + ' text-xl"></i></div><div class="flex-1 min-w-0"><p class="text-sm ' + (isUnread ? 'font-semibold text-slate-800' : 'text-slate-600') + '">' + escapeHtml(n.title) + '</p><p class="text-sm text-slate-500 mt-0.5">' + escapeHtml(n.message || '') + '</p><p class="text-xs text-slate-400 mt-1">' + escapeHtml(n.timeago || n.created_at || '') + '</p></div>' + (isUnread ? '<span class="flex-shrink-0 w-2.5 h-2.5 bg-teal-500 rounded-full mt-2"></span>' : '') + '</div>';
            });
            html += '</div>';
            notifPageContainer.innerHTML = html;

            document.querySelectorAll('.notif-page-item').forEach(function (el) {
                el.addEventListener('click', function () {
                    var id = this.dataset.id;
                    var link = this.dataset.link;
                    var csrfToken = document.querySelector('meta[name="csrf-token"]').content;
                    fetch('ajax/notifications.php?action=mark_read', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: id, csrf_token: csrfToken }),
                    }).then(function () {
                        if (link) window.location.href = link;
                        fetchNotifPage();
                    }).catch(function () {});
                });
            });

            if (pagination && notifPageInfo && notifPagePrev && notifPageNext) {
                var totalPages = Math.ceil(pagination.total / notifPageLimit) || 1;
                notifPageInfo.textContent = 'Page ' + notifPage + ' of ' + totalPages + ' (' + pagination.total + ' total)';
                notifPagePrev.disabled = notifPage <= 1;
                notifPageNext.disabled = notifPage >= totalPages;
                if (notifPagePagination) notifPagePagination.classList.remove('hidden');
            }
        }

        if (notifPagePrev) {
            notifPagePrev.addEventListener('click', function () {
                if (notifPage > 1) { notifPage--; fetchNotifPage(); }
            });
        }
        if (notifPageNext) {
            notifPageNext.addEventListener('click', function () {
                notifPage++; fetchNotifPage();
            });
        }

        var notifPageMarkAllRead = document.getElementById('notifPageMarkAllRead');
        if (notifPageMarkAllRead) {
            notifPageMarkAllRead.addEventListener('click', function () {
                var csrfToken = document.querySelector('meta[name="csrf-token"]').content;
                fetch('ajax/notifications.php?action=mark_all_read', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ csrf_token: csrfToken }),
                }).then(function () {
                    notifPage = 1;
                    fetchNotifPage();
                }).catch(function () {});
            });
        }

        fetchNotifPage();
    }
});
