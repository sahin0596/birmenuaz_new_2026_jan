<?php
session_start();

// Check if logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ../login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - BirMenu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="admin.css">
    <style>
        body {
            background: #f1f5f9;
            color: var(--text-primary);
            min-height: 100vh;
            font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            line-height: 1.6;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        [data-theme="dark"] body {
            background: #0c0c0c;
        }

        .admin-header-new {
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: none;
            border-bottom: 1px solid rgba(0, 0, 0, 0.06);
            border-radius: 0;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            transition: all 0.3s ease;
        }

        [data-theme="dark"] .admin-header-new {
            background: rgba(18, 18, 18, 0.92);
            border-bottom-color: rgba(255, 255, 255, 0.06);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }

        .admin-header-new h1 {
            font-size: 1.35rem;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
            letter-spacing: -0.03em;
        }

        .admin-user-info {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .admin-username {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            color: #64748b;
            padding: 8px 14px;
            background: rgba(0, 0, 0, 0.03);
            border-radius: 10px;
            transition: all 0.2s ease;
            font-size: 0.875rem;
        }

        [data-theme="dark"] .admin-username {
            color: #94a3b8;
            background: rgba(255, 255, 255, 0.05);
        }

        .admin-username i {
            font-size: 15px;
            opacity: 0.8;
        }

        .btn-logout {
            background: #0f172a;
            color: white;
            padding: 8px 18px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
            font-size: 0.85rem;
        }

        [data-theme="dark"] .btn-logout {
            background: #f8fafc;
            color: #0f172a;
        }

        .btn-logout:hover {
            background: #1e293b;
            transform: translateY(-1px);
        }

        [data-theme="dark"] .btn-logout:hover {
            background: #e2e8f0;
            color: #0f172a;
        }

        /* Statistics Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 32px;
        }
        
        @media (min-width: 1920px) {
            .stats-grid {
                gap: 24px;
            }
        }

        .stat-card {
            background: white;
            border: 1px solid rgba(0, 0, 0, 0.06);
            border-radius: 14px;
            padding: 24px;
            display: flex;
            align-items: center;
            gap: 18px;
            transition: all 0.25s ease;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        }

        [data-theme="dark"] .stat-card {
            background: #141414;
            border-color: rgba(255, 255, 255, 0.06);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.08);
        }

        [data-theme="dark"] .stat-card:hover {
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.35);
        }

        .stat-icon {
            width: 52px;
            height: 52px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f1f5f9;
            border-radius: 12px;
            flex-shrink: 0;
        }

        [data-theme="dark"] .stat-icon {
            background: rgba(255, 255, 255, 0.06);
        }

        .stat-icon i {
            font-size: 24px;
            color: #64748b;
        }

        [data-theme="dark"] .stat-icon i {
            color: #94a3b8;
        }

        .stat-card.active .stat-icon {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        }

        [data-theme="dark"] .stat-card.active .stat-icon {
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
        }

        .stat-card.active .stat-icon i {
            color: white;
        }

        [data-theme="dark"] .stat-card.active .stat-icon i {
            color: #0f172a;
        }

        .stat-card.inactive .stat-icon i {
            color: #64748b;
        }

        .stat-content {
            flex: 1;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1.2;
            margin-bottom: 4px;
            letter-spacing: -0.02em;
        }

        .stat-label {
            font-size: 13px;
            color: #64748b;
            font-weight: 500;
        }

        [data-theme="dark"] .stat-label {
            color: #94a3b8;
        }

        .restaurant-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 18px;
            margin-top: 28px;
        }
        
        @media (min-width: 1920px) {
            .restaurant-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 20px;
            }
        }

        .restaurant-card {
            background: white;
            border: 1px solid rgba(0, 0, 0, 0.06);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            transition: all 0.25s ease;
            position: relative;
        }

        [data-theme="dark"] .restaurant-card {
            background: #141414;
            border-color: rgba(255, 255, 255, 0.06);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
        }

        .restaurant-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 16px 32px rgba(0, 0, 0, 0.1);
            border-color: rgba(0, 0, 0, 0.08);
        }

        [data-theme="dark"] .restaurant-card:hover {
            border-color: rgba(255, 255, 255, 0.1);
            box-shadow: 0 16px 32px rgba(0, 0, 0, 0.4);
        }

        .restaurant-card-cover {
            height: 95px;
            background: #f1f5f9;
            position: relative;
            overflow: hidden;
        }

        [data-theme="dark"] .restaurant-card-cover {
            background: #1a1a1a;
        }

        .restaurant-card-cover::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 36px;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.25), transparent);
        }

        .restaurant-card-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s ease;
        }

        .restaurant-card:hover .restaurant-card-cover img {
            transform: scale(1.05);
        }

        .restaurant-card-body {
            padding: 14px;
            position: relative;
            z-index: 1;
        }

        .restaurant-logo {
            width: 42px;
            height: 42px;
            border-radius: 8px;
            border: 2px solid white;
            margin-top: -21px;
            margin-bottom: 8px;
            object-fit: cover;
            background: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: all 0.25s ease;
        }

        [data-theme="dark"] .restaurant-logo {
            border-color: #141414;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }

        .restaurant-card:hover .restaurant-logo {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .restaurant-name {
            font-size: 0.9375rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 6px;
            line-height: 1.3;
            letter-spacing: -0.02em;
        }

        .restaurant-meta {
            display: flex;
            gap: 6px;
            margin-bottom: 6px;
            padding-bottom: 6px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.06);
            flex-wrap: wrap;
        }

        [data-theme="dark"] .restaurant-meta {
            border-bottom-color: rgba(255, 255, 255, 0.06);
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 10px;
            color: #9ca3af;
        }

        .meta-item i {
            font-size: 11px;
        }

        .meta-item span {
            font-weight: 500;
        }

        .restaurant-info {
            color: #6b7280;
            font-size: 11px;
            margin-bottom: 8px;
            line-height: 1.4;
        }

        [data-theme="dark"] .restaurant-info {
            color: #9ca3af;
        }

        .restaurant-info div {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 4px;
        }

        .restaurant-info i {
            color: #6b7280;
            font-size: 14px;
        }

        [data-theme="dark"] .restaurant-info i {
            color: #9ca3af;
        }

        .restaurant-actions {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 5px;
            padding-top: 10px;
            border-top: 1px solid rgba(0, 0, 0, 0.06);
        }

        [data-theme="dark"] .restaurant-actions {
            border-top-color: rgba(255, 255, 255, 0.06);
        }

        .btn-edit, .btn-delete-rest, .btn-qr, .btn-menu, .btn-export, .btn-view {
            flex: 1;
            padding: 7px 5px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 3px;
            font-size: 10px;
            border: 1px solid rgba(0, 0, 0, 0.1);
        }

        .btn-menu {
            background: transparent;
            color: #6b7280;
            border-color: #d1d5db;
        }

        [data-theme="dark"] .btn-menu {
            color: #9ca3af;
            border-color: #2a2a2a;
        }

        .btn-menu:hover {
            background: #000;
            color: white;
            border-color: #000;
        }

        [data-theme="dark"] .btn-menu:hover {
            background: white;
            color: #000;
            border-color: white;
        }

        .btn-edit {
            background: transparent;
            color: #6b7280;
            border-color: #d1d5db;
        }

        [data-theme="dark"] .btn-edit {
            color: #9ca3af;
            border-color: #2a2a2a;
        }

        .btn-edit:hover {
            background: #000;
            color: white;
            border-color: #000;
        }

        [data-theme="dark"] .btn-edit:hover {
            background: white;
            color: #000;
            border-color: white;
        }

        .btn-qr {
            background: transparent;
            color: #6b7280;
            border-color: #d1d5db;
        }

        [data-theme="dark"] .btn-qr {
            color: #9ca3af;
            border-color: #2a2a2a;
        }

        .btn-qr:hover {
            background: #000;
            color: white;
            border-color: #000;
        }

        [data-theme="dark"] .btn-qr:hover {
            background: white;
            color: #000;
            border-color: white;
        }

        .btn-delete-rest {
            background: transparent;
            color: #6b7280;
            border-color: #d1d5db;
        }

        [data-theme="dark"] .btn-delete-rest {
            color: #9ca3af;
            border-color: #2a2a2a;
        }

        .btn-delete-rest:hover {
            background: #000;
            color: white;
            border-color: #000;
        }

        [data-theme="dark"] .btn-delete-rest:hover {
            background: white;
            color: #000;
            border-color: white;
        }

        .btn-export {
            background: transparent;
            color: #6b7280;
            border-color: #d1d5db;
        }

        [data-theme="dark"] .btn-export {
            color: #9ca3af;
            border-color: #2a2a2a;
        }

        .btn-export:hover {
            background: #10b981;
            color: white;
            border-color: #10b981;
        }

        [data-theme="dark"] .btn-export:hover {
            background: #10b981;
            color: white;
            border-color: #10b981;
        }

        .btn-import-top:hover {
            background: transparent;
            color: #3b82f6;
            border-color: #3b82f6;
        }

        [data-theme="dark"] .btn-import-top {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }

        [data-theme="dark"] .btn-import-top:hover {
            background: transparent;
            color: #3b82f6;
            border-color: #3b82f6;
        }

        .btn-add-restaurant {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: white;
            padding: 12px 22px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.875rem;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.3);
        }

        [data-theme="dark"] .btn-add-restaurant {
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            color: #0f172a;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        .btn-add-restaurant:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.35);
        }

        [data-theme="dark"] .btn-add-restaurant:hover {
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.4);
        }

        .btn-add-restaurant i {
            transition: transform 0.3s ease;
        }

        .btn-add-restaurant:hover i {
            transform: rotate(90deg);
        }

        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(13, 17, 23, 0.85);
            backdrop-filter: blur(12px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: 20px;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border: 1px solid rgba(0, 0, 0, 0.06);
            border-radius: 20px;
            padding: 36px;
            max-width: 750px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 24px 48px rgba(0, 0, 0, 0.12);
        }
        
        @media (min-width: 1920px) {
            .modal-content {
                max-width: 850px;
                padding: 40px;
            }
        }

        [data-theme="dark"] .modal-content {
            background: #141414;
            border-color: rgba(255, 255, 255, 0.06);
            box-shadow: 0 24px 48px rgba(0, 0, 0, 0.5);
        }

        .modal-content::-webkit-scrollbar {
            width: 6px;
        }

        .modal-content::-webkit-scrollbar-track {
            background: var(--bg-secondary);
        }

        .modal-content::-webkit-scrollbar-thumb {
            background: var(--border-color);
            border-radius: 3px;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.06);
        }

        [data-theme="dark"] .modal-header {
            border-bottom-color: rgba(255, 255, 255, 0.06);
        }

        .modal-header h2 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0;
            letter-spacing: 0;
        }

        .btn-close-modal {
            background: rgba(0, 0, 0, 0.04);
            border: 1px solid rgba(0, 0, 0, 0.08);
            font-size: 18px;
            color: var(--text-secondary);
            cursor: pointer;
            padding: 0;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            transition: all 0.2s ease;
        }

        .btn-close-modal:hover {
            background: #0f172a;
            color: white;
            border-color: #0f172a;
        }

        [data-theme="dark"] .btn-close-modal {
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(255, 255, 255, 0.1);
        }

        [data-theme="dark"] .btn-close-modal:hover {
            background: #f1f5f9;
            color: #0f172a;
            border-color: #f1f5f9;
        }

        .form-group-new {
            margin-bottom: 24px;
        }

        .form-group-new label {
            display: block;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 10px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-group-new input,
        .form-group-new textarea {
            width: 100%;
            padding: 12px 14px;
            background: #f8fafc;
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.2s ease;
            font-family: inherit;
            color: var(--text-primary);
        }

        [data-theme="dark"] .form-group-new input,
        [data-theme="dark"] .form-group-new textarea {
            background: rgba(255, 255, 255, 0.04);
            border-color: rgba(255, 255, 255, 0.1);
        }

        .form-group-new input::placeholder,
        .form-group-new textarea::placeholder {
            color: #9ca3af;
        }

        .form-group-new input:focus,
        .form-group-new textarea:focus {
            outline: none;
            border-color: #000;
            background: white;
        }

        [data-theme="dark"] .form-group-new input:focus,
        [data-theme="dark"] .form-group-new textarea:focus {
            background: #1a1a1a;
            border-color: white;
        }

        .form-group-new textarea {
            resize: vertical;
            min-height: 80px;
        }

        .form-group-new small {
            color: var(--text-secondary);
            font-size: 12px;
            margin-top: 4px;
            display: block;
        }

        .form-group-new input[type="checkbox"] {
            width: auto;
            margin-right: 8px;
            cursor: pointer;
        }

        .btn-submit {
            width: 100%;
            padding: 14px 24px;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 20px;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.25);
        }

        [data-theme="dark"] .btn-submit {
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            color: #0f172a;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        .btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.3);
        }

        [data-theme="dark"] .btn-submit:hover {
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.4);
        }

        .image-preview-new {
            margin-top: 12px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .image-preview-new img {
            max-width: 150px;
            max-height: 150px;
            border-radius: 12px;
            object-fit: cover;
            border: 1px solid rgba(0, 0, 0, 0.08);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .no-restaurants {
            text-align: center;
            padding: 64px 24px;
            color: var(--text-secondary);
            grid-column: 1 / -1;
            background: white;
            border-radius: 20px;
            border: 1px dashed rgba(0, 0, 0, 0.1);
        }

        [data-theme="dark"] .no-restaurants {
            background: #141414;
            border-color: rgba(255, 255, 255, 0.08);
        }

        .no-restaurants i {
            font-size: 64px;
            color: rgba(15, 23, 42, 0.2);
            margin-bottom: 20px;
            display: block;
        }

        [data-theme="dark"] .no-restaurants i {
            color: rgba(255, 255, 255, 0.1);
        }

        .no-restaurants h3 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text-primary);
            letter-spacing: -0.02em;
        }

        .no-restaurants p {
            font-size: 0.9rem;
            color: #64748b;
        }

        .top-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
            padding: 0;
            background: transparent;
            border: none;
        }

        .back-link-new {
            text-decoration: none;
            color: #64748b;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 10px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            background: white;
            transition: all 0.2s ease;
            font-size: 0.875rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        }

        [data-theme="dark"] .back-link-new {
            color: #94a3b8;
            border-color: rgba(255, 255, 255, 0.1);
            background: #1a1a1a;
        }

        .back-link-new:hover {
            background: #0f172a;
            color: white;
            border-color: #0f172a;
        }

        [data-theme="dark"] .back-link-new:hover {
            background: #f1f5f9;
            color: #0f172a;
            border-color: #f1f5f9;
        }

        .modal-content::-webkit-scrollbar {
            width: 8px;
        }

        .modal-content::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
        }

        .modal-content::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 0;
        }

        @media (max-width: 768px) {
            .admin-header-new {
                flex-direction: column;
                gap: 16px;
                text-align: center;
                padding: 16px 20px;
            }

            .admin-header-new h1 {
                font-size: 1.3rem;
            }

            .admin-user-info {
                flex-direction: row;
                gap: 10px;
                margin-right: 0;
                width: 100%;
                justify-content: center;
            }

            .admin-username {
                font-size: 0.85rem;
                padding: 7px 14px;
            }

            .btn-logout {
                font-size: 0.8rem;
                padding: 7px 16px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 12px;
                margin-bottom: 24px;
            }

            .stat-card {
                padding: 16px;
            }

            .stat-icon {
                width: 42px;
                height: 42px;
            }

            .stat-icon i {
                font-size: 22px;
            }

            .stat-value {
                font-size: 28px;
            }

            .stat-label {
                font-size: 12px;
            }

            .restaurant-meta {
                gap: 8px;
            }

            .meta-item {
                font-size: 10px;
            }

            .restaurant-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 18px;
            }

            .restaurant-card-cover {
                height: 100px;
            }

            .restaurant-card-body {
                padding: 14px;
            }

            .restaurant-logo {
                width: 44px;
                height: 44px;
                margin-top: -22px;
            }

            .restaurant-name {
                font-size: 0.95rem;
            }

            .restaurant-info {
                font-size: 11px;
            }

            .restaurant-actions {
                grid-template-columns: repeat(3, 1fr);
                gap: 6px;
            }

            .btn-edit, .btn-delete-rest, .btn-qr, .btn-menu, .btn-export, .btn-view {
                font-size: 10px;
                padding: 7px 5px;
            }

            .top-controls {
                flex-direction: column;
                gap: 14px;
                padding: 0;
            }

            .back-link-new {
                font-size: 0.8rem;
                padding: 7px 14px;
            }

            .btn-add-restaurant {
                font-size: 0.8rem;
                padding: 10px 20px;
                width: 100%;
                justify-content: center;
            }

            .modal-content {
                padding: 24px;
                margin: 10px;
                max-width: calc(100% - 20px);
            }

            .modal-header h2 {
                font-size: 1.15rem;
            }

            .form-group-new label {
                font-size: 13px;
            }

            .form-group-new input,
            .form-group-new textarea {
                padding: 9px 12px;
                font-size: 13px;
            }

            .form-group-new small {
                font-size: 11px;
            }

            .btn-submit {
                padding: 11px 20px;
                font-size: 0.8rem;
            }

            main {
                padding: 0 20px !important;
            }

            .qr-modal-content {
                padding: 24px;
                width: 95%;
            }

            .qr-modal-header h2 {
                font-size: 1.15rem;
            }

            .qr-code-wrapper {
                padding: 16px;
            }

            .qr-actions {
                flex-direction: column;
                gap: 10px;
            }

            .btn-download-qr,
            .btn-close-qr {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 576px) {
            .admin-header-new {
                padding: 14px 16px;
            }

            .admin-header-new h1 {
                font-size: 1.2rem;
            }

            .admin-user-info {
                flex-direction: column;
                gap: 8px;
            }

            .admin-username {
                font-size: 0.8rem;
                padding: 6px 12px;
            }

            .btn-logout {
                font-size: 0.75rem;
                padding: 6px 14px;
            }

            .stats-grid {
                gap: 10px;
                margin-bottom: 20px;
            }

            .stat-card {
                padding: 14px;
            }

            .stat-icon {
                width: 38px;
                height: 38px;
            }

            .stat-icon i {
                font-size: 20px;
            }

            .stat-value {
                font-size: 26px;
            }

            .stat-label {
                font-size: 11px;
            }

            .restaurant-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .restaurant-card-cover {
                height: 90px;
            }

            .restaurant-card-body {
                padding: 12px;
            }

            .restaurant-logo {
                width: 40px;
                height: 40px;
                margin-top: -20px;
            }

            .restaurant-name {
                font-size: 0.9rem;
                margin-bottom: 6px;
            }

            .restaurant-info {
                font-size: 10px;
                margin-bottom: 8px;
            }

            .restaurant-actions {
                grid-template-columns: repeat(3, 1fr);
                gap: 5px;
            }

            .btn-edit, .btn-delete-rest, .btn-qr, .btn-menu, .btn-export, .btn-view {
                font-size: 9px;
                padding: 6px 4px;
            }

            .back-link-new {
                font-size: 0.75rem;
                padding: 6px 12px;
            }

            .btn-add-restaurant {
                font-size: 0.75rem;
                padding: 9px 18px;
            }

            main {
                padding: 0 15px !important;
            }

            .modal-content {
                padding: 20px;
                margin: 8px;
            }

            .modal-header {
                margin-bottom: 20px;
                padding-bottom: 12px;
            }

            .modal-header h2 {
                font-size: 1.05rem;
            }

            .btn-close-modal {
                width: 36px;
                height: 36px;
                font-size: 18px;
            }

            .form-group-new {
                margin-bottom: 18px;
            }

            .form-group-new label {
                font-size: 12px;
                margin-bottom: 8px;
            }

            .form-group-new input,
            .form-group-new textarea {
                padding: 8px 11px;
                font-size: 12px;
            }

            .form-group-new small {
                font-size: 10px;
            }

            .btn-submit {
                padding: 10px 18px;
                font-size: 0.75rem;
            }

            .image-preview-new img {
                max-width: 120px;
                max-height: 120px;
            }

            .qr-modal-content {
                padding: 20px;
            }

            .qr-modal-header h2 {
                font-size: 1.05rem;
            }

            .qr-code-wrapper {
                padding: 14px;
            }

            .qr-url {
                font-size: 11px;
                padding: 9px 12px;
            }

            .btn-download-qr,
            .btn-close-qr {
                font-size: 12px;
                padding: 9px 16px;
            }
        }

        /* QR Code Modal */
        #qrModal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.85);
            z-index: 10000;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(5px);
        }

        #qrModal.active {
            display: flex;
        }

        .qr-modal-content {
            background: white;
            border: 1px solid rgba(0, 0, 0, 0.06);
            border-radius: 20px;
            padding: 32px;
            max-width: 500px;
            width: 90%;
            text-align: center;
            box-shadow: 0 24px 48px rgba(0, 0, 0, 0.15);
        }

        [data-theme="dark"] .qr-modal-content {
            background: #141414;
            border-color: rgba(255, 255, 255, 0.06);
            box-shadow: 0 24px 48px rgba(0, 0, 0, 0.5);
        }

        .qr-modal-header {
            margin-bottom: 24px;
        }

        .qr-modal-header h2 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0 0 8px 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .qr-modal-header h2 i {
            color: var(--text-primary);
        }

        .qr-code-wrapper {
            background: #f8fafc;
            padding: 20px;
            border-radius: 16px;
            display: inline-block;
            margin-bottom: 20px;
            position: relative;
        }

        [data-theme="dark"] .qr-code-wrapper {
            background: rgba(255, 255, 255, 0.04);
        }

        .qr-code-wrapper #qrcode {
            display: inline-block;
            position: relative;
        }

        .qr-code-wrapper img,
        .qr-code-wrapper canvas {
            display: block;
            max-width: 100%;
            height: auto;
        }

        #qrCodeCanvas {
            border-radius: 12px;
        }

        .qr-url {
            background: #f1f5f9;
            border: 1px solid rgba(0, 0, 0, 0.06);
            padding: 10px 14px;
            border-radius: 10px;
            color: #64748b;
            font-size: 12px;
            word-break: break-all;
            margin-bottom: 16px;
            font-family: 'SF Mono', 'Monaco', 'Consolas', monospace;
        }

        [data-theme="dark"] .qr-url {
            background: rgba(255, 255, 255, 0.04);
            border-color: rgba(255, 255, 255, 0.08);
            color: #94a3b8;
        }

        .qr-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-download-qr {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            text-decoration: none;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.25);
        }

        [data-theme="dark"] .btn-download-qr {
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            color: #0f172a;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        .btn-download-qr:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.3);
        }

        .btn-close-qr {
            background: rgba(0, 0, 0, 0.04);
            color: #64748b;
            padding: 10px 20px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
        }

        [data-theme="dark"] .btn-close-qr {
            color: #94a3b8;
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(255, 255, 255, 0.1);
        }

        .btn-close-qr:hover {
            background: #0f172a;
            color: white;
            border-color: #0f172a;
        }

        [data-theme="dark"] .btn-close-qr:hover {
            background: #f1f5f9;
            color: #0f172a;
            border-color: #f1f5f9;
        }

        /* QR Settings Modal */
        #qrSettingsModal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.85);
            z-index: 10001;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(5px);
        }
        #qrSettingsModal.active { display: flex; }
        .qr-settings-content {
            background: white;
            border-radius: 20px;
            padding: 28px;
            max-width: 460px;
            width: 90%;
            box-shadow: 0 24px 48px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(0, 0, 0, 0.06);
        }
        [data-theme="dark"] .qr-settings-content {
            background: #141414;
            border-color: rgba(255, 255, 255, 0.06);
        }
        .qr-settings-content h2 {
            font-size: 1.2rem;
            font-weight: 600;
            margin: 0 0 20px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .qr-settings-section {
            margin-bottom: 20px;
        }
        .qr-settings-section label {
            display: block;
            font-weight: 500;
            margin-bottom: 8px;
            font-size: 13px;
        }
        .qr-logo-options {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .qr-logo-opt {
            padding: 10px 14px;
            border: 2px solid rgba(0, 0, 0, 0.12);
            border-radius: 12px;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .qr-logo-opt:hover { border-color: #667eea; background: rgba(102, 126, 234, 0.08); }
        .qr-logo-opt.selected { border-color: #667eea; background: rgba(102, 126, 234, 0.15); }
        [data-theme="dark"] .qr-logo-opt { border-color: rgba(255,255,255,0.2); }
        [data-theme="dark"] .qr-logo-opt.selected { border-color: #667eea; }
        .qr-color-picker-wrap { margin-top: 12px; padding: 12px; background: rgba(0,0,0,0.03); border-radius: 12px; }
        [data-theme="dark"] .qr-color-picker-wrap { background: rgba(255,255,255,0.05); }
        .qr-color-row { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; }
        .qr-color-row:last-child { margin-bottom: 0; }
        .qr-color-row label { min-width: 80px; font-size: 12px; font-weight: 500; }
        .qr-color-row input[type="color"] { width: 36px; height: 36px; padding: 2px; border: none; border-radius: 8px; cursor: pointer; background: transparent; }
        .qr-color-row input[type="text"] { flex: 1; padding: 8px 10px; border-radius: 8px; border: 1px solid rgba(0,0,0,0.15); font-size: 12px; font-family: monospace; }
        .qr-corner-opt {
            padding: 10px 14px;
            border: 2px solid rgba(0, 0, 0, 0.12);
            border-radius: 12px;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .qr-corner-opt:hover { border-color: #667eea; background: rgba(102, 126, 234, 0.08); }
        .qr-corner-opt.selected { border-color: #667eea; background: rgba(102, 126, 234, 0.15); }
        [data-theme="dark"] .qr-corner-opt { border-color: rgba(255,255,255,0.2); }
        .qr-corner-preview {
            width: 28px;
            height: 28px;
            background: #1e293b;
            display: inline-block;
        }
        .qr-logo-preview {
            width: 32px;
            height: 32px;
            object-fit: contain;
            border-radius: 6px;
        }
        .qr-upload-wrap { margin-top: 10px; }
        .qr-upload-wrap input[type="file"] { display: none; }
        .qr-upload-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
            border-radius: 10px;
            cursor: pointer;
            font-size: 12px;
        }
        .qr-settings-actions {
            display: flex;
            gap: 12px;
            margin-top: 24px;
        }
        .qr-settings-actions button { flex: 1; padding: 12px; border-radius: 12px; font-weight: 600; cursor: pointer; }
        .btn-save-qr-settings { background: #667eea; color: white; border: none; }
        .btn-close-qr-settings { background: rgba(0,0,0,0.06); border: 1px solid rgba(0,0,0,0.1); }
        [data-theme="dark"] .btn-close-qr-settings { background: rgba(255,255,255,0.06); border-color: rgba(255,255,255,0.1); }
    </style>
</head>
<body class="admin-page admin-wrap">
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="admin-sidebar-logo">
            <img src="../assets/images/logo.png" alt="BirMenu" id="sidebarLogo">
        </div>
        <nav class="admin-sidebar-nav">
            <a href="../" class="admin-sidebar-nav-item">
                <i class="bi bi-house-door"></i>
                Ana səhifə
            </a>
            <span class="admin-sidebar-nav-item active">
                <i class="bi bi-grid-1x2"></i>
                Restoranlar
            </span>
            <span class="admin-sidebar-nav-item" onclick="openQrSettingsModal()" role="button" tabindex="0" style="cursor: pointer;" title="QR kod parametrləri">
                <i class="bi bi-qr-code-scan"></i>
                QR Kod
            </span>
        </nav>
        <div class="admin-sidebar-user">
            <div class="admin-sidebar-user-inner">
                <div class="admin-sidebar-user-avatar-wrap">
                    <div class="admin-sidebar-user-avatar<?php echo ($_SESSION['admin_username'] ?? '') === 'suleymansuleymanli2005' ? ' avatar-general-director' : ''; ?>"><?php echo ($_SESSION['admin_username'] ?? '') === 'suleymansuleymanli2005' ? 'S' : strtoupper(mb_substr($_SESSION['admin_username'] ?? '', 0, 1)); ?></div>
                    <?php if (($_SESSION['admin_username'] ?? '') === 'suleymansuleymanli2005'): ?>
                    <span class="avatar-badge-gd" title="General Director"><i class="bi bi-award-fill"></i></span>
                    <?php endif; ?>
                </div>
                <div style="flex:1;min-width:0;">
                    <div class="admin-sidebar-user-name"><?php echo ($_SESSION['admin_username'] ?? '') === 'suleymansuleymanli2005' ? 'Süleymanlı Süleyman' : htmlspecialchars($_SESSION['admin_username'] ?? ''); ?></div>
                    <?php if (($_SESSION['admin_username'] ?? '') === 'suleymansuleymanli2005'): ?>
                    <div class="admin-sidebar-user-role">General Director</div>
                    <?php endif; ?>
                </div>
            </div>
            <a href="?logout=1" class="admin-btn" style="width:100%;margin-top:12px;justify-content:center;">
                <i class="bi bi-box-arrow-right"></i> Çıxış
            </a>
        </div>
    </aside>

    <div class="admin-main">
        <header class="admin-header">
            <h1 class="admin-header-title">Restoranlar</h1>
            <div class="admin-header-actions">
                <button type="button" class="admin-theme-toggle" onclick="toggleTheme()" aria-label="Rəng rejimi">
                    <i class="bi bi-moon-fill" id="themeIcon"></i>
                </button>
            </div>
        </header>

        <div class="admin-content">
            <div class="admin-top-controls">
                <a href="../" class="admin-btn">
                    <i class="bi bi-arrow-left"></i> Ana səhifə
                </a>
                <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                    <button type="button" class="admin-btn" onclick="importRestaurant()">
                        <i class="bi bi-upload"></i> Restoran İdxal Et
                    </button>
                    <button type="button" class="admin-btn admin-btn-primary" onclick="openAddModal()">
                        <i class="bi bi-plus-lg"></i> Yeni Restoran
                    </button>
                </div>
            </div>

            <div class="admin-stats">
                <div class="admin-stat-card">
                    <div class="admin-stat-icon primary"><i class="bi bi-shop"></i></div>
                    <div>
                        <div class="admin-stat-value" id="totalRestaurants">-</div>
                        <div class="admin-stat-label">Ümumi Restoran</div>
                    </div>
                </div>
                <div class="admin-stat-card">
                    <div class="admin-stat-icon success"><i class="bi bi-check-circle"></i></div>
                    <div>
                        <div class="admin-stat-value" id="activeRestaurants">-</div>
                        <div class="admin-stat-label">Aktiv Restoran</div>
                    </div>
                </div>
                <div class="admin-stat-card">
                    <div class="admin-stat-icon muted"><i class="bi bi-x-circle"></i></div>
                    <div>
                        <div class="admin-stat-value" id="inactiveRestaurants">-</div>
                        <div class="admin-stat-label">Deaktiv Restoran</div>
                    </div>
                </div>
            </div>

            <div id="restaurantsList" class="admin-grid restaurant-grid">
                <!-- Restaurants will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Add/Edit Restaurant Modal -->
    <div class="modal-overlay" id="restaurantModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Yeni Restoran</h2>
                <button class="btn-close-modal" onclick="closeModal()">×</button>
            </div>
            <form id="restaurantForm" enctype="multipart/form-data">
                <input type="hidden" id="restaurantId" name="id">
                
                <div class="form-group-new">
                    <label for="restaurantName">
                        <i class="bi bi-shop"></i> Restoran Adı *
                    </label>
                    <input type="text" id="restaurantName" name="name" required placeholder="Məsələn: BirMenu Restaurant">
                </div>

                <div class="form-group-new">
                    <label for="restaurantSlug">
                        <i class="bi bi-link-45deg"></i> Slug (URL) *
                    </label>
                    <input type="text" id="restaurantSlug" name="slug" required placeholder="birmenu-restaurant">
                    <small>Yalnız kiçik hərflər, rəqəmlər və tire (-) istifadə edin. Bu URL ünvanı olacaq.</small>
                </div>

                <div class="form-group-new">
                    <label for="restaurantDescription">
                        <i class="bi bi-card-text"></i> Təsvir
                    </label>
                    <textarea id="restaurantDescription" name="description" placeholder="Restoran haqqında qısa məlumat..."></textarea>
                </div>

                <div class="form-group-new">
                    <label for="restaurantAddress">
                        <i class="bi bi-geo-alt-fill"></i> Ünvan
                    </label>
                    <input type="text" id="restaurantAddress" name="address" placeholder="Bakı şəhəri, Nəsimi rayonu...">
                </div>

                <div class="form-group-new">
                    <label for="restaurantPhone">
                        <i class="bi bi-telephone-fill"></i> Telefon *
                    </label>
                    <input type="tel" id="restaurantPhone" name="phone" placeholder="+994 XX XXX XX XX">
                </div>
                <div class="form-group-new">
                    <label for="restaurantPhone2">
                        <i class="bi bi-telephone"></i> Telefon 2 (opsional)
                    </label>
                    <input type="tel" id="restaurantPhone2" name="phone2" placeholder="+994 XX XXX XX XX">
                </div>
                <div class="form-group-new">
                    <label for="restaurantPhone3">
                        <i class="bi bi-telephone"></i> Telefon 3 (opsional)
                    </label>
                    <input type="tel" id="restaurantPhone3" name="phone3" placeholder="+994 XX XXX XX XX">
                </div>
                <div class="form-group-new">
                    <label for="restaurantPhone4">
                        <i class="bi bi-telephone"></i> Telefon 4 (opsional)
                    </label>
                    <input type="tel" id="restaurantPhone4" name="phone4" placeholder="+994 XX XXX XX XX">
                </div>

                <div class="form-group-new">
                    <label for="restaurantWifiName">
                        <i class="bi bi-wifi"></i> WiFi Adı (Şəbəkə)
                    </label>
                    <input type="text" id="restaurantWifiName" name="wifi_name" placeholder="MyRestaurant-WiFi">
                </div>

                <div class="form-group-new">
                    <label for="restaurantWifi">
                        <i class="bi bi-key"></i> WiFi Şifrəsi
                    </label>
                    <input type="text" id="restaurantWifi" name="wifi_password" placeholder="••••••••">
                </div>

                <div class="form-group-new">
                    <label for="restaurantLoginUsername">
                        <i class="bi bi-person-badge"></i> Restoran Login Adı
                    </label>
                    <input type="text" id="restaurantLoginUsername" name="login_username" placeholder="restoran_login">
                    <small>Restoran üçün giriş istifadəçi adı</small>
                </div>

                <div class="form-group-new">
                    <label for="restaurantLoginPassword">
                        <i class="bi bi-shield-lock"></i> Restoran Login Şifrəsi
                    </label>
                    <input type="password" id="restaurantLoginPassword" name="login_password" placeholder="••••••••">
                    <small>Restoran üçün giriş şifrəsi (boş buraxsanız, mövcud şifrə dəyişməyəcək)</small>
                </div>

                <div class="form-group-new">
                    <label><i class="bi bi-share"></i> Sosial media linkləri</label>
                    <small style="display: block; margin-bottom: 10px; color: var(--text-secondary);">Restoran səhifəsində göstəriləcək</small>
                </div>
                <div class="form-group-new">
                    <label for="restaurantInstagram">
                        <i class="bi bi-instagram"></i> Instagram
                    </label>
                    <input type="url" id="restaurantInstagram" name="instagram_url" placeholder="https://instagram.com/...">
                </div>
                <div class="form-group-new">
                    <label for="restaurantFacebook">
                        <i class="bi bi-facebook"></i> Facebook
                    </label>
                    <input type="url" id="restaurantFacebook" name="facebook_url" placeholder="https://facebook.com/...">
                </div>
                <div class="form-group-new">
                    <label for="restaurantWhatsApp">
                        <i class="bi bi-whatsapp"></i> WhatsApp
                    </label>
                    <input type="url" id="restaurantWhatsApp" name="whatsapp_url" placeholder="https://wa.me/994...">
                </div>
                <div class="form-group-new">
                    <label for="restaurantTikTok">
                        <i class="bi bi-tiktok"></i> TikTok
                    </label>
                    <input type="url" id="restaurantTikTok" name="tiktok_url" placeholder="https://tiktok.com/@...">
                </div>

                <div class="form-group-new">
                    <label for="restaurantLogo">
                        <i class="bi bi-image"></i> Logo
                    </label>
                    <input type="file" id="restaurantLogo" name="logo" accept="image/*">
                    <small>Tövsiyə olunan ölçü: 512x512px (kvadrat)</small>
                    <div class="image-preview-new" id="logoPreview"></div>
                </div>

                <div class="form-group-new">
                    <label for="restaurantCover">
                        <i class="bi bi-card-image"></i> Kapak Şəkli
                    </label>
                    <input type="file" id="restaurantCover" name="cover" accept="image/*">
                    <small>Tövsiyə olunan ölçü: 1920x1080px (geniş)</small>
                    <div class="image-preview-new" id="coverPreview"></div>
                </div>

                <div class="form-group-new" style="display: flex; align-items: center; gap: 12px; padding: 16px; background: rgba(0,0,0,0.02); border-radius: 12px; border: 1px solid rgba(0,0,0,0.06);">
                    <input type="checkbox" id="restaurantActive" name="is_active" checked style="width: 20px; height: 20px; cursor: pointer;">
                    <label for="restaurantActive" style="margin: 0; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                        <i class="bi bi-check-circle-fill" style="font-size: 18px; color: var(--primary-color);"></i>
                        <span style="color: var(--text-primary); font-weight: 600;">Restoranı aktiv et (Saytda görünsün)</span>
                    </label>
                </div>

                <button type="submit" class="btn-submit">Yadda Saxla</button>
            </form>
        </div>
    </div>

    <!-- Restorana məhsul əlavə etmə seçimi -->
    <div id="productAddChoiceModal" class="modal-overlay" onclick="if(event.target === this) closeModal('productAddChoiceModal')">
        <div class="modal-content" onclick="event.stopPropagation()" style="max-width:420px;">
            <div class="modal-header">
                <h2>Məhsul necə əlavə edilsin?</h2>
                <button class="btn-close-modal" onclick="closeModal('productAddChoiceModal')"><i class="bi bi-x"></i></button>
            </div>
            <div style="display:flex;flex-direction:column;gap:12px;">
                <button type="button" class="admin-btn admin-btn-primary" style="justify-content:center;padding:14px 20px;" onclick="addProductManualGeneral()">
                    <i class="bi bi-pencil-square"></i> Əl ilə daxil et
                </button>
                <button type="button" class="admin-btn" style="justify-content:center;padding:14px 20px;" onclick="openProductFromLibraryFromChoice()">
                    <i class="bi bi-database-add"></i> Bazadan seç (şəkilli)
                </button>
            </div>
        </div>
    </div>

    <!-- Əl ilə məhsul forması (general admin) -->
    <div id="productManualModal" class="modal-overlay" onclick="if(event.target === this) closeModal('productManualModal')">
        <div class="modal-content" onclick="event.stopPropagation()" style="max-width:520px;">
            <div class="modal-header">
                <h2>Məhsul əlavə et (əl ilə)</h2>
                <button class="btn-close-modal" onclick="closeModal('productManualModal')"><i class="bi bi-x"></i></button>
            </div>
            <form id="productManualForm" onsubmit="saveProductGeneral(event)">
                <div class="form-group-new">
                    <label>Restoran *</label>
                    <select id="productManualRestaurant" name="restaurant_id" required style="width:100%;padding:12px 14px;border-radius:10px;border:1px solid rgba(0,0,0,0.08);">
                        <option value="">Restoran seçin...</option>
                    </select>
                </div>
                <div class="form-group-new">
                    <label>Ad *</label>
                    <input type="text" id="productManualName" name="name" required placeholder="Məhsul adı">
                </div>
                <div class="form-group-new">
                    <label>Təsvir</label>
                    <textarea id="productManualDescription" name="description" rows="3" placeholder="Təsvir (opsional)"></textarea>
                </div>
                <div class="form-group-new">
                    <label>Qiymət (₼) *</label>
                    <input type="number" id="productManualPrice" name="price" step="0.01" min="0" required placeholder="0.00">
                </div>
                <div class="form-group-new">
                    <label>Endirim qiyməti (₼)</label>
                    <input type="number" id="productManualDiscountPrice" name="discount_price" step="0.01" min="0" placeholder="0.00">
                </div>
                <div class="form-group-new">
                    <label>Kateqoriya *</label>
                    <select id="productManualCategory" name="category_id" required style="width:100%;padding:12px 14px;border-radius:10px;border:1px solid rgba(0,0,0,0.08);">
                        <option value="">Əvvəlcə restoran seçin...</option>
                    </select>
                </div>
                <div class="form-group-new">
                    <label>Şəkil</label>
                    <input type="file" id="productManualImage" name="image" accept="image/*">
                </div>
                <button type="submit" class="btn-submit">Yadda Saxla</button>
            </form>
        </div>
    </div>

    <!-- Bazadan məhsul seçmə (general admin) -->
    <div id="productLibraryModal" class="modal-overlay" onclick="if(event.target === this) closeModal('productLibraryModal')">
        <div class="modal-content" onclick="event.stopPropagation()" style="max-width:720px;max-height:90vh;display:flex;flex-direction:column;">
            <div class="modal-header">
                <h2>Bazadan məhsul seçin</h2>
                <button class="btn-close-modal" onclick="closeModal('productLibraryModal')"><i class="bi bi-x"></i></button>
            </div>
            <div style="margin-bottom:12px;">
                <label style="font-weight:600;margin-right:8px;">Restoran:</label>
                <select id="productLibraryRestaurant" style="padding:8px 12px;min-width:200px;border-radius:8px;border:1px solid rgba(0,0,0,0.1);">
                    <option value="">Restoran seçin...</option>
                </select>
            </div>
            <div style="margin-bottom:12px;">
                <input type="text" id="productLibrarySearch" placeholder="Məhsul və ya kateqoriya adına görə axtarış..." style="width:100%;padding:10px 14px;border-radius:8px;border:1px solid rgba(0,0,0,0.1);" oninput="filterProductLibraryList(this.value)">
            </div>
            <div id="productLibraryAddBar" style="display:none;padding:12px 0;border-bottom:1px solid #e5e7eb;margin-bottom:12px;">
                <span>Seçildi: <strong id="productLibrarySelectedName"></strong> <span id="productLibraryCategoryHint"></span></span>
                <button type="button" class="btn-submit" style="margin-left:12px;width:auto;padding:8px 16px;" onclick="addProductFromTemplateSubmit()"><i class="bi bi-plus-lg"></i> Əlavə et</button>
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

    <!-- QR Settings Modal -->
    <div id="qrSettingsModal">
        <div class="qr-settings-content">
            <h2><i class="bi bi-gear-wide-connected"></i> QR Kod Parametrləri</h2>
            <div class="qr-settings-section">
                <label>Rəng</label>
                <div class="qr-color-picker-wrap" id="qrColorPickerWrap">
                    <div class="qr-color-row">
                        <label>QR rəngi</label>
                        <input type="color" id="qrColorPicker" value="#000000" onchange="updateQrCustomColor()">
                        <input type="text" id="qrColorHex" value="#000000" maxlength="7" onchange="updateQrColorFromHex()" placeholder="#000000">
                    </div>
                    <div class="qr-color-row">
                        <label>Fon rəngi</label>
                        <input type="color" id="qrBgColorPicker" value="#ffffff" onchange="updateQrCustomColor()">
                        <input type="text" id="qrBgColorHex" value="#ffffff" maxlength="7" onchange="updateQrColorFromHex()" placeholder="#ffffff">
                    </div>
                </div>
            </div>
            <div class="qr-settings-section">
                <label>Ortadakı logo</label>
                <div class="qr-logo-options">
                    <div class="qr-logo-opt selected" data-logo="default" onclick="selectQrLogo('default')">
                        <span style="font-weight:700;font-size:14px;">BIRMENU</span>
                        <span>Varsayılan</span>
                    </div>
                    <div class="qr-logo-opt" data-logo="logo" onclick="selectQrLogo('logo')">
                        <img src="../assets/images/logo.png" class="qr-logo-preview" alt="">
                        <span>Loqo (tünd)</span>
                    </div>
                    <div class="qr-logo-opt" data-logo="logo_light" onclick="selectQrLogo('logo_light')">
                        <img src="../assets/images/logo.png" class="qr-logo-preview" alt="">
                        <span>Loqo (açıq)</span>
                    </div>
                    <div class="qr-logo-opt" data-logo="custom" onclick="selectQrLogo('custom')">
                        <i class="bi bi-upload"></i>
                        <span>Yüklə</span>
                    </div>
                </div>
                <div class="qr-upload-wrap" id="qrCustomUploadWrap" style="display:none;">
                    <input type="file" id="qrLogoUpload" accept="image/*" onchange="handleQrLogoUpload(this)">
                    <label for="qrLogoUpload" class="qr-upload-btn"><i class="bi bi-image"></i> Loqo seç</label>
                </div>
            </div>
            <div class="qr-settings-section">
                <label>Künc radiusu (border radius)</label>
                <div class="qr-style-options qr-corner-options">
                    <div class="qr-corner-opt selected" data-corner="sharp" onclick="selectQrCorner('sharp')">
                        <span class="qr-corner-preview" style="border-radius:0;"></span>
                        <span>Kəskin</span>
                    </div>
                    <div class="qr-corner-opt" data-corner="small" onclick="selectQrCorner('small')">
                        <span class="qr-corner-preview" style="border-radius:6px;"></span>
                        <span>Kiçik</span>
                    </div>
                    <div class="qr-corner-opt" data-corner="medium" onclick="selectQrCorner('medium')">
                        <span class="qr-corner-preview" style="border-radius:12px;"></span>
                        <span>Orta</span>
                    </div>
                    <div class="qr-corner-opt" data-corner="large" onclick="selectQrCorner('large')">
                        <span class="qr-corner-preview" style="border-radius:20px;"></span>
                        <span>Böyük</span>
                    </div>
                </div>
            </div>
            <div class="qr-settings-actions">
                <button class="btn-save-qr-settings" onclick="saveQrSettings()"><i class="bi bi-check-lg"></i> Saxla</button>
                <button class="btn-close-qr-settings" onclick="closeQrSettingsModal()"><i class="bi bi-x-lg"></i> Bağla</button>
            </div>
        </div>
    </div>

    <!-- QR Code Modal -->
    <div id="qrModal">
        <div class="qr-modal-content">
            <div class="qr-modal-header">
                <h2><i class="bi bi-qr-code"></i> QR Kod</h2>
                <p style="color: var(--text-secondary); margin: 0;">Restoranın QR kodu</p>
            </div>
            <div class="qr-code-wrapper">
                <canvas id="qrCodeCanvas" width="300" height="300"></canvas>
            </div>
            <div class="qr-url" id="qrUrl"></div>
            <div class="qr-actions">
                <button id="downloadQrBtn" class="btn-download-qr" onclick="downloadQrCode()">
                    <i class="bi bi-download"></i> Yüklə
                </button>
                <button class="btn-close-qr" onclick="closeQrModal()">
                    <i class="bi bi-x-lg"></i> Bağla
                </button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- QRCode.js Library -->
    <script src="../assets/js/qrcode.min.js"></script>
    <script>
        // Apply theme immediately on page load
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
            if (icon) icon.className = theme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
            const logo = document.getElementById('sidebarLogo');
            if (logo) logo.src = '../assets/images/logo.png';
        }
    </script>
    <script src="admin.js"></script>
</body>
</html>
