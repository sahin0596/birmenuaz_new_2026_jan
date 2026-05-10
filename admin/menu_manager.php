<?php
session_start();

// Check if logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

// Get restaurant ID
$restaurant_id = $_GET['restaurant_id'] ?? 0;
if (!$restaurant_id) {
    header('Location: admin.php');
    exit;
}

// Get restaurant info
require_once '../config.php';
$conn = getDBConnection();
$stmt = $conn->prepare("SELECT * FROM restaurants WHERE id = ?");
$stmt->bind_param("i", $restaurant_id);
$stmt->execute();
$result = $stmt->get_result();
$restaurant = $result->fetch_assoc();
$stmt->close();
$conn->close();

if (!$restaurant) {
    header('Location: admin.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menyu İdarəetməsi - <?php echo htmlspecialchars($restaurant['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background: #f8fafc;
            color: var(--text-primary);
            min-height: 100vh;
            font-family: ui-sans-serif, system-ui, sans-serif;
            overflow-x: hidden;
        }

        [data-theme="dark"] body {
            background: #0a0a0a;
        }

        .menu-header {
            background: white;
            border-bottom: 1px solid #e5e7eb;
            margin-bottom: 32px;
            overflow: hidden;
        }

        [data-theme="dark"] .menu-header {
            background: #0f0f0f;
            border-bottom-color: #1a1a1a;
        }

        .menu-header-inner {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px 32px;
        }

        .menu-header-brand {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .menu-header-logo {
            width: 72px;
            height: 72px;
            border-radius: 12px;
            object-fit: cover;
            border: 3px solid white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            background: #f3f4f6;
            flex-shrink: 0;
        }

        [data-theme="dark"] .menu-header-logo {
            border-color: #1a1a1a;
            background: #2a2a2a;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }

        .menu-header-logo-placeholder {
            width: 72px;
            height: 72px;
            border-radius: 12px;
            background: #e5e7eb;
            border: 3px solid white;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #9ca3af;
            font-size: 28px;
        }

        [data-theme="dark"] .menu-header-logo-placeholder {
            background: #2a2a2a;
            border-color: #1a1a1a;
            color: #6b7280;
        }

        .menu-title h1 {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
            color: var(--text-primary);
        }

        .menu-title p {
            font-size: 0.85rem;
            color: #6b7280;
            margin: 4px 0 0 0;
        }

        [data-theme="dark"] .menu-title p {
            color: #9ca3af;
        }

        .btn-back {
            background: transparent;
            color: #6b7280;
            padding: 8px 16px;
            border: 1px solid #d1d5db;
            border-radius: 0;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }

        [data-theme="dark"] .btn-back {
            color: #9ca3af;
            border-color: #2a2a2a;
        }

        .btn-back:hover {
            background: #000;
            color: white;
            border-color: #000;
        }

        [data-theme="dark"] .btn-back:hover {
            background: white;
            color: #000;
            border-color: white;
        }

        main {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 32px 40px;
        }

        .menu-tabs {
            display: flex;
            gap: 0;
            border: 1px solid #e5e7eb;
            margin-bottom: 32px;
        }

        [data-theme="dark"] .menu-tabs {
            border-color: #1f1f1f;
        }

        .menu-tab {
            flex: 1;
            padding: 12px 20px;
            background: transparent;
            border: none;
            border-right: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        [data-theme="dark"] .menu-tab {
            border-right-color: #1f1f1f;
            color: #9ca3af;
        }

        .menu-tab:last-child {
            border-right: none;
        }

        .menu-tab:hover {
            background: #f9fafb;
        }

        [data-theme="dark"] .menu-tab:hover {
            background: #151515;
        }

        .menu-tab.active {
            background: #000;
            color: white;
        }

        [data-theme="dark"] .menu-tab.active {
            background: white;
            color: #000;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .btn-add {
            background: #000;
            color: white;
            padding: 12px 24px;
            border: 1px solid #000;
            border-radius: 0;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }

        [data-theme="dark"] .btn-add {
            background: white;
            color: #000;
            border-color: white;
        }

        .btn-add:hover {
            background: transparent;
            color: #000;
            border-color: #000;
        }

        [data-theme="dark"] .btn-add:hover {
            background: transparent;
            color: white;
            border-color: white;
        }

        .items-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 16px;
            margin-top: 24px;
        }

        #categories-list {
            display: flex;
            flex-wrap: wrap;
            flex-direction: row;
            gap: 10px;
        }

        #categories-list .item-card {
            flex: 0 0 auto;
            width: 300px;
            min-width: 100px;
            padding: 10px;
            border-radius: 12px;
            border: none;
            text-align: center;
        }

        [data-theme="dark"] #categories-list .item-card {
            border: none;
        }

        #categories-list .item-image {
            width: 48px;
            height: 48px;
            aspect-ratio: 1;
            margin: 0 auto 8px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        #categories-list .item-image i {
            font-size: 22px;
        }

        #categories-list .item-name {
            font-size: 0.95rem;
            font-weight: 600;
            margin-bottom: 4px;
            line-height: 1.2;
        }

        #categories-list .item-price {
            font-size: 0.65rem;
            margin-bottom: 6px;
        }

        #categories-list .item-actions {
            flex-direction: row;
            gap: 3px;
            justify-content: center;
        }

        #categories-list .btn-small {
            padding: 3px 6px;
            font-size: 0.65rem;
        }

        #category-products-list {
            display: flex;
            flex-wrap: wrap;
            flex-direction: row;
            gap: 12px;
        }

        #category-products-list .item-card {
            flex: 0 0 auto;
            width: 120px;
            min-width: 100px;
            padding: 8px;
            border-radius: 12px;
            text-align: center;
        }

        #category-products-list .item-image {
            width: 64px;
            height: 64px;
            margin: 0 auto 6px;
            border-radius: 10px;
            overflow: hidden;
        }

        #category-products-list .item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        #category-products-list .item-image i {
            font-size: 20px;
        }

        #category-products-list .item-name {
            font-size: 0.75rem;
            margin-bottom: 2px;
        }

        #category-products-list .item-price {
            font-size: 0.7rem;
            margin-bottom: 6px;
        }

        #category-products-list .item-actions {
            flex-direction: row;
            gap: 4px;
            justify-content: center;
        }

        #category-products-list .btn-small {
            padding: 3px 6px;
            font-size: 0.65rem;
        }

        .item-card {
            background: white;
            border: 1px solid #e5e7eb;
            padding: 16px;
            transition: all 0.2s ease;
        }

        [data-theme="dark"] .item-card {
            background: #151515;
            border-color: #1f1f1f;
        }

        .item-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        [data-theme="dark"] .item-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        .item-image {
            width: 100%;
            aspect-ratio: 1;
            background: #f3f4f6;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        [data-theme="dark"] .item-image {
            background: #1a1a1a;
        }

        .item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .item-image i {
            font-size: 32px;
            color: #d1d5db;
        }

        .item-name {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 4px;
        }

        .item-price {
            font-size: 0.85rem;
            color: #6b7280;
            margin-bottom: 12px;
        }

        [data-theme="dark"] .item-price {
            color: #9ca3af;
        }

        .item-actions {
            display: flex;
            gap: 8px;
        }

        .category-card-clickable {
            cursor: pointer;
        }

        .category-card-clickable .item-actions {
            pointer-events: auto;
        }

        .btn-small {
            flex: 1;
            padding: 6px 12px;
            border: 1px solid #d1d5db;
            background: transparent;
            color: #6b7280;
            font-size: 0.75rem;
            border-radius: 0;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        [data-theme="dark"] .btn-small {
            border-color: #2a2a2a;
            color: #9ca3af;
        }

        .btn-small:hover {
            background: #000;
            color: white;
            border-color: #000;
        }

        [data-theme="dark"] .btn-small:hover {
            background: white;
            color: #000;
            border-color: white;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #9ca3af;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.5;
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
            color: var(--text-primary);
            margin-bottom: 6px;
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
            color: var(--text-primary);
        }

        [data-theme="dark"] .form-group input,
        [data-theme="dark"] .form-group textarea,
        [data-theme="dark"] .form-group select {
            background: #1a1a1a;
            border-color: #2a2a2a;
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

        [data-theme="dark"]         .btn-submit:hover {
            background: transparent;
            color: white;
            border-color: white;
        }

        /* Responsive Styles - Tablet */
        @media (max-width: 768px) {
            .menu-header-inner {
                padding: 16px 20px;
                flex-wrap: wrap;
                gap: 12px;
            }

            .menu-header-brand {
                gap: 12px;
                flex: 1;
                min-width: 0;
            }

            .menu-header-logo,
            .menu-header-logo-placeholder {
                width: 56px;
                height: 56px;
                font-size: 22px;
            }

            .menu-title {
                min-width: 0;
            }

            .menu-title h1 {
                font-size: 1.1rem;
            }

            .menu-title p {
                font-size: 0.8rem;
            }

            .btn-back {
                padding: 7px 14px;
                font-size: 0.8rem;
                flex-shrink: 0;
            }

            main {
                padding: 0 20px 30px;
            }

            .btn-add {
                padding: 10px 20px;
                font-size: 0.8rem;
                width: 100%;
                justify-content: center;
            }

            /* Kateqoriyalar - 2 sütun grid */
            #categories-list {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 10px;
            }

            #categories-list .item-card {
                width: 100%;
                min-width: 0;
                padding: 12px;
                border-radius: 10px;
                box-sizing: border-box;
            }

            #categories-list .item-name {
                font-size: 0.9rem;
            }

            #categories-list .item-price {
                font-size: 0.65rem;
            }

            #categories-list .item-actions {
                flex-direction: row;
                gap: 4px;
                justify-content: center;
                flex-wrap: wrap;
            }

            #categories-list .btn-small {
                padding: 6px 10px;
                font-size: 0.7rem;
            }

            /* Məhsullar - 2 sütun grid */
            #category-products-list {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 12px;
            }

            #category-products-list .item-card {
                width: 100%;
                min-width: 0;
                padding: 10px;
                box-sizing: border-box;
            }

            #category-products-list .item-image {
                width: 100%;
                aspect-ratio: 1;
                height: auto;
                max-height: 100px;
                margin: 0 auto 8px;
            }

            #category-products-list .item-name {
                font-size: 0.8rem;
                line-height: 1.3;
            }

            #category-products-list .item-price {
                font-size: 0.75rem;
            }

            #category-products-list .item-actions {
                flex-direction: row;
                gap: 4px;
                justify-content: center;
                flex-wrap: wrap;
            }

            #category-products-list .btn-small {
                padding: 6px 8px;
                font-size: 0.65rem;
            }

            .category-detail-header {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 12px !important;
            }

            .category-detail-header h2 {
                width: 100%;
                font-size: 1.1rem !important;
            }

            .category-detail-header > div:last-child {
                margin-left: 0 !important;
            }

            .modal-box {
                width: 95%;
                max-width: none;
                padding: 24px;
                margin: 16px;
            }

            .modal-header h2 {
                font-size: 1.1rem;
            }

            .form-group label {
                font-size: 0.8rem;
            }

            .form-group input,
            .form-group textarea,
            .form-group select {
                padding: 10px 12px;
                font-size: 16px;
            }

            .btn-submit {
                padding: 12px 20px;
                font-size: 0.9rem;
            }

            .image-preview {
                aspect-ratio: 1;
            }
        }

        /* Responsive Styles - Mobile */
        @media (max-width: 480px) {
            .menu-header-inner {
                padding: 12px 16px;
            }

            .menu-header-logo,
            .menu-header-logo-placeholder {
                width: 48px;
                height: 48px;
                font-size: 20px;
            }

            .menu-title h1 {
                font-size: 1rem;
            }

            .menu-title p {
                display: none;
            }

            .btn-back {
                padding: 6px 12px;
                font-size: 0.75rem;
            }

            main {
                padding: 0 16px 24px;
            }

            .btn-add {
                padding: 10px 16px;
                font-size: 0.85rem;
            }

            /* Kateqoriyalar - 2 sütun, kompakt */
            #categories-list {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 8px;
            }

            #categories-list .item-card {
                padding: 10px;
            }

            #categories-list .item-name {
                font-size: 0.85rem;
            }

            #categories-list .btn-small {
                padding: 5px 8px;
                font-size: 0.65rem;
            }

            /* Məhsullar - 2 sütun, kompakt */
            #category-products-list {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 8px;
            }

            #category-products-list .item-card {
                padding: 8px;
            }

            #category-products-list .item-image {
                max-height: 80px;
            }

            #category-products-list .item-name {
                font-size: 0.75rem;
                -webkit-line-clamp: 2;
                display: -webkit-box;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }

            #category-products-list .btn-small {
                padding: 5px 6px;
                font-size: 0.6rem;
            }

            .category-detail-header h2 {
                font-size: 1rem !important;
            }

            .modal-box {
                width: 100%;
                max-width: 100%;
                margin: 0;
                padding: 20px 16px;
                min-height: 100vh;
                border-radius: 0;
            }

            .modal-header h2 {
                font-size: 1rem;
            }

            .form-group input,
            .form-group textarea,
            .form-group select {
                font-size: 16px;
            }

            .btn-close-modal {
                width: 36px;
                height: 36px;
            }
        }

        /* Product library table responsive */
        @media (max-width: 768px) {
            #productLibraryModal .modal-box {
                overflow: hidden;
                display: flex;
                flex-direction: column;
            }

            #productLibraryModal .modal-box > div:last-child {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            #productLibraryModal .modal-box table {
                font-size: 0.8rem;
            }

            #productLibraryModal .modal-box th,
            #productLibraryModal .modal-box td {
                padding: 8px 6px;
            }

            #productLibraryModal .modal-box th:nth-child(3),
            #productLibraryModal .modal-box td:nth-child(3) {
                display: none;
            }
        }

        @media (max-width: 480px) {
            #productLibraryModal .modal-box th:nth-child(2),
            #productLibraryModal .modal-box td:nth-child(2) {
                max-width: 80px;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            #productLibraryAddBar {
                flex-wrap: wrap;
                gap: 8px;
            }

            #productLibraryAddBar .btn-submit {
                margin-left: 0;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php 
        $baseUrl = '../';
        $logoPath = !empty($restaurant['logo_path']) ? trim($restaurant['logo_path']) : '';
    ?>
    <div class="menu-header">
        <div class="menu-header-inner">
            <div class="menu-header-brand">
                <?php 
                    if ($logoPath) {
                        $logoUrl = ($logoPath[0] === '/' || strpos($logoPath, 'http') === 0) ? $logoPath : $baseUrl . $logoPath;
                        echo '<img src="' . htmlspecialchars($logoUrl) . '" alt="' . htmlspecialchars($restaurant['name']) . '" class="menu-header-logo">';
                    } else {
                        echo '<div class="menu-header-logo-placeholder"><i class="bi bi-shop"></i></div>';
                    }
                ?>
                <div class="menu-title">
                    <h1><?php echo htmlspecialchars($restaurant['name']); ?></h1>
                    <p>Menyu İdarəetməsi</p>
                </div>
            </div>
            <a href="admin.php" class="btn-back">
                <i class="bi bi-arrow-left"></i> Geri
            </a>
        </div>
    </div>

    <main>
        <!-- Categories -->
        <div id="categories-tab" class="tab-content active">
            <div id="categories-list-view">
                <button class="btn-add" onclick="showAddCategoryChoice()">
                    <i class="bi bi-plus-circle"></i> Kateqoriya Əlavə Et
                </button>
                <div id="categories-list" class="items-grid"></div>
            </div>
            <div id="category-detail-view" style="display:none;">
                <div class="category-detail-header" style="display:flex;align-items:center;gap:16px;margin-bottom:20px;flex-wrap:wrap;">
                    <button type="button" class="btn-small" onclick="showCategoriesList()" style="flex:none;">
                        <i class="bi bi-arrow-left"></i> Geri
                    </button>
                    <h2 id="categoryDetailTitle" style="margin:0;font-size:1.25rem;"></h2>
                    <div style="display:flex;gap:8px;margin-left:auto;">
                        <button class="btn-small" onclick="editCategoryFromDetail()"><i class="bi bi-pencil"></i> Redaktə</button>
                        <button class="btn-small" onclick="deleteCategoryFromDetail()"><i class="bi bi-trash"></i> Sil</button>
                    </div>
                </div>
                <button class="btn-add" onclick="openAddProductFromCategoryPage()">
                    <i class="bi bi-plus-circle"></i> Məhsul Əlavə Et
                </button>
                <div id="category-products-list" class="items-grid"></div>
            </div>
        </div>
    </main>

    <!-- Məhsul əlavə etmə seçimi (əl ilə / bazadan) -->
    <div id="productAddChoiceModal" class="modal-overlay" onclick="if(event.target === this) closeModal('productAddChoiceModal')">
        <div class="modal-box" onclick="event.stopPropagation()" style="max-width:420px;">
            <div class="modal-header">
                <h2>Məhsul necə əlavə edilsin?</h2>
                <button class="btn-close-modal" onclick="closeModal('productAddChoiceModal')"><i class="bi bi-x"></i></button>
            </div>
            <div style="display:flex;flex-direction:column;gap:12px;">
                <button type="button" class="btn-submit" style="justify-content:center;padding:14px 20px;" onclick="addProductManualFromMenu()">
                    <i class="bi bi-pencil-square"></i> Əl ilə daxil et
                </button>
                <button type="button" class="btn-small" style="justify-content:center;padding:14px 20px;width:100%;" onclick="openProductFromLibraryFromMenu()">
                    <i class="bi bi-database-add"></i> Bazadan seç (şəkilli)
                </button>
            </div>
        </div>
    </div>

    <!-- Bazadan məhsul seçmə (menyu idarəçisində) -->
    <div id="productLibraryModal" class="modal-overlay" onclick="if(event.target === this) closeModal('productLibraryModal')">
        <div class="modal-box" onclick="event.stopPropagation()" style="max-width:720px;max-height:90vh;display:flex;flex-direction:column;">
            <div class="modal-header">
                <h2>Bazadan məhsul seçin</h2>
                <button class="btn-close-modal" onclick="closeModal('productLibraryModal')"><i class="bi bi-x"></i></button>
            </div>
            <div style="margin-bottom:12px;">
                <input type="text" id="productLibrarySearch" placeholder="Məhsul və ya kateqoriya adına görə axtarış..." style="width:100%;padding:10px 14px;border-radius:8px;border:1px solid #e5e7eb;" oninput="filterProductLibraryList(this.value)">
            </div>
            <div id="productLibraryAddBar" style="display:none;padding:12px 0;border-bottom:1px solid #e5e7eb;margin-bottom:12px;">
                <span>Seçildi: <strong id="productLibrarySelectedName"></strong> <span id="productLibraryCategoryHint"></span></span>
                <button type="button" class="btn-submit" style="margin-left:12px;width:auto;padding:8px 16px;" onclick="addProductFromTemplateSubmitInMenu()"><i class="bi bi-plus-lg"></i> Əlavə et</button>
            </div>
            <div style="overflow:auto;flex:1;">
                <table class="table" style="margin:0;width:100%;">
                    <thead>
                        <tr>
                            <th style="width:70px;">Şəkil</th>
                            <th>Ad</th>
                            <th>Kateqoriya</th>
                            <th>Qiymət</th>
                            <th>Əməliyyat</th>
                        </tr>
                    </thead>
                    <tbody id="productLibraryList"></tbody>
                </table>
            </div>
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

    <!-- Kateqoriya əlavə etmə seçimi (əl ilə / bazadan) -->
    <div id="categoryAddChoiceModal" class="modal-overlay" onclick="if(event.target === this) closeModal('categoryAddChoiceModal')">
        <div class="modal-box" onclick="event.stopPropagation()" style="max-width:420px;">
            <div class="modal-header">
                <h2>Kateqoriya necə əlavə edilsin?</h2>
                <button class="btn-close-modal" onclick="closeModal('categoryAddChoiceModal')"><i class="bi bi-x"></i></button>
            </div>
            <div style="display:flex;flex-direction:column;gap:12px;">
                <button type="button" class="btn-submit" style="justify-content:center;padding:14px 20px;" onclick="addCategoryManualFromMenu()">
                    <i class="bi bi-pencil-square"></i> Əl ilə daxil et
                </button>
                <button type="button" class="btn-small" style="justify-content:center;padding:14px 20px;width:100%;" onclick="openCategoryFromLibraryFromMenu()">
                    <i class="bi bi-database-add"></i> Bazadan seç
                </button>
            </div>
        </div>
    </div>

    <!-- Bazadan kateqoriya seçmə -->
    <div id="categoryLibraryModal" class="modal-overlay" onclick="if(event.target === this) closeModal('categoryLibraryModal')">
        <div class="modal-box" onclick="event.stopPropagation()" style="max-width:560px;max-height:80vh;display:flex;flex-direction:column;">
            <div class="modal-header">
                <h2>Bazadan kateqoriya seçin</h2>
                <button class="btn-close-modal" onclick="closeModal('categoryLibraryModal')"><i class="bi bi-x"></i></button>
            </div>
            <div style="margin-bottom:12px;">
                <input type="text" id="categoryLibrarySearch" placeholder="Kateqoriya adına görə axtarış..." style="width:100%;padding:10px 14px;border-radius:8px;border:1px solid #e5e7eb;" oninput="filterCategoryLibraryList(this.value)">
            </div>
            <div style="overflow:auto;flex:1;">
                <table class="table" style="margin:0;width:100%;">
                    <thead>
                        <tr>
                            <th style="width:40px;"><input type="checkbox" id="categoryLibrarySelectAll" title="Hamısını seç" onclick="toggleCategoryLibrarySelectAll(this)"></th>
                            <th>Ad</th>
                        </tr>
                    </thead>
                    <tbody id="categoryLibraryList"></tbody>
                </table>
            </div>
            <div style="padding:12px;border-top:1px solid #e5e7eb;">
                <button type="button" class="btn-submit" onclick="addSelectedCategoriesFromTemplateInMenu()"><i class="bi bi-plus-lg"></i> Seçilənləri əlavə et</button>
            </div>
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

    <!-- Set Modal -->
    <div id="setModal" class="modal-overlay" onclick="if(event.target === this) closeModal('setModal')">
        <div class="modal-box" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h2 id="setModalTitle">Set Əlavə Et</h2>
                <button class="btn-close-modal" onclick="closeModal('setModal')">
                    <i class="bi bi-x"></i>
                </button>
            </div>
            <form id="setForm" onsubmit="saveSet(event)">
                <input type="hidden" id="setId" name="id">
                <div class="form-group">
                    <label>Ad *</label>
                    <input type="text" id="setName" name="name" required>
                </div>
                <div class="form-group">
                    <label>Təsvir</label>
                    <textarea id="setDescription" name="description" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label>Qiymət (₼) *</label>
                    <input type="number" id="setPrice" name="price" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <label>Şəkil</label>
                    <input type="file" id="setImage" name="image" accept="image/*" onchange="previewImage(this, 'setPreview')">
                    <div class="image-preview" id="setPreview">
                        <i class="bi bi-image"></i>
                    </div>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="setActive" name="is_active" checked>
                        Aktiv
                    </label>
                </div>
                <button type="submit" class="btn-submit">Yadda Saxla</button>
            </form>
        </div>
    </div>

    <div class="theme-toggle-container" style="position: fixed; top: 20px; right: 20px; z-index: 1000;">
        <button class="theme-toggle" onclick="toggleTheme()" aria-label="Rəng rejimini dəyişdir" title="Rəng rejimi" style="width: 44px; height: 44px; background: var(--dark-card); border: 1px solid var(--dark-border); border-radius: 0; color: var(--text-primary); cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 18px; transition: all 0.3s ease;">
            <i class="bi bi-moon-fill" id="themeIcon"></i>
        </button>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const RESTAURANT_ID = <?php echo $restaurant_id; ?>;

        // Theme management
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
            updateThemeIcon(savedTheme);
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
            } else if (modalId === 'productAddChoiceModal' || modalId === 'productLibraryModal') {
                // no reset needed
            } else if (modalId === 'categoryModal') {
                document.getElementById('categoryForm').reset();
                document.getElementById('categoryId').value = '';
            } else if (modalId === 'setModal') {
                document.getElementById('setForm').reset();
                document.getElementById('setPreview').innerHTML = '<i class="bi bi-image"></i>';
                document.getElementById('setId').value = '';
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

        // Load data
        function loadCategories() {
            fetch(`../api/api.php?action=get_categories&restaurant_id=${RESTAURANT_ID}`)
                .then(response => response.json())
                .then(data => {
                    const list = document.getElementById('categories-list');
                    if (data.success && data.categories.length > 0) {
                        list.innerHTML = data.categories.map((item, idx) => {
                            const hues = [220, 280, 340, 160, 30, 200, 260];
                            const code = (item.name || '').split('').reduce((a, c) => a + c.charCodeAt(0), 0) + idx;
                            const hue = hues[code % hues.length];
                            const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
                            const bg = isDark ? `hsla(${hue}, 25%, 18%, 0.6)` : `hsla(${hue}, 40%, 95%, 0.9)`;
                            return `<div class="item-card category-card-clickable" onclick="openCategoryPage(${item.id}, this.getAttribute('data-cat-name'))" data-cat-name="${escapeHtml(item.name || '')}" title="Kateqoriyaya daxil ol" style="background:${bg}">
                                <div class="item-name">${escapeHtml(item.name)}</div>
                                <div class="item-price">Sıra: ${item.display_order}</div>
                                <div class="item-actions" onclick="event.stopPropagation()">
                                    <button class="btn-small" onclick="editCategory(${item.id})">
                                        <i class="bi bi-pencil"></i> Redaktə
                                    </button>
                                    <button class="btn-small" onclick="deleteCategory(${item.id})">
                                        <i class="bi bi-trash"></i> Sil
                                    </button>
                                </div>
                            </div>`;
                        }).join('');
                    } else {
                        list.innerHTML = '<div class="empty-state"><i class="bi bi-inbox"></i><p>Kateqoriya yoxdur</p></div>';
                    }
                });
        }

        // Load categories into product form
        function loadCategoriesForProductForm(onLoaded) {
            fetch(`../api/api.php?action=get_categories&restaurant_id=${RESTAURANT_ID}`)
                .then(response => response.json())
                .then(data => {
                    const select = document.getElementById('productCategory');
                    if (data.success && data.categories) {
                        select.innerHTML = '<option value="">Kateqoriya seçin...</option>';
                        data.categories.forEach(cat => {
                            const option = document.createElement('option');
                            option.value = cat.id;
                            option.textContent = cat.name;
                            select.appendChild(option);
                        });
                    } else {
                        select.innerHTML = '<option value="">Kateqoriya yoxdur</option>';
                    }
                    if (typeof onLoaded === 'function') onLoaded();
                });
        }

        // Məhsul əlavə et: seçim (əl ilə / bazadan)
        let productTemplatesCacheMenu = [];
        let selectedTemplateIdMenu = null;
        let selectedCategoryIdForProduct = null;
        let currentViewedCategoryId = null;
        let currentViewedCategoryName = null;

        function openCategoryPage(categoryId, categoryName) {
            currentViewedCategoryId = categoryId;
            currentViewedCategoryName = categoryName || '';
            selectedCategoryIdForProduct = categoryId;
            document.getElementById('categories-list-view').style.display = 'none';
            document.getElementById('category-detail-view').style.display = 'block';
            document.getElementById('categoryDetailTitle').textContent = categoryName || 'Kateqoriya';
            loadCategoryProducts(categoryId);
        }

        function showCategoriesList() {
            currentViewedCategoryId = null;
            currentViewedCategoryName = null;
            selectedCategoryIdForProduct = null;
            document.getElementById('category-detail-view').style.display = 'none';
            document.getElementById('categories-list-view').style.display = 'block';
            loadCategories();
        }

        function loadCategoryProducts(categoryId) {
            fetch(`../api/api.php?action=get&restaurant_id=${RESTAURANT_ID}&category_id=${categoryId}`)
                .then(response => response.json())
                .then(data => {
                    const list = document.getElementById('category-products-list');
                    if (data.success && data.products && data.products.length > 0) {
                        const baseUrl = '../';
                        list.innerHTML = data.products.map(item => {
                            const imgSrc = item.image_path ? (item.image_path.startsWith('http') || item.image_path.startsWith('/') ? item.image_path : baseUrl + item.image_path) : '';
                            return `<div class="item-card">
                                <div class="item-image">
                                    ${imgSrc ? `<img src="${imgSrc}" alt="${escapeHtml(item.name || '')}">` : '<i class="bi bi-image"></i>'}
                                </div>
                                <div class="item-name">${escapeHtml(item.name)}</div>
                                <div class="item-price">${parseFloat(item.price || 0).toFixed(2)} ₼</div>
                                <div class="item-actions">
                                    <button class="btn-small" onclick="editProduct(${item.id})"><i class="bi bi-pencil"></i> Redaktə</button>
                                    <button class="btn-small" onclick="deleteProduct(${item.id})"><i class="bi bi-trash"></i> Sil</button>
                                </div>
                            </div>`;
                        }).join('');
                    } else {
                        list.innerHTML = '<div class="empty-state"><i class="bi bi-inbox"></i><p>Bu kateqoriyada məhsul yoxdur</p></div>';
                    }
                });
        }

        function editCategoryFromDetail() {
            if (currentViewedCategoryId) editCategory(currentViewedCategoryId);
        }

        function deleteCategoryFromDetail() {
            if (currentViewedCategoryId) {
                if (confirm('Bu kateqoriyanı silmək istədiyinizə əminsiniz?')) {
                    fetch(`../api/api.php?action=delete_category&id=${currentViewedCategoryId}`, { method: 'POST' })
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) {
                                showCategoriesList();
                                loadCategoriesForProductForm();
                                alert('Kateqoriya silindi');
                            } else alert('Xəta: ' + (data.message || ''));
                        });
                }
            }
        }

        function openAddProductFromCategoryPage() {
            if (selectedCategoryIdForProduct) openModal('productAddChoiceModal');
        }

        function showAddProductChoice() {
            selectedCategoryIdForProduct = null;
            openModal('productAddChoiceModal');
        }

        function addProductManualFromMenu() {
            closeModal('productAddChoiceModal');
            openProductModal();
        }

        function openProductFromLibraryFromMenu() {
            closeModal('productAddChoiceModal');
            const addBar = document.getElementById('productLibraryAddBar');
            const listTbody = document.getElementById('productLibraryList');
            if (!listTbody) return;
            if (addBar) addBar.style.display = 'none';
            const searchInput = document.getElementById('productLibrarySearch');
            if (searchInput) searchInput.value = '';
            selectedTemplateIdMenu = null;
            listTbody.innerHTML = '<tr><td colspan="5" class="text-center">Yüklənir...</td></tr>';
            openModal('productLibraryModal');
            fetch('../api/api.php?action=get_product_templates')
                .then(r => r.json())
                .then(data => {
                    if (!data.success || !data.templates || data.templates.length === 0) {
                        listTbody.innerHTML = '<tr><td colspan="5" class="text-center">Bazada hələ məhsul yoxdur.</td></tr>';
                        productTemplatesCacheMenu = [];
                        return;
                    }
                    productTemplatesCacheMenu = data.templates;
                    const baseUrl = '../';
                    listTbody.innerHTML = data.templates.map((t, idx) => {
                        const imgSrc = t.image_path ? (t.image_path.startsWith('http') || t.image_path.startsWith('/') ? t.image_path : baseUrl + t.image_path) : '';
                        const searchText = ((t.name || '') + ' ' + (t.category_name || '')).toLowerCase().replace(/"/g, '&quot;');
                        return `<tr data-search="${searchText}">
                            <td style="width:70px;">${imgSrc ? `<img src="${imgSrc}" alt="" style="width:56px;height:56px;object-fit:cover;border-radius:6px;">` : '<div style="width:56px;height:56px;background:#eee;border-radius:6px;"></div>'}</td>
                            <td><strong>${escapeHtml(t.name || '-')}</strong></td>
                            <td>${escapeHtml(t.category_name || '-')}</td>
                            <td>${parseFloat(t.price || 0).toFixed(2)} ₼</td>
                            <td><button type="button" class="btn-small" onclick="selectTemplateInMenu(${t.id}, ${idx})"><i class="bi bi-plus-circle"></i> Seç</button></td>
                        </tr>`;
                    }).join('');
                })
                .catch(() => {
                    listTbody.innerHTML = '<tr><td colspan="5" class="text-center">Yükləmə xətası</td></tr>';
                    productTemplatesCacheMenu = [];
                });
        }

        function filterProductLibraryList(query) {
            const q = (query || '').trim().toLowerCase();
            document.querySelectorAll('#productLibraryList tr[data-search]').forEach(tr => {
                tr.style.display = !q || tr.getAttribute('data-search').indexOf(q) >= 0 ? '' : 'none';
            });
        }

        function selectTemplateInMenu(id, idx) {
            selectedTemplateIdMenu = id;
            const tpl = productTemplatesCacheMenu[idx];
            const name = (tpl && tpl.name) ? tpl.name : '(seçildi)';
            const categoryName = (tpl && tpl.category_name) ? tpl.category_name : '';
            document.getElementById('productLibrarySelectedName').textContent = name;
            const hintEl = document.getElementById('productLibraryCategoryHint');
            if (hintEl) hintEl.textContent = categoryName ? `(${categoryName})` : '';
            document.getElementById('productLibraryAddBar').style.display = 'block';
        }

        function addProductFromTemplateSubmitInMenu() {
            if (!selectedTemplateIdMenu) {
                alert('Məhsul seçin.');
                return;
            }
            const formData = new FormData();
            formData.append('action', 'add_product_from_template');
            formData.append('template_id', selectedTemplateIdMenu);
            formData.append('restaurant_id', RESTAURANT_ID);
            if (selectedCategoryIdForProduct) formData.append('category_id', selectedCategoryIdForProduct);
            fetch('../api/api.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        closeModal('productLibraryModal');
                        if (currentViewedCategoryId) loadCategoryProducts(currentViewedCategoryId);
                        alert('Məhsul əlavə edildi.');
                    } else {
                        alert('Xəta: ' + (data.message || ''));
                    }
                })
                .catch(() => alert('Şəbəkə xətası'));
        }

        // Product functions
        function openProductModal() {
            document.getElementById('productModalTitle').textContent = 'Məhsul Əlavə Et';
            document.getElementById('productForm').reset();
            document.getElementById('productId').value = '';
            document.getElementById('productPreview').innerHTML = '<i class="bi bi-image"></i>';
            loadCategoriesForProductForm(function() {
                if (selectedCategoryIdForProduct) {
                    const sel = document.getElementById('productCategory');
                    if (sel) sel.value = selectedCategoryIdForProduct;
                }
            });
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
                        // Wait a bit for categories to load
                        setTimeout(() => {
                            document.getElementById('productCategory').value = item.category_id || '';
                        }, 100);
                        if (item.image_path) {
                            const imgSrc = item.image_path.startsWith('http') || item.image_path.startsWith('/') ? item.image_path : '../' + item.image_path;
                            document.getElementById('productPreview').innerHTML = `<img src="${imgSrc}" alt="Preview">`;
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
                    if (currentViewedCategoryId) loadCategoryProducts(currentViewedCategoryId);
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
                            if (currentViewedCategoryId) loadCategoryProducts(currentViewedCategoryId);
                            alert('Məhsul silindi');
                        } else {
                            alert('Xəta: ' + data.message);
                        }
                    });
            }
        }

        // Category functions
        function showAddCategoryChoice() {
            openModal('categoryAddChoiceModal');
        }
        function addCategoryManualFromMenu() {
            closeModal('categoryAddChoiceModal');
            openCategoryModal();
        }
        function openCategoryModal() {
            document.getElementById('categoryModalTitle').textContent = 'Kateqoriya Əlavə Et';
            document.getElementById('categoryForm').reset();
            document.getElementById('categoryId').value = '';
            openModal('categoryModal');
        }
        function openCategoryFromLibraryFromMenu() {
            closeModal('categoryAddChoiceModal');
            const listTbody = document.getElementById('categoryLibraryList');
            const selectAll = document.getElementById('categoryLibrarySelectAll');
            if (!listTbody) return;
            if (selectAll) selectAll.checked = false;
            const searchInput = document.getElementById('categoryLibrarySearch');
            if (searchInput) searchInput.value = '';
            listTbody.innerHTML = '<tr><td colspan="2" class="text-center">Yüklənir...</td></tr>';
            openModal('categoryLibraryModal');
            fetch('../api/api.php?action=get_category_templates')
                .then(r => r.json())
                .then(data => {
                    if (!data.success || !data.templates || data.templates.length === 0) {
                        listTbody.innerHTML = '<tr><td colspan="2" class="text-center">Bazada hələ kateqoriya yoxdur. Əvvəlcə məhsul əlavə edin və mərkəzi bazanı yeniləyin.</td></tr>';
                        return;
                    }
                    listTbody.innerHTML = data.templates.map(t => {
                        const searchText = (t.name || '').toLowerCase().replace(/"/g, '&quot;');
                        return `<tr data-search="${searchText}">
                            <td><input type="checkbox" class="category-library-cb" value="${t.id}"></td>
                            <td><strong>${escapeHtml(t.name || '-')}</strong></td>
                        </tr>`;
                    }).join('');
                })
                .catch(() => {
                    listTbody.innerHTML = '<tr><td colspan="2" class="text-center">Yükləmə xətası</td></tr>';
                });
        }
        function filterCategoryLibraryList(query) {
            const q = (query || '').trim().toLowerCase();
            document.querySelectorAll('#categoryLibraryList tr[data-search]').forEach(tr => {
                tr.style.display = !q || tr.getAttribute('data-search').indexOf(q) >= 0 ? '' : 'none';
            });
        }

        function toggleCategoryLibrarySelectAll(checkbox) {
            document.querySelectorAll('.category-library-cb').forEach(cb => cb.checked = !!checkbox.checked);
        }
        function addSelectedCategoriesFromTemplateInMenu() {
            const checked = Array.from(document.querySelectorAll('.category-library-cb:checked')).map(cb => cb.value);
            if (checked.length === 0) {
                alert('Ən azı bir kateqoriya seçin.');
                return;
            }
            const formData = new FormData();
            formData.append('action', 'add_categories_from_template');
            formData.append('template_ids', JSON.stringify(checked));
            formData.append('restaurant_id', RESTAURANT_ID);
            fetch('../api/api.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        closeModal('categoryLibraryModal');
                        loadCategories();
                        loadCategoriesForProductForm();
                        alert(data.message || 'Kateqoriyalar əlavə edildi.');
                    } else {
                        alert('Xəta: ' + (data.message || ''));
                    }
                })
                .catch(() => alert('Şəbəkə xətası'));
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
                        document.getElementById('categoryOrder').value = item.display_order;
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
                    loadCategoriesForProductForm(); // Reload categories in product form
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
                            loadCategoriesForProductForm(); // Reload categories in product form
                            alert('Kateqoriya silindi');
                        } else {
                            alert('Xəta: ' + data.message);
                        }
                    });
            }
        }

        // Set functions
        function openSetModal() {
            document.getElementById('setModalTitle').textContent = 'Set Əlavə Et';
            document.getElementById('setForm').reset();
            document.getElementById('setId').value = '';
            document.getElementById('setPreview').innerHTML = '<i class="bi bi-image"></i>';
            document.getElementById('setActive').checked = true;
            openModal('setModal');
        }

        function editSet(id) {
            fetch(`../api/api.php?action=get_sets&restaurant_id=${RESTAURANT_ID}`)
                .then(response => response.json())
                .then(data => {
                    const item = data.sets.find(s => s.id == id);
                    if (item) {
                        document.getElementById('setModalTitle').textContent = 'Seti Redaktə Et';
                        document.getElementById('setId').value = item.id;
                        document.getElementById('setName').value = item.name;
                        document.getElementById('setDescription').value = item.description || '';
                        document.getElementById('setPrice').value = item.price;
                        document.getElementById('setActive').checked = item.is_active == 1;
                        if (item.image_path) {
                            document.getElementById('setPreview').innerHTML = `<img src="${item.image_path}" alt="Preview">`;
                        }
                        openModal('setModal');
                    }
                });
        }

        function saveSet(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            formData.append('action', document.getElementById('setId').value ? 'update_set' : 'add_set');
            formData.append('restaurant_id', RESTAURANT_ID);
            
            fetch('../api/api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeModal('setModal');
                    alert('Set uğurla yadda saxlanıldı');
                } else {
                    alert('Xəta: ' + data.message);
                }
            });
        }

        function deleteSet(id) {
            if (confirm('Bu seti silmək istədiyinizə əminsiniz?')) {
                fetch(`../api/api.php?action=delete_set&id=${id}`, { method: 'POST' })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Set silindi');
                        } else {
                            alert('Xəta: ' + data.message);
                        }
                    });
            }
        }

        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text ? String(text).replace(/[&<>"']/g, m => map[m]) : '';
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            loadCategories();
        });
    </script>
</body>
</html>

