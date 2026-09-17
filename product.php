<?php
require_once __DIR__ . '/bootstrap_simple.php';
SimpleSecurity::requirePermission('products');
require_once 'includes/header_sidebar.php';
require_once 'php_action/inventory_service.php';

$productAvailabilitySql = inventoryAvailabilitySql('p');
?>

<style>
    /* Dynamic styling for Category Circle Icons in tables */
    .category-icon-circle {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        margin-right: 12px;
        flex-shrink: 0;
    }
    .category-icon-circle.yellow { background-color: #FEF3C7; color: #D97706; }
    .category-icon-circle.purple { background-color: #F3E8FF; color: #7C3AED; }
    .category-icon-circle.green { background-color: #D1FAE5; color: #059669; }
    .category-icon-circle.blue { background-color: #DBEAFE; color: #2563EB; }
    .category-icon-circle.red { background-color: #FEE2E2; color: #DC2626; }
    .category-icon-circle.grey { background-color: #F3F4F6; color: #4B5563; }

    /* Dynamic Brand & Category Pills in Product Table */
    .badge-category {
        padding: 5px 10px;
        border-radius: 6px;
        font-size: 0.76rem;
        font-weight: 600;
        display: inline-block;
    }
    .badge-category.poultry { background-color: #E6F4EA; color: #137333; }
    .badge-category.swine { background-color: #F3E8FF; color: #6B21A8; }
    .badge-category.ruminant { background-color: #E8F0FE; color: #1A73E8; }
    .badge-category.aquatic, .badge-category.fish { background-color: #DBEAFE; color: #1E40AF; }
    .badge-category.equipment { background-color: #E0E7FF; color: #3730A3; }
    .badge-category.medicine { background-color: #FEE2E2; color: #B91C1C; }
    .badge-category.supplement { background-color: #FEF3C7; color: #B45309; }
    .badge-category.other { background-color: #F3F4F6; color: #374151; }

    .badge-brand {
        padding: 5px 10px;
        border-radius: 6px;
        font-size: 0.76rem;
        font-weight: 600;
        display: inline-block;
    }
    .badge-brand.integra { background-color: #E8F0FE; color: #1A73E8; }
    .badge-brand.bmega { background-color: #FEF3C7; color: #B45309; }
    .badge-brand.feedsco { background-color: #FEE2E2; color: #B91C1C; }
    .badge-brand.nutriplus { background-color: #E6F4EA; color: #137333; }
    .badge-brand.unahco { background-color: #E8F0FE; color: #1C3FAA; }
    .badge-brand.other { background-color: #F3F4F6; color: #374151; }

    /* Dynamic Status Badges */
    .badge-status {
        padding: 5px 10px;
        border-radius: 6px;
        font-size: 0.76rem;
        font-weight: 600;
        display: inline-block;
    }
    .badge-status.active {
        background-color: #E6F4EA;
        color: #137333;
    }
    .badge-status.inactive {
        background-color: #FCE8E6;
        color: #C5221F;
    }

    /* Inline Action Buttons Cell */
    .action-buttons-cell, .brand-card-actions {
        display: flex;
        gap: 8px;
        align-items: center;
    }
    .btn-action-batch {
        background-color: #FFF !important;
        border: 1.5px solid #2563EB !important;
        color: #2563EB !important;
        width: 32px;
        height: 32px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        padding: 0;
    }
    .btn-action-batch:hover {
        background-color: #2563EB !important;
        color: #FFF !important;
    }
    .btn-action-edit {
        background-color: #FFF !important;
        border: 1.5px solid #F27A1A !important;
        color: #F27A1A !important;
        width: 32px;
        height: 32px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        padding: 0;
    }
    .btn-action-edit:hover {
        background-color: #F27A1A !important;
        color: #FFF !important;
    }
    .btn-action-delete {
        background-color: #FFF !important;
        border: 1.5px solid #DC2626 !important;
        color: #DC2626 !important;
        width: 32px;
        height: 32px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        padding: 0;
    }
    .btn-action-delete:hover {
        background-color: #DC2626 !important;
        color: #FFF !important;
    }

    /* ============================================================
       PRODUCTS, BRANDS & CATEGORIES — Premium Redesign
       ============================================================ */
    .combined-container { min-height: calc(100vh - 120px); }

    /* ---- Page Header Section ---- */
    .page-header-section {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 28px;
        padding-top: 12px;
        flex-wrap: wrap;
        gap: 16px;
    }
    .page-header-title {
        display: flex;
        align-items: center;
        gap: 16px;
    }
    .page-header-icon {
        background: linear-gradient(135deg, var(--primary), var(--gold));
        color: #fff;
        width: 52px;
        height: 52px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        box-shadow: 0 4px 14px rgba(201,84,12,0.25);
    }
    .page-header-title h2 {
        font-family: var(--font-display);
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--text);
        margin: 0;
    }
    .page-header-title p {
        font-size: 0.85rem;
        color: var(--text-muted);
        margin: 0;
        margin-top: 2px;
    }

    /* ---- Stat Cards Row ---- */
    .stats-row {
        display: flex;
        gap: 16px;
        flex-wrap: wrap;
    }
    .stat-card {
        background: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: 16px 20px;
        display: flex;
        align-items: center;
        gap: 14px;
        box-shadow: var(--card-shadow);
        transition: all 0.3s ease;
        min-width: 160px;
    }
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px var(--shadow);
    }
    .clickable-stat-card {
        cursor: pointer !important;
        user-select: none;
        position: relative;
        overflow: hidden;
        transition: transform 0.22s ease, box-shadow 0.22s ease, border-color 0.22s ease, background-color 0.22s ease;
    }
    .clickable-stat-card:hover {
        transform: translateY(-3px) !important;
        box-shadow: 0 8px 24px rgba(0,0,0,0.12) !important;
        border-color: var(--primary) !important;
    }
    .clickable-stat-card:active {
        transform: translateY(-1px) !important;
    }
    .clickable-stat-card.active-filter {
        border: 2px solid var(--primary) !important;
        background: var(--primary-light, #FFF7ED) !important;
        box-shadow: 0 4px 14px rgba(242,122,26,0.2) !important;
    }
    .clickable-stat-card.active-filter#statCardLowStock {
        border: 2px solid #D97706 !important;
        background: #FFFBEB !important;
        box-shadow: 0 4px 14px rgba(217,119,6,0.2) !important;
    }
    .clickable-stat-card.active-filter#statCardOutOfStock {
        border: 2px solid #DC2626 !important;
        background: #FEF2F2 !important;
        box-shadow: 0 4px 14px rgba(220,38,38,0.2) !important;
    }
    [data-theme="dark"] .clickable-stat-card.active-filter#statCardTotalProducts {
        background: rgba(242,122,26,0.18) !important;
    }
    [data-theme="dark"] .clickable-stat-card.active-filter#statCardLowStock {
        background: rgba(217,119,6,0.18) !important;
    }
    [data-theme="dark"] .clickable-stat-card.active-filter#statCardOutOfStock {
        background: rgba(220,38,38,0.18) !important;
    }
    .stat-card-icon {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        flex-shrink: 0;
    }
    .stat-card-icon.total { background: var(--primary-light); color: var(--primary); }
    .stat-card-icon.low { background: #FEF3C7; color: #92400E; }
    .stat-card-icon.out { background: #FDE8E8; color: #9B1C1C; }
    [data-theme="dark"] .stat-card-icon.low { background: #3A2E10; color: #E8A317; }
    [data-theme="dark"] .stat-card-icon.out { background: #3A1515; color: #E85D5D; }
    .stat-card-label {
        font-size: 0.7rem;
        color: var(--text-muted);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .stat-card-value {
        font-size: 1.4rem;
        font-weight: 700;
        color: var(--text);
        line-height: 1.2;
    }

    /* ---- Unified Card Container ---- */
    .combined-card {
        background: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: 16px;
        box-shadow: var(--card-shadow);
        margin-bottom: 24px;
        overflow: hidden;
    }
    .combined-card-header {
        background: transparent;
        border-bottom: 1px solid var(--border);
        padding: 0 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
    }

    /* ---- Nav Tabs ---- */
    .nav-tabs {
        border-bottom: none;
    }
    .nav-tabs .nav-item {
        margin-bottom: 0;
    }
    .nav-tabs .nav-link {
        font-weight: 600;
        color: var(--text-muted);
        border: none;
        background: transparent;
        padding: 16px 20px;
        border-bottom: 3px solid transparent;
        transition: all 0.3s ease;
        font-size: 0.95rem;
        display: flex;
        align-items: center;
        gap: 8px;
        position: relative;
    }
    .nav-tabs .nav-link:hover {
        color: var(--primary);
    }
    .nav-tabs .nav-link.active {
        color: var(--primary) !important;
        border-bottom: 3px solid var(--primary) !important;
        background: transparent !important;
    }
    .tab-count-badge {
        background: var(--border);
        color: var(--text-muted);
        font-size: 0.7rem;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 10px;
        min-width: 24px;
        text-align: center;
        transition: all 0.3s ease;
    }
    .nav-tabs .nav-link.active .tab-count-badge {
        background: var(--primary-light);
        color: var(--primary);
    }

    /* ---- Action Buttons ---- */
    .btn-tab-action {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: #fff;
        border: none;
        border-radius: 10px;
        padding: 10px 20px;
        font-weight: 700;
        font-size: 0.9rem;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 6px;
        box-shadow: 0 2px 8px rgba(201,84,12,0.2);
    }
    .btn-tab-action:hover {
        background: linear-gradient(135deg, var(--primary-dark), var(--primary));
        color: #fff;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(201,84,12,0.3);
    }

    /* ---- Filter Bar ---- */
    .filter-bar {
        display: flex;
        gap: 12px;
        margin-bottom: 20px;
        flex-wrap: wrap;
        align-items: center;
    }
    .filter-bar select {
        border-radius: 10px;
        padding: 10px 14px;
        font-size: 0.9rem;
        border-color: var(--border);
        width: auto;
        min-width: 180px;
        max-width: 250px;
        background-color: var(--input-bg);
        color: var(--text);
        transition: border-color 0.2s ease;
    }
    .filter-bar select:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(201,84,12,0.08);
    }
    .filter-bar .search-wrapper {
        position: relative;
        flex: 1;
        min-width: 240px;
        max-width: 400px;
    }
    .filter-bar .search-wrapper input {
        border-radius: 10px;
        padding: 10px 14px;
        padding-right: 65px;
        font-size: 0.9rem;
        border-color: var(--border);
        width: 100%;
        background-color: var(--input-bg);
        color: var(--text);
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }
    .filter-bar .search-wrapper input:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(201,84,12,0.08);
    }
    .filter-bar .search-wrapper .search-icon,
    .filter-bar .search-wrapper > i.fa-search {
        position: absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-muted);
        pointer-events: none;
        font-size: 0.88rem;
    }
    .filter-bar .search-wrapper .clear-search-btn {
        position: absolute;
        right: 36px;
        top: 50%;
        transform: translateY(-50%);
        background: transparent;
        border: none;
        color: #94a3b8;
        padding: 0;
        font-size: 0.88rem;
        cursor: pointer;
        display: none;
        align-items: center;
        justify-content: center;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        line-height: 1;
        transition: color 0.15s ease, background-color 0.15s ease, transform 0.15s ease;
        z-index: 5;
    }
    .filter-bar .search-wrapper .clear-search-btn.is-visible {
        display: flex !important;
    }
    .filter-bar .search-wrapper .clear-search-btn:hover {
        color: #ef4444;
        background-color: rgba(239, 68, 68, 0.15);
        transform: translateY(-50%) scale(1.12);
    }
    .filter-bar .search-wrapper .clear-search-btn:focus {
        outline: none;
        box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.25);
    }
    .btn-filter-action {
        border: 1px solid var(--border);
        background: var(--card-bg);
        color: var(--text-muted);
        font-weight: 600;
        font-size: 0.9rem;
        border-radius: 10px;
        padding: 10px 16px;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .btn-filter-action:hover {
        border-color: var(--primary);
        color: var(--primary);
        background: var(--primary-light);
    }

    /* ---- Product Table (Ultra-Compact Fit No-Swipe) ---- */
    .table-responsive {
        border-radius: 12px;
        overflow-x: auto;
        border: 1px solid var(--border);
    }
    .table-responsive table.table {
        table-layout: auto !important;
        width: 100% !important;
        margin-bottom: 0;
    }
    .table th {
        font-size: 0.68rem !important;
        font-weight: 700;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.3px;
        padding: 8px 6px !important;
        background-color: var(--border-lightest);
        border-bottom: 1.5px solid var(--border);
        white-space: nowrap;
    }
    .table td {
        padding: 6px 6px !important;
        vertical-align: middle;
        font-size: 0.82rem !important;
        border-bottom: 1px solid var(--border-light);
        white-space: nowrap;
    }
    .table tbody tr {
        transition: background-color 0.15s ease;
    }
    .table tbody tr:hover {
        background-color: var(--table-hover);
    }

    /* Product thumbnail in table */
    .table-thumb {
        width: 36px;
        height: 36px;
        object-fit: cover;
        border-radius: 8px;
        border: 1px solid var(--border-light);
        transition: transform 0.22s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.22s ease;
        backface-visibility: hidden;
        -webkit-backface-visibility: hidden;
        will-change: transform;
    }
    .table-thumb:hover {
        transform: scale(1.08);
        position: relative;
        z-index: 2;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .table-thumb-placeholder {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        background: linear-gradient(135deg, var(--primary-light), var(--gold-light));
        color: var(--primary);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.88rem;
        border: 1px solid var(--border-light);
    }

    /* ---- Status Badges ---- */
    .badge-in-stock, .badge-active {
        background-color: #DEF7EC;
        color: #03543F;
        font-weight: 600;
        font-size: 0.73rem;
        padding: 5px 10px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .badge-low-stock {
        background-color: #FEF3C7;
        color: #92400E;
        font-weight: 600;
        font-size: 0.73rem;
        padding: 5px 10px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .badge-out-of-stock, .badge-not-available {
        background-color: #FDE8E8;
        color: #9B1C1C;
        font-weight: 600;
        font-size: 0.73rem;
        padding: 5px 10px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    [data-theme="dark"] .badge-in-stock,
    [data-theme="dark"] .badge-active {
        background-color: #1A3A2A;
        color: #6EE7A0;
    }
    [data-theme="dark"] .badge-low-stock {
        background-color: #3A2E10;
        color: #FCD34D;
    }
    [data-theme="dark"] .badge-out-of-stock,
    [data-theme="dark"] .badge-not-available {
        background-color: #3A1515;
        color: #FCA5A5;
    }

    /* ---- Table Action Buttons ---- */
    .action-dropdown {
        background: var(--border-lightest);
        border: 1px solid var(--border);
        border-radius: 8px;
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: var(--text-muted);
        transition: all 0.2s ease;
    }
    .action-dropdown:hover {
        background: var(--primary-light);
        color: var(--primary);
        border-color: var(--primary);
    }

    .btn-action-edit {
        background: var(--border-lightest);
        color: var(--text-muted);
        border: 1px solid var(--border);
        width: 34px;
        height: 34px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        font-size: 0.8rem;
    }
    .btn-action-edit:hover {
        background: var(--primary-light);
        color: var(--primary);
        border-color: var(--primary);
        transform: translateY(-1px);
    }
    .btn-action-delete {
        background: #FDE8E8;
        color: #DC3545;
        border: 1px solid #FECACA;
        width: 34px;
        height: 34px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        margin-left: 6px;
        font-size: 0.8rem;
    }
    .btn-action-delete:hover {
        background: #DC3545;
        color: #fff;
        border-color: #DC3545;
        transform: translateY(-1px);
    }
    [data-theme="dark"] .btn-action-delete {
        background: #3A1515;
        color: #FCA5A5;
        border-color: #5A2020;
    }
    [data-theme="dark"] .btn-action-delete:hover {
        background: #DC3545;
        color: #fff;
    }

    /* ---- Brand / Category Card Grid ---- */
    .card-grid-container {
        padding: 0;
    }
    .card-grid-container .table {
        margin-bottom: 0;
    }
    .brand-card-row {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 16px 0;
    }
    .brand-card-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        font-weight: 700;
        flex-shrink: 0;
    }
    .brand-card-icon.brand-icon {
        background: linear-gradient(135deg, var(--gold-light), var(--primary-light));
        color: var(--gold-dark);
    }
    .brand-card-icon.category-icon {
        background: linear-gradient(135deg, #E0F2FE, #DBEAFE);
        color: #1D4ED8;
    }
    [data-theme="dark"] .brand-card-icon.brand-icon {
        background: linear-gradient(135deg, var(--gold-light), var(--primary-light));
        color: var(--gold);
    }
    [data-theme="dark"] .brand-card-icon.category-icon {
        background: linear-gradient(135deg, #1E2D4A, #1E3A5F);
        color: #60A5FA;
    }
    .brand-card-info {
        flex: 1;
        min-width: 0;
    }
    .brand-card-name {
        font-weight: 700;
        font-size: 0.92rem;
        color: var(--text);
        margin: 0;
        line-height: 1.3;
    }
    .brand-status-badge {
        font-size: 0.72rem;
        font-weight: 600;
        padding: 3px 8px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .brand-status-badge.active {
        background: #DEF7EC;
        color: #03543F;
    }
    .brand-status-badge.inactive {
        background: #FDE8E8;
        color: #9B1C1C;
    }
    [data-theme="dark"] .brand-status-badge.active {
        background: #1A3A2A;
        color: #6EE7A0;
    }
    [data-theme="dark"] .brand-status-badge.inactive {
        background: #3A1515;
        color: #FCA5A5;
    }
    .brand-product-count {
        font-size: 0.78rem;
        color: var(--text-muted);
        display: flex;
        align-items: center;
        gap: 5px;
    }
    .brand-card-actions {
        display: flex;
        gap: 6px;
        flex-shrink: 0;
    }

    /* ---- Row not available (muted) ---- */
    .row-not-available {
        opacity: 0.55;
        background-color: var(--border-lightest);
    }

    /* ---- Modal Enhancements ---- */
    .modal-content {
        border-radius: 16px;
        border: 1px solid var(--border);
        overflow: hidden;
    }
    .modal-header {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: #fff;
        border-bottom: none;
        padding: 18px 24px;
    }
    .modal-header .modal-title {
        font-family: var(--font-display);
        font-weight: 700;
        font-size: 1.1rem;
    }
    .modal-header .btn-close {
        filter: brightness(0) invert(1);
        opacity: 0.8;
    }
    .modal-header .btn-close:hover {
        opacity: 1;
    }
    .modal-body {
        padding: 24px;
    }
    .modal-body-scroll {
        max-height: 70vh;
        overflow-y: auto;
    }
    .modal-footer {
        border-top: 1px solid var(--border);
        padding: 16px 24px;
        background: var(--border-lightest);
    }
    [data-theme="dark"] .modal-footer {
        background: var(--input-bg);
    }
    .modal-footer .btn {
        border-radius: 10px;
        font-weight: 600;
        padding: 8px 20px;
    }

    /* Delete confirmation modal */
    .delete-confirm-icon {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        background: #FDE8E8;
        color: #DC3545;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin: 0 auto 16px;
    }
    [data-theme="dark"] .delete-confirm-icon {
        background: #3A1515;
        color: #FCA5A5;
    }

    /* ---- FileInput Previews ---- */
    .file-preview {
        max-height: 150px !important;
        min-height: 100px !important;
        padding: 5px !important;
        border-radius: 10px;
        border: 1px dashed var(--border);
    }
    .file-preview .file-preview-thumbnails {
        max-height: 120px !important;
    }
    /* ---- Enhanced Product Image Upload & Dropzone Styling ---- */
    .photo-preview-card {
        background: #f8fafc;
        border: 1.5px solid #e2e8f0;
        border-radius: 16px;
        transition: all 0.2s ease;
    }
    .photo-upload-card {
        background: #ffffff;
        border: 1.5px solid #e2e8f0;
        border-radius: 16px;
    }
    .current-photo-wrapper {
        width: 190px;
        height: 190px;
        border-radius: 16px;
        background: #ffffff;
        border: 2px solid #e2e8f0;
        position: relative;
        overflow: hidden;
        box-shadow: 0 4px 14px rgba(0,0,0,0.05);
    }
    .current-product-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }
    .current-photo-wrapper:hover .current-product-img {
        transform: scale(1.04);
    }
    .photo-fallback-box {
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    }
    .upload-dropzone-box {
        border: 2px dashed #cbd5e1;
        background: #f8fafc;
        border-radius: 16px;
        min-height: 190px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        position: relative;
        transition: all 0.25s ease;
    }
    .upload-dropzone-box:hover, .upload-dropzone-box.dragover {
        border-color: #ea580c;
        background: #fff7ed;
        box-shadow: 0 0 0 4px rgba(234, 88, 12, 0.08);
    }
    .upload-dropzone-input {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        cursor: pointer;
        z-index: 5;
    }
    .upload-icon-circle {
        width: 52px;
        height: 52px;
        border-radius: 50%;
        background: rgba(234, 88, 12, 0.12);
        color: #ea580c;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        transition: transform 0.2s ease;
    }
    .upload-dropzone-box:hover .upload-icon-circle {
        transform: translateY(-2px) scale(1.06);
    }
    .add-product-dropzone {
        background: #f8fafc;
        border: 2px dashed #cbd5e1;
        border-radius: 12px;
        transition: all 0.2s ease;
        position: relative;
        cursor: pointer;
    }
    .add-product-dropzone:hover, .add-product-dropzone.dragover {
        border-color: #ea580c;
        background: #fff7ed;
    }
    [data-theme="dark"] .photo-preview-card,
    [data-theme="dark"] .photo-upload-card,
    [data-theme="dark"] .upload-dropzone-box,
    [data-theme="dark"] .add-product-dropzone {
        background: #1e293b;
        border-color: #334155;
    }
    [data-theme="dark"] .current-photo-wrapper {
        background: #0f172a;
        border-color: #334155;
    }

    /* ---- Form Enhancements ---- */
    .form-label {
        font-weight: 600;
        font-size: 0.85rem;
        color: var(--text);
        margin-bottom: 6px;
    }
    .form-control, .form-select {
        border-radius: 10px;
        border-color: var(--border);
        padding: 10px 14px;
        font-size: 0.9rem;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }
    .form-control:focus, .form-select:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(201,84,12,0.08);
    }

    /* ---- Hide DataTables default controls ---- */
    .dataTables_filter, .dataTables_length {
        display: none !important;
    }

    /* ---- Animation: fade in rows ---- */
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(8px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .tab-pane.active {
        animation: fadeInUp 0.3s ease;
    }

    /* ---- Empty state ---- */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: var(--text-muted);
    }
    .empty-state i {
        font-size: 3rem;
        margin-bottom: 16px;
        opacity: 0.3;
    }
    .empty-state p {
        font-size: 0.9rem;
        margin: 0;
    }
</style>

<div class="combined-container">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb" id="dynamicBreadcrumb">
            <li class="breadcrumb-item"><a href="dashboard_secure.php">Home</a></li>
            <li class="breadcrumb-item">Inventory</li>
            <li class="breadcrumb-item active">Feeds Inventory</li>
        </ol>
    </nav>

    <!-- Page Header with Stats -->
    <div class="page-header-section">
        <div class="page-header-title">
            <div class="page-header-icon" id="dynamicPageIcon"><i class="fa-solid fa-sack-dry"></i></div>
            <div>
                <h2 id="dynamicPageTitle">Feeds Inventory</h2>
                <p id="dynamicPageSub">Manage all your feed products. Track sacks, loose stock and total available inventory.</p>
            </div>
        </div>
        <?php
        $totalProducts = $connect->query("SELECT COUNT(*) as c FROM product WHERE status=1")->fetch_assoc()['c'];
        $totalBrands = $connect->query("SELECT COUNT(*) as c FROM brands WHERE brand_status=1")->fetch_assoc()['c'];
        $totalCategories = $connect->query("SELECT COUNT(*) as c FROM categories WHERE categories_status=1")->fetch_assoc()['c'];
        $lowStockProducts = $connect->query(
            "SELECT COUNT(*) as c FROM product p
             WHERE p.status=1 AND p.active=1
               AND ({$productAvailabilitySql}) > 0
               AND ({$productAvailabilitySql}) <= p.reorder_level"
        )->fetch_assoc()['c'];
        $outOfStockProducts = $connect->query(
            "SELECT COUNT(*) as c FROM product p
             WHERE p.status=1 AND p.active=1
               AND ({$productAvailabilitySql}) <= 0"
        )->fetch_assoc()['c'];
        ?>
        <div class="stats-row">
            <div class="stat-card clickable-stat-card" id="statCardTotalProducts" onclick="filterProductsByStatus('all')" title="Click to view all total products" style="cursor: pointer;">
                <div class="stat-card-icon total" style="background: #FFF0E6; color: #D97706;"><i class="fas fa-box"></i></div>
                <div>
                    <div class="stat-card-label" style="display: flex; align-items: center; gap: 4px;">TOTAL PRODUCTS <i class="fas fa-filter" style="font-size: 0.62rem; opacity: 0.6;"></i></div>
                    <div class="stat-card-value"><?= $totalProducts ?></div>
                </div>
            </div>
            <div class="stat-card clickable-stat-card" id="statCardLowStock" onclick="filterProductsByStatus('low_stock')" title="Click to filter low stock products" style="cursor: pointer;">
                <div class="stat-card-icon low" style="background: #FFFBEB; color: #D97706;"><i class="fas fa-exclamation-triangle"></i></div>
                <div>
                    <div class="stat-card-label" style="display: flex; align-items: center; gap: 4px;">LOW STOCK <i class="fas fa-filter" style="font-size: 0.62rem; opacity: 0.6;"></i></div>
                    <div class="stat-card-value"><?= $lowStockProducts ?></div>
                </div>
            </div>
            <div class="stat-card clickable-stat-card" id="statCardOutOfStock" onclick="filterProductsByStatus('out_of_stock')" title="Click to filter out of stock products" style="cursor: pointer;">
                <div class="stat-card-icon out" style="background: #FEF2F2; color: #DC2626;"><i class="fas fa-times-circle"></i></div>
                <div>
                    <div class="stat-card-label" style="display: flex; align-items: center; gap: 4px;">OUT OF STOCK <i class="fas fa-filter" style="font-size: 0.62rem; opacity: 0.6;"></i></div>
                    <div class="stat-card-value"><?= $outOfStockProducts ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Unified Card -->
    <div class="combined-card">
        <!-- Card Header containing the tabs and add action buttons -->
        <div class="combined-card-header">
            <ul class="nav nav-tabs" id="combinedTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="products-tab" data-bs-toggle="tab" data-bs-target="#products-content" type="button" role="tab">
                        <i class="fa-solid fa-sack-dry"></i> Products
                        <span class="tab-count-badge"><?= $totalProducts ?></span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="categories-tab" data-bs-toggle="tab" data-bs-target="#categories-content" type="button" role="tab">
                        <i class="fas fa-th-large"></i> Categories
                        <span class="tab-count-badge"><?= $totalCategories ?></span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="brands-tab" data-bs-toggle="tab" data-bs-target="#brands-content" type="button" role="tab">
                        <i class="fas fa-tag"></i> Brands
                        <span class="tab-count-badge"><?= $totalBrands ?></span>
                    </button>
                </li>
            </ul>
            <div class="tab-action-buttons" style="padding:12px 0;">
                <!-- Add Product Button (shows on Products tab) -->
                <button class="btn btn-tab-action" data-bs-toggle="modal" data-bs-target="#addProductModal" id="addProductModalBtn" style="background: #F27A1A; border-color: #F27A1A;">
                    <i class="fas fa-plus"></i> Add Feed Product
                </button>
                <!-- Add Category Button (shows on Categories tab) -->
                <button class="btn btn-tab-action d-none" data-bs-toggle="modal" data-bs-target="#addCategoriesModal" id="addCategoriesModalBtn" style="background: #F27A1A; border-color: #F27A1A;">
                    <i class="fas fa-plus"></i> Add Category
                </button>
                <!-- Add Brand Button (shows on Brands tab) -->
                <button class="btn btn-tab-action d-none" data-bs-toggle="modal" data-bs-target="#addBrandModel" id="addBrandModalBtn" style="background: #F27A1A; border-color: #F27A1A;">
                    <i class="fas fa-plus"></i> Add Brand
                </button>
            </div>
        </div>

        <!-- Card Body containing Tab Contents -->
        <div class="card-body p-4">
            <div class="remove-messages"></div>

            <!-- Info Alert Banner -->
            <div class="alert d-flex align-items-center gap-3 py-3 px-4 mb-4" id="tabInfoBanner" style="background-color: #FFF3CD; border: 1px solid rgba(242, 122, 26, 0.2); border-radius: 12px; color: #856404;">
                <i class="fas fa-info-circle fs-5" style="color: #F27A1A;"></i>
                <span id="tabInfoBannerText" style="font-weight: 500; font-size: 0.9rem;">Manage all your feed products along with their categories and brands in one place.</span>
            </div>

            <div class="tab-content">
                <!-- ============ PRODUCTS TAB ============ -->
                <div class="tab-pane fade show active" id="products-content" role="tabpanel" aria-labelledby="products-tab">
                    <!-- Filters Grid -->
                    <div class="filter-bar">
                        <!-- Custom Search Box -->
                        <div class="search-wrapper">
                            <input type="text" class="form-control" id="productSearchInput" placeholder="Search feed products...">
                            <button type="button" class="clear-search-btn" id="clearProductSearch" title="Clear search" aria-label="Clear search">
                                <i class="fas fa-times"></i>
                            </button>
                            <i class="fas fa-search search-icon"></i>
                        </div>

                        <!-- Category Filter -->
                        <select class="form-select" id="categoryFilter">
                            <option value="">All Categories</option>
                            <?php
                            $sql = "SELECT categories_name FROM categories WHERE categories_status = 1 AND categories_active = 1";
                            $result = $connect->query($sql);
                            while($row = $result->fetch_array()) {
                                echo "<option value='".htmlspecialchars($row[0], ENT_QUOTES, 'UTF-8')."'>".htmlspecialchars($row[0], ENT_QUOTES, 'UTF-8')."</option>";
                            }
                            ?>
                        </select>

                        <!-- Brand Filter -->
                        <select class="form-select" id="brandFilter">
                            <option value="">All Brands</option>
                            <?php
                            $sql = "SELECT brand_name FROM brands WHERE brand_status = 1 AND brand_active = 1";
                            $result = $connect->query($sql);
                            while($row = $result->fetch_array()) {
                                echo "<option value='".htmlspecialchars($row[0], ENT_QUOTES, 'UTF-8')."'>".htmlspecialchars($row[0], ENT_QUOTES, 'UTF-8')."</option>";
                            }
                            ?>
                        </select>

                        <!-- Export Button -->
                        <button class="btn btn-filter-action ms-md-auto" id="exportProductsBtn" onclick="exportProductsToCSV()">
                            <i class="fas fa-download"></i> Export
                        </button>
                    </div>

                    <!-- Products Table -->
                    <div class="table-responsive">
                        <table class="table table-hover align-middle" id="manageProductTable" style="width:100%;">
                            <thead>
                                <tr>
                                    <th rowspan="2" scope="col" style="width:42px; text-align: center; vertical-align: middle;">IMAGE</th>
                                    <th rowspan="2" scope="col" style="vertical-align: middle;">PRODUCT / SKU</th>
                                    <th rowspan="2" scope="col" style="vertical-align: middle; text-align: center;">BRAND</th>
                                    <th rowspan="2" scope="col" style="vertical-align: middle; text-align: center;">SACK (KG)</th>
                                    <th colspan="4" scope="colgroup" style="text-align: center; border-bottom: 1px solid var(--border);">INVENTORY</th>
                                    <th rowspan="2" scope="col" style="vertical-align: middle; text-align: center;">PRICE</th>
                                    <th rowspan="2" scope="col" style="vertical-align: middle; text-align: center;">REORDER</th>
                                    <th rowspan="2" scope="col" style="vertical-align: middle; text-align: center;">STATUS</th>
                                    <th rowspan="2" scope="col" style="width:65px; text-align: center; vertical-align: middle;">ACTION</th>
                                </tr>
                                <tr>
                                    <th scope="col" style="text-align: center; font-size: 0.68rem; font-weight: 700; color: var(--text-muted); border-top: 1px solid var(--border);">FULL</th>
                                    <th scope="col" style="text-align: center; font-size: 0.68rem; font-weight: 700; color: var(--text-muted); border-top: 1px solid var(--border);">OPEN</th>
                                    <th scope="col" style="text-align: center; font-size: 0.68rem; font-weight: 700; color: var(--text-muted); border-top: 1px solid var(--border);">LOOSE (KG)</th>
                                    <th scope="col" style="text-align: center; font-size: 0.68rem; font-weight: 700; color: var(--text-muted); border-top: 1px solid var(--border);">TOTAL (KG)</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>

                <!-- ============ CATEGORIES TAB ============ -->
                <div class="tab-pane fade" id="categories-content" role="tabpanel" aria-labelledby="categories-tab">
                    <!-- Filters Grid -->
                    <div class="filter-bar">
                        <!-- Custom Search Box -->
                        <div class="search-wrapper">
                            <input type="text" class="form-control" id="categoriesSearchInput" placeholder="Search categories...">
                            <button type="button" class="clear-search-btn" id="clearCategoriesSearch" title="Clear search" aria-label="Clear search">
                                <i class="fas fa-times"></i>
                            </button>
                            <i class="fas fa-search search-icon"></i>
                        </div>

                        <!-- Export Button -->
                        <button class="btn btn-filter-action ms-md-auto" id="exportCategoriesBtn">
                            <i class="fas fa-download"></i> Export
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle" id="manageCategoriesTable" style="width:100%;">
                            <thead>
                                <tr>
                                    <th scope="col">CATEGORY NAME</th>
                                    <th scope="col">DESCRIPTION</th>
                                    <th scope="col" style="text-align: center; width:150px;">TOTAL PRODUCTS</th>
                                    <th scope="col" style="width:140px;">STATUS</th>
                                    <th scope="col" style="width:100px; text-align: center;">ACTION</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>

                <!-- ============ BRANDS TAB ============ -->
                <div class="tab-pane fade" id="brands-content" role="tabpanel" aria-labelledby="brands-tab">
                    <!-- Filters Grid -->
                    <div class="filter-bar">
                        <!-- Custom Search Box -->
                        <div class="search-wrapper">
                            <input type="text" class="form-control" id="brandsSearchInput" placeholder="Search brands...">
                            <button type="button" class="clear-search-btn" id="clearBrandsSearch" title="Clear search" aria-label="Clear search">
                                <i class="fas fa-times"></i>
                            </button>
                            <i class="fas fa-search search-icon"></i>
                        </div>

                        <!-- Export Button -->
                        <button class="btn btn-filter-action ms-md-auto" id="exportBrandsBtn">
                            <i class="fas fa-download"></i> Export
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle" id="manageBrandTable" style="width:100%;">
                            <thead>
                                <tr>
                                    <th scope="col">BRAND NAME</th>
                                    <th scope="col">DESCRIPTION</th>
                                    <th scope="col" style="text-align: center; width:150px;">TOTAL PRODUCTS</th>
                                    <th scope="col" style="width:140px;">STATUS</th>
                                    <th scope="col" style="width:100px; text-align: center;">ACTION</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     DIALOGS & MODALS — Products, Brands & Categories
     ============================================================ -->

<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content shadow-lg" style="border-radius: 16px; border: none; overflow: hidden;">
            <form id="submitProductForm" action="php_action/createProduct.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                <input type="hidden" name="productStatus" id="productStatus" value="1">
                
                <div class="modal-header py-3 px-4" style="background: linear-gradient(135deg, #ea580c, #c2410c); color: #ffffff;">
                    <h5 class="modal-title fw-bold d-flex align-items-center gap-2" id="addProductModalLabel" style="font-size: 1.15rem; color: #ffffff;">
                        <i class="fas fa-plus-circle fs-5"></i> Add New Product
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body p-4" style="background: #fafafa; max-height: 82vh; overflow-y: auto;">
                    <div id="add-product-messages"></div>

                    <!-- Type Selection Bar -->
                    <div class="p-3 mb-4 rounded-3 border bg-white d-flex align-items-center justify-content-between flex-wrap gap-2 shadow-sm">
                        <div class="d-flex align-items-center gap-2">
                            <span class="fw-bold text-dark" style="font-size:0.88rem;"><i class="fas fa-layer-group text-warning me-1"></i> Product Type:</span>
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-outline-warning active fw-bold text-dark px-3" onclick="setFormProductType(this, 'feeds')"><i class="fa-solid fa-sack-dry me-1"></i> Feeds</button>
                                <button type="button" class="btn btn-outline-warning fw-bold text-dark px-3" onclick="setFormProductType(this, 'supplement')"><i class="fas fa-flask me-1"></i> Vitamins &amp; Meds</button>
                                <button type="button" class="btn btn-outline-warning fw-bold text-dark px-3" onclick="setFormProductType(this, 'equipment')"><i class="fas fa-tools me-1"></i> Equipment</button>
                            </div>
                        </div>
                        <span class="badge bg-amber text-warning fw-semibold px-3 py-2" id="selectedTypeBadge" style="background:#fff7ed; border:1px solid #fed7aa; color:#c2410c !important;">Feed Inventory Item</span>
                    </div>

                    <div class="row g-4">
                        <!-- Left Column: Product Information -->
                        <div class="col-lg-6">
                            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100">
                                <h6 class="fw-bold mb-3 border-bottom pb-2" id="productInfoTitle" style="color: #ea580c; font-size: 0.95rem;">
                                    <i class="fas fa-info-circle me-1"></i> Basic Product Information
                                </h6>
                                
                                <div class="row g-3">
                                    <div class="col-md-12 form-group">
                                        <label for="productName" class="form-label fw-bold" id="productNameLabel" style="font-size:0.84rem;">Product Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control fw-semibold" id="productName" placeholder="e.g., Broiler Starter 50kg" name="productName" autocomplete="off" required>
                                    </div>

                                    <div class="col-md-6 form-group">
                                        <label for="categoryName" class="form-label fw-bold d-flex justify-content-between align-items-center" style="font-size:0.84rem;">
                                            <span>Category <span class="text-danger">*</span></span>
                                            <a href="javascript:void(0)" onclick="openAddCategoryQuickModal('#addProductModal')" class="text-decoration-none fw-semibold" style="font-size:0.75rem; color: #ea580c;"><i class="fas fa-plus me-1"></i>Add</a>
                                        </label>
                                        <select class="form-select fw-semibold" id="categoryName" name="categoryName" required onchange="onAddProductCategoryChange(this)">
                                            <option value="">Select Category</option>
                                            <?php
                                            $sql = "SELECT categories_id, categories_name FROM categories WHERE categories_status = 1 AND categories_active = 1 ORDER BY categories_name";
                                            $result = $connect->query($sql);
                                            while($row = $result->fetch_array()) {
                                                echo "<option value='".intval($row[0])."'>".htmlspecialchars($row[1], ENT_QUOTES, 'UTF-8')."</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>

                                    <div class="col-md-6 form-group">
                                        <label for="brandName" class="form-label fw-bold d-flex justify-content-between align-items-center" style="font-size:0.84rem;">
                                            <span>Brand <span class="text-danger">*</span></span>
                                            <a href="javascript:void(0)" onclick="openAddBrandQuickModal('#addProductModal')" class="text-decoration-none fw-semibold" style="font-size:0.75rem; color: #ea580c;"><i class="fas fa-plus me-1"></i>Add</a>
                                        </label>
                                        <select class="form-select fw-semibold" id="brandName" name="brandName" required>
                                            <option value="">Select Brand</option>
                                            <?php
                                            $sql = "SELECT brand_id, brand_name FROM brands WHERE brand_status = 1 AND brand_active = 1 ORDER BY brand_name";
                                            $result = $connect->query($sql);
                                            while($row = $result->fetch_array()) {
                                                echo "<option value='".intval($row[0])."'>".htmlspecialchars($row[1], ENT_QUOTES, 'UTF-8')."</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>

                                    <input type="hidden" id="productSku" name="productSku" value="">

                                    <div class="col-md-6 form-group" id="expiryDateContainer">
                                         <label for="expiryDate" class="form-label fw-bold" style="font-size:0.84rem;">Expiry Date <span class="text-muted fw-normal">(Optional)</span></label>
                                         <input type="date" class="form-control fw-semibold" id="expiryDate" name="expiryDate">
                                     </div>

                                     <div class="col-md-6 form-group" id="equipmentExpiryNoticeContainer" style="display:none;">
                                         <label class="form-label fw-bold text-muted" style="font-size:0.84rem;">Expiry Date</label>
                                         <div class="form-control bg-light text-muted fw-semibold d-flex align-items-center gap-2" style="font-size:0.84rem; background:#f8fafc !important; color:#64748b !important; border:1px solid #e2e8f0; height:38px;">
                                             <i class="fas fa-check-circle text-success"></i> <span>N/A (Equipment / Tools Have No Expiry)</span>
                                         </div>
                                     </div>

                                     <div class="col-md-6 form-group">
                                         <label class="form-label fw-bold d-flex justify-content-between align-items-center" style="font-size:0.84rem;">
                                             <span>Product Image <span class="text-danger">*</span></span>
                                             <span class="text-muted fw-normal" style="font-size:0.75rem;">JPG, PNG, WebP (Max 5MB)</span>
                                         </label>
                                         <div class="add-product-dropzone rounded-3 text-center p-3 position-relative border" id="addPhotoDropzone" style="background:#f8fafc; border:2px dashed #cbd5e1 !important; cursor:pointer; min-height:115px; display:flex; flex-direction:column; align-items:center; justify-content:center;">
                                             <input type="file" class="upload-dropzone-input" id="productImage" name="productImage" accept="image/jpeg,image/png,image/webp,image/gif" required style="position:absolute; inset:0; width:100%; height:100%; opacity:0; cursor:pointer; z-index:5;">
                                             
                                             <div class="add-dropzone-idle" id="addDropzoneIdle">
                                                 <i class="fas fa-cloud-arrow-up fs-4 mb-1" style="color:#ea580c;"></i>
                                                 <div class="fw-bold text-dark" style="font-size:0.82rem;">Click or drag product image here</div>
                                                 <div class="text-muted" style="font-size:0.72rem;">Crisp feeds / packaging photo</div>
                                             </div>

                                             <div class="add-dropzone-preview d-none d-flex align-items-center gap-3 w-100" id="addDropzonePreview">
                                                 <img src="" id="addImagePreview" class="rounded-3 shadow-sm border bg-white" style="width:60px; height:60px; object-fit:cover;">
                                                 <div class="text-start flex-grow-1 overflow-hidden">
                                                     <div class="fw-bold text-dark text-truncate small" id="addPhotoName">image.jpg</div>
                                                     <div class="text-muted" style="font-size:0.72rem;" id="addPhotoSize">250 KB</div>
                                                     <span class="badge bg-success-subtle text-success py-0 px-1" style="font-size:0.68rem;">Ready to upload</span>
                                                 </div>
                                                 <button type="button" class="btn btn-sm btn-outline-danger p-1 rounded-circle z-index-10" id="btnRemoveAddPhoto" title="Change photo" style="z-index:10; width:28px; height:28px;">
                                                     <i class="fas fa-times" style="font-size:12px;"></i>
                                                 </button>
                                             </div>
                                         </div>
                                     </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Pricing & Stock Inventory -->
                        <div class="col-lg-6">
                            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100">
                                <h6 class="fw-bold mb-3 border-bottom pb-2" style="color: #ea580c; font-size: 0.95rem;">
                                    <i class="fas fa-boxes-stacked me-1"></i> Pricing &amp; Stock Inventory
                                </h6>
                                
                                <div class="row g-3">
                                    <div class="col-md-6 form-group">
                                        <label for="rate" class="form-label fw-bold" id="rateLabel" style="font-size:0.84rem;">Selling Price (₱) <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text fw-bold bg-light">₱</span>
                                            <input type="number" step="0.01" min="0" class="form-control fw-bold text-success" id="rate" placeholder="150.00" name="rate" autocomplete="off" required oninput="updateFeedCalculations()">
                                        </div>
                                        <div class="form-text text-muted" style="font-size:0.72rem;" id="priceSubtext">Selling price per sack</div>
                                    </div>

                                    <div class="col-md-6 form-group">
                                        <label for="originalPrice" class="form-label fw-bold" id="costPriceLabel" style="font-size:0.84rem;">Cost Price per KG (₱) <span class="text-muted fw-normal">(Optional)</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text fw-bold bg-light">₱</span>
                                            <input type="number" step="0.01" min="0" class="form-control fw-bold text-secondary" id="originalPrice" placeholder="25.00" name="originalPrice" autocomplete="off" oninput="updateFeedCalculations()">
                                        </div>
                                        <div class="form-text text-muted" style="font-size:0.72rem;" id="costPriceSubtext">Supplier cost price per 1 KG (for POS transactions)</div>
                                    </div>

                                    <div class="col-md-6 form-group">
                                        <label for="unitType" class="form-label fw-bold" style="font-size:0.84rem;">Unit Type <span class="text-danger">*</span></label>
                                        <select class="form-select fw-semibold" id="unitType" name="unitType" onchange="onAddUnitTypeSelectChange(this)">
                                            <option value="Sack" selected>Whole Sack (50kg)</option>
                                            <option value="Bottle">Bottle</option>
                                            <option value="Piece">Piece (Pcs)</option>
                                            <option value="Box">Box</option>
                                            <option value="Case">Case / Carton</option>
                                            <option value="Container">Gallon / Container</option>
                                            <option value="Kilo">Kilo (kg)</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6 form-group">
                                        <label for="quantity" class="form-label fw-bold" id="quantityLabel" style="font-size:0.84rem;">Quantity of Stock to Add <span class="text-danger">*</span></label>
                                        <input type="number" step="0.01" min="0.01" class="form-control fw-bold text-primary" id="quantity" placeholder="20" name="quantity" autocomplete="off" required oninput="updateFeedCalculations()">
                                        <div class="form-text text-muted" style="font-size:0.72rem;" id="stockSubtext">Enter quantity of stock to add</div>
                                    </div>

                                    <div class="col-md-6 form-group">
                                        <label for="reorderLevel" class="form-label fw-bold" id="reorderLabel" style="font-size:0.84rem;">Reorder Alert Level <span class="text-danger">*</span></label>
                                        <input type="number" step="0.01" min="0" class="form-control fw-bold text-warning" id="reorderLevel" placeholder="5" name="reorderLevel" value="5" autocomplete="off" required>
                                        <div class="form-text text-muted" style="font-size:0.72rem;">Alert when stock drops below this</div>
                                    </div>

                                    <div class="col-md-6 form-group" id="sackSizeContainer" style="display:none;">
                                        <label for="sackSizeKg" class="form-label fw-bold" style="font-size:0.84rem;">Sack Size (KG)</label>
                                        <input type="number" step="0.1" min="1" class="form-control fw-bold" id="sackSizeKg" name="sackSizeKg" placeholder="50" value="50" oninput="updateFeedCalculations()">
                                        <div class="form-text text-muted" style="font-size:0.72rem;">Default weight of 1 sack</div>
                                    </div>

                                    <div class="col-md-12 form-group">
                                        <label for="description" class="form-label fw-bold" style="font-size:0.84rem;">Notes / Description <span class="text-muted fw-normal">(Optional)</span></label>
                                        <textarea class="form-control" id="description" name="description" placeholder="Optional notes or instructions..." rows="2"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer py-3 px-4 bg-white border-top d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary px-4 fw-bold" data-bs-dismiss="modal" style="border-radius:10px; border-color:#ea580c; color:#ea580c;"><i class="fas fa-times me-1"></i>Cancel</button>
                    <button type="submit" class="btn btn-success px-4 py-2 fw-bold shadow-sm" id="createProductBtn" style="border-radius:10px; background:#ea580c; border:none;"><i class="fas fa-check me-1"></i>Save Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Product Modal -->
<div class="modal fade" id="editProductModal" tabindex="-1" aria-labelledby="editProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editProductModalLabel"><i class="fas fa-edit me-2"></i>Edit Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body modal-body-scroll">
                <div class="div-loading">
                    <div class="text-center py-4">
                        <i class="fas fa-spinner fa-pulse fa-2x" style="color:var(--primary);"></i>
                        <p class="mt-2" style="font-size:.85rem;color:var(--text-muted);">Loading product...</p>
                    </div>
                </div>

                <div class="div-result">
                    <ul class="nav nav-tabs" role="tablist">
                        <li class="nav-item"><a class="nav-link active" href="#photo" role="tab" data-bs-toggle="tab"><i class="fas fa-image me-1"></i>Photo</a></li>
                        <li class="nav-item"><a class="nav-link" href="#productInfo" role="tab" data-bs-toggle="tab"><i class="fas fa-info-circle me-1"></i>Product Info</a></li>
                    </ul>

                    <div class="tab-content">
                        <!-- Photo Tab -->
                        <div role="tabpanel" class="tab-pane fade show active" id="photo">
                            <form action="php_action/editProductImage.php" method="POST" id="updateProductImageForm" enctype="multipart/form-data">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                                <div class="mt-2 mb-3" id="edit-productPhoto-messages"></div>
                                
                                <div class="row g-4 align-items-stretch">
                                    <!-- Left: Current Photo Preview Card -->
                                    <div class="col-md-5">
                                        <div class="photo-preview-card h-100 p-3 rounded-4 border bg-light text-center d-flex flex-column align-items-center justify-content-between">
                                            <div class="w-100">
                                                <div class="d-flex align-items-center justify-content-between mb-2">
                                                    <span class="fw-bold text-muted small text-uppercase" style="letter-spacing:0.5px;"><i class="fas fa-image me-1 text-primary"></i> Current Photo</span>
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1 small">Live</span>
                                                </div>
                                                <div class="current-photo-wrapper position-relative mx-auto my-2 rounded-3 overflow-hidden shadow-sm border bg-white d-flex align-items-center justify-content-center">
                                                    <img src="" id="getProductImage" class="img-fluid current-product-img" alt="Product Photo" onerror="this.style.display='none'; document.getElementById('currentPhotoFallback').style.display='flex';" onload="this.style.display='block'; document.getElementById('currentPhotoFallback').style.display='none';" />
                                                    <div id="currentPhotoFallback" class="photo-fallback-box d-flex flex-column align-items-center justify-content-center p-3 text-muted" style="display:none;">
                                                        <i class="fa-solid fa-wheat-awn fs-1 mb-2 text-warning opacity-75"></i>
                                                        <span class="small fw-semibold">No Image Uploaded</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="w-100 pt-2 border-top text-muted small" style="font-size:0.75rem;">
                                                <i class="fas fa-info-circle me-1 text-info"></i> Displayed in POS, Inventory &amp; Catalog
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Right: Upload New Photo Dropzone Card -->
                                    <div class="col-md-7">
                                        <div class="photo-upload-card h-100 p-3 rounded-4 border bg-white d-flex flex-column justify-content-between">
                                            <div>
                                                <div class="d-flex align-items-center justify-content-between mb-2">
                                                    <span class="fw-bold text-dark small text-uppercase" style="letter-spacing:0.5px;"><i class="fas fa-cloud-arrow-up me-1" style="color:#ea580c;"></i> Upload New Photo</span>
                                                    <span class="text-muted small" style="font-size:0.75rem;">JPG, PNG, WebP (Max 5MB)</span>
                                                </div>

                                                <!-- Dropzone Area -->
                                                <div class="upload-dropzone-box rounded-3 text-center p-4 position-relative" id="editPhotoDropzone">
                                                    <input type="file" class="upload-dropzone-input" id="editProductImage" name="editProductImage" accept="image/jpeg,image/png,image/webp,image/gif">
                                                    
                                                    <div class="dropzone-idle-content" id="editDropzoneIdle">
                                                        <div class="upload-icon-circle mx-auto mb-2">
                                                            <i class="fas fa-cloud-arrow-up fs-3" style="color:#ea580c;"></i>
                                                        </div>
                                                        <div class="fw-bold text-dark mb-1" style="font-size:0.92rem;">Drag &amp; drop image here, or <span style="color:#ea580c; text-decoration:underline;">Browse</span></div>
                                                        <p class="text-muted small mb-0" style="font-size:0.78rem;">Select a fresh product packaging image</p>
                                                    </div>

                                                    <!-- Live Selected Preview in Dropzone -->
                                                    <div class="dropzone-preview-content d-none" id="editDropzonePreview">
                                                        <div class="position-relative d-inline-block">
                                                            <img src="" id="editNewImagePreview" class="img-thumbnail rounded-3 shadow-sm" style="max-height:130px; max-width:180px; object-fit:contain;">
                                                            <button type="button" class="btn btn-sm btn-danger rounded-circle position-absolute top-0 start-100 translate-middle shadow-sm p-0 d-flex align-items-center justify-content-center" style="width:24px; height:24px;" id="btnCancelNewPhoto" title="Remove file">
                                                                <i class="fas fa-times" style="font-size:10px;"></i>
                                                            </button>
                                                        </div>
                                                        <div class="mt-2">
                                                            <span class="badge bg-light text-dark border px-2 py-1 fw-semibold small" id="editNewPhotoName">photo.png</span>
                                                            <span class="badge bg-secondary-subtle text-secondary px-2 py-1 small ms-1" id="editNewPhotoSize">1.2 MB</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="modal-footer editProductPhotoFooter px-0 pb-0 pt-3 border-top mt-3 d-flex justify-content-end gap-2">
                                                <button type="button" class="btn btn-outline-secondary px-3 fw-semibold" data-bs-dismiss="modal" style="border-radius:10px;"><i class="fas fa-times me-1"></i>Close</button>
                                                <button type="submit" class="btn btn-success px-4 fw-bold shadow-sm" id="editProductImageBtn" style="border-radius:10px; background:#ea580c; border:none;">
                                                    <i class="fas fa-cloud-arrow-up me-1"></i> Update Photo
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Product Info Tab -->
                        <div role="tabpanel" class="tab-pane fade" id="productInfo">
                            <form id="editProductForm" action="php_action/editProduct.php" method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                                <div class="mt-3" id="edit-product-messages"></div>

                                <div class="mb-3 form-group">
                                    <label for="editProductName" class="form-label">Feed Name *</label>
                                    <input type="text" class="form-control" id="editProductName" placeholder="Feed Name" name="editProductName" autocomplete="off">
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3 form-group">
                                        <label for="editProductSku" class="form-label">SKU *</label>
                                        <input type="text" class="form-control" id="editProductSku" placeholder="e.g. BRS-001" name="editProductSku" autocomplete="off" maxlength="50">
                                    </div>
                                    <div class="col-md-6 mb-3 form-group">
                                        <label for="editFeedType" class="form-label">Feed Type *</label>
                                        <input type="text" class="form-control" id="editFeedType" placeholder="e.g. Crumbles" name="editFeedType" autocomplete="off">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3 form-group">
                                        <label for="editCategoryName" class="form-label w-100 d-flex justify-content-between align-items-center">
                                            <span>Category *</span>
                                            <a href="javascript:void(0)" onclick="openAddCategoryQuickModal('#editProductModal')" class="text-decoration-none fw-semibold" style="font-size:0.78rem;"><i class="fas fa-plus"></i> Add</a>
                                        </label>
                                        <select class="form-select" id="editCategoryName" name="editCategoryName">
                                            <option value="">Select Category</option>
                                            <?php
                                            $sql = "SELECT categories_id, categories_name FROM categories WHERE categories_status = 1 AND categories_active = 1";
                                            $result = $connect->query($sql);
                                            while($row = $result->fetch_array()) {
                                                echo "<option value='".intval($row[0])."'>".htmlspecialchars($row[1], ENT_QUOTES, 'UTF-8')."</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3 form-group">
                                        <label for="editBrandName" class="form-label w-100 d-flex justify-content-between align-items-center">
                                            <span>Brand *</span>
                                            <a href="javascript:void(0)" onclick="openAddBrandQuickModal('#editProductModal')" class="text-decoration-none fw-semibold" style="font-size:0.78rem;"><i class="fas fa-plus"></i> Add</a>
                                        </label>
                                        <select class="form-select" id="editBrandName" name="editBrandName">
                                            <option value="">Select Brand</option>
                                            <?php
                                            $sql = "SELECT brand_id, brand_name FROM brands WHERE brand_status = 1 AND brand_active = 1";
                                            $result = $connect->query($sql);
                                            while($row = $result->fetch_array()) {
                                                echo "<option value='".intval($row[0])."'>".htmlspecialchars($row[1], ENT_QUOTES, 'UTF-8')."</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4 mb-3 form-group">
                                        <label for="editRate" class="form-label">Price/KG (₱) *</label>
                                        <input type="text" class="form-control" id="editRate" placeholder="Price per Kilo" name="editRate" autocomplete="off">
                                    </div>
                                    <div class="col-md-4 mb-3 form-group">
                                        <label for="editQuantity" class="form-label" id="editStockLabel">Stock (Sacks) (Read-only)</label>
                                        <input type="text" class="form-control" id="editQuantity" placeholder="Stock" name="editQuantity" autocomplete="off" readonly>
                                    </div>
                                    <div class="col-md-4 mb-3 form-group">
                                        <label for="editReorderLevel" class="form-label">Reorder Level *</label>
                                        <input type="number" step="0.01" class="form-control" id="editReorderLevel" placeholder="Reorder Level" name="editReorderLevel" autocomplete="off" min="0">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3 form-group">
                                        <label for="editSackSize" class="form-label">Sack Size (KG) *</label>
                                        <input type="number" step="0.1" class="form-control" id="editSackSize" placeholder="Sack Size (KG)" name="editSackSize" autocomplete="off" min="1">
                                    </div>
                                    <div class="col-md-6 mb-3 form-group">
                                        <label for="editExpiryDate" class="form-label">Expiry Date</label>
                                        <input type="date" class="form-control" id="editExpiryDate" name="editExpiryDate">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12 mb-3 form-group d-flex align-items-center">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="editExpiryTracking" name="editExpiryTracking" value="1">
                                            <label class="form-check-label" for="editExpiryTracking" style="font-weight: 600;">Track batches &amp; expiry</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3 form-group">
                                    <label for="editDescription" class="form-label">Description</label>
                                    <textarea class="form-control" id="editDescription" name="editDescription" placeholder="Optional description..." rows="3"></textarea>
                                </div>

                                <!-- Collapsible Advanced Settings Accordion (Edit) -->
                                <div class="accordion mt-3" id="editProductAdvancedAccordion">
                                    <div class="accordion-item" style="border: 1px solid var(--border); border-radius: 10px; overflow: hidden;">
                                        <h2 class="accordion-header" id="headingEditAdvanced">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEditAdvanced" aria-expanded="false" aria-controls="collapseEditAdvanced" style="font-size: 0.85rem; font-weight: 600; background-color: var(--border-lightest); color: var(--text); padding: 12px 16px;">
                                                <i class="fas fa-cog me-2"></i> Advanced Settings (Original/Wholesale Price, Unit Type, Expiry tracking)
                                            </button>
                                        </h2>
                                        <div id="collapseEditAdvanced" class="accordion-collapse collapse" aria-labelledby="headingEditAdvanced" data-bs-parent="#editProductAdvancedAccordion">
                                            <div class="accordion-body p-3" style="background-color: var(--card-bg);">
                                                <div class="row">
                                                    <div class="col-md-6 mb-3 form-group">
                                                        <label for="editOriginalPrice" class="form-label">Original Price</label>
                                                        <input type="text" class="form-control" id="editOriginalPrice" placeholder="Original Price" name="editOriginalPrice" autocomplete="off">
                                                    </div>
                                                    <div class="col-md-6 mb-3 form-group">
                                                        <label for="editWholesalePrice" class="form-label">Wholesale Price</label>
                                                        <input type="text" class="form-control" id="editWholesalePrice" placeholder="Wholesale Price" name="editWholesalePrice" autocomplete="off">
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6 mb-3 form-group">
                                                        <label for="editProductBarcode" class="form-label">Barcode</label>
                                                        <input type="text" class="form-control" id="editProductBarcode" placeholder="e.g. 4800101" name="editProductBarcode" autocomplete="off" maxlength="64">
                                                    </div>
                                                    <div class="col-md-6 mb-3 form-group">
                                                        <label for="editUnitType" class="form-label">Unit Type</label>
                                                        <select class="form-select" id="editUnitType" name="editUnitType">
                                                            <option value="Piece">Piece</option>
                                                            <option value="Sack">Sack</option>
                                                            <option value="Kilo">Kilo</option>
                                                            <option value="Box">Box</option>
                                                            <option value="Pack">Pack</option>
                                                            <option value="Dozen">Dozen</option>
                                                            <option value="Liter">Liter</option>
                                                            <option value="Meter">Meter</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6 mb-3 form-group">
                                                        <label for="editProductStatus" class="form-label">Status</label>
                                                        <select class="form-select" id="editProductStatus" name="editProductStatus">
                                                            <option value="1">Available</option>
                                                            <option value="2">Not Available</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6 mb-3 form-group">
                                                        <label for="editProductType" class="form-label">Product Type</label>
                                                        <select class="form-select" id="editProductType" name="editProductType">
                                                            <option value="feeds">Feeds</option>
                                                            <option value="medicine">Medicine</option>
                                                            <option value="supplement">Supplement</option>
                                                            <option value="equipment">Equipment</option>
                                                            <option value="accessory">Accessory</option>
                                                            <option value="other">Other</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-12 mb-2">
                                                        <div class="form-check">
                                                            <input type="checkbox" class="form-check-input" id="editSoldByWeight" name="editSoldByWeight" value="1">
                                                            <label class="form-check-label" for="editSoldByWeight" style="font-weight: 600;">Sold by weight (kilo/sack)?</label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="modal-footer editProductFooter">
                                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal"><i class="fas fa-times me-1"></i>Cancel</button>
                                    <button type="submit" class="btn btn-success" id="editProductBtn" data-loading-text="Loading..."><i class="fas fa-check me-1"></i>Save Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Remove Product Modal -->
<div class="modal fade" id="removeProductModal" tabindex="-1" aria-labelledby="removeProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #DC3545, #B02A37);">
                <h5 class="modal-title" id="removeProductModalLabel"><i class="fas fa-trash me-2"></i>Remove Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="removeProductMessages"></div>
                <div class="text-center py-3">
                    <div class="delete-confirm-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <p style="font-size:.9rem;color:var(--text);margin:0;font-weight:600;">Are you sure you want to remove this product?</p>
                    <p style="font-size:.78rem;color:var(--text-muted);margin-top:.5rem;">This action cannot be undone.</p>
                </div>
            </div>
            <div class="modal-footer removeProductFooter">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times me-1"></i>Cancel</button>
                <button type="button" class="btn btn-danger" id="removeProductBtn" data-loading-text="Loading..."><i class="fas fa-trash me-1"></i>Remove</button>
            </div>
        </div>
    </div>
</div>

<!-- Barcode/QR Modal -->
<div class="modal fade" id="barcodeModal" tabindex="-1">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-barcode"></i> Barcode & QR Code</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <h6 id="barcodeProductName" style="color:var(--text);font-weight:700;margin-bottom:16px;"></h6>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="card p-3" style="border-radius:12px;">
                            <h6 style="font-size:0.8rem;color:var(--text-muted);margin-bottom:12px;">BARCODE</h6>
                            <div id="barcodeContainer" style="display:flex;justify-content:center;"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card p-3" style="border-radius:12px;">
                            <h6 style="font-size:0.8rem;color:var(--text-muted);margin-bottom:12px;">QR CODE</h6>
                            <div id="qrcodeContainer" style="display:flex;justify-content:center;"></div>
                        </div>
                    </div>
                </div>

                <div class="mt-3 d-flex gap-2 justify-content-center">
                    <button class="btn btn-success btn-sm" onclick="printBarcode()"><i class="fas fa-print"></i> Print Barcode</button>
                    <button class="btn btn-info btn-sm" onclick="printQR()"><i class="fas fa-print"></i> Print QR</button>
                    <button class="btn btn-outline-primary btn-sm" onclick="downloadBarcode()"><i class="fas fa-download"></i> Download</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Price Tag Modal -->
<div class="modal fade" id="priceTagModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-tag"></i> Price Tag</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <label class="form-label" style="font-size:0.82rem;">Tag Size:</label>
                        <select id="tagSize" class="form-select form-select-sm" style="width:auto;display:inline-block;" onchange="updateTagPreview()">
                            <option value="small">Small (Shelf Tag)</option>
                            <option value="medium" selected>Medium</option>
                            <option value="large">Large (Display)</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" style="font-size:0.82rem;">Copies:</label>
                        <input type="number" id="tagCopies" class="form-control form-control-sm" style="width:70px;display:inline-block;" value="1" min="1" max="50">
                    </div>
                </div>

                <div id="tagPreview" style="display:flex;flex-wrap:wrap;gap:12px;justify-content:center;padding:20px;background:var(--border-lightest);border-radius:12px;">
                    <!-- Preview rendered by JS -->
                </div>

                <div class="mt-3 text-center">
                    <button class="btn btn-success" onclick="printPriceTags()"><i class="fas fa-print"></i> Print Tags</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Brand Modal -->
<div class="modal fade" id="addBrandModel" tabindex="-1" aria-labelledby="addBrandModelLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="submitBrandForm" action="php_action/createBrand.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                <div class="modal-header">
                    <h5 class="modal-title" id="addBrandModelLabel"><i class="fas fa-plus me-2"></i>Add Brand</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="add-brand-messages"></div>

                    <div class="mb-3 form-group">
                        <label for="brandName" class="form-label">Brand Name</label>
                        <input type="text" class="form-control" id="brandName" placeholder="Enter brand name" name="brandName" autocomplete="off">
                    </div>
                    <div class="mb-3 form-group">
                        <label for="brandDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="brandDescription" name="brandDescription" placeholder="Enter brand description" rows="3"></textarea>
                    </div>
                    <div class="mb-3 form-group">
                        <label for="brandStatus" class="form-label">Status</label>
                        <select class="form-select" id="brandStatus" name="brandStatus">
                            <option value="">-- Select Status --</option>
                            <option value="1">Available</option>
                            <option value="2">Not Available</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal"><i class="fas fa-times me-1"></i>Cancel</button>
                    <button type="submit" class="btn btn-primary" id="createBrandBtn" data-loading-text="Loading..."><i class="fas fa-check me-1"></i>Save Brand</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Brand Modal -->
<div class="modal fade" id="editBrandModel" tabindex="-1" aria-labelledby="editBrandModelLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editBrandForm" action="php_action/editBrand.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                <div class="modal-header">
                    <h5 class="modal-title" id="editBrandModelLabel"><i class="fas fa-edit me-2"></i>Edit Brand</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="edit-brand-messages"></div>

                    <div class="modal-loading div-hide">
                        <div class="text-center py-4">
                            <i class="fas fa-spinner fa-pulse fa-2x" style="color:var(--primary);"></i>
                            <p class="mt-2" style="font-size:.85rem;color:var(--text-muted);">Loading brand...</p>
                        </div>
                    </div>

                    <div class="edit-brand-result">
                        <div class="mb-3 form-group">
                            <label for="editBrandName" class="form-label">Brand Name</label>
                            <input type="text" class="form-control" id="editBrandName" placeholder="Enter brand name" name="editBrandName" autocomplete="off">
                        </div>
                        <div class="mb-3 form-group">
                            <label for="editBrandDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="editBrandDescription" name="editBrandDescription" placeholder="Enter brand description" rows="3"></textarea>
                        </div>
                        <div class="mb-3 form-group">
                            <label for="editBrandStatus" class="form-label">Status</label>
                            <select class="form-select" id="editBrandStatus" name="editBrandStatus">
                                <option value="">-- Select Status --</option>
                                <option value="1">Available</option>
                                <option value="2">Not Available</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer editBrandFooter">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal"><i class="fas fa-times me-1"></i>Cancel</button>
                    <button type="submit" class="btn btn-success" id="editBrandBtn" data-loading-text="Loading..."><i class="fas fa-check me-1"></i>Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Remove Brand Modal -->
<div class="modal fade" tabindex="-1" aria-hidden="true" id="removeBrandModal">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #DC3545, #B02A37);">
                <h5 class="modal-title"><i class="fas fa-trash me-2"></i>Remove Brand</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="removeBrandMessages"></div>
                <div class="text-center py-3">
                    <div class="delete-confirm-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <p style="font-size:.9rem;color:var(--text);margin:0;font-weight:600;">Are you sure you want to remove this brand?</p>
                    <p style="font-size:.78rem;color:var(--text-muted);margin-top:.5rem;">This action cannot be undone.</p>
                </div>
            </div>
            <div class="modal-footer removeBrandFooter">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times me-1"></i>Cancel</button>
                <button type="button" class="btn btn-danger" id="removeBrandBtn" data-loading-text="Loading..."><i class="fas fa-trash me-1"></i>Remove</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoriesModal" tabindex="-1" aria-labelledby="addCategoriesModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="submitCategoriesForm" action="php_action/createCategories.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCategoriesModalLabel"><i class="fas fa-plus me-2"></i>Add Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="add-categories-messages"></div>

                    <div class="mb-3 form-group">
                        <label for="categoriesName" class="form-label">Category Name</label>
                        <input type="text" class="form-control" id="categoriesName" placeholder="Enter category name" name="categoriesName" autocomplete="off">
                    </div>
                    <div class="mb-3 form-group">
                        <label for="categoriesDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="categoriesDescription" name="categoriesDescription" placeholder="Enter category description" rows="3"></textarea>
                    </div>
                    <div class="mb-3 form-group">
                        <label for="categoriesStatus" class="form-label">Status</label>
                        <select class="form-select" id="categoriesStatus" name="categoriesStatus">
                            <option value="">-- Select Status --</option>
                            <option value="1">Available</option>
                            <option value="2">Not Available</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal"><i class="fas fa-times me-1"></i>Cancel</button>
                    <button type="submit" class="btn btn-primary" id="createCategoriesBtn" data-loading-text="Loading..."><i class="fas fa-check me-1"></i>Save Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoriesModal" tabindex="-1" aria-labelledby="editCategoriesModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editCategoriesForm" action="php_action/editCategories.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCategoriesModalLabel"><i class="fas fa-edit me-2"></i>Edit Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="edit-categories-messages"></div>

                    <div class="modal-loading div-hide">
                        <div class="text-center py-4">
                            <i class="fas fa-spinner fa-pulse fa-2x" style="color:var(--primary);"></i>
                            <p class="mt-2" style="font-size:.85rem;color:var(--text-muted);">Loading category...</p>
                        </div>
                    </div>

                    <div class="edit-categories-result">
                        <div class="mb-3 form-group">
                            <label for="editCategoriesName" class="form-label">Category Name</label>
                            <input type="text" class="form-control" id="editCategoriesName" placeholder="Enter category name" name="editCategoriesName" autocomplete="off">
                        </div>
                        <div class="mb-3 form-group">
                            <label for="editCategoriesDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="editCategoriesDescription" name="editCategoriesDescription" placeholder="Enter category description" rows="3"></textarea>
                        </div>
                        <div class="mb-3 form-group">
                            <label for="editCategoriesStatus" class="form-label">Status</label>
                            <select class="form-select" id="editCategoriesStatus" name="editCategoriesStatus">
                                <option value="">-- Select Status --</option>
                                <option value="1">Available</option>
                                <option value="2">Not Available</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer editCategoriesFooter">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal"><i class="fas fa-times me-1"></i>Cancel</button>
                    <button type="submit" class="btn btn-success" id="editCategoriesBtn" data-loading-text="Loading..."><i class="fas fa-check me-1"></i>Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Remove Category Modal -->
<div class="modal fade" tabindex="-1" aria-hidden="true" id="removeCategoriesModal">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #DC3545, #B02A37);">
                <h5 class="modal-title"><i class="fas fa-trash me-2"></i>Remove Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="removeCategoriesMessages"></div>
                <div class="text-center py-3">
                    <div class="delete-confirm-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <p style="font-size:.9rem;color:var(--text);margin:0;font-weight:600;">Are you sure you want to remove this category?</p>
                    <p style="font-size:.78rem;color:var(--text-muted);margin-top:.5rem;">This action cannot be undone.</p>
                </div>
            </div>
            <div class="modal-footer removeCategoriesFooter">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times me-1"></i>Cancel</button>
                <button type="button" class="btn btn-danger" id="removeCategoriesBtn" data-loading-text="Loading..."><i class="fas fa-trash me-1"></i>Remove</button>
            </div>
        </div>
    </div>
</div>

<!-- ========== VIEW PRODUCT BATCHES MODAL ========== -->
<div class="modal fade" id="viewProductBatchesModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content shadow-lg" style="border-radius:14px; overflow:hidden;">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-layer-group me-2 text-warning"></i>Batches &amp; Expiry: <span id="viewBatchesProductName" class="fw-bold"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 p-3 bg-light rounded-3 border">
                    <div>
                        <span class="text-muted small text-uppercase fw-bold">Total Active Batches:</span>
                        <span class="badge bg-primary fs-6 ms-2" id="viewBatchesCount">0</span>
                    </div>
                    <div>
                        <span class="text-muted small text-uppercase fw-bold">Total Sellable Stock:</span>
                        <span class="fw-bold fs-6 text-success ms-2" id="viewBatchesTotalStock">0</span>
                    </div>
                </div>
                <div id="viewBatchesLoading" class="text-center py-4">
                    <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                    <div class="text-muted mt-2">Loading batch inventory...</div>
                </div>
                <div class="table-responsive rounded-3 border" id="viewBatchesTableWrapper" style="display:none;">
                    <table class="table table-hover align-middle mb-0" id="viewBatchesTable">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Batch #</th>
                                <th scope="col">Lot #</th>
                                <th scope="col">Received Date</th>
                                <th scope="col">Expiry Date</th>
                                <th scope="col" class="text-center">Status</th>
                                <th scope="col" class="text-end">Qty Recv</th>
                                <th scope="col" class="text-end">Qty Remaining</th>
                                <th scope="col" class="text-end">Unit Cost</th>
                                <th scope="col">PO / Supplier</th>
                            </tr>
                        </thead>
                        <tbody><!-- JS populated --></tbody>
                    </table>
                </div>
                <div id="viewBatchesEmpty" class="text-center py-4 text-muted" style="display:none;">
                    <i class="fas fa-box-open fa-3x mb-2 text-muted opacity-50"></i>
                    <div>No batches recorded for this product yet.</div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer_sidebar.php'; ?>

<!-- Import Datatables & Helpers -->
<script src="custom/js/brand.js?v=<?php echo time(); ?>"></script>
<script src="custom/js/categories.js?v=<?php echo time(); ?>"></script>
<script src="custom/js/product.js?v=<?php echo time(); ?>"></script>

<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>

<script>
var currentBarcodeProduct = null;

$(function() {
    // Show/hide correct Add button next to active tab and update titles/breadcrumbs dynamically
    $('button[data-bs-toggle="tab"], button[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        var targetId = $(e.target).attr('id');
        if (targetId === 'products-tab') {
            $('#addProductModalBtn').removeClass('d-none');
            $('#addBrandModalBtn').addClass('d-none');
            $('#addCategoriesModalBtn').addClass('d-none');
            
            // Update UI elements
            $('#dynamicBreadcrumb').html('<li class="breadcrumb-item"><a href="dashboard_secure.php">Home</a></li><li class="breadcrumb-item">Inventory</li><li class="breadcrumb-item active">Feeds Inventory</li>');
            $('#dynamicPageIcon').html('<i class="fa-solid fa-sack-dry"></i>');
            $('#dynamicPageTitle').text('Feeds Inventory');
            $('#dynamicPageSub').text('Manage all your feed products along with their categories and brands in one place.');
            
            $('#tabInfoBanner').css({
                'background-color': '#FFF3CD',
                'border-color': 'rgba(242, 122, 26, 0.2)',
                'color': '#856404'
            }).find('i').css('color', '#F27A1A');
            $('#tabInfoBannerText').text('Manage all your feed products along with their categories and brands in one place.');

            if (window.manageProductTable) {
                setTimeout(function() { manageProductTable.columns.adjust().draw(); }, 50);
            }
        } else if (targetId === 'categories-tab') {
            $('#addProductModalBtn').addClass('d-none');
            $('#addBrandModalBtn').addClass('d-none');
            $('#addCategoriesModalBtn').removeClass('d-none');

            // Update UI elements
            $('#dynamicBreadcrumb').html('<li class="breadcrumb-item"><a href="dashboard_secure.php">Home</a></li><li class="breadcrumb-item">Inventory</li><li class="breadcrumb-item"><a href="product.php">Feeds Inventory</a></li><li class="breadcrumb-item active">Categories</li>');
            $('#dynamicPageIcon').html('<i class="fas fa-th-large"></i>');
            $('#dynamicPageTitle').text('Feed Categories');
            $('#dynamicPageSub').text('Manage classifications and categories for your feed inventory.');
            
            $('#tabInfoBanner').css({
                'background-color': '#E6F4EA',
                'border-color': 'rgba(19, 115, 51, 0.2)',
                'color': '#137333'
            }).find('i').css('color', '#137333');
            $('#tabInfoBannerText').text('Manage classifications and categories for your feed inventory.');

            if (window.manageCategoriesTable) {
                setTimeout(function() { manageCategoriesTable.columns.adjust().draw(); }, 50);
            }
        } else if (targetId === 'brands-tab') {
            $('#addProductModalBtn').addClass('d-none');
            $('#addBrandModalBtn').removeClass('d-none');
            $('#addCategoriesModalBtn').addClass('d-none');

            // Update UI elements
            $('#dynamicBreadcrumb').html('<li class="breadcrumb-item"><a href="dashboard_secure.php">Home</a></li><li class="breadcrumb-item">Inventory</li><li class="breadcrumb-item"><a href="product.php">Feeds Inventory</a></li><li class="breadcrumb-item active">Brands</li>');
            $('#dynamicPageIcon').html('<i class="fas fa-tag"></i>');
            $('#dynamicPageTitle').text('Feed Brands');
            $('#dynamicPageSub').text('Manage all feed manufacturers and suppliers.');
            
            $('#tabInfoBanner').css({
                'background-color': '#E8F0FE',
                'border-color': 'rgba(26, 115, 232, 0.2)',
                'color': '#1A73E8'
            }).find('i').css('color', '#1A73E8');
            $('#tabInfoBannerText').text('Manage all feed manufacturers and suppliers.');

            if (window.manageBrandTable) {
                setTimeout(function() { manageBrandTable.columns.adjust().draw(); }, 50);
            }
        }
    });

    // Check for explicit query parameter to auto-switch tab
    var urlParams = new URLSearchParams(window.location.search);
    var tab = urlParams.get('tab');
    if (tab === 'brands') {
        $('#brands-tab').trigger('click');
    } else if (tab === 'categories') {
        $('#categories-tab').trigger('click');
    }

    // Connect custom filters to product table search (Column 12 is category, Column 2 is brand)
    $('#categoryFilter').on('change', function() {
        var val = $(this).val();
        manageProductTable.column(12).search(val ? '^' + val + '$' : '', true, false).draw();
    });

    $('#brandFilter').on('change', function() {
        var val = $(this).val();
        manageProductTable.column(2).search(val ? '^' + val + '$' : '', true, false).draw();
    });

    // Feed Product search clear button handler
    function syncProductSearchClear() {
        var val = $('#productSearchInput').val();
        if (val && val.trim().length > 0) {
            $('#clearProductSearch').addClass('is-visible').show();
        } else {
            $('#clearProductSearch').removeClass('is-visible').hide();
        }
    }

    $('#productSearchInput').on('input keyup change clear', function() {
        syncProductSearchClear();
        if (window.manageProductTable) {
            manageProductTable.search(this.value).draw();
        }
    });

    $(document).on('click', '#clearProductSearch', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $('#productSearchInput').val('').trigger('input');
        if (typeof window.clearProductFilter === 'function') {
            window.clearProductFilter();
        } else if (window.manageProductTable) {
            manageProductTable.search('').draw();
        }
        syncProductSearchClear();
        $('#productSearchInput').focus();
    });

    $('#productSearchInput').on('keydown', function(e) {
        if (e.key === 'Escape' && $(this).val()) {
            $('#clearProductSearch').trigger('click');
        }
    });

    // Feed Categories search clear button handler
    function syncCategoriesSearchClear() {
        var val = $('#categoriesSearchInput').val();
        if (val && val.trim().length > 0) {
            $('#clearCategoriesSearch').addClass('is-visible').show();
        } else {
            $('#clearCategoriesSearch').removeClass('is-visible').hide();
        }
    }

    $('#categoriesSearchInput').on('input keyup change clear', function() {
        syncCategoriesSearchClear();
        if (window.manageCategoriesTable) {
            manageCategoriesTable.search(this.value).draw();
        }
    });

    $(document).on('click', '#clearCategoriesSearch', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $('#categoriesSearchInput').val('').trigger('input');
        if (window.manageCategoriesTable) {
            manageCategoriesTable.search('').draw();
        }
        syncCategoriesSearchClear();
        $('#categoriesSearchInput').focus();
    });

    $('#categoriesSearchInput').on('keydown', function(e) {
        if (e.key === 'Escape' && $(this).val()) {
            $('#clearCategoriesSearch').trigger('click');
        }
    });

    syncCategoriesSearchClear();

    // Feed Brands search clear button handler
    function syncBrandsSearchClear() {
        var val = $('#brandsSearchInput').val();
        if (val && val.trim().length > 0) {
            $('#clearBrandsSearch').addClass('is-visible').show();
        } else {
            $('#clearBrandsSearch').removeClass('is-visible').hide();
        }
    }

    $('#brandsSearchInput').on('input keyup change clear', function() {
        syncBrandsSearchClear();
        if (window.manageBrandTable) {
            manageBrandTable.search(this.value).draw();
        }
    });

    $(document).on('click', '#clearBrandsSearch', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $('#brandsSearchInput').val('').trigger('input');
        if (window.manageBrandTable) {
            manageBrandTable.search('').draw();
        }
        syncBrandsSearchClear();
        $('#brandsSearchInput').focus();
    });

    $('#brandsSearchInput').on('keydown', function(e) {
        if (e.key === 'Escape' && $(this).val()) {
            $('#clearBrandsSearch').trigger('click');
        }
    });

    syncBrandsSearchClear();

    $('#clearFiltersBtn').on('click', function() {
        $('#categoryFilter').val('').trigger('change');
        $('#brandFilter').val('').trigger('change');
        $('#productSearchInput').val('').trigger('input');
    });

    $('#exportCategoriesBtn').on('click', function() {
        exportCategoriesToCSV();
    });

    $('#exportBrandsBtn').on('click', function() {
        exportBrandsToCSV();
    });

    // Auto-trigger edit modal if requested by URL query
    var requestedEditId = <?php
        $requestedEditId = filter_input(INPUT_GET, 'editProductId', FILTER_VALIDATE_INT);
        echo json_encode($requestedEditId && $requestedEditId > 0 ? $requestedEditId : null);
    ?>;
    if (requestedEditId) {
        editProduct(requestedEditId);
        bootstrap.Modal.getOrCreateInstance(document.getElementById('editProductModal')).show();
    }
});

// CSV Exporter for Products Inventory
function exportProductsToCSV() {
    var csv = [];
    var headers = [];
    
    // Extract table headers (excluding Image and Action)
    $('#manageProductTable thead th').each(function(index) {
        if (index > 0 && index < 8) {
            headers.push($(this).text().trim());
        }
    });
    csv.push(headers.join(','));

    // Extract table rows matching the search/filter criteria
    manageProductTable.rows({ search: 'applied' }).data().each(function(row) {
        var rowData = [];
        for (var i = 1; i <= 7; i++) {
            var val = String(row[i]);
            // Extract text content from columns containing HTML elements (e.g. status badge)
            if (val.indexOf('<') !== -1) {
                val = $('<div>' + val + '</div>').text().trim();
            }
            val = val.replace(/"/g, '""');
            rowData.push('"' + val + '"');
        }
        csv.push(rowData.join(','));
    });

    // Generate CSV Blob and trigger file download
    var csvFile = new Blob([csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
    var downloadLink = document.createElement('a');
    downloadLink.download = 'feeds_inventory.csv';
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = 'none';
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}

// CSV Exporter for Categories
function exportCategoriesToCSV() {
    var csv = [];
    var headers = [];
    $('#manageCategoriesTable thead th').each(function(index) {
        if (index < 4) {
            headers.push($(this).text().trim());
        }
    });
    csv.push(headers.join(','));
    manageCategoriesTable.rows({ search: 'applied' }).data().each(function(row) {
        var rowData = [];
        for (var i = 0; i < 4; i++) {
            var val = String(row[i]);
            if (val.indexOf('<') !== -1) {
                val = $('<div>' + val + '</div>').text().trim();
            }
            val = val.replace(/"/g, '""');
            rowData.push('"' + val + '"');
        }
        csv.push(rowData.join(','));
    });
    var csvFile = new Blob([csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
    var downloadLink = document.createElement('a');
    downloadLink.download = 'feed_categories.csv';
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = 'none';
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}

// CSV Exporter for Brands
function exportBrandsToCSV() {
    var csv = [];
    var headers = [];
    $('#manageBrandTable thead th').each(function(index) {
        if (index < 4) {
            headers.push($(this).text().trim());
        }
    });
    csv.push(headers.join(','));
    manageBrandTable.rows({ search: 'applied' }).data().each(function(row) {
        var rowData = [];
        for (var i = 0; i < 4; i++) {
            var val = String(row[i]);
            if (val.indexOf('<') !== -1) {
                val = $('<div>' + val + '</div>').text().trim();
            }
            val = val.replace(/"/g, '""');
            rowData.push('"' + val + '"');
        }
        csv.push(rowData.join(','));
    });
    var csvFile = new Blob([csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
    var downloadLink = document.createElement('a');
    downloadLink.download = 'feed_brands.csv';
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = 'none';
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}

function escapeHtml(str) {
    if (!str) return '';
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
}

function showBarcode(productId) {
    $.ajax({
        url: 'php_action/fetchProductDetail.php',
        type: 'POST',
        data: { productId: productId },
        dataType: 'json',
        success: function(data) {
            currentBarcodeProduct = data;
            $('#barcodeProductName').text(data.product_name);

            // Generate barcode
            var barcodeContainer = document.getElementById('barcodeContainer');
            barcodeContainer.innerHTML = '<svg id="barcodeSvg"></svg>';
            try {
                JsBarcode('#barcodeSvg', 'PRD-' + String(data.product_id).padStart(6, '0'), {
                    format: 'CODE128',
                    width: 2,
                    height: 60,
                    displayValue: true,
                    fontSize: 12,
                    margin: 5
                });
            } catch(e) {
                barcodeContainer.innerHTML = '<p style="color:var(--danger)">Error generating barcode</p>';
            }

            // Generate QR code
            var qrContainer = document.getElementById('qrcodeContainer');
            qrContainer.innerHTML = '';
            new QRCode(qrContainer, {
                text: JSON.stringify({
                    id: data.product_id,
                    name: data.product_name,
                    price: data.rate,
                    sku: 'PRD-' + String(data.product_id).padStart(6, '0')
                }),
                width: 150,
                height: 150,
                colorDark: '#1C1C1C',
                colorLight: '#ffffff'
            });

            var modal = new bootstrap.Modal(document.getElementById('barcodeModal'));
            modal.show();
        }
    });
}

function printBarcode() {
    var svg = document.getElementById('barcodeSvg');
    if (!svg) return;
    var safeName = escapeHtml(currentBarcodeProduct.product_name);
    var printWin = window.open('', '_blank', 'width=400,height=300');
    printWin.document.write('<html><head><title>Barcode - ' + safeName + '</title>');
    printWin.document.write('<style>body{text-align:center;padding:20px;font-family:Arial,sans-serif;} h3{margin:0 0 10px;font-size:14px;}</style>');
    printWin.document.write('</head><body>');
    printWin.document.write('<h3>' + safeName + '</h3>');
    printWin.document.write(svg.outerHTML);
    printWin.document.write('</body></html>');
    printWin.document.close();
    printWin.print();
}

function printQR() {
    var canvas = document.querySelector('#qrcodeContainer canvas');
    if (!canvas) return;
    var safeName = escapeHtml(currentBarcodeProduct.product_name);
    var printWin = window.open('', '_blank', 'width=400,height=400');
    printWin.document.write('<html><head><title>QR Code - ' + safeName + '</title>');
    printWin.document.write('<style>body{text-align:center;padding:20px;font-family:Arial,sans-serif;} h3{margin:0 0 10px;font-size:14px;} p{font-size:12px;color:#666;}</style>');
    printWin.document.write('</head><body>');
    printWin.document.write('<h3>' + safeName + '</h3>');
    printWin.document.write('<img src="' + canvas.toDataURL() + '" width="200">');
    printWin.document.write('<p>SKU: PRD-' + String(currentBarcodeProduct.product_id).padStart(6, '0') + '</p>');
    printWin.document.write('</body></html>');
    printWin.document.close();
    printWin.print();
}

function downloadBarcode() {
    var canvas = document.querySelector('#qrcodeContainer canvas');
    if (!canvas) return;
    var link = document.createElement('a');
    link.download = 'qr-' + currentBarcodeProduct.product_name.replace(/\s+/g, '-') + '.png';
    link.href = canvas.toDataURL();
    link.click();
}

function printPriceTag(productId) {
    $.ajax({
        url: 'php_action/fetchProductDetail.php',
        type: 'POST',
        data: { productId: productId },
        dataType: 'json',
        success: function(data) {
            currentBarcodeProduct = data;
            updateTagPreview();
            var modal = new bootstrap.Modal(document.getElementById('priceTagModal'));
            modal.show();
        }
    });
}

function updateTagPreview() {
    var data = currentBarcodeProduct;
    if (!data) return;
    var size = $('#tagSize').val();
    var preview = $('#tagPreview');
    preview.empty();

    var sizes = {
        small: { w: 180, h: 100, nameSize: '0.7rem', priceSize: '1.2rem', padding: '8px' },
        medium: { w: 250, h: 150, nameSize: '0.85rem', priceSize: '1.6rem', padding: '12px' },
        large: { w: 350, h: 220, nameSize: '1rem', priceSize: '2.2rem', padding: '16px' }
    };
    var s = sizes[size];

    var tag = '<div class="price-tag" style="width:'+s.w+'px;height:'+s.h+'px;border:2px solid #333;border-radius:8px;padding:'+s.padding+';display:flex;flex-direction:column;justify-content:space-between;background:#fff;color:#333;">' +
        '<div style="text-align:center;">' +
            '<div style="font-size:0.6rem;font-weight:700;color:#E8A317;letter-spacing:1px;">AGRIVET INVENTORY SUPPLY</div>' +
            '<div style="font-size:'+s.nameSize+';font-weight:700;margin-top:4px;line-height:1.2;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' + escapeHtml(data.product_name) + '</div>' +
        '</div>' +
        '<div style="text-align:center;">' +
            '<div style="font-size:'+s.priceSize+';font-weight:900;color:#C62828;">&#8369;' + parseFloat(data.rate).toFixed(2) + '</div>' +
            (data.wholesale_price && parseFloat(data.wholesale_price) > 0 ? '<div style="font-size:0.65rem;color:#666;">Wholesale: &#8369;' + parseFloat(data.wholesale_price).toFixed(2) + '</div>' : '') +
        '</div>' +
        '<div style="text-align:center;font-size:0.55rem;color:#999;">SKU: PRD-' + String(data.product_id).padStart(6, '0') + ' | ' + (data.unit_type || 'pcs') + '</div>' +
    '</div>';

    preview.html(tag);
}

function printPriceTags() {
    var data = currentBarcodeProduct;
    if (!data) return;
    var copies = parseInt($('#tagCopies').val()) || 1;
    var size = $('#tagSize').val();

    var safeName = escapeHtml(data.product_name);
    var printWin = window.open('', '_blank', 'width=800,height=600');
    printWin.document.write('<html><head><title>Price Tags - ' + safeName + '</title>');
    printWin.document.write('<style>');
    printWin.document.write('body{margin:10px;font-family:Arial,sans-serif;}');
    printWin.document.write('.tags{display:flex;flex-wrap:wrap;gap:8px;}');
    printWin.document.write('.tag{border:2px solid #333;border-radius:8px;padding:10px;display:flex;flex-direction:column;justify-content:space-between;page-break-inside:avoid;}');
    printWin.document.write('.tag-sm{width:170px;height:90px;} .tag-md{width:240px;height:140px;} .tag-lg{width:340px;height:210px;}');
    printWin.document.write('.brand{font-size:8px;font-weight:700;color:#E8A317;letter-spacing:1px;text-align:center;}');
    printWin.document.write('.name{font-weight:700;text-align:center;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}');
    printWin.document.write('.price{text-align:center;font-weight:900;color:#C62828;}');
    printWin.document.write('.sku{text-align:center;font-size:8px;color:#999;}');
    printWin.document.write('@media print{body{margin:0;}}');
    printWin.document.write('</style></head><body><div class="tags">');

    var sizeClass = size === 'small' ? 'tag-sm' : (size === 'large' ? 'tag-lg' : 'tag-md');
    var nameSize = size === 'small' ? '11px' : (size === 'large' ? '16px' : '13px');
    var priceSize = size === 'small' ? '18px' : (size === 'large' ? '32px' : '24px');

    for (var i = 0; i < copies; i++) {
        printWin.document.write('<div class="tag ' + sizeClass + '">' +
            '<div class="brand">AGRIVET INVENTORY SUPPLY</div>' +
            '<div class="name" style="font-size:' + nameSize + '">' + safeName + '</div>' +
            '<div class="price" style="font-size:' + priceSize + '">&#8369;' + parseFloat(data.rate).toFixed(2) + '</div>' +
            '<div class="sku">SKU: PRD-' + String(data.product_id).padStart(6, '0') + '</div>' +
        '</div>');
    }

    printWin.document.write('</div></body></html>');
    printWin.document.close();
    setTimeout(function() { printWin.print(); }, 300);
}
</script>
