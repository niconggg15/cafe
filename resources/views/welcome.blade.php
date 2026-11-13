<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Pesanan Baru | Kedai Kopi Senja</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,500;9..144,600&display=swap" rel="stylesheet">
    <style>
        :root {
            color-scheme: light;
            --ink: #20251f;
            --muted: #73796f;
            --line: #e4e7e1;
            --paper: #ffffff;
            --canvas: #f5f6f2;
            --green: #355b42;
            --green-dark: #244231;
            --green-pale: #edf3ed;
            --amber: #a76d2d;
            --amber-pale: #fbf1e4;
            --red: #a6483e;
            --radius: 8px;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: var(--canvas);
            color: var(--ink);
            font-family: "DM Sans", sans-serif;
            font-size: 14px;
            line-height: 1.5;
        }
        button, input, select { font: inherit; }
        button { cursor: pointer; }
        .app-shell { display: flex; min-height: 100vh; }
        .sidebar {
            position: fixed;
            inset: 0 auto 0 0;
            z-index: 3;
            display: flex;
            width: 238px;
            flex-direction: column;
            padding: 28px 18px 20px;
            background: var(--paper);
            border-right: 1px solid var(--line);
        }
        .brand { display: flex; align-items: center; gap: 11px; padding: 0 10px 31px; }
        .brand-mark {
            display: grid;
            width: 36px;
            height: 36px;
            place-items: center;
            border-radius: 50%;
            background: var(--green);
            color: white;
            font-family: "Fraunces", serif;
            font-size: 20px;
        }
        .brand-name { font-family: "Fraunces", serif; font-size: 17px; font-weight: 600; }
        .brand-caption { color: var(--muted); font-size: 11px; }
        .nav-label { padding: 0 11px 9px; color: #9a9f96; font-size: 10px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; }
        .nav-list { display: grid; gap: 4px; }
        .nav-link {
            display: flex;
            min-height: 42px;
            align-items: center;
            gap: 12px;
            padding: 0 11px;
            border: 0;
            border-radius: 6px;
            background: transparent;
            color: #5e655c;
            text-align: left;
            text-decoration: none;
            transition: background .16s ease, color .16s ease;
        }
        .nav-link:hover { background: #f4f6f2; color: var(--ink); }
        .nav-link.active { background: var(--green-pale); color: var(--green-dark); font-weight: 700; }
        .nav-icon { display: grid; width: 19px; place-items: center; font-size: 16px; }
        .sidebar-bottom { margin-top: auto; padding: 16px 10px 0; border-top: 1px solid var(--line); }
        .staff-label { color: var(--muted); font-size: 11px; }
        .staff-name { margin-top: 3px; font-weight: 600; }
        main { width: calc(100% - 238px); margin-left: 238px; padding: 36px 40px 48px; }
        .page-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 20px; margin-bottom: 25px; }
        .eyebrow { margin-bottom: 5px; color: var(--green); font-size: 11px; font-weight: 700; letter-spacing: 1.1px; text-transform: uppercase; }
        h1 { margin: 0; font-family: "Fraunces", serif; font-size: 30px; font-weight: 500; line-height: 1.2; }
        .page-description { margin: 7px 0 0; color: var(--muted); font-size: 13px; }
        .sample-badge { flex: 0 0 auto; margin-top: 6px; padding: 6px 10px; border: 1px solid #e8d6bd; border-radius: 999px; background: var(--amber-pale); color: #87571f; font-size: 11px; font-weight: 600; }
        .workspace { display: grid; grid-template-columns: minmax(0, 1.55fr) minmax(320px, .9fr); align-items: start; gap: 20px; }
        .panel { min-width: 0; border: 1px solid var(--line); border-radius: var(--radius); background: var(--paper); }
        .panel-head { display: flex; align-items: center; justify-content: space-between; gap: 15px; padding: 19px 20px 16px; border-bottom: 1px solid var(--line); }
        .panel-title { margin: 0; font-size: 15px; font-weight: 700; }
        .panel-hint { margin: 3px 0 0; color: var(--muted); font-size: 11px; }
        .menu-tools { display: grid; gap: 14px; padding: 15px 20px 13px; }
        .search-wrap { position: relative; }
        .search-icon { position: absolute; top: 9px; left: 12px; color: #858b82; font-size: 15px; }
        .search-input { width: 100%; height: 37px; padding: 0 12px 0 35px; border: 1px solid var(--line); border-radius: 5px; outline: none; background: #fff; }
        .search-input:focus, select:focus { border-color: #829b87; box-shadow: 0 0 0 3px rgb(53 91 66 / 9%); }
        .filters { display: flex; flex-wrap: wrap; gap: 7px; padding-bottom: 2px; }
        .filter-button { flex: 0 0 auto; padding: 6px 11px; border: 1px solid var(--line); border-radius: 999px; background: white; color: #5f665e; font-size: 11px; }
        .filter-button:hover, .filter-button.active { border-color: var(--green); background: var(--green); color: white; }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { padding: 10px 12px; background: #f8f9f6; color: #858b82; font-size: 10px; font-weight: 700; letter-spacing: .45px; text-transform: uppercase; white-space: nowrap; }
        td { padding: 12px; border-top: 1px solid #eef0ec; color: #525950; font-size: 12px; white-space: nowrap; }
        th:first-child, td:first-child { padding-left: 20px; }
        th:last-child, td:last-child { padding-right: 20px; text-align: right; }
        .menu-name { color: var(--ink); font-size: 12px; font-weight: 600; }
        .menu-category { margin-top: 2px; color: #92978f; font-size: 10px; }
        .status { display: inline-flex; align-items: center; gap: 5px; color: var(--green); font-size: 10px; }
        .status::before { width: 6px; height: 6px; border-radius: 50%; background: #6d9a74; content: ""; }
        .add-button { width: 28px; height: 28px; border: 1px solid #d5e0d5; border-radius: 5px; background: var(--green-pale); color: var(--green-dark); font-size: 17px; line-height: 1; }
        .add-button:hover { border-color: var(--green); background: var(--green); color: white; }
        .empty-menu { padding: 32px 20px; color: var(--muted); text-align: center; }
        .order-panel { position: sticky; top: 20px; }
        .order-count { display: grid; min-width: 24px; height: 24px; place-items: center; border-radius: 50%; background: var(--green-pale); color: var(--green-dark); font-size: 11px; font-weight: 700; }
        .order-body { padding: 0 20px; }
        .order-empty { padding: 33px 8px; color: var(--muted); text-align: center; }
        .order-empty-mark { display: grid; width: 42px; height: 42px; margin: 0 auto 10px; place-items: center; border: 1px solid var(--line); border-radius: 50%; color: #8d958b; font-size: 18px; }
        .order-row { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 10px; padding: 15px 0; border-bottom: 1px solid #eef0ec; }
        .order-item-name { color: var(--ink); font-size: 12px; font-weight: 600; }
        .order-item-price { margin-top: 3px; color: var(--muted); font-size: 11px; }
        .order-row-actions { display: flex; align-items: center; gap: 7px; }
        .quantity-control { display: flex; align-items: center; gap: 8px; }
        .quantity-button { display: grid; width: 24px; height: 24px; place-items: center; border: 1px solid var(--line); border-radius: 4px; background: white; color: #4e554c; }
        .quantity-button:hover { border-color: var(--green); color: var(--green); }
        .quantity-value { min-width: 12px; color: var(--ink); font-size: 11px; text-align: center; }
        .remove-button { padding: 3px 5px; border: 0; background: transparent; color: #9b7772; font-size: 10px; }
        .remove-button:hover { color: var(--red); }
        .order-settings { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; padding: 17px 0 15px; }
        .field-label { display: block; margin-bottom: 6px; color: #777e74; font-size: 10px; font-weight: 700; }
        select { width: 100%; height: 36px; padding: 0 9px; border: 1px solid var(--line); border-radius: 5px; outline: none; background: white; color: var(--ink); font-size: 11px; }
        .totals { padding: 14px 0; border-top: 1px solid var(--line); }
        .total-line { display: flex; justify-content: space-between; gap: 12px; padding: 4px 0; color: #73796f; font-size: 11px; }
        .total-line strong { color: var(--ink); font-weight: 600; }
        .grand-total { margin-top: 7px; padding-top: 11px; border-top: 1px dashed #dfe3dc; color: var(--ink); font-size: 13px; font-weight: 700; }
        .save-button { width: 100%; min-height: 42px; margin: 2px 0 19px; border: 0; border-radius: 5px; background: var(--green); color: white; font-size: 12px; font-weight: 700; transition: background .16s ease, transform .16s ease; }
        .save-button:hover:not(:disabled) { transform: translateY(-1px); background: var(--green-dark); }
        .save-button:disabled { cursor: not-allowed; background: #b7c0b5; }
        .toast { position: fixed; right: 24px; bottom: 24px; z-index: 5; max-width: min(390px, calc(100vw - 32px)); padding: 12px 16px; border: 1px solid #d9e3d8; border-radius: 6px; background: #fff; box-shadow: 0 8px 28px rgb(25 36 25 / 12%); color: var(--ink); font-size: 12px; opacity: 0; pointer-events: none; transform: translateY(8px); transition: opacity .2s ease, transform .2s ease; }
        .toast.visible { opacity: 1; transform: translateY(0); }
        .mobile-menu-button { display: none; }
        .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0; }

        @media (max-width: 1080px) {
            main { padding: 30px 24px 40px; }
            .workspace { grid-template-columns: 1fr; gap: 14px; }
            .order-panel { position: static; }
            .panel-head, .menu-tools { padding-right: 15px; padding-left: 15px; }
            th, td { padding-right: 9px; padding-left: 9px; }
            th:first-child, td:first-child { padding-left: 15px; }
            th:last-child, td:last-child { padding-right: 15px; }
        }
        @media (max-width: 790px) {
            .sidebar { width: 72px; align-items: center; padding: 19px 10px; }
            .brand { justify-content: center; padding: 0 0 27px; }
            .brand-copy, .nav-label, .nav-text, .sidebar-bottom { display: none; }
            .nav-list { width: 100%; }
            .nav-link { justify-content: center; padding: 0; }
            .nav-icon { font-size: 17px; }
            main { width: calc(100% - 72px); margin-left: 72px; padding: 27px 19px 36px; }
            .workspace { grid-template-columns: 1fr; }
            .order-panel { position: static; }
        }
        @media (max-width: 520px) {
            .sidebar { position: sticky; top: 0; height: 100vh; }
            main { padding: 22px 13px 28px; }
            .page-head { margin-bottom: 18px; }
            h1 { font-size: 25px; }
            .page-description { max-width: 240px; font-size: 12px; }
            .sample-badge { padding: 5px 8px; font-size: 10px; }
            .panel-head { padding-top: 16px; padding-bottom: 13px; }
            .menu-tools { gap: 11px; padding-top: 12px; padding-bottom: 11px; }
            .table-wrap { margin: 0 -1px; }
            th, td { padding-top: 10px; padding-bottom: 10px; }
            .order-panel { margin-bottom: 10px; }
        }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { scroll-behavior: auto !important; transition-duration: .01ms !important; }
        }
    </style>
</head>
<body>
    <div class="app-shell">
        <aside class="sidebar" aria-label="Navigasi utama">
            <div class="brand">
                <div class="brand-mark" aria-hidden="true">S</div>
                <div class="brand-copy">
                    <div class="brand-name">Kedai Kopi Senja</div>
                    <div class="brand-caption">Sistem Informasi Kafe</div>
                </div>
            </div>

            <div class="nav-label">Transaksi</div>
            <nav class="nav-list">
                <a class="nav-link active" href="#pesanan-baru" aria-current="page" title="Pesanan Baru">
                    <span class="nav-icon" aria-hidden="true">+</span><span class="nav-text">Pesanan Baru</span>
                </a>
                <button class="nav-link" type="button" data-coming-soon title="Daftar Pesanan">
                    <span class="nav-icon" aria-hidden="true">&#9776;</span><span class="nav-text">Daftar Pesanan</span>
                </button>
            </nav>

            <div class="nav-label" style="margin-top: 27px">Operasional</div>
            <nav class="nav-list">
                <button class="nav-link" type="button" data-coming-soon title="Menu">
                    <span class="nav-icon" aria-hidden="true">&#9634;</span><span class="nav-text">Menu</span>
                </button>
                <button class="nav-link" type="button" data-coming-soon title="Bahan & Stok">
                    <span class="nav-icon" aria-hidden="true">&#9671;</span><span class="nav-text">Bahan &amp; Stok</span>
                </button>
                <button class="nav-link" type="button" data-coming-soon title="Stok Masuk">
                    <span class="nav-icon" aria-hidden="true">&#8595;</span><span class="nav-text">Stok Masuk</span>
                </button>
                <button class="nav-link" type="button" data-coming-soon title="Laporan">
                    <span class="nav-icon" aria-hidden="true">&#9636;</span><span class="nav-text">Laporan</span>
                </button>
            </nav>

            <div class="sidebar-bottom">
                <div class="staff-label">Masuk sebagai</div>
                <div class="staff-name">Kasir</div>
            </div>
        </aside>

        <main id="pesanan-baru">
            <header class="page-head">
                <div>
                    <div class="eyebrow">Transaksi</div>
                    <h1>Pesanan Baru</h1>
                    <p class="page-description">Pilih menu, atur jumlah, lalu lengkapi rincian pesanan.</p>
                </div>
                <span class="sample-badge">Data contoh</span>
            </header>

            <div class="workspace">
                <section class="panel" aria-labelledby="menu-heading">
                    <div class="panel-head">
                        <div>
                            <h2 class="panel-title" id="menu-heading">Menu tersedia</h2>
                            <p class="panel-hint">Pilih menu untuk menambahkannya ke pesanan.</p>
                        </div>
                    </div>
                    <div class="menu-tools">
                        <label class="search-wrap">
                            <span class="search-icon" aria-hidden="true">&#9906;</span>
                            <input class="search-input" id="menu-search" type="search" placeholder="Cari nama menu..." aria-label="Cari menu">
                        </label>
                        <div class="filters" role="group" aria-label="Filter kategori menu">
                            <button class="filter-button active" type="button" data-category="Semua">Semua</button>
                            <button class="filter-button" type="button" data-category="Kopi">Kopi</button>
                            <button class="filter-button" type="button" data-category="Non-kopi">Non-kopi</button>
                            <button class="filter-button" type="button" data-category="Makanan">Makanan</button>
                        </div>
                    </div>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr><th>Menu</th><th>Harga</th><th>Status</th><th><span class="sr-only">Tambah</span></th></tr>
                            </thead>
                            <tbody id="menu-list"></tbody>
                        </table>
                        <div class="empty-menu" id="menu-empty" hidden>Menu tidak ditemukan.</div>
                    </div>
                </section>

                <section class="panel order-panel" aria-labelledby="order-heading">
                    <div class="panel-head">
                        <div>
                            <h2 class="panel-title" id="order-heading">Rincian Pesanan</h2>
                            <p class="panel-hint">Periksa kembali sebelum melanjutkan.</p>
                        </div>
                        <span class="order-count" id="item-count" aria-label="Jumlah item">0</span>
                    </div>
                    <div class="order-body">
                        <div id="order-items" aria-live="polite"></div>
                        <div class="order-empty" id="order-empty">
                            <div class="order-empty-mark" aria-hidden="true">+</div>
                            Belum ada menu dipilih.
                        </div>
                        <div class="order-settings">
                            <label>
                                <span class="field-label">Nomor meja</span>
                                <select id="table-number" aria-label="Nomor meja">
                                    <option value="">Pilih meja</option>
                                    <option>1</option><option>2</option><option>3</option><option>4</option><option>5</option>
                                    <option>6</option><option>7</option><option>8</option><option>9</option><option>10</option>
                                </select>
                            </label>
                            <label>
                                <span class="field-label">Metode bayar</span>
                                <select id="payment-method" aria-label="Metode pembayaran">
                                    <option value="Tunai">Tunai</option>
                                    <option value="QRIS">QRIS</option>
                                    <option value="Kartu debit">Kartu debit</option>
                                </select>
                            </label>
                        </div>
                        <div class="totals" aria-live="polite">
                            <div class="total-line"><span>Subtotal</span><strong id="subtotal">Rp0</strong></div>
                            <div class="total-line"><span>Biaya layanan (5%)</span><strong id="service-charge">Rp0</strong></div>
                            <div class="total-line"><span>Pajak (10%)</span><strong id="tax">Rp0</strong></div>
                            <div class="total-line grand-total"><span>Total bayar</span><strong id="grand-total">Rp0</strong></div>
                        </div>
                        <button class="save-button" id="save-order" type="button" disabled>Simpan Pesanan</button>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <div class="toast" id="toast" role="status" aria-live="polite"></div>

    <script>
        const menuItems = [
            { id: 1, name: 'Kopi Susu Senja', category: 'Kopi', price: 22000 },
            { id: 2, name: 'Americano', category: 'Kopi', price: 18000 },
            { id: 3, name: 'Cafe Latte', category: 'Kopi', price: 25000 },
            { id: 4, name: 'Matcha Latte', category: 'Non-kopi', price: 26000 },
            { id: 5, name: 'Chocolate', category: 'Non-kopi', price: 24000 },
            { id: 6, name: 'Croissant Butter', category: 'Makanan', price: 20000 },
            { id: 7, name: 'Pisang Goreng', category: 'Makanan', price: 18000 }
        ];
        const cart = new Map();
        let activeCategory = 'Semua';
        let toastTimer;

        function formatRupiah(value) {
            return `Rp${new Intl.NumberFormat('id-ID').format(value)}`;
        }

        function renderMenu() {
            const query = document.getElementById('menu-search').value.trim().toLocaleLowerCase('id');
            const visibleItems = menuItems.filter((item) => {
                const matchesCategory = activeCategory === 'Semua' || item.category === activeCategory;
                return matchesCategory && item.name.toLocaleLowerCase('id').includes(query);
            });
            const list = document.getElementById('menu-list');
            list.replaceChildren(...visibleItems.map((item) => {
                const row = document.createElement('tr');
                row.innerHTML = `<td><div class="menu-name"></div><div class="menu-category"></div></td><td></td><td><span class="status">Tersedia</span></td><td><button class="add-button" type="button" aria-label="Tambah menu"></button></td>`;
                row.querySelector('.menu-name').textContent = item.name;
                row.querySelector('.menu-category').textContent = item.category;
                row.children[1].textContent = formatRupiah(item.price);
                row.querySelector('.add-button').textContent = '+';
                row.querySelector('.add-button').addEventListener('click', () => changeQuantity(item.id, 1));
                return row;
            }));
            document.getElementById('menu-empty').hidden = visibleItems.length > 0;
        }

        function changeQuantity(id, amount) {
            const quantity = (cart.get(id) || 0) + amount;
            if (quantity <= 0) cart.delete(id);
            else cart.set(id, quantity);
            renderOrder();
        }

        function renderOrder() {
            const orderItems = document.getElementById('order-items');
            const selectedItems = menuItems.filter((item) => cart.has(item.id));
            orderItems.replaceChildren(...selectedItems.map((item) => {
                const row = document.createElement('div');
                row.className = 'order-row';
                row.innerHTML = '<div><div class="order-item-name"></div><div class="order-item-price"></div></div><div class="order-row-actions"><div class="quantity-control"><button class="quantity-button decrease" type="button" aria-label="Kurangi jumlah">-</button><span class="quantity-value"></span><button class="quantity-button increase" type="button" aria-label="Tambah jumlah">+</button></div><button class="remove-button" type="button">Hapus</button></div>';
                row.querySelector('.order-item-name').textContent = item.name;
                row.querySelector('.order-item-price').textContent = `${formatRupiah(item.price)} / item`;
                row.querySelector('.quantity-value').textContent = cart.get(item.id);
                row.querySelector('.decrease').addEventListener('click', () => changeQuantity(item.id, -1));
                row.querySelector('.increase').addEventListener('click', () => changeQuantity(item.id, 1));
                row.querySelector('.remove-button').addEventListener('click', () => changeQuantity(item.id, -cart.get(item.id)));
                return row;
            }));

            const subtotal = selectedItems.reduce((sum, item) => sum + item.price * cart.get(item.id), 0);
            const serviceCharge = Math.round(subtotal * 0.05);
            const tax = Math.round(subtotal * 0.10);
            const count = [...cart.values()].reduce((sum, quantity) => sum + quantity, 0);
            document.getElementById('order-empty').hidden = count > 0;
            document.getElementById('item-count').textContent = count;
            document.getElementById('subtotal').textContent = formatRupiah(subtotal);
            document.getElementById('service-charge').textContent = formatRupiah(serviceCharge);
            document.getElementById('tax').textContent = formatRupiah(tax);
            document.getElementById('grand-total').textContent = formatRupiah(subtotal + serviceCharge + tax);
            document.getElementById('save-order').disabled = count === 0;
        }

        function showToast(message) {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.classList.add('visible');
            clearTimeout(toastTimer);
            toastTimer = setTimeout(() => toast.classList.remove('visible'), 3200);
        }

        document.getElementById('menu-search').addEventListener('input', renderMenu);
        document.querySelectorAll('.filter-button').forEach((button) => {
            button.addEventListener('click', () => {
                activeCategory = button.dataset.category;
                document.querySelector('.filter-button.active').classList.remove('active');
                button.classList.add('active');
                renderMenu();
            });
        });
        document.querySelectorAll('[data-coming-soon]').forEach((button) => {
            button.addEventListener('click', () => showToast('Halaman ini akan dibuat pada tahap berikutnya.'));
        });
        document.getElementById('save-order').addEventListener('click', () => {
            showToast('Tampilan ini belum menyimpan transaksi ke database.');
        });

        renderMenu();
        renderOrder();
    </script>
</body>
</html>