document.addEventListener('DOMContentLoaded', () => {
    // --------------------------------------------------------
    // 1. Notification System
    // --------------------------------------------------------
    function getToastContainer() {
        let container = document.getElementById('toastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toastContainer';
            container.className = 'fixed top-5 right-5 z-[9999] flex flex-col gap-2 max-w-sm w-full pointer-events-none';
            document.body.appendChild(container);
        }
        return container;
    }

    window.showToast = function(message, type = 'success', duration = 3500) {
        const container = getToastContainer();
        const toast = document.createElement('div');
        toast.className = `pointer-events-auto flex items-center justify-between p-4 rounded-xl shadow-lg border text-sm transition-all duration-300 transform translate-x-full opacity-0 ${
            type === 'success'
                ? 'bg-slate-900 text-white border-teal/40'
                : 'bg-red-900 text-white border-red-500/40'
        }`;

        const iconClass = type === 'success' ? 'bi-check-circle-fill text-teal' : 'bi-exclamation-triangle-fill text-red-400';

        toast.innerHTML = `
            <div class="flex items-center gap-3">
                <i class="bi ${iconClass} text-lg shrink-0"></i>
                <span class="font-medium">${escapeHtml(message)}</span>
            </div>
            <button type="button" class="ml-4 text-slate-400 hover:text-white transition-colors cursor-pointer" onclick="this.parentElement.remove()">
                <i class="bi bi-x-lg text-xs"></i>
            </button>
        `;

        container.appendChild(toast);

        // Animate in
        requestAnimationFrame(() => {
            toast.classList.remove('translate-x-full', 'opacity-0');
        });

        // Auto remove
        setTimeout(() => {
            toast.classList.add('translate-x-full', 'opacity-0');
            setTimeout(() => {
                if (toast.parentElement) toast.remove();
            }, 300);
        }, duration);
    };

    // --------------------------------------------------------
    // 2. Utility Helpers
    // --------------------------------------------------------
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

    function formatCurrency(value) {
        return '$' + Number(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    // --------------------------------------------------------
    // 3. Category Management Module
    // --------------------------------------------------------
    const categoriesTableBody = document.getElementById('categoriesTableBody');
    if (categoriesTableBody) {
        let categoriesData = [];
        let categoryToDeleteId = null;

        const categoryModal = document.getElementById('categoryModal');
        const categoryForm = document.getElementById('categoryForm');
        const categoryModalTitle = document.getElementById('categoryModalTitle');
        const categoryFormId = document.getElementById('categoryFormId');
        const categoryNameInput = document.getElementById('categoryNameInput');
        const categorySearchInput = document.getElementById('categorySearchInput');

        const deleteCategoryModal = document.getElementById('deleteCategoryModal');
        const deleteCategoryMsg = document.getElementById('deleteCategoryMsg');
        const confirmDeleteCategoryBtn = document.getElementById('confirmDeleteCategoryBtn');

        // Fetch & render categories
        async function fetchCategories() {
            try {
                const res = await fetch('ajax/categories.php?action=list');
                const result = await res.json();

                if (result.success) {
                    categoriesData = result.data || [];
                    renderCategoriesTable(categoriesData);
                } else {
                    categoriesTableBody.innerHTML = `<tr><td colspan="4" class="px-4 py-6 text-center text-red-500 text-sm">${escapeHtml(result.message)}</td></tr>`;
                }
            } catch (err) {
                console.error('Error fetching categories:', err);
                categoriesTableBody.innerHTML = '<tr><td colspan="4" class="px-4 py-6 text-center text-red-500 text-sm">Failed to load categories. Please try again.</td></tr>';
            }
        }

        function renderCategoriesTable(data) {
            const query = (categorySearchInput ? categorySearchInput.value : '').trim().toLowerCase();
            const filtered = data.filter(c => c.category_name.toLowerCase().includes(query));

            if (filtered.length === 0) {
                categoriesTableBody.innerHTML = `<tr><td colspan="4" class="px-4 py-8 text-center text-slate-400 text-sm">No categories found matching your search.</td></tr>`;
                return;
            }

            categoriesTableBody.innerHTML = filtered.map(c => `
                <tr class="hover:bg-slate-50/80 transition-colors">
                    <td class="px-4 py-3 font-mono text-xs text-slate-500">#${c.id}</td>
                    <td class="px-4 py-3 font-semibold text-slate-800">${escapeHtml(c.category_name)}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                            <i class="bi bi-box-seam text-slate-400"></i>
                            ${c.product_count} product(s)
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-1">
                            <button type="button" class="editCategoryBtn text-slate-500 hover:text-teal p-1.5 rounded-lg hover:bg-teal/10 transition-colors cursor-pointer" data-id="${c.id}" title="Edit Category">
                                <i class="bi bi-pencil-square text-base"></i>
                            </button>
                            <button type="button" class="deleteCategoryBtn text-slate-500 hover:text-red-600 p-1.5 rounded-lg hover:bg-red-50 transition-colors cursor-pointer" data-id="${c.id}" data-name="${escapeHtml(c.category_name)}" title="Delete Category">
                                <i class="bi bi-trash3 text-base"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');

            // Attach row button events
            document.querySelectorAll('.editCategoryBtn').forEach(btn => {
                btn.addEventListener('click', () => openEditCategoryModal(btn.dataset.id));
            });

            document.querySelectorAll('.deleteCategoryBtn').forEach(btn => {
                btn.addEventListener('click', () => openDeleteCategoryModal(btn.dataset.id, btn.dataset.name));
            });
        }

        if (categorySearchInput) {
            categorySearchInput.addEventListener('input', () => renderCategoriesTable(categoriesData));
        }

        // Modal Open/Close helpers
        const openAddCategoryBtn = document.getElementById('openAddCategoryBtn');
        if (openAddCategoryBtn) {
            openAddCategoryBtn.addEventListener('click', () => {
                categoryForm.reset();
                categoryFormId.value = '';
                categoryModalTitle.textContent = 'Add Category';
                categoryModal.classList.remove('hidden');
                categoryNameInput.focus();
            });
        }

        async function openEditCategoryModal(id) {
            try {
                const res = await fetch(`ajax/categories.php?action=get&id=${id}`);
                const result = await res.json();
                if (result.success) {
                    categoryFormId.value = result.data.id;
                    categoryNameInput.value = result.data.category_name;
                    categoryModalTitle.textContent = 'Edit Category';
                    categoryModal.classList.remove('hidden');
                    categoryNameInput.focus();
                } else {
                    showToast(result.message, 'error');
                }
            } catch (err) {
                showToast('Failed to fetch category details.', 'error');
            }
        }

        function closeCategoryModal() {
            categoryModal.classList.add('hidden');
        }

        document.querySelectorAll('.closeModalBtn').forEach(btn => {
            btn.addEventListener('click', closeCategoryModal);
        });

        // Submit form (Add / Edit)
        categoryForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const id = categoryFormId.value;
            const action = id ? 'update' : 'create';

            const formData = new FormData(categoryForm);
            formData.append('action', action);
            if (!formData.has('csrf_token')) {
                formData.append('csrf_token', getCsrfToken());
            }

            try {
                const res = await fetch('ajax/categories.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await res.json();

                if (result.success) {
                    showToast(result.message, 'success');
                    closeCategoryModal();
                    fetchCategories();
                } else {
                    showToast(result.message, 'error');
                }
            } catch (err) {
                showToast('Failed to save category. Network error.', 'error');
            }
        });

        // Delete Category logic
        function openDeleteCategoryModal(id, name) {
            categoryToDeleteId = id;
            deleteCategoryMsg.textContent = `Are you sure you want to delete category "${name}"? This action cannot be undone.`;
            deleteCategoryModal.classList.remove('hidden');
        }

        function closeDeleteCategoryModal() {
            deleteCategoryModal.classList.add('hidden');
            categoryToDeleteId = null;
        }

        document.querySelectorAll('.closeDeleteCategoryModalBtn').forEach(btn => {
            btn.addEventListener('click', closeDeleteCategoryModal);
        });

        if (confirmDeleteCategoryBtn) {
            confirmDeleteCategoryBtn.addEventListener('click', async () => {
                if (!categoryToDeleteId) return;

                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', categoryToDeleteId);
                formData.append('csrf_token', getCsrfToken());

                try {
                    const res = await fetch('ajax/categories.php', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await res.json();

                    if (result.success) {
                        showToast(result.message, 'success');
                        closeDeleteCategoryModal();
                        fetchCategories();
                    } else {
                        showToast(result.message, 'error');
                        closeDeleteCategoryModal();
                    }
                } catch (err) {
                    showToast('Failed to delete category.', 'error');
                    closeDeleteCategoryModal();
                }
            });
        }

        // Initial Load
        fetchCategories();
    }

    // --------------------------------------------------------
    // 4. Item Management Module
    // --------------------------------------------------------
    const itemsTableBody = document.getElementById('itemsTableBody');
    if (itemsTableBody) {
        let itemToDeleteId = null;

        const itemModal = document.getElementById('itemModal');
        const itemForm = document.getElementById('itemForm');
        const itemModalTitle = document.getElementById('itemModalTitle');
        const itemFormId = document.getElementById('itemFormId');
        const itemSkuInput = document.getElementById('itemSkuInput');
        const itemNameInput = document.getElementById('itemNameInput');
        const itemCategorySelect = document.getElementById('itemCategorySelect');
        const itemPriceInput = document.getElementById('itemPriceInput');
        const itemQuantityInput = document.getElementById('itemQuantityInput');
        const itemThresholdInput = document.getElementById('itemThresholdInput');

        const itemSearchInput = document.getElementById('itemSearchInput');
        const itemCategoryFilter = document.getElementById('itemCategoryFilter');
        const itemLowStockFilter = document.getElementById('itemLowStockFilter');

        const deleteItemModal = document.getElementById('deleteItemModal');
        const deleteItemMsg = document.getElementById('deleteItemMsg');
        const confirmDeleteItemBtn = document.getElementById('confirmDeleteItemBtn');

        // Fetch categories for select dropdowns
        async function fetchCategoryOptions() {
            try {
                const res = await fetch('ajax/categories.php?action=list');
                const result = await res.json();
                if (result.success) {
                    const categories = result.data || [];
                    
                    // Filter dropdown
                    itemCategoryFilter.innerHTML = '<option value="">All Categories</option>' +
                        categories.map(c => `<option value="${c.id}">${escapeHtml(c.category_name)}</option>`).join('');

                    // Form select dropdown
                    itemCategorySelect.innerHTML = '<option value="">Select Category</option>' +
                        categories.map(c => `<option value="${c.id}">${escapeHtml(c.category_name)}</option>`).join('');
                }
            } catch (err) {
                console.error('Error fetching category options:', err);
            }
        }

        // Fetch & render items
        async function fetchItems() {
            const search = itemSearchInput ? itemSearchInput.value.trim() : '';
            const categoryId = itemCategoryFilter ? itemCategoryFilter.value : '';
            const lowStock = itemLowStockFilter && itemLowStockFilter.checked ? '1' : '0';

            const params = new URLSearchParams({
                action: 'list',
                search: search,
                category_id: categoryId,
                low_stock: lowStock
            });

            try {
                const res = await fetch(`ajax/items.php?${params.toString()}`);
                const result = await res.json();

                if (result.success) {
                    renderItemsTable(result.data || []);
                } else {
                    itemsTableBody.innerHTML = `<tr><td colspan="8" class="px-4 py-6 text-center text-red-500 text-sm">${escapeHtml(result.message)}</td></tr>`;
                }
            } catch (err) {
                console.error('Error fetching items:', err);
                itemsTableBody.innerHTML = '<tr><td colspan="8" class="px-4 py-6 text-center text-red-500 text-sm">Failed to load items. Please try again.</td></tr>';
            }
        }

        function getStockBadge(quantity, threshold) {
            if (quantity === 0) {
                return '<span class="inline-flex items-center gap-1 bg-red-100 text-red-700 text-xs font-semibold px-2.5 py-0.5 rounded-full"><i class="bi bi-x-circle-fill"></i> Out of Stock</span>';
            } else if (quantity <= threshold) {
                return '<span class="inline-flex items-center gap-1 bg-amber-100 text-amber-800 text-xs font-semibold px-2.5 py-0.5 rounded-full"><i class="bi bi-exclamation-triangle-fill"></i> Low Stock</span>';
            }
            return '<span class="inline-flex items-center gap-1 bg-green-100 text-green-700 text-xs font-semibold px-2.5 py-0.5 rounded-full"><i class="bi bi-check-circle-fill"></i> In Stock</span>';
        }

        function renderItemsTable(items) {
            if (items.length === 0) {
                itemsTableBody.innerHTML = `<tr><td colspan="8" class="px-4 py-8 text-center text-slate-400 text-sm">No items found.</td></tr>`;
                return;
            }

            itemsTableBody.innerHTML = items.map(item => `
                <tr class="hover:bg-slate-50/80 transition-colors">
                    <td class="px-4 py-3 font-mono text-xs font-semibold text-slate-600">${escapeHtml(item.sku)}</td>
                    <td class="px-4 py-3 font-semibold text-slate-800">${escapeHtml(item.name)}</td>
                    <td class="px-4 py-3 text-slate-600">${escapeHtml(item.category_name)}</td>
                    <td class="px-4 py-3 font-medium text-slate-700">${formatCurrency(item.price)}</td>
                    <td class="px-4 py-3 font-bold text-slate-800">${Number(item.quantity).toLocaleString()}</td>
                    <td class="px-4 py-3 text-slate-500 text-xs">${item.low_stock_threshold}</td>
                    <td class="px-4 py-3">${getStockBadge(Number(item.quantity), Number(item.low_stock_threshold))}</td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-1">
                            <button type="button" class="editItemBtn text-slate-500 hover:text-teal p-1.5 rounded-lg hover:bg-teal/10 transition-colors cursor-pointer" data-id="${item.id}" title="Edit Item">
                                <i class="bi bi-pencil-square text-base"></i>
                            </button>
                            <button type="button" class="deleteItemBtn text-slate-500 hover:text-red-600 p-1.5 rounded-lg hover:bg-red-50 transition-colors cursor-pointer" data-id="${item.id}" data-name="${escapeHtml(item.name)}" title="Delete Item">
                                <i class="bi bi-trash3 text-base"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');

            // Attach row action listeners
            document.querySelectorAll('.editItemBtn').forEach(btn => {
                btn.addEventListener('click', () => openEditItemModal(btn.dataset.id));
            });

            document.querySelectorAll('.deleteItemBtn').forEach(btn => {
                btn.addEventListener('click', () => openDeleteItemModal(btn.dataset.id, btn.dataset.name));
            });
        }

        // Filters debounce & listeners
        let searchTimeout = null;
        if (itemSearchInput) {
            itemSearchInput.addEventListener('input', () => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(fetchItems, 250);
            });
        }
        if (itemCategoryFilter) {
            itemCategoryFilter.addEventListener('change', fetchItems);
        }
        if (itemLowStockFilter) {
            itemLowStockFilter.addEventListener('change', fetchItems);
        }

        // Modal Open/Close
        const openAddItemBtn = document.getElementById('openAddItemBtn');
        if (openAddItemBtn) {
            openAddItemBtn.addEventListener('click', () => {
                itemForm.reset();
                itemFormId.value = '';
                itemModalTitle.textContent = 'Add Item';
                itemThresholdInput.value = 10;
                itemModal.classList.remove('hidden');
                itemSkuInput.focus();
            });
        }

        async function openEditItemModal(id) {
            try {
                const res = await fetch(`ajax/items.php?action=get&id=${id}`);
                const result = await res.json();
                if (result.success) {
                    const item = result.data;
                    itemFormId.value = item.id;
                    itemSkuInput.value = item.sku;
                    itemNameInput.value = item.name;
                    itemCategorySelect.value = item.category_id;
                    itemPriceInput.value = item.price;
                    itemQuantityInput.value = item.quantity;
                    itemThresholdInput.value = item.low_stock_threshold;

                    itemModalTitle.textContent = 'Edit Item';
                    itemModal.classList.remove('hidden');
                    itemSkuInput.focus();
                } else {
                    showToast(result.message, 'error');
                }
            } catch (err) {
                showToast('Failed to fetch item details.', 'error');
            }
        }

        function closeItemModal() {
            itemModal.classList.add('hidden');
        }

        document.querySelectorAll('.closeItemModalBtn').forEach(btn => {
            btn.addEventListener('click', closeItemModal);
        });

        // Submit Item Form (Add / Edit)
        itemForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const id = itemFormId.value;
            const action = id ? 'update' : 'create';

            const formData = new FormData(itemForm);
            formData.append('action', action);
            if (!formData.has('csrf_token')) {
                formData.append('csrf_token', getCsrfToken());
            }

            try {
                const res = await fetch('ajax/items.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await res.json();

                if (result.success) {
                    showToast(result.message, 'success');
                    closeItemModal();
                    fetchItems();
                } else {
                    showToast(result.message, 'error');
                }
            } catch (err) {
                showToast('Failed to save item. Network error.', 'error');
            }
        });

        // Delete Item
        function openDeleteItemModal(id, name) {
            itemToDeleteId = id;
            deleteItemMsg.textContent = `Are you sure you want to delete "${name}"? This action will mark the product as deleted.`;
            deleteItemModal.classList.remove('hidden');
        }

        function closeDeleteItemModal() {
            deleteItemModal.classList.add('hidden');
            itemToDeleteId = null;
        }

        document.querySelectorAll('.closeDeleteItemModalBtn').forEach(btn => {
            btn.addEventListener('click', closeDeleteItemModal);
        });

        if (confirmDeleteItemBtn) {
            confirmDeleteItemBtn.addEventListener('click', async () => {
                if (!itemToDeleteId) return;

                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', itemToDeleteId);
                formData.append('csrf_token', getCsrfToken());

                try {
                    const res = await fetch('ajax/items.php', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await res.json();

                    if (result.success) {
                        showToast(result.message, 'success');
                        closeDeleteItemModal();
                        fetchItems();
                    } else {
                        showToast(result.message, 'error');
                        closeDeleteItemModal();
                    }
                } catch (err) {
                    showToast('Failed to delete item.', 'error');
                    closeDeleteItemModal();
                }
            });
        }

        // Init
        fetchCategoryOptions();
        fetchItems();
    }

    // --------------------------------------------------------
    // 5. Batch Management Module
    // --------------------------------------------------------
    const batchesTableBody = document.getElementById('batchesTableBody');
    if (batchesTableBody) {
        let batchToDeleteId = null;

        const batchModal = document.getElementById('batchModal');
        const batchForm = document.getElementById('batchForm');
        const batchModalTitle = document.getElementById('batchModalTitle');
        const batchFormId = document.getElementById('batchFormId');
        const batchItemSelect = document.getElementById('batchItemSelect');
        const batchNumberInput = document.getElementById('batchNumberInput');
        const batchQuantityInput = document.getElementById('batchQuantityInput');
        const batchExpiryInput = document.getElementById('batchExpiryInput');
        const batchItemFilter = document.getElementById('batchItemFilter');

        const deleteBatchModal = document.getElementById('deleteBatchModal');
        const deleteBatchMsg = document.getElementById('deleteBatchMsg');
        const confirmDeleteBatchBtn = document.getElementById('confirmDeleteBatchBtn');

        // Fetch items for dropdowns
        async function fetchBatchItemOptions() {
            try {
                const res = await fetch('ajax/items.php?action=list');
                const result = await res.json();
                if (result.success) {
                    const items = result.data || [];
                    const options = items.map(i => `<option value="${i.id}">${escapeHtml(i.name)} (${escapeHtml(i.sku)})</option>`).join('');

                    batchItemFilter.innerHTML = '<option value="">All Items</option>' + options;
                    batchItemSelect.innerHTML = '<option value="">Select Item</option>' + options;
                }
            } catch (err) {
                console.error('Error fetching items for batch dropdowns:', err);
            }
        }

        // Fetch & render batches
        async function fetchBatches() {
            const itemId = batchItemFilter ? batchItemFilter.value : '';
            const params = new URLSearchParams({ action: 'list' });
            if (itemId) params.append('item_id', itemId);

            try {
                const res = await fetch(`ajax/batches.php?${params.toString()}`);
                const result = await res.json();

                if (result.success) {
                    renderBatchesTable(result.data || []);
                } else {
                    batchesTableBody.innerHTML = `<tr><td colspan="8" class="px-4 py-6 text-center text-red-500 text-sm">${escapeHtml(result.message)}</td></tr>`;
                }
            } catch (err) {
                console.error('Error fetching batches:', err);
                batchesTableBody.innerHTML = '<tr><td colspan="8" class="px-4 py-6 text-center text-red-500 text-sm">Failed to load batches. Please try again.</td></tr>';
            }
        }

        function getBatchStatusBadge(status) {
            const badges = {
                'Expired': 'bg-red-100 text-red-700',
                'Near Expiry': 'bg-amber-100 text-amber-700',
                'Safe': 'bg-emerald-100 text-emerald-700',
            };
            const classes = badges[status] || 'bg-slate-100 text-slate-700';
            return `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold ${classes}">${escapeHtml(status)}</span>`;
        }

        function formatDate(dateStr) {
            if (!dateStr) return '--';
            const d = new Date(dateStr.replace(' ', 'T'));
            return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
        }

        function renderBatchesTable(batches) {
            if (batches.length === 0) {
                batchesTableBody.innerHTML = '<tr><td colspan="8" class="px-4 py-8 text-center text-slate-400 text-sm">No batches found.</td></tr>';
                return;
            }

            batchesTableBody.innerHTML = batches.map(b => `
                <tr class="hover:bg-slate-50/80 transition-colors">
                    <td class="px-4 py-3 font-mono text-xs text-slate-500">#${b.id}</td>
                    <td class="px-4 py-3 font-mono text-xs font-semibold text-slate-700">${escapeHtml(b.batch_number)}</td>
                    <td class="px-4 py-3 text-slate-700 font-medium">${escapeHtml(b.item_name)}</td>
                    <td class="px-4 py-3 font-bold text-slate-800">${Number(b.quantity).toLocaleString()}</td>
                    <td class="px-4 py-3 text-slate-600">${formatDate(b.expiry_date)}</td>
                    <td class="px-4 py-3">${getBatchStatusBadge(b.status)}</td>
                    <td class="px-4 py-3 text-slate-400 text-xs">${formatDate(b.created_at)}</td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-1">
                            <button type="button" class="editBatchBtn text-slate-500 hover:text-teal p-1.5 rounded-lg hover:bg-teal/10 transition-colors cursor-pointer" data-id="${b.id}" title="Edit Batch">
                                <i class="bi bi-pencil-square text-base"></i>
                            </button>
                            <button type="button" class="deleteBatchBtn text-slate-500 hover:text-red-600 p-1.5 rounded-lg hover:bg-red-50 transition-colors cursor-pointer" data-id="${b.id}" data-number="${escapeHtml(b.batch_number)}" title="Delete Batch">
                                <i class="bi bi-trash3 text-base"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');

            document.querySelectorAll('.editBatchBtn').forEach(btn => {
                btn.addEventListener('click', () => openEditBatchModal(btn.dataset.id));
            });

            document.querySelectorAll('.deleteBatchBtn').forEach(btn => {
                btn.addEventListener('click', () => openDeleteBatchModal(btn.dataset.id, btn.dataset.number));
            });
        }

        // Filter listener
        if (batchItemFilter) {
            batchItemFilter.addEventListener('change', fetchBatches);
        }

        // Modal Open/Close
        const openAddBatchBtn = document.getElementById('openAddBatchBtn');
        if (openAddBatchBtn) {
            openAddBatchBtn.addEventListener('click', () => {
                batchForm.reset();
                batchFormId.value = '';
                batchModalTitle.textContent = 'Add Batch';
                batchModal.classList.remove('hidden');
                batchNumberInput.focus();
            });
        }

        async function openEditBatchModal(id) {
            try {
                const res = await fetch(`ajax/batches.php?action=get&id=${id}`);
                const result = await res.json();
                if (result.success) {
                    const batch = result.data;
                    batchFormId.value = batch.id;
                    batchItemSelect.value = batch.item_id;
                    batchNumberInput.value = batch.batch_number;
                    batchQuantityInput.value = batch.quantity;
                    batchExpiryInput.value = batch.expiry_date;

                    batchModalTitle.textContent = 'Edit Batch';
                    batchModal.classList.remove('hidden');
                    batchNumberInput.focus();
                } else {
                    showToast(result.message, 'error');
                }
            } catch (err) {
                showToast('Failed to fetch batch details.', 'error');
            }
        }

        function closeBatchModal() {
            batchModal.classList.add('hidden');
        }

        document.querySelectorAll('.closeBatchModalBtn').forEach(btn => {
            btn.addEventListener('click', closeBatchModal);
        });

        // Submit form
        batchForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const id = batchFormId.value;
            const action = id ? 'update' : 'create';

            const formData = new FormData(batchForm);
            formData.append('action', action);
            if (!formData.has('csrf_token')) {
                formData.append('csrf_token', getCsrfToken());
            }

            try {
                const res = await fetch('ajax/batches.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await res.json();

                if (result.success) {
                    showToast(result.message, 'success');
                    closeBatchModal();
                    fetchBatches();
                } else {
                    showToast(result.message, 'error');
                }
            } catch (err) {
                showToast('Failed to save batch. Network error.', 'error');
            }
        });

        // Delete Batch
        function openDeleteBatchModal(id, number) {
            batchToDeleteId = id;
            deleteBatchMsg.textContent = `Are you sure you want to delete batch "${number}"? This action cannot be undone.`;
            deleteBatchModal.classList.remove('hidden');
        }

        function closeDeleteBatchModal() {
            deleteBatchModal.classList.add('hidden');
            batchToDeleteId = null;
        }

        document.querySelectorAll('.closeDeleteBatchModalBtn').forEach(btn => {
            btn.addEventListener('click', closeDeleteBatchModal);
        });

        if (confirmDeleteBatchBtn) {
            confirmDeleteBatchBtn.addEventListener('click', async () => {
                if (!batchToDeleteId) return;

                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', batchToDeleteId);
                formData.append('csrf_token', getCsrfToken());

                try {
                    const res = await fetch('ajax/batches.php', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await res.json();

                    if (result.success) {
                        showToast(result.message, 'success');
                        closeDeleteBatchModal();
                        fetchBatches();
                    } else {
                        showToast(result.message, 'error');
                        closeDeleteBatchModal();
                    }
                } catch (err) {
                    showToast('Failed to delete batch.', 'error');
                    closeDeleteBatchModal();
                }
            });
        }

        // Init
        fetchBatchItemOptions();
        fetchBatches();
    }

    // --------------------------------------------------------
    // 6. Activity Log Module
    // --------------------------------------------------------
    const activityLogTableBody = document.getElementById('activityLogTableBody');
    if (activityLogTableBody) {
        let currentPage = 1;
        let totalPages = 1;
        const limit = 20;

        const logUserFilter = document.getElementById('logUserFilter');
        const logActionFilter = document.getElementById('logActionFilter');
        const logDateFrom = document.getElementById('logDateFrom');
        const logDateTo = document.getElementById('logDateTo');
        const applyLogFilters = document.getElementById('applyLogFilters');
        const resetLogFilters = document.getElementById('resetLogFilters');
        const logPagination = document.getElementById('logPagination');
        const logPaginationInfo = document.getElementById('logPaginationInfo');
        const logPrevPage = document.getElementById('logPrevPage');
        const logNextPage = document.getElementById('logNextPage');
        const logPageNumbers = document.getElementById('logPageNumbers');

        function getActionLogBadge(actionType) {
            const badges = {
                'create': 'bg-teal-100 text-teal-700',
                'delete': 'bg-red-100 text-red-700',
                'stock_in': 'bg-green-100 text-green-700',
                'stock_out': 'bg-amber-100 text-amber-700',
                'update': 'bg-blue-100 text-blue-700',
            };
            const classes = badges[actionType] || 'bg-slate-100 text-slate-700';
            const label = actionType.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
            return `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold ${classes}">${escapeHtml(label)}</span>`;
        }

        function formatTimestamp(dateStr) {
            if (!dateStr) return '--';
            const d = new Date(dateStr.replace(' ', 'T'));
            return d.toLocaleString('en-US', {
                year: 'numeric', month: 'short', day: 'numeric',
                hour: '2-digit', minute: '2-digit'
            });
        }

        function renderPagination() {
            logPaginationInfo.textContent = `Page ${currentPage} of ${totalPages}`;

            logPrevPage.disabled = currentPage <= 1;
            logNextPage.disabled = currentPage >= totalPages;

            // Render page number buttons
            let pageHtml = '';
            const maxVisible = 5;
            let start = Math.max(1, currentPage - Math.floor(maxVisible / 2));
            let end = Math.min(totalPages, start + maxVisible - 1);
            if (end - start + 1 < maxVisible) {
                start = Math.max(1, end - maxVisible + 1);
            }

            for (let i = start; i <= end; i++) {
                const active = i === currentPage
                    ? 'bg-teal text-white border-teal'
                    : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50';
                pageHtml += `<button type="button" class="logPageBtn px-3 py-1.5 text-sm font-medium border rounded-lg cursor-pointer ${active}" data-page="${i}">${i}</button>`;
            }
            logPageNumbers.innerHTML = pageHtml;

            document.querySelectorAll('.logPageBtn').forEach(btn => {
                btn.addEventListener('click', () => {
                    currentPage = parseInt(btn.dataset.page);
                    fetchActivityLogs();
                });
            });
        }

        // Fetch users for filter dropdown
        async function fetchLogUsers() {
            try {
                const res = await fetch('ajax/users.php');
                const result = await res.json();
                if (result.success) {
                    const users = result.data || [];
                    logUserFilter.innerHTML = '<option value="">All Users</option>' +
                        users.map(u => `<option value="${u.id}">${escapeHtml(u.full_name)}</option>`).join('');
                }
            } catch (err) {
                console.error('Error fetching users:', err);
            }
        }

        async function fetchActivityLogs() {
            const params = new URLSearchParams({
                action: 'list',
                page: currentPage,
                limit: limit,
            });

            const userId = logUserFilter ? logUserFilter.value : '';
            const actionType = logActionFilter ? logActionFilter.value : '';
            const dateFrom = logDateFrom ? logDateFrom.value : '';
            const dateTo = logDateTo ? logDateTo.value : '';

            if (userId) params.append('user_id', userId);
            if (actionType) params.append('action_type', actionType);
            if (dateFrom) params.append('date_from', dateFrom);
            if (dateTo) params.append('date_to', dateTo);

            try {
                const res = await fetch(`ajax/activity_log.php?${params.toString()}`);
                const result = await res.json();

                if (result.success) {
                    renderActivityLogTable(result.data || []);
                    totalPages = result.pagination ? result.pagination.totalPages : 1;
                    currentPage = result.pagination ? result.pagination.currentPage : 1;
                    renderPagination();
                    logPagination.classList.remove('hidden');
                } else {
                    activityLogTableBody.innerHTML = `<tr><td colspan="8" class="px-4 py-6 text-center text-red-500 text-sm">${escapeHtml(result.message)}</td></tr>`;
                    logPagination.classList.add('hidden');
                }
            } catch (err) {
                console.error('Error fetching activity logs:', err);
                activityLogTableBody.innerHTML = '<tr><td colspan="8" class="px-4 py-6 text-center text-red-500 text-sm">Failed to load activity logs. Please try again.</td></tr>';
                logPagination.classList.add('hidden');
            }
        }

        function renderActivityLogTable(logs) {
            if (logs.length === 0) {
                activityLogTableBody.innerHTML = '<tr><td colspan="8" class="px-4 py-8 text-center text-slate-400 text-sm">No activity logs found matching your criteria.</td></tr>';
                return;
            }

            activityLogTableBody.innerHTML = logs.map(log => `
                <tr class="hover:bg-slate-50/80 transition-colors">
                    <td class="px-4 py-3 font-mono text-xs text-slate-500">#${log.id}</td>
                    <td class="px-4 py-3 font-semibold text-slate-700 text-xs">${escapeHtml(log.user_name || 'System')}</td>
                    <td class="px-4 py-3 text-slate-600">${escapeHtml(log.item_name || '--')}</td>
                    <td class="px-4 py-3">${getActionLogBadge(log.action_type)}</td>
                    <td class="px-4 py-3 text-slate-600">${log.old_quantity !== null ? Number(log.old_quantity).toLocaleString() : '--'}</td>
                    <td class="px-4 py-3 text-slate-600">${log.new_quantity !== null ? Number(log.new_quantity).toLocaleString() : '--'}</td>
                    <td class="px-4 py-3 text-slate-500 text-xs max-w-xs truncate" title="${escapeHtml(log.description || '')}">${escapeHtml(log.description || '--')}</td>
                    <td class="px-4 py-3 text-slate-400 text-xs whitespace-nowrap">${formatTimestamp(log.created_at)}</td>
                </tr>
            `).join('');
        }

        // Filter events
        if (applyLogFilters) {
            applyLogFilters.addEventListener('click', () => {
                currentPage = 1;
                fetchActivityLogs();
            });
        }

        if (resetLogFilters) {
            resetLogFilters.addEventListener('click', () => {
                if (logUserFilter) logUserFilter.value = '';
                if (logActionFilter) logActionFilter.value = '';
                if (logDateFrom) logDateFrom.value = '';
                if (logDateTo) logDateTo.value = '';
                currentPage = 1;
                fetchActivityLogs();
            });
        }

        if (logPrevPage) {
            logPrevPage.addEventListener('click', () => {
                if (currentPage > 1) {
                    currentPage--;
                    fetchActivityLogs();
                }
            });
        }

        if (logNextPage) {
            logNextPage.addEventListener('click', () => {
                if (currentPage < totalPages) {
                    currentPage++;
                    fetchActivityLogs();
                }
            });
        }

        // Init
        fetchLogUsers();
        fetchActivityLogs();
    }
});
