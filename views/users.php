<?php
// User Management View (Admin Only)
?>

<div class="card-custom p-6">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h4 class="text-xl font-bold text-slate-800 mb-1">User Management</h4>
            <p class="text-slate-500 text-xs sm:text-sm mb-0">Manage system users, roles, and access controls</p>
        </div>
        <div class="flex items-center gap-3">
            <div class="relative w-full sm:w-64">
                <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input type="text" id="userSearchInput" class="w-full pl-9 pr-3 py-2 text-sm border rounded-lg bg-slate-50 border-slate-200 text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-teal/30 focus:border-teal" placeholder="Search users...">
            </div>
            <button type="button" id="openAddUserBtn" class="btn-teal py-2 px-4 font-semibold text-sm flex items-center gap-2 shrink-0 cursor-pointer">
                <i class="bi bi-person-plus-fill"></i>
                <span>Add User</span>
            </button>
        </div>
    </div>

    <!-- User Table -->
    <div class="overflow-x-auto rounded-lg border border-slate-200">
        <table class="w-full text-left text-sm text-slate-600">
            <thead class="bg-slate-100/70 text-slate-700 font-semibold border-b border-slate-200">
                <tr>
                    <th class="px-4 py-3 w-20">#</th>
                    <th class="px-4 py-3">Full Name</th>
                    <th class="px-4 py-3">Username</th>
                    <th class="px-4 py-3">Role</th>
                    <th class="px-4 py-3 w-40">Created</th>
                    <th class="px-4 py-3 w-32 text-right">Actions</th>
                </tr>
            </thead>
            <tbody id="usersTableBody" class="divide-y divide-slate-100">
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-slate-400">
                        <i class="bi bi-arrow-repeat spin text-xl block mb-2"></i>
                        Loading users...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Add / Edit User Modal -->
