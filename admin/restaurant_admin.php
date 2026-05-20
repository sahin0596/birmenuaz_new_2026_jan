<?php
// Set UTF-8 encoding
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

session_start();
require_once '../config.php';

// Check if logged in as restaurant admin
if (!isset($_SESSION['restaurant_logged_in']) || $_SESSION['restaurant_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ../login.php');
    exit;
}

$restaurantId = $_SESSION['restaurant_id'];
$restaurantName = $_SESSION['restaurant_name'];
$restaurantSlug = $_SESSION['restaurant_slug'];
?>
<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($restaurantName); ?> - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="admin.css">
    <style>
        body {
            background: var(--admin-content-bg);
            color: var(--admin-text);
            min-height: 100vh;
            font-family: var(--admin-font);
            line-height: 1.6;
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        .restaurant-admin-wrap { min-height: 100vh; display: flex; flex-direction: column; }

        .container-fluid {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 32px 40px;
        }

        .stat-card h3 {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--admin-text-muted);
            margin: 0 0 12px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-card .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--admin-text);
            margin: 0;
            letter-spacing: -0.02em;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--admin-text);
            margin: 0;
            letter-spacing: -0.02em;
        }

        .table-container {
            background: var(--admin-card-bg);
            border: 1px solid var(--admin-card-border);
            border-radius: var(--admin-radius);
            overflow: hidden;
            margin-bottom: 32px;
            box-shadow: var(--admin-card-shadow);
        }

        .table {
            margin: 0;
            width: 100%;
        }

        .table thead {
            background: #f9fafb;
            border-bottom: 2px solid #e5e7eb;
        }

        [data-theme="dark"] .table thead {
            background: #0a0a0a;
            border-bottom-color: #1a1a1a;
        }

        .table th {
            font-size: 0.75rem;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 16px 20px;
            border: none;
        }

        [data-theme="dark"] .table th {
            color: #9ca3af;
        }

        .table td {
            padding: 16px 20px;
            color: #000;
            border-top: 1px solid #e5e7eb;
            vertical-align: middle;
        }

        [data-theme="dark"] .table td {
            color: #e5e7eb;
            border-top-color: #1a1a1a;
        }

        .table .text-center {
            color: #6b7280;
        }

        [data-theme="dark"] .table .text-center {
            color: #9ca3af;
        }

        .table td strong {
            color: #000;
        }

        [data-theme="dark"] .table td strong {
            color: #fff;
        }

        /* Status colors for dark mode */
        [data-theme="dark"] .table td span[style*="color: #10b981"] {
            color: #34d399 !important;
        }

        [data-theme="dark"] .table td span[style*="color: #6b7280"] {
            color: #9ca3af !important;
        }

        .product-placeholder {
            width: 50px;
            height: 50px;
            background: #e5e7eb;
            border-radius: 4px;
        }

        [data-theme="dark"] .product-placeholder {
            background: #2a2a2a;
        }

        .btn-action {
            padding: 8px 14px;
            border-radius: var(--admin-radius-sm);
            font-size: 0.8125rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-right: 6px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-action.btn-edit {
            border: 1px solid var(--admin-card-border);
            background: transparent;
            color: var(--admin-text-muted);
        }
        .btn-action.btn-edit:hover {
            border-color: var(--admin-primary);
            color: var(--admin-primary);
        }

        .btn-action.btn-delete {
            border: 1px solid rgba(220, 38, 38, 0.4);
            background: transparent;
            color: var(--admin-danger);
        }
        .btn-action.btn-delete:hover {
            background: var(--admin-danger);
            color: #fff;
        }

        /* Modal Styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 10000;
            justify-content: center;
            align-items: center;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-box {
            background: white;
            border: 1px solid #e5e7eb;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            padding: 32px;
        }

        [data-theme="dark"] .modal-box {
            background: #151515;
            border-color: #1f1f1f;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid #e5e7eb;
        }

        [data-theme="dark"] .modal-header {
            border-bottom-color: #1f1f1f;
        }

        .modal-header h2 {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
            color: #000;
        }

        [data-theme="dark"] .modal-header h2 {
            color: #fff;
        }

        .btn-close-modal {
            background: transparent;
            border: 1px solid #d1d5db;
            color: #6b7280;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        [data-theme="dark"] .btn-close-modal {
            border-color: #2a2a2a;
            color: #9ca3af;
        }

        .btn-close-modal:hover {
            background: #000;
            color: white;
            border-color: #000;
        }

        [data-theme="dark"] .btn-close-modal:hover {
            background: white;
            color: #000;
            border-color: white;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 500;
            color: #000;
            margin-bottom: 6px;
        }

        [data-theme="dark"] .form-group label {
            color: #e5e7eb;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px 14px;
            background: white;
            border: 1px solid #d1d5db;
            border-radius: 0;
            font-size: 0.85rem;
            color: #000;
        }

        [data-theme="dark"] .form-group input,
        [data-theme="dark"] .form-group textarea,
        [data-theme="dark"] .form-group select {
            background: #1a1a1a;
            border-color: #2a2a2a;
            color: #e5e7eb;
        }

        [data-theme="dark"] .form-group input::placeholder,
        [data-theme="dark"] .form-group textarea::placeholder {
            color: #6b7280;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #000;
        }

        [data-theme="dark"] .form-group input:focus,
        [data-theme="dark"] .form-group textarea:focus,
        [data-theme="dark"] .form-group select:focus {
            border-color: white;
        }

        .image-preview {
            width: 100%;
            aspect-ratio: 1;
            background: #f3f4f6;
            margin-top: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        [data-theme="dark"] .image-preview {
            background: #1a1a1a;
        }

        .image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .image-preview i {
            font-size: 48px;
            color: #d1d5db;
        }

        [data-theme="dark"] .image-preview i {
            color: #4b5563;
        }

        .btn-submit {
            width: 100%;
            padding: 12px 24px;
            background: #000;
            color: white;
            border: 1px solid #000;
            border-radius: 0;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            margin-top: 8px;
            transition: all 0.2s ease;
        }

        [data-theme="dark"] .btn-submit {
            background: white;
            color: #000;
            border-color: white;
        }

        .btn-submit:hover {
            background: transparent;
            color: #000;
            border-color: #000;
        }

        [data-theme="dark"] .btn-submit:hover {
            background: transparent;
            color: white;
            border-color: white;
        }

        @media (max-width: 768px) {
            .container-fluid {
                padding: 0 20px;
            }

            .admin-header-new {
                padding: 16px 20px;
                flex-direction: column;
                gap: 16px;
            }

            .admin-header-left {
                flex: none;
                width: 100%;
                justify-content: flex-start;
            }

            .admin-header-center {
                position: static;
                transform: none;
                flex: none;
                width: 100%;
                order: 2;
            }

            .admin-header-right {
                flex: none;
                width: 100%;
                justify-content: flex-end;
                order: 3;
            }

            .admin-logo {
                height: 32px;
            }

            .admin-header-new h1 {
                font-size: 1.4rem;
            }

            .restaurant-name i {
                font-size: 1.3rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .section-header {
                flex-direction: column;
                gap: 16px;
                align-items: flex-start;
            }

            .table-container {
                overflow-x: auto;
            }

            .modal-box {
                width: 95%;
                padding: 24px;
            }
        }
    </style>
    <script>
        // Apply theme immediately
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();

        function toggleTheme() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeIcon(newTheme);
        }

        function updateThemeIcon(theme) {
            const icon = document.getElementById('themeIcon');
            if (icon) {
                icon.className = theme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
            }
            const logo = document.getElementById('adminLogo');
            if (logo) logo.src = '../assets/images/logo.png';
        }

        document.addEventListener('DOMContentLoaded', function() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            updateThemeIcon(currentTheme);
            loadStatistics();
            loadCategories();
            loadProducts();
            
            const logo = document.getElementById('adminLogo');
            if (logo) logo.src = '../assets/images/logo.png';
        });

        // Restaurant ID from session
        const RESTAURANT_ID = <?php echo $restaurantId; ?>;
        const RESTAURANT_SLUG = '<?php echo $restaurantSlug; ?>';

        // Load statistics
        async function loadStatistics() {
            try {
                const response = await fetch(`../api/api.php?action=get_restaurant_stats&restaurant_id=${RESTAURANT_ID}`);
                const data = await response.json();
                if (data.success) {
                    document.getElementById('totalCategories').textContent = data.stats.total_categories || 0;
                    document.getElementById('totalProducts').textContent = data.stats.total_products || 0;
                    document.getElementById('activeProducts').textContent = data.stats.active_products || 0;
                }
            } catch (error) {
                console.error('Statistics yüklənmədi:', error);
            }
        }

        // Load categories
        async function loadCategories() {
            try {
                const response = await fetch(`../api/api.php?action=get_categories&restaurant_id=${RESTAURANT_ID}`);
                const data = await response.json();
                if (data.success) {
                    renderCategories(data.categories || []);
                }
            } catch (error) {
                console.error('Kateqoriyalar yüklənmədi:', error);
            }
        }

        function renderCategories(categories) {
            const tbody = document.getElementById('categoriesTableBody');
            if (categories.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center">Kateqoriya yoxdur</td></tr>';
                return;
            }

            tbody.innerHTML = categories.map(cat => `
                <tr>
                    <td><strong>${cat.name}</strong></td>
                    <td>${cat.product_count || 0}</td>
                    <td>${cat.is_active == 1 ? '<span style="color: #10b981;">Aktiv</span>' : '<span style="color: #6b7280;">Deaktiv</span>'}</td>
                    <td>
                        <button class="btn-action btn-edit" onclick="editCategory(${cat.id})">
                            <i class="bi bi-pencil"></i> Redaktə
                        </button>
                        <button class="btn-action btn-delete" onclick="deleteCategory(${cat.id})">
                            <i class="bi bi-trash"></i> Sil
                        </button>
                    </td>
                </tr>
            `).join('');
        }

        // Load products
        async function loadProducts() {
            try {
                const response = await fetch(`../api/api.php?action=get_products&restaurant_id=${RESTAURANT_ID}`);
                const data = await response.json();
                if (data.success) {
                    // API returns 'products' array
                    const products = data.products || data.data || [];
                    renderProducts(products);
                }
            } catch (error) {
                console.error('Məhsullar yüklənmədi:', error);
            }
        }

        function renderProducts(products) {
            const tbody = document.getElementById('productsTableBody');
            if (products.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center">Məhsul yoxdur</td></tr>';
                return;
            }

            tbody.innerHTML = products.map(prod => `
                <tr>
                    <td>
                        ${prod.image_path ? `<img src="${prod.image_path}" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">` : '<div class="product-placeholder"></div>'}
                    </td>
                    <td><strong>${prod.name}</strong></td>
                    <td>${prod.category_name || '-'}</td>
                    <td>${prod.price} ₼</td>
                    <td>${prod.is_active == 1 ? '<span style="color: #10b981;">Aktiv</span>' : '<span style="color: #6b7280;">Deaktiv</span>'}</td>
                    <td>
                        <button class="btn-action btn-edit" onclick="editProduct(${prod.id})">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn-action btn-delete" onclick="deleteProduct(${prod.id})">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
        }

        // Modal functions
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
            if (modalId === 'productModal') {
                document.getElementById('productForm').reset();
                document.getElementById('productPreview').innerHTML = '<i class="bi bi-image"></i>';
                document.getElementById('productId').value = '';
            } else if (modalId === 'categoryModal') {
                document.getElementById('categoryForm').reset();
                document.getElementById('categoryId').value = '';
            }
        }

        function previewImage(input, previewId) {
            const preview = document.getElementById(previewId);
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Category functions
        function addCategory() {
            document.getElementById('categoryModalTitle').textContent = 'Kateqoriya Əlavə Et';
            document.getElementById('categoryForm').reset();
            document.getElementById('categoryId').value = '';
            openModal('categoryModal');
        }

        function editCategory(id) {
            fetch(`../api/api.php?action=get_categories&restaurant_id=${RESTAURANT_ID}`)
                .then(response => response.json())
                .then(data => {
                    const item = data.categories.find(c => c.id == id);
                    if (item) {
                        document.getElementById('categoryModalTitle').textContent = 'Kateqoriyanı Redaktə Et';
                        document.getElementById('categoryId').value = item.id;
                        document.getElementById('categoryName').value = item.name;
                        document.getElementById('categoryIcon').value = '';
                        document.getElementById('categoryOrder').value = item.display_order || 0;
                        openModal('categoryModal');
                    }
                });
        }

        function saveCategory(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            formData.append('action', document.getElementById('categoryId').value ? 'update_category' : 'add_category');
            formData.append('restaurant_id', RESTAURANT_ID);
            
            fetch('../api/api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeModal('categoryModal');
                    loadCategories();
                    loadStatistics();
                    loadCategoriesForProductForm();
                    alert('Kateqoriya uğurla yadda saxlanıldı');
                } else {
                    alert('Xəta: ' + data.message);
                }
            });
        }

        function deleteCategory(id) {
            if (confirm('Bu kateqoriyanı silmək istədiyinizə əminsiniz?')) {
                fetch(`../api/api.php?action=delete_category&id=${id}`, { method: 'POST' })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            loadCategories();
                            loadStatistics();
                            loadCategoriesForProductForm();
                            alert('Kateqoriya silindi');
                        } else {
                            alert('Xəta: ' + data.message);
                        }
                    });
            }
        }

        function addProductManual() {
            document.getElementById('productModalTitle').textContent = 'Məhsul Əlavə Et';
            document.getElementById('productForm').reset();
            document.getElementById('productId').value = '';
            document.getElementById('productPreview').innerHTML = '<i class="bi bi-image"></i>';
            loadCategoriesForProductForm();
            openModal('productModal');
        }
        function escapeHtml(s) {
            const d = document.createElement('div');
            d.textContent = s;
            return d.innerHTML;
        }
        function addProduct() {
            document.getElementById('productModalTitle').textContent = 'Məhsul Əlavə Et';
            document.getElementById('productForm').reset();
            document.getElementById('productId').value = '';
            document.getElementById('productPreview').innerHTML = '<i class="bi bi-image"></i>';
            loadCategoriesForProductForm();
            openModal('productModal');
        }

        function editProduct(id) {
            loadCategoriesForProductForm();
            fetch(`../api/api.php?action=get&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.products.length > 0) {
                        const item = data.products[0];
                        document.getElementById('productModalTitle').textContent = 'Məhsulu Redaktə Et';
                        document.getElementById('productId').value = item.id;
                        document.getElementById('productName').value = item.name;
                        document.getElementById('productDescription').value = item.description || '';
                        document.getElementById('productPrice').value = item.price;
                        document.getElementById('productDiscountPrice').value = item.discount_price || '';
                        setTimeout(() => {
                            document.getElementById('productCategory').value = item.category_id || '';
                        }, 100);
                        if (item.image_path) {
                            document.getElementById('productPreview').innerHTML = `<img src="${item.image_path}" alt="Preview">`;
                        }
                        openModal('productModal');
                    }
                });
        }

        function saveProduct(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            formData.append('action', document.getElementById('productId').value ? 'update' : 'add');
            formData.append('restaurant_id', RESTAURANT_ID);
            
            fetch('../api/api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeModal('productModal');
                    loadProducts();
                    loadStatistics();
                    alert('Məhsul uğurla yadda saxlanıldı');
                } else {
                    alert('Xəta: ' + data.message);
                }
            });
        }

        function deleteProduct(id) {
            if (confirm('Bu məhsulu silmək istədiyinizə əminsiniz?')) {
                fetch(`../api/api.php?action=delete&id=${id}`, { method: 'POST' })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            loadProducts();
                            loadStatistics();
                            alert('Məhsul silindi');
                        } else {
                            alert('Xəta: ' + data.message);
                        }
                    });
            }
        }

        // Load categories for product form
        function loadCategoriesForProductForm() {
            fetch(`../api/api.php?action=get_categories&restaurant_id=${RESTAURANT_ID}`)
                .then(response => response.json())
                .then(data => {
                    const select = document.getElementById('productCategory');
                    select.innerHTML = '<option value="">Kateqoriya seçin...</option>';
                    if (data.success && data.categories) {
                        data.categories.forEach(cat => {
                            const option = document.createElement('option');
                            option.value = cat.id;
                            option.textContent = cat.name;
                            select.appendChild(option);
                        });
                    }
                });
        }

    </script>
</head>
<body class="admin-wrap restaurant-admin-wrap">
    <header class="admin-header">
        <a href="../" class="admin-header-title" style="text-decoration:none;color:inherit;display:flex;align-items:center;gap:10px;">
            <img src="../assets/images/logo.png" alt="BirMenu" class="admin-logo" id="adminLogo" style="height:36px;">
            <span><?php echo htmlspecialchars($restaurantName); ?></span>
        </a>
        <div class="admin-header-actions">
            <button type="button" class="admin-theme-toggle" onclick="toggleTheme()" aria-label="Rəng rejimi">
                <i class="bi bi-moon-fill" id="themeIcon"></i>
            </button>
            <a href="?logout=1" class="admin-btn admin-btn-primary">
                <i class="bi bi-box-arrow-right"></i> Çıxış
            </a>
        </div>
    </header>

    <div class="container-fluid">
        <!-- Statistics -->
        <div class="admin-stats">
            <div class="admin-stat-card">
                <div class="admin-stat-icon primary"><i class="bi bi-grid-3x3-gap"></i></div>
                <div>
                    <div class="admin-stat-value" id="totalCategories">0</div>
                    <div class="admin-stat-label">Kateqoriyalar</div>
                </div>
            </div>
            <div class="admin-stat-card">
                <div class="admin-stat-icon primary"><i class="bi bi-basket"></i></div>
                <div>
                    <div class="admin-stat-value" id="totalProducts">0</div>
                    <div class="admin-stat-label">Ümumi Məhsullar</div>
                </div>
            </div>
            <div class="admin-stat-card">
                <div class="admin-stat-icon success"><i class="bi bi-check-circle"></i></div>
                <div>
                    <div class="admin-stat-value" id="activeProducts">0</div>
                    <div class="admin-stat-label">Aktiv Məhsullar</div>
                </div>
            </div>
        </div>

        <!-- Categories Section -->
        <div class="section-header">
            <h2 class="section-title">Kateqoriyalar</h2>
            <button type="button" class="admin-btn admin-btn-primary" onclick="addCategory()">
                <i class="bi bi-plus-circle"></i>
                Kateqoriya Əlavə Et
            </button>
        </div>

        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Ad</th>
                        <th>Məhsul Sayı</th>
                        <th>Status</th>
                        <th>Əməliyyatlar</th>
                    </tr>
                </thead>
                <tbody id="categoriesTableBody">
                    <tr><td colspan="4" class="text-center">Yüklənir...</td></tr>
                </tbody>
            </table>
        </div>

        <!-- Products Section -->
        <div class="section-header">
            <h2 class="section-title">Məhsullar</h2>
            <button type="button" class="admin-btn admin-btn-primary" onclick="addProductManual()">
                <i class="bi bi-plus-circle"></i>
                Məhsul Əlavə Et
            </button>
        </div>

        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Şəkil</th>
                        <th>Ad</th>
                        <th>Kateqoriya</th>
                        <th>Qiymət</th>
                        <th>Status</th>
                        <th>Əməliyyatlar</th>
                    </tr>
                </thead>
                <tbody id="productsTableBody">
                    <tr><td colspan="6" class="text-center">Yüklənir...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Product Modal -->
    <div id="productModal" class="modal-overlay" onclick="if(event.target === this) closeModal('productModal')">
        <div class="modal-box" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h2 id="productModalTitle">Məhsul Əlavə Et</h2>
                <button class="btn-close-modal" onclick="closeModal('productModal')">
                    <i class="bi bi-x"></i>
                </button>
            </div>
            <form id="productForm" onsubmit="saveProduct(event)">
                <input type="hidden" id="productId" name="id">
                <div class="form-group">
                    <label>Ad *</label>
                    <input type="text" id="productName" name="name" required>
                </div>
                <div class="form-group">
                    <label>Təsvir</label>
                    <textarea id="productDescription" name="description" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label>Qiymət (₼) *</label>
                    <input type="number" id="productPrice" name="price" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <label>Endirim Qiyməti (₼)</label>
                    <input type="number" id="productDiscountPrice" name="discount_price" step="0.01" min="0">
                </div>
                <div class="form-group">
                    <label>Kateqoriya *</label>
                    <select id="productCategory" name="category_id" required>
                        <option value="">Kateqoriya seçin...</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Şəkil</label>
                    <input type="file" id="productImage" name="image" accept="image/*" onchange="previewImage(this, 'productPreview')">
                    <div class="image-preview" id="productPreview">
                        <i class="bi bi-image"></i>
                    </div>
                </div>
                <button type="submit" class="btn-submit">Yadda Saxla</button>
            </form>
        </div>
    </div>

    <!-- Category Modal -->
    <div id="categoryModal" class="modal-overlay" onclick="if(event.target === this) closeModal('categoryModal')">
        <div class="modal-box" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h2 id="categoryModalTitle">Kateqoriya Əlavə Et</h2>
                <button class="btn-close-modal" onclick="closeModal('categoryModal')">
                    <i class="bi bi-x"></i>
                </button>
            </div>
            <form id="categoryForm" onsubmit="saveCategory(event)">
                <input type="hidden" id="categoryId" name="id">
                <div class="form-group">
                    <label>Ad *</label>
                    <input type="text" id="categoryName" name="name" required>
                </div>
                <input type="hidden" id="categoryIcon" name="icon" value="">
                <div class="form-group">
                    <label>Sıralama</label>
                    <input type="number" id="categoryOrder" name="display_order" value="0" min="0">
                </div>
                <button type="submit" class="btn-submit">Yadda Saxla</button>
            </form>
        </div>
    </div>
</body>
</html>

