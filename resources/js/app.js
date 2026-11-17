import './bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('order-form');
    if (!form) return;

    const cart = new Map();
    const menuItems = new Map();
    const currency = new Intl.NumberFormat('id-ID');
    const orderItems = document.getElementById('order-items');
    const inputContainer = document.getElementById('cart-inputs');
    const emptyState = document.getElementById('order-empty');
    const submitButton = document.getElementById('save-order');

    document.querySelectorAll('[data-add-menu]').forEach((button) => {
        const id = button.dataset.addMenu;
        menuItems.set(id, {
            id,
            name: button.dataset.name,
            category: button.dataset.category,
            price: Number(button.dataset.price),
        });
        button.addEventListener('click', () => updateQuantity(id, 1));
    });

    document.querySelectorAll('[data-remove-menu]').forEach((button) => {
        button.addEventListener('click', () => updateQuantity(button.dataset.removeMenu, -1));
    });

    const initialCart = JSON.parse(form.dataset.initialCart || '{}');
    Object.entries(initialCart).forEach(([id, quantity]) => {
        const parsedQuantity = Number(quantity);
        if (menuItems.has(id) && Number.isInteger(parsedQuantity) && parsedQuantity > 0) {
            cart.set(id, parsedQuantity);
        }
    });

    function rupiah(value) {
        return `Rp${currency.format(Math.round(value))}`;
    }

    function updateQuantity(id, amount) {
        const nextQuantity = (cart.get(id) || 0) + amount;
        if (nextQuantity <= 0) cart.delete(id);
        else cart.set(id, nextQuantity);
        renderOrder();
    }

    function renderOrder() {
        orderItems.replaceChildren();
        inputContainer.replaceChildren();
        let subtotal = 0;
        let totalCount = 0;

        cart.forEach((quantity, id) => {
            const item = menuItems.get(id);
            subtotal += item.price * quantity;
            totalCount += quantity;

            const row = document.createElement('div');
            row.className = 'order-row';
            const details = document.createElement('div');
            const name = document.createElement('div');
            name.className = 'order-item-name';
            name.textContent = item.name;
            const price = document.createElement('div');
            price.className = 'order-item-price';
            price.textContent = `${rupiah(item.price)} / item`;
            details.append(name, price);

            const actions = document.createElement('div');
            actions.className = 'order-row-actions';
            const quantityControls = document.createElement('div');
            quantityControls.className = 'quantity-control';
            const decrease = document.createElement('button');
            decrease.type = 'button';
            decrease.className = 'quantity-button';
            decrease.textContent = '-';
            decrease.setAttribute('aria-label', `Kurangi ${item.name}`);
            decrease.addEventListener('click', () => updateQuantity(id, -1));
            const count = document.createElement('span');
            count.className = 'quantity-value';
            count.textContent = String(quantity);
            const increase = document.createElement('button');
            increase.type = 'button';
            increase.className = 'quantity-button';
            increase.textContent = '+';
            increase.setAttribute('aria-label', `Tambah ${item.name}`);
            increase.addEventListener('click', () => updateQuantity(id, 1));
            quantityControls.append(decrease, count, increase);

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'remove-button';
            remove.textContent = 'Hapus';
            remove.addEventListener('click', () => updateQuantity(id, -quantity));
            actions.append(quantityControls, remove);
            row.append(details, actions);
            orderItems.append(row);

            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = `items[${id}]`;
            hiddenInput.value = String(quantity);
            inputContainer.append(hiddenInput);
        });

        const serviceCharge = Math.round(subtotal * 0.05);
        const tax = Math.round((subtotal + serviceCharge) * 0.10);
        document.getElementById('item-count').textContent = String(totalCount);
        document.getElementById('subtotal').textContent = rupiah(subtotal);
        document.getElementById('service-charge').textContent = rupiah(serviceCharge);
        document.getElementById('tax').textContent = rupiah(tax);
        document.getElementById('grand-total').textContent = rupiah(subtotal + serviceCharge + tax);
        emptyState.hidden = totalCount > 0;
        submitButton.disabled = totalCount === 0;
        document.querySelectorAll('[data-menu-count]').forEach((count) => {
            count.textContent = String(cart.get(count.dataset.menuCount) || 0);
        });
    }

    const search = document.getElementById('menu-search');
    const emptySearch = document.getElementById('menu-search-empty');
    let activeCategory = 'Semua';
    function filterMenu() {
        const query = search.value.trim().toLocaleLowerCase('id');
        let visibleCount = 0;
        document.querySelectorAll('[data-menu-row]').forEach((row) => {
            const matchesName = row.dataset.name.includes(query);
            const matchesCategory = activeCategory === 'Semua' || row.dataset.rowCategory === activeCategory;
            const visible = matchesName && matchesCategory;
            row.hidden = !visible;
            if (visible) visibleCount++;
        });
        emptySearch.hidden = visibleCount > 0;
    }

    search.addEventListener('input', filterMenu);
    document.querySelectorAll('[data-category-filter]').forEach((button) => {
        button.addEventListener('click', () => {
            activeCategory = button.dataset.categoryFilter;
            document.querySelector('.category-filter.active')?.classList.remove('active');
            button.classList.add('active');
            filterMenu();
        });
    });

    renderOrder();
});

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('recipe-form');
    if (!form) return;

    const materialSelect = document.getElementById('recipe-material');
    const amountInput = document.getElementById('recipe-amount');
    const unitDisplay = document.getElementById('recipe-unit');
    const rows = document.getElementById('recipe-rows');
    const emptyState = document.getElementById('recipe-empty-state');
    if (!materialSelect || !amountInput || !unitDisplay || !rows) return;

    const menuId = form.querySelector('[name="menu_id"]').value;
    const recipeBadge = document.querySelector(`[data-recipe-status="${menuId}"]`);

    function updateSelectedUnit() {
        unitDisplay.textContent = materialSelect.selectedOptions[0]?.dataset.unit || '-';
    }

    function updateRecipeState() {
        if (!emptyState) return;

        const materialCount = rows.querySelectorAll('[data-recipe-row]:not([hidden])').length;
        emptyState.textContent = materialCount > 0
            ? 'Bahan sudah ditambahkan. Klik Simpan Resep untuk menyimpannya.'
            : 'Resep belum dibuat untuk menu ini. Tambahkan minimal satu bahan dan takaran untuk membuat formulanya.';
        if (recipeBadge) {
            recipeBadge.textContent = materialCount > 0 ? `Draf: ${materialCount} bahan` : 'Belum ada resep';
        }
    }

    function updateAvailableMaterials() {
        const usedIds = new Set(
            [...rows.querySelectorAll('[data-recipe-row]:not([hidden])')].map((row) => row.dataset.bahanId)
        );
        [...materialSelect.options].forEach((option) => {
            if (option.value) option.disabled = usedIds.has(option.value);
        });
    }

    function removeRecipeRow(button) {
        const row = button.closest('[data-recipe-row]');
        const input = row.querySelector('input');
        input.value = '';
        input.disabled = true;
        row.hidden = true;
        updateAvailableMaterials();
        updateRecipeState();
    }

    rows.querySelectorAll('[data-remove-recipe]').forEach((button) => {
        button.addEventListener('click', () => removeRecipeRow(button));
    });

    materialSelect.addEventListener('change', updateSelectedUnit);

    document.getElementById('add-recipe-material').addEventListener('click', () => {
        const option = materialSelect.selectedOptions[0];
        const id = materialSelect.value;
        const amount = Number(amountInput.value);
        if (!id) {
            materialSelect.reportValidity();
            return;
        }
        if (!Number.isFinite(amount) || amount <= 0) {
            amountInput.reportValidity();
            return;
        }

        let row = rows.querySelector(`[data-bahan-id="${id}"]`);
        if (!row) {
            row = document.createElement('tr');
            row.dataset.recipeRow = '';
            row.dataset.bahanId = id;
            const name = document.createElement('td');
            name.textContent = option.dataset.name;
            const amountCell = document.createElement('td');
            const input = document.createElement('input');
            input.className = 'table-input';
            input.type = 'number';
            input.min = '0';
            input.step = '0.001';
            input.required = true;
            input.name = `bahan[${id}]`;
            amountCell.append(input);
            const unit = document.createElement('td');
            unit.textContent = option.dataset.unit;
            const action = document.createElement('td');
            const remove = document.createElement('button');
            remove.className = 'button button-small button-secondary';
            remove.type = 'button';
            remove.textContent = 'Hapus';
            remove.addEventListener('click', () => removeRecipeRow(remove));
            action.append(remove);
            row.append(name, amountCell, unit, action);
            rows.append(row);
        }

        row.hidden = false;
        const input = row.querySelector('input');
        input.disabled = false;
        input.value = String(amount);
        materialSelect.value = '';
        amountInput.value = '';
        updateSelectedUnit();
        updateAvailableMaterials();
        updateRecipeState();
    });

    updateSelectedUnit();
    updateAvailableMaterials();
    updateRecipeState();
});