<div id="userModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden transform transition-all">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
            <h5 id="userModalTitle" class="font-bold text-slate-800 text-base mb-0">Add User</h5>
            <button type="button" class="closeUserModalBtn text-slate-400 hover:text-slate-600 text-lg cursor-pointer">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <form id="userForm" class="p-6">
            <input type="hidden" id="userFormId" name="id" value="">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

            <div class="mb-4">
                <label for="userFullNameInput" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                    Full Name <span class="text-red-500">*</span>
                </label>
                <input type="text" id="userFullNameInput" name="full_name" required maxlength="100" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal/30 focus:border-teal" placeholder="e.g. John Doe">
            </div>

            <div class="mb-4">
                <label for="userUsernameInput" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                    Username <span class="text-red-500">*</span>
                </label>
                <input type="text" id="userUsernameInput" name="username" required maxlength="50" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal/30 focus:border-teal" placeholder="e.g. johndoe">
            </div>

            <div class="mb-4">
                <label for="userPasswordInput" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                    Password <span id="passwordRequired" class="text-red-500">*</span>
                </label>
                <input type="password" id="userPasswordInput" name="password" maxlength="255" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal/30 focus:border-teal" placeholder="Leave blank to keep current password" autocomplete="new-password">
            </div>

            <div class="mb-4">
                <label for="userRoleSelect" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                    Role <span class="text-red-500">*</span>
                </label>
                <select id="userRoleSelect" name="role_id" required class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal/30 focus:border-teal bg-white">
                    <option value="">Select Role</option>
                </select>
            </div>

            <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100">
                <button type="button" class="closeUserModalBtn px-4 py-2 text-sm font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg cursor-pointer">
                    Cancel
                </button>
                <button type="submit" id="saveUserBtn" class="btn-teal px-4 py-2 text-sm font-medium rounded-lg cursor-pointer flex items-center gap-2">
                    <i class="bi bi-check-lg"></i>
                    <span>Save User</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteUserModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm overflow-hidden p-6 text-center">
        <div class="w-12 h-12 rounded-full bg-red-100 text-red-600 flex items-center justify-center mx-auto mb-4 text-2xl">
            <i class="bi bi-exclamation-triangle"></i>
        </div>
        <h5 class="font-bold text-slate-800 text-base mb-1">Delete User?</h5>
        <p class="text-slate-500 text-xs mb-6" id="deleteUserMsg">Are you sure you want to delete this user? This action cannot be undone.</p>

        <div class="flex items-center justify-center gap-3">
            <button type="button" class="closeDeleteUserModalBtn px-4 py-2 text-sm font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg cursor-pointer">
                Cancel
            </button>
            <button type="button" id="confirmDeleteUserBtn" class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg cursor-pointer">
                Delete
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const usersTableBody = document.getElementById('usersTableBody');
    if (!usersTableBody) return;

    let usersData = [];
    let userToDeleteId = null;

    const userModal = document.getElementById('userModal');
    const userForm = document.getElementById('userForm');
    const userModalTitle = document.getElementById('userModalTitle');
    const userFormId = document.getElementById('userFormId');
    const userFullNameInput = document.getElementById('userFullNameInput');
    const userUsernameInput = document.getElementById('userUsernameInput');
    const userPasswordInput = document.getElementById('userPasswordInput');
    const userRoleSelect = document.getElementById('userRoleSelect');
    const passwordRequired = document.getElementById('passwordRequired');
    const userSearchInput = document.getElementById('userSearchInput');

    const deleteUserModal = document.getElementById('deleteUserModal');
    const deleteUserMsg = document.getElementById('deleteUserMsg');
    const confirmDeleteUserBtn = document.getElementById('confirmDeleteUserBtn');

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function getRoleBadge(role) {
        const isAdmin = role.toLowerCase() === 'admin';
        const color = isAdmin ? 'bg-teal-100 text-teal-700' : 'bg-slate-100 text-slate-700';
        return `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold ${color}">${escapeHtml(role)}</span>`;
    }

    function formatDate(dateStr) {
        if (!dateStr) return '--';
        const d = new Date(dateStr.replace(' ', 'T'));
        return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    }

    async function loadUsers() {
        try {
            const res = await fetch('ajax/users.php?action=list');
            const result = await res.json();
            if (result.success) {
                usersData = result.data || [];
                renderUsersTable(usersData);
            } else {
                usersTableBody.innerHTML = `<tr><td colspan="6" class="px-4 py-6 text-center text-red-500 text-sm">${escapeHtml(result.message)}</td></tr>`;
            }
        } catch (err) {
            console.error('Error fetching users:', err);
            usersTableBody.innerHTML = '<tr><td colspan="6" class="px-4 py-6 text-center text-red-500 text-sm">Failed to load users. Please try again.</td></tr>';
        }
    }

    function renderUsersTable(data) {
        const query = (userSearchInput ? userSearchInput.value : '').trim().toLowerCase();
        const filtered = data.filter(u =>
            u.full_name.toLowerCase().includes(query) ||
            u.username.toLowerCase().includes(query)
        );

        if (filtered.length === 0) {
            usersTableBody.innerHTML = `<tr><td colspan="6" class="px-4 py-8 text-center text-slate-400 text-sm">No users found matching your search.</td></tr>`;
            return;
        }

        usersTableBody.innerHTML = filtered.map(u => `
            <tr class="hover:bg-slate-50/80 transition-colors">
                <td class="px-4 py-3 font-mono text-xs text-slate-500">#${u.id}</td>
                <td class="px-4 py-3 font-semibold text-slate-800">${escapeHtml(u.full_name)}</td>
                <td class="px-4 py-3 text-slate-600">${escapeHtml(u.username)}</td>
                <td class="px-4 py-3">${getRoleBadge(u.role)}</td>
                <td class="px-4 py-3 text-slate-400 text-xs">${formatDate(u.created_at)}</td>
                <td class="px-4 py-3 text-right">
                    <div class="flex items-center justify-end gap-1">
                        <button type="button" class="editUserBtn text-slate-500 hover:text-teal p-1.5 rounded-lg hover:bg-teal/10 transition-colors cursor-pointer" data-id="${u.id}" title="Edit User">
                            <i class="bi bi-pencil-square text-base"></i>
                        </button>
                        <button type="button" class="deleteUserBtn text-slate-500 hover:text-red-600 p-1.5 rounded-lg hover:bg-red-50 transition-colors cursor-pointer" data-id="${u.id}" data-name="${escapeHtml(u.full_name)}" title="Delete User">
                            <i class="bi bi-trash3 text-base"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');

        document.querySelectorAll('.editUserBtn').forEach(btn => {
            btn.addEventListener('click', () => openEditUserModal(btn.dataset.id));
        });
        document.querySelectorAll('.deleteUserBtn').forEach(btn => {
            btn.addEventListener('click', () => openDeleteUserModal(btn.dataset.id, btn.dataset.name));
        });
    }

    if (userSearchInput) {
        userSearchInput.addEventListener('input', () => renderUsersTable(usersData));
    }

    // Load roles for the dropdown
    async function loadRoles() {
        try {
            const res = await fetch('ajax/users.php?action=roles');
            const result = await res.json();
            if (result.success && result.data) {
                userRoleSelect.innerHTML = '<option value="">Select Role</option>' +
                    result.data.map(r => `<option value="${r.id}">${escapeHtml(r.role_name)}</option>`).join('');
            }
        } catch (err) {
            console.error('Error loading roles:', err);
        }
    }

    // Modal Open/Close
    const openAddUserBtn = document.getElementById('openAddUserBtn');
    if (openAddUserBtn) {
        openAddUserBtn.addEventListener('click', () => {
            userForm.reset();
            userFormId.value = '';
            userPasswordInput.required = true;
            passwordRequired.style.display = 'inline';
            userPasswordInput.placeholder = 'Minimum 6 characters';
            userModalTitle.textContent = 'Add User';
            userModal.classList.remove('hidden');
            userFullNameInput.focus();
        });
    }

    async function openEditUserModal(id) {
        try {
            const res = await fetch(`ajax/users.php?action=get&id=${id}`);
            const result = await res.json();
            if (result.success) {
                const user = result.data;
                userFormId.value = user.id;
                userFullNameInput.value = user.full_name;
                userUsernameInput.value = user.username;
                userPasswordInput.required = false;
                passwordRequired.style.display = 'none';
                userPasswordInput.placeholder = 'Leave blank to keep current password';
                userPasswordInput.value = '';
                userRoleSelect.value = user.role_id;
                userModalTitle.textContent = 'Edit User';
                userModal.classList.remove('hidden');
                userFullNameInput.focus();
            } else {
                showToast(result.message, 'error');
            }
        } catch (err) {
            showToast('Failed to fetch user details.', 'error');
        }
    }

    function closeUserModal() {
        userModal.classList.add('hidden');
    }

    document.querySelectorAll('.closeUserModalBtn').forEach(btn => {
        btn.addEventListener('click', closeUserModal);
    });

    // Submit form (Add / Edit)
    userForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const id = userFormId.value;
        const action = id ? 'update' : 'create';

        const formData = new FormData(userForm);
        formData.append('action', action);
        if (!formData.has('csrf_token')) {
            formData.append('csrf_token', getCsrfToken());
        }

        try {
            const res = await fetch('ajax/users.php', {
                method: 'POST',
                body: formData
            });
            const result = await res.json();

            if (result.success) {
                showToast(result.message, 'success');
                closeUserModal();
                loadUsers();
            } else {
                showToast(result.message, 'error');
            }
        } catch (err) {
            showToast('Failed to save user. Network error.', 'error');
        }
    });

    // Delete User
    function openDeleteUserModal(id, name) {
        userToDeleteId = id;
        deleteUserMsg.textContent = `Are you sure you want to delete "${name}"? This action cannot be undone.`;
        deleteUserModal.classList.remove('hidden');
    }

    function closeDeleteUserModal() {
        deleteUserModal.classList.add('hidden');
        userToDeleteId = null;
    }

    document.querySelectorAll('.closeDeleteUserModalBtn').forEach(btn => {
        btn.addEventListener('click', closeDeleteUserModal);
    });

    if (confirmDeleteUserBtn) {
        confirmDeleteUserBtn.addEventListener('click', async () => {
            if (!userToDeleteId) return;

            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', userToDeleteId);
            formData.append('csrf_token', getCsrfToken());

            try {
                const res = await fetch('ajax/users.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await res.json();

                if (result.success) {
                    showToast(result.message, 'success');
                    closeDeleteUserModal();
                    loadUsers();
                } else {
                    showToast(result.message, 'error');
                    closeDeleteUserModal();
                }
            } catch (err) {
                showToast('Failed to delete user.', 'error');
                closeDeleteUserModal();
            }
        });
    }

    // Init
    loadRoles();
    loadUsers();
});
</script>
