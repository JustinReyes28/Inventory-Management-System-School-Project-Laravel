<?php
// Master Layout Template for InvControl System
require_once __DIR__ . '/../config/session.php';

// Page Title Mapping
$pageTitles = [
    'dashboard' => 'Dashboard Overview',
    'items' => 'Inventory Items',
    'categories' => 'Category Management',
    'batches' => 'Batch & Expiry Tracking',
    'activity_log' => 'System Activity Logs',
    'users' => 'User Management',
    'reports' => 'Analytics & Reports',
    'notifications' => 'Notifications',
];

$currentTitle = $pageTitles[$page] ?? 'Dashboard Overview';
$userName = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User';
$userRole = $_SESSION['role'] ?? 'Employee';
$isAdmin = strtolower($userRole) === 'admin';

// Initials for avatar
$nameParts = explode(' ', trim($userName));
$initials = strtoupper(substr($nameParts[0], 0, 1) . (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : ''));

$pageTitle = $currentTitle . ' - IMS';
include __DIR__ . '/header.php';
?>

<div id="wrapper">
    <!-- Sidebar Navigation -->
    <nav id="sidebar">
        <!-- Sidebar Brand -->
        <div class="sidebar-header">
            <a href="index.php?page=dashboard" class="sidebar-brand">
                <div class="sidebar-brand-icon">
                    <i class="bi bi-box-seam-fill"></i>
                </div>
                <span class="sidebar-brand-text">IMS</span>
            </a>
        </div>

        <!-- User Profile Section -->
        <div class="user-profile-section">
            <div class="avatar-circle"><?= htmlspecialchars($initials) ?></div>
            <div class="user-info">
                <p class="user-name mb-0"><?= htmlspecialchars($userName) ?></p>
                <span class="user-role-badge"><?= htmlspecialchars($userRole) ?></span>
            </div>
        </div>

        <!-- Navigation Links -->
        <ul class="sidebar-menu">
            <li class="nav-item">
                <a href="index.php?page=dashboard" class="nav-link-custom <?= $page === 'dashboard' ? 'active' : '' ?>">
                    <i class="bi bi-grid-1x2-fill"></i>
                    <span class="nav-text">Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="index.php?page=items" class="nav-link-custom <?= $page === 'items' ? 'active' : '' ?>">
                    <i class="bi bi-box-fill"></i>
                    <span class="nav-text">Items</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="index.php?page=categories"
                    class="nav-link-custom <?= $page === 'categories' ? 'active' : '' ?>">
                    <i class="bi bi-tags-fill"></i>
                    <span class="nav-text">Categories</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="index.php?page=batches" class="nav-link-custom <?= $page === 'batches' ? 'active' : '' ?>">
                    <i class="bi bi-layers-fill"></i>
                    <span class="nav-text">Batches</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="index.php?page=activity_log"
                    class="nav-link-custom <?= $page === 'activity_log' ? 'active' : '' ?>">
                    <i class="bi bi-journal-text"></i>
                    <span class="nav-text">Activity Log</span>
                </a>
            </li>

            <?php if ($isAdmin): ?>
                <li class="nav-item">
                    <a href="index.php?page=users" class="nav-link-custom <?= $page === 'users' ? 'active' : '' ?>">
                        <i class="bi bi-people-fill"></i>
                        <span class="nav-text">Users</span>
                    </a>
                </li>
            <?php endif; ?>

            <li class="nav-item">
                <a href="index.php?page=reports" class="nav-link-custom <?= $page === 'reports' ? 'active' : '' ?>">
                    <i class="bi bi-bar-chart-line-fill"></i>
                    <span class="nav-text">Reports</span>
                </a>
            </li>
        </ul>
    </nav>

    <!-- Sidebar Backdrop for Offcanvas (Mobile) -->
    <div id="sidebarBackdrop" class="sidebar-backdrop hidden"></div>

    <!-- Main Content Wrapper -->
    <div id="content-wrapper">
        <!-- Top Navbar -->
        <header class="top-navbar">
            <div class="flex items-center gap-3">
                <button type="button" id="sidebarToggle" class="toggle-sidebar-btn" title="Toggle Sidebar">
                    <i class="bi bi-list"></i>
                </button>
                <h5 class="mb-0 font-semibold hidden sm:block text-base"><?= htmlspecialchars($currentTitle) ?></h5>
            </div>

            <div class="flex items-center gap-3">

                <!-- Notifications Dropdown -->
                <div class="relative" id="notifContainer">
                    <button id="notifBellBtn"
                        class="bg-transparent border-none text-current p-1 cursor-pointer relative" type="button"
                        title="Notifications">
                        <i class="bi bi-bell text-lg"></i>
                        <span id="notifBadge"
                            class="hidden absolute -top-1 -right-1 min-w-[18px] h-[18px] flex items-center justify-center bg-red-500 text-white text-[10px] font-bold rounded-full px-1 leading-none"></span>
                    </button>

                    <div id="notifDropdown"
                        class="hidden absolute right-0 top-full mt-2 w-[360px] bg-white rounded-xl shadow-lg border-2 border-slate-200 z-50">
                        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                            <strong class="text-sm text-slate-800">Notifications</strong>
                            <button id="notifMarkAllRead"
                                class="text-xs text-teal-600 hover:text-teal-700 font-medium bg-transparent border-none cursor-pointer">Mark
                                all as read</button>
                        </div>
                        <div id="notifList" class="max-h-[400px] overflow-y-auto">
                        </div>
                        <a href="index.php?page=notifications"
                            class="block text-center py-2.5 text-sm text-teal-600 hover:text-teal-700 font-medium border-t border-gray-100 no-underline hover:bg-gray-50 rounded-b-xl transition-colors">
                            View all notifications
                        </a>
                    </div>
                </div>

                <!-- User Dropdown Menu -->
                <div class="relative">
                    <button id="userDropdownToggle"
                        class="bg-transparent border-none p-0 flex items-center gap-2 cursor-pointer" type="button">
                        <div class="avatar-circle" style="width: 34px; height: 34px; font-size: 0.85rem;">
                            <?= htmlspecialchars($initials) ?>
                        </div>
                    </button>
                    <div id="userDropdownMenu"
                        class="hidden absolute right-0 top-full mt-2 w-56 bg-white rounded-xl shadow-lg border border-gray-100 py-2 z-50">
                        <div class="px-4 py-2">
                            <strong class="text-sm text-slate-800"><?= htmlspecialchars($userName) ?></strong><br>
                            <small class="text-xs text-slate-500"><?= htmlspecialchars($userRole) ?></small>
                        </div>
                        <hr class="my-1 border-gray-100">
                        <a href="index.php?action=logout"
                            class="flex items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50 no-underline">
                            <i class="bi bi-box-arrow-right"></i> Sign Out
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main View Content Inclusion -->
        <main class="main-content">
            <?php
            $viewPath = __DIR__ . '/' . $page . '.php';
            if (file_exists($viewPath)) {
                include $viewPath;
            } else {
                echo '<div class="bg-yellow-50 border border-yellow-200 text-yellow-800 rounded-lg p-4 text-sm">View file not found.</div>';
            }
            ?>
        </main>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>