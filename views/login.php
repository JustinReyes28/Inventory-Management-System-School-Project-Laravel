<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../classes/User.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php?page=dashboard');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!validateCsrfToken($csrfToken)) {
        $error = 'Invalid security token. Please refresh and try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = 'Please enter both username and password.';
        } else {
            $userObj = new User();
            $user = $userObj->login($username, $password);

            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['role_id'] = $user['role_id'];

                session_regenerate_id(true);

                header('Location: index.php?page=dashboard');
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        }
    }
}

$pageTitle = 'Login - IMS';
$bodyClass = 'login-body';
include __DIR__ . '/header.php';
?>

<div class="flex items-center justify-center min-h-screen px-4">
    <div class="login-card p-8 md:p-12 shadow-2xl">
        <div class="text-center mb-6">
            <div class="brand-badge inline-flex items-center justify-center mb-3">
                <i class="bi bi-box-seam-fill text-white text-2xl"></i>
            </div>
            <h3 class="font-bold mb-1 text-2xl brand-title">Inventory Management System</h3>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert-container flex items-center gap-2 bg-red-500/10 border border-red-500/20 text-red-400 rounded-lg p-3 mb-6"
                role="alert">
                <i class="bi bi-exclamation-triangle-fill text-lg shrink-0"></i>
                <div class="flex-1 text-sm"><?= htmlspecialchars($error) ?></div>
                <button type="button" id="alertCloseBtn"
                    class="text-red-400 hover:text-red-300 text-xl leading-none p-1 cursor-pointer bg-transparent border-none"
                    aria-label="Close">&times;</button>
            </div>
        <?php endif; ?>

        <form method="POST" action="index.php?page=login" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken()) ?>">

            <div class="mb-5">
                <label for="username" class="block font-semibold text-sm mb-1.5 text-white/80">Username</label>
                <div class="flex">
                    <span
                        class="inline-flex items-center px-3 rounded-l-lg border border-r-0 border-white/10 bg-white/10 text-slate-400"><i
                            class="bi bi-person"></i></span>
                    <input type="text"
                        class="flex-1 px-3 py-2.5 rounded-r-lg border border-l-0 border-white/10 bg-white/10 text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-500/50 focus:border-teal-500/50 text-sm w-0 min-w-0"
                        id="username" name="username" placeholder="Enter username" required autofocus
                        value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                </div>
            </div>

            <div class="mb-6">
                <label for="password" class="block font-semibold text-sm mb-1.5 text-white/80">Password</label>
                <div class="flex">
                    <span
                        class="inline-flex items-center px-3 rounded-l-lg border border-r-0 border-white/10 bg-white/10 text-slate-400"><i
                            class="bi bi-lock"></i></span>
                    <input type="password"
                        class="flex-1 px-3 py-2.5 rounded-r-lg border border-l-0 border-white/10 bg-white/10 text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-500/50 focus:border-teal-500/50 text-sm w-0 min-w-0"
                        id="password" name="password" placeholder="Enter password" required>
                </div>
            </div>

            <button type="submit"
                class="btn-teal w-full py-2.5 font-semibold flex items-center justify-center gap-2 text-sm cursor-pointer shadow-lg">
                <i class="bi bi-box-arrow-in-right"></i> Sign In
            </button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>