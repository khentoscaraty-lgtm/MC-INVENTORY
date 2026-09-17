<?php
require_once __DIR__ . '/bootstrap_simple.php';
SimpleSecurity::requirePermission('transaction');
require_once 'includes/header_sidebar.php';
?>
<?php require_once 'php_action/unit_helper.php'; ?>
<?php require_once 'php_action/sales_core.php'; ?>

<script>window.sackSizeKg = <?php echo json_encode(getSackSizeKg($connect)); ?>;</script>
<script>window.vatPercent = <?php echo json_encode(salesGetVatPercent($connect)); ?>;</script>

<style>
/* ============================================================
   POS TRANSACTION — Single Page Multi-Item Cart Layout
   Matches Agrivet Inventory Supply POS design:
   Left: Product Info | Center: Weight Selector | Right: Cart
   ============================================================ */

.pos-container { min-height: calc(100vh - 100px); padding-bottom: 20px; }

/* ── Top Bar ──────────────────────────────────────────────── */
.pos-search-row {
    display: flex;
    gap: 12px;
    margin-bottom: 16px;
    align-items: center;
}
.pos-search-wrap {
    flex: 1;
    position: relative;
}
.pos-search-wrap .search-icon {
    position: absolute;
    left: 16px; top: 50%;
    transform: translateY(-50%);
    color: var(--text-muted);
    pointer-events: none;
    font-size: 1rem;
}
.pos-search-wrap .form-control {
    padding-left: 44px;
    padding-right: 44px;
    border-radius: 12px;
    border: 1px solid var(--border);
    height: 48px;
    font-size: 0.95rem;
    background: var(--card-bg);
}
.pos-search-wrap .form-control:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(242,122,26,.12);
}
.pos-search-wrap .clear-search-btn {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    background: transparent;
    border: none;
    color: #94a3b8;
    padding: 0;
    font-size: 0.92rem;
    cursor: pointer;
    display: none;
    align-items: center;
    justify-content: center;
    width: 26px;
    height: 26px;
    border-radius: 50%;
    transition: color 0.15s ease, background-color 0.15s ease, transform 0.15s ease;
    z-index: 5;
}
.pos-search-wrap .clear-search-btn.is-visible {
    display: flex !important;
}
.pos-search-wrap .clear-search-btn:hover {
    color: #ef4444;
    background-color: rgba(239, 68, 68, 0.15);
    transform: translateY(-50%) scale(1.12);
}
.pos-search-wrap .clear-search-btn:focus {
    outline: none;
}
.product-expiry-box {
    background: #FFF8F3;
    color: #E65100;
    border: 1.5px solid #F5E6D3;
    border-radius: 12px;
    padding: 0 18px;
    font-weight: 700;
    font-size: 0.88rem;
    display: flex;
    align-items: center;
    gap: 8px;
    height: 48px;
    white-space: nowrap;
    transition: all .2s;
}

#productSearchResults {
    position: absolute;
    z-index: 1050;
    width: 100%;
    max-height: 320px;
    overflow-y: auto;
    display: none;
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0,0,0,.15);
    margin-top: 6px;
    top: 100%;
}
#productSearchResults .list-group-item {
    border: none;
    border-bottom: 1px solid var(--border-light);
    border-left: 3px solid transparent;
    padding: 12px 16px;
    font-size: 0.9rem;
    color: var(--text);
    background: transparent;
    cursor: pointer;
    transition: background 0.15s ease, border-color 0.15s ease;
}
#productSearchResults .list-group-item:last-child { border-bottom: none; }
#productSearchResults .list-group-item:hover,
#productSearchResults .list-group-item.active {
    background: #FFF8F3 !important;
    border-left: 3px solid #F27A1A;
}
#productSearchResults .list-group-item.active strong,
#productSearchResults .list-group-item:hover strong {
    color: #F27A1A;
}
[data-theme="dark"] #productSearchResults {
    background: #1e293b;
    border-color: #334155;
}
[data-theme="dark"] #productSearchResults .list-group-item {
    color: #f1f5f9;
    border-bottom-color: #334155;
}
[data-theme="dark"] #productSearchResults .list-group-item:hover,
[data-theme="dark"] #productSearchResults .list-group-item.active {
    background: #2A2218 !important;
    border-left-color: #F27A1A;
}

/* ── Customer + Date Meta ──────────────────────────────────── */
.pos-meta-row {
    display: flex;
    gap: 14px;
    margin-bottom: 14px;
}
.pos-meta-row .meta-item { flex: 1; }
.pos-meta-row label {
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 4px;
    display: flex; align-items: center; gap: 6px;
}
.pos-meta-row .form-control {
    border-radius: 10px;
    padding: 8px 14px;
    font-size: 0.9rem;
    border: 1px solid var(--border);
    height: 40px;
}

/* ── 3-Column POS Grid ────────────────────────────────────── */
.pos-3col {
    display: grid;
    grid-template-columns: 310px 1fr 340px;
    gap: 16px;
    align-items: start;
}
@media (max-width: 1350px) {
    .pos-3col { grid-template-columns: 270px 1fr 310px; }
}
@media (max-width: 1100px) {
    .pos-3col { grid-template-columns: 1fr 1fr; }
    .pos-col-right { grid-column: 1 / -1; }
}
@media (max-width: 768px) {
    .pos-3col { grid-template-columns: 1fr; }
}

.pos-col {
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,.03);
}
.pos-col-header {
    padding: 14px 20px;
    border-bottom: 1px solid var(--border);
    font-weight: 800;
    font-size: 0.82rem;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    color: var(--text);
    background: var(--card-bg);
    display: flex;
    align-items: center;
    gap: 10px;
}
.pos-col-body { padding: 18px; }

/* ── LEFT: Product Information ─────────────────────────────── */
.product-info-head {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
    padding-bottom: 12px;
    border-bottom: 1px solid var(--border);
}
.product-thumb-wrap {
    width: 80px;
    height: 80px;
    border-radius: 12px;
    overflow: hidden;
    flex-shrink: 0;
    background: #F8FAFC;
    border: 1.5px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 6px rgba(0,0,0,0.04);
}
[data-theme="dark"] .product-thumb-wrap { background: #1E293B; }
.product-thumb-wrap img { width: 100%; height: 100%; object-fit: cover; }
.product-thumb-wrap .thumb-ph { font-size: 2rem; color: var(--text-muted); opacity: .4; }

.product-head-info {
    flex: 1;
    min-width: 0;
}
.product-title-text {
    font-weight: 800;
    font-size: 1.05rem;
    margin: 0 0 6px;
    color: var(--text);
    line-height: 1.3;
    word-break: break-word;
}
.product-price-box {
    display: inline-flex;
    align-items: baseline;
    gap: 6px;
    background: #FFF7ED;
    border: 1px solid #FFEDD5;
    padding: 3px 10px;
    border-radius: 8px;
    max-width: 100%;
    flex-wrap: wrap;
}
[data-theme="dark"] .product-price-box {
    background: #2D1E12;
    border-color: #4A3018;
}
.product-price-box .price-lbl {
    font-size: 0.72rem;
    font-weight: 700;
    color: #C2410C;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}
[data-theme="dark"] .product-price-box .price-lbl { color: #FB923C; }
.product-price-box .price-val {
    font-size: 1.15rem;
    font-weight: 900;
    color: #EA580C;
    font-family: var(--font-display, sans-serif);
}

/* Product Spec Table */
.product-spec-list {
    display: flex;
    flex-direction: column;
    border: 1px solid var(--border);
    border-radius: 10px;
    background: var(--card-bg);
    overflow: hidden;
    margin-bottom: 12px;
}
.spec-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 12px;
    font-size: 0.82rem;
    border-bottom: 1px solid var(--border-light, #f1f5f9);
    gap: 8px;
}
[data-theme="dark"] .spec-item { border-bottom-color: rgba(255,255,255,0.06); }
.spec-item:last-child {
    border-bottom: none;
}
.spec-item .spec-label {
    color: var(--text-muted, #64748b);
    font-weight: 600;
    white-space: nowrap;
    font-size: 0.78rem;
    display: flex;
    align-items: center;
    gap: 6px;
    flex-shrink: 0;
}
.spec-item .spec-value {
    font-weight: 700;
    color: var(--text, #1e293b);
    text-align: right;
    word-break: break-word;
    font-size: 0.82rem;
}

/* Stock Summary Box */
.stock-summary-box {
    background: #FFFDF9;
    border: 1.5px solid #F5E6D3;
    border-radius: 10px;
    padding: 12px;
    margin-bottom: 12px;
}
[data-theme="dark"] .stock-summary-box { background: #2A2218; border-color: #4A3828; }
.ss-title {
    font-size: 0.72rem;
    font-weight: 800;
    color: #8C6A47;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    border-bottom: 1px solid #F5E6D3;
    padding-bottom: 6px;
    margin-bottom: 8px;
}
[data-theme="dark"] .ss-title { color: #C9A47A; border-color: #4A3828; }
.ss-row {
    display: flex;
    justify-content: space-between;
    font-size: 0.82rem;
    color: var(--text);
    padding: 3px 0;
}
.ss-row .ssv { font-weight: 700; }
.ss-row.ss-total {
    border-top: 1px dashed #F5E6D3;
    padding-top: 6px;
    margin-top: 4px;
}
[data-theme="dark"] .ss-row.ss-total { border-top-color: #4A3828; }
.ss-row.ss-total .ssv { font-weight: 800; color: #16A34A; }

.in-stock-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.82rem;
    font-weight: 700;
    color: #16A34A;
    background: #F0FDF4;
    padding: 4px 12px;
    border-radius: 20px;
    border: 1px solid #DCFCE7;
    margin-top: 6px;
}
.weight-guide-box {
    background: #FAFAFA;
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 12px 14px;
    font-size: 0.78rem;
    color: var(--text-muted);
    display: flex;
    align-items: flex-start;
    gap: 10px;
    line-height: 1.5;
    margin-top: 14px;
}
.weight-guide-box i { color: #8C6A47; flex-shrink: 0; margin-top: 2px; font-size: 0.95rem; }

/* ── CENTER: Weight Selector ──────────────────────────────── */
.selected-item-bar {
    background: #FFFDF9;
    border: 1.5px solid #F5E6D3;
    border-radius: 12px;
    padding: 14px 16px;
    margin-top: 16px;
    display: none;
}
[data-theme="dark"] .selected-item-bar { background: #2A2218; border-color: #4A3828; }
.selected-item-bar.show { display: block; }

.sib-top {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
}
.sib-img {
    width: 50px; height: 50px;
    border-radius: 10px;
    overflow: hidden;
    flex-shrink: 0;
    border: 1px solid var(--border);
    background: #FFF;
    display: flex; align-items: center; justify-content: center;
}
.sib-img img { width: 100%; height: 100%; object-fit: cover; }
.sib-img .sib-ph { font-size: 1.3rem; color: var(--text-muted); opacity: .5; }
.sib-info { flex: 1; min-width: 0; }
.sib-name { font-weight: 800; font-size: 0.95rem; color: var(--text); }
.sib-weight { font-size: 0.82rem; color: var(--text-muted); margin-top: 2px; }
.sib-total {
    text-align: right;
    font-size: 0.75rem;
    color: var(--text-muted);
    font-weight: 600;
}
.sib-total-amount {
    font-weight: 900;
    font-size: 1.25rem;
    color: #F27A1A;
    line-height: 1.2;
}
.sib-actions {
    display: flex;
    gap: 10px;
}
.btn-change-weight {
    flex: 1;
    border: 1.5px solid #E5E7EB;
    color: #374151;
    background: #FFF;
    font-weight: 700;
    font-size: 0.85rem;
    border-radius: 10px;
    padding: 10px 14px;
    cursor: pointer;
    transition: all .2s;
    display: flex; align-items: center; justify-content: center; gap: 6px;
}
.btn-change-weight:hover { border-color: #D1D5DB; background: #F9FAFB; }
.btn-add-to-cart {
    flex: 1.8;
    background: #F27A1A;
    color: #FFF;
    border: none;
    font-weight: 800;
    font-size: 0.9rem;
    border-radius: 10px;
    padding: 10px 16px;
    cursor: pointer;
    transition: all .2s;
    display: flex; align-items: center; justify-content: center; gap: 8px;
    box-shadow: 0 4px 12px rgba(242,122,26,.25);
}
.btn-add-to-cart:hover { background: #E0690B; }

/* ── RIGHT: Current Order Cart ─────────────────────────────── */
.pos-col-right { position: sticky; top: 16px; }
.cart-header {
    background: #18181B;
    color: #FFF;
    padding: 14px 20px;
    border-radius: 14px 14px 0 0;
    font-weight: 800;
    font-size: 0.82rem;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.cart-count-badge {
    background: #DC2626;
    color: #FFF;
    border-radius: 20px;
    padding: 3px 10px;
    font-size: 0.72rem;
    font-weight: 800;
}

/* Cart items list */
.cart-items-list {
    max-height: 280px;
    overflow-y: auto;
    padding: 6px 0;
    scrollbar-width: thin;
}
.cart-items-list::-webkit-scrollbar { width: 4px; }
.cart-items-list::-webkit-scrollbar-thumb { background: var(--border); border-radius: 4px; }

.cart-empty-state {
    text-align: center;
    padding: 40px 20px;
    color: var(--text-muted);
    font-size: 0.88rem;
}
.cart-empty-state i { font-size: 2.8rem; opacity: .2; display: block; margin-bottom: 10px; }

.cart-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    border-bottom: 1px solid var(--border-light);
    transition: background .15s;
}
.cart-item:last-child { border-bottom: none; }
.cart-item:hover { background: #FAFAFA; }
.cart-item-info { flex: 1; min-width: 0; }
.cart-item-name {
    font-weight: 800;
    font-size: 0.88rem;
    color: var(--text);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.cart-item-sub {
    font-size: 0.78rem;
    color: var(--text-muted);
    margin-top: 2px;
}
.cart-item-qty-ctrl {
    display: flex;
    align-items: center;
    gap: 6px;
}
.qty-ctrl-btn {
    width: 26px; height: 26px;
    border: 1px solid #D1D5DB;
    background: #FFF;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 800;
    color: #374151;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: all .15s;
}
.qty-ctrl-btn:hover { border-color: #F27A1A; color: #F27A1A; background: #FFF8F3; }
.cart-item-count {
    font-weight: 800;
    font-size: 0.85rem;
    color: var(--text);
    min-width: 20px;
    text-align: center;
}
.cart-item-total {
    font-weight: 800;
    font-size: 0.92rem;
    color: var(--text);
    white-space: nowrap;
}
.btn-cart-remove {
    background: none;
    border: none;
    color: #EF4444;
    cursor: pointer;
    padding: 4px;
    border-radius: 6px;
    font-size: 0.95rem;
    transition: all .15s;
    opacity: .7;
}
.btn-cart-remove:hover { opacity: 1; transform: scale(1.1); }

/* Cart Totals */
.cart-totals {
    padding: 14px 18px;
    border-top: 1px solid var(--border);
}
.cart-total-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.85rem;
    color: var(--text);
    margin-bottom: 8px;
}
.cart-total-row .ctl { color: var(--text-muted); font-weight: 600; }
.cart-total-row .ctv { font-weight: 700; }
.cart-discount-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 10px;
}
.cart-discount-row .ctl { font-size: 0.85rem; color: var(--text-muted); font-weight: 600; }
.discount-select {
    font-size: 0.8rem;
    padding: 4px 8px;
    border-radius: 8px;
    border: 1px solid var(--border);
    font-weight: 700;
    background: var(--card-bg);
    color: var(--text);
}

.cart-grand-total {
    padding: 10px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-top: 1px dashed var(--border);
    margin-top: 6px;
}
.cgt-label { font-weight: 800; font-size: 0.82rem; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text); }
.cgt-value { font-weight: 900; font-size: 1.7rem; color: #F27A1A; font-family: var(--font-display); }

/* Cart Action Buttons */
.cart-actions { padding: 14px 18px; display: flex; flex-direction: column; gap: 10px; }
.btn-checkout-order {
    background: #16A34A;
    color: #FFF;
    border: none;
    border-radius: 10px;
    padding: 13px;
    font-weight: 800;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all .2s;
    display: flex; align-items: center; justify-content: center; gap: 8px;
    width: 100%;
    box-shadow: 0 4px 12px rgba(22,163,74,.25);
}
.btn-checkout-order:hover { background: #15803D; }

.btn-clear-cart {
    background: #FFF;
    color: #DC2626;
    border: 1.5px solid #FCA5A5;
    border-radius: 10px;
    padding: 10px;
    font-weight: 700;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all .2s;
    display: flex; align-items: center; justify-content: center; gap: 6px;
    width: 100%;
}
.btn-clear-cart:hover { background: #FEF2F2; border-color: #EF4444; }

/* Payment Modal / Panel */
.payment-panel {
    padding: 14px 18px;
    border-top: 1px solid var(--border);
    display: none;
}
.payment-panel.show { display: block; }
.payment-panel-title {
    font-size: 0.8rem;
    font-weight: 800;
    color: var(--text);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 10px;
}
.pm-grid {
    display: flex;
    gap: 8px;
    margin-bottom: 12px;
}
.pm-btn {
    flex: 1;
    border: 1.5px solid var(--border);
    background: var(--card-bg);
    border-radius: 10px;
    padding: 10px 4px;
    text-align: center;
    cursor: pointer;
    transition: all .2s;
    display: flex; flex-direction: column; align-items: center; gap: 4px;
}
.pm-btn i { font-size: 1.1rem; color: var(--text-muted); }
.pm-btn span { font-size: 0.72rem; font-weight: 700; color: var(--text-muted); }
.pm-btn:hover { border-color: #F27A1A; }
.pm-btn.active { border-color: #F27A1A; background: #FFF8F3; }
.pm-btn.active i, .pm-btn.active span { color: #F27A1A; }

.pay-input-group { margin-bottom: 10px; }
.pay-input-group label {
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--text);
    margin-bottom: 4px;
    display: block;
    text-transform: uppercase;
}
.pay-input-group .form-control {
    border-radius: 10px;
    font-weight: 800;
    font-size: 1rem;
    padding: 10px 14px;
    border: 1px solid var(--border);
}
.ref-group { display: none; }
.ref-group.show { display: block; }

.change-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #F0FDF4;
    border-radius: 10px;
    padding: 10px 14px;
    margin-bottom: 12px;
}
[data-theme="dark"] .change-row { background: #132A1E; }
.change-row .clbl { font-size: 0.82rem; font-weight: 700; color: #16A34A; }
.change-row .cval { font-weight: 900; font-size: 1.1rem; }
.change-positive { color: #16A34A; }
.change-negative { color: #DC2626; }

.btn-confirm-checkout {
    background: #16A34A;
    color: #FFF;
    border: none;
    border-radius: 10px;
    padding: 13px;
    font-weight: 800;
    font-size: 0.95rem;
    cursor: pointer;
    width: 100%;
    display: flex; align-items: center; justify-content: center; gap: 8px;
    transition: all .2s;
}
.btn-confirm-checkout:hover { background: #15803D; }
.btn-cancel-checkout {
    background: none;
    color: var(--text-muted);
    border: none;
    font-size: 0.8rem;
    cursor: pointer;
    padding: 6px;
    width: 100%;
    margin-top: 4px;
    text-align: center;
    text-decoration: underline;
}

/* Saved Orders Section (Right Panel) */
.saved-orders-section {
    margin-top: 14px;
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: 14px;
    overflow: hidden;
}
.saved-orders-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 18px;
    border-bottom: 1px solid var(--border);
}
.saved-orders-header .soh-title {
    font-weight: 800;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    color: var(--text);
}
.saved-orders-header .btn-view-all {
    font-size: 0.78rem;
    color: #2563EB;
    text-decoration: none;
    font-weight: 700;
    cursor: pointer;
    border: none;
    background: none;
}
.saved-orders-header .btn-view-all:hover { text-decoration: underline; }

.saved-order-card {
    padding: 12px 18px;
    border-bottom: 1px solid var(--border-light);
    transition: background .15s;
}
.saved-order-card:last-child { border-bottom: none; }
.saved-order-card:hover { background: #FAFAFA; }
.soc-row1 {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 4px;
}
.soc-ref { font-weight: 800; font-size: 0.85rem; color: var(--text); }
.soc-status {
    font-size: 0.72rem;
    font-weight: 800;
    padding: 2px 10px;
    border-radius: 20px;
    background: #FEF3C7;
    color: #D97706;
}
.soc-customer { font-size: 0.82rem; color: var(--text-muted); margin-bottom: 4px; }
.soc-row2 {
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.soc-meta { font-size: 0.75rem; color: var(--text-muted); }
.btn-open-order {
    background: #FFF;
    color: #374151;
    border: 1px solid #D1D5DB;
    border-radius: 8px;
    padding: 5px 14px;
    font-size: 0.78rem;
    font-weight: 700;
    cursor: pointer;
    transition: all .15s;
}
.btn-open-order:hover { border-color: #F27A1A; color: #F27A1A; background: #FFF8F3; }

.saved-orders-empty {
    padding: 24px;
    text-align: center;
    font-size: 0.85rem;
    color: var(--text-muted);
}


/* History Tables */
.history-card {
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: 14px;
    overflow: hidden;
    margin-top: 20px;
}
.history-card-header {
    background: var(--card-bg);
    padding: 16px 22px;
    border-bottom: 1px solid var(--border);
}
.history-card-header h6 {
    margin: 0;
    font-weight: 800;
    font-size: 0.95rem;
    color: var(--text);
}
.history-card-body { padding: 16px 22px; }

#pos-messages { margin-bottom: 12px; }
</style>

<div class="pos-container">

    <!-- ── Search Row ── -->
    <div class="pos-search-row">
        <div class="pos-search-wrap">
            <i class="fas fa-search search-icon"></i>
            <input type="text" class="form-control" id="productSearchInput"
                   placeholder="Search by product name or SKU..." autocomplete="off">
            <button type="button" class="clear-search-btn" id="clearPosProductSearch" title="Clear search" aria-label="Clear search">
                <i class="fas fa-times"></i>
            </button>
            <div id="productSearchResults" class="list-group"></div>
        </div>
        <div class="product-expiry-box" id="productExpiryBox">
            <i class="fas fa-clock" style="color: #F27A1A; font-size: 1rem;"></i>
            <span>Expiry Date: <strong id="topExpiryDateDisplay" style="color: var(--text);">No product chosen</strong></span>
        </div>
    </div>

    <!-- Hidden data-stores -->
    <select id="productSelect" style="display:none;"><option value="">--</option></select>
    <span id="unitPriceDisplay" style="display:none;">0.00</span>
    <span id="availableStock"   style="display:none;">0</span>
    <span id="stockUnitLabel"   style="display:none;">pcs</span>
    <input type="radio" name="priceType" value="Retail"    checked style="display:none;">
    <input type="radio" name="priceType" value="Wholesale"         style="display:none;">
    <span class="or-badge" id="orNumber" style="display:none;"></span>
    <input type="text" id="transactionDate" value="<?php echo date('Y-m-d'); ?>" style="display:none;">

    <!-- Messages -->
    <div id="pos-messages"></div>

    <!-- ── Customer Meta Hidden Data ── -->
    <input type="hidden" id="customerName" value="Walk-in Customer">

    <!-- ── 3-Column Layout ── -->
    <div class="pos-3col" id="pos3col">

        <!-- LEFT: Product Information -->
        <div class="pos-col pos-col-left" id="posColLeft">
            <div class="pos-col-header">
                <i class="fas fa-box-open me-1" style="color:#F27A1A;"></i> PRODUCT INFORMATION
            </div>
            <div class="pos-col-body">
                <div id="productInfoContent">
                    <div class="product-info-head">
                        <div class="product-thumb-wrap">
                            <img id="productInfoImage" src="" alt="Product" style="display:none;">
                            <i class="fas fa-box-open thumb-ph" id="productInfoPlaceholder"></i>
                        </div>
                        <div class="product-head-info">
                            <h4 id="productInfoName" class="product-title-text">—</h4>
                            <div class="product-price-box">
                                <span class="price-lbl" id="productInfoPriceLabel">Price per pcs:</span>
                                <span class="price-val" id="productInfoPrice">₱0.00</span>
                            </div>
                        </div>
                    </div>

                    <!-- Specification List -->
                    <div class="product-spec-list">
                        <div class="spec-item" id="productInfoCategoryRow">
                            <span class="spec-label"><i class="fas fa-folder text-muted"></i> Category</span>
                            <span class="spec-value" id="productInfoCategory">—</span>
                        </div>
                        <div class="spec-item" id="productInfoBrandRow">
                            <span class="spec-label"><i class="fas fa-tag text-muted"></i> Brand</span>
                            <span class="spec-value" id="productInfoBrand">—</span>
                        </div>
                        <div class="spec-item" id="productInfoSkuRow">
                            <span class="spec-label"><i class="fas fa-barcode text-muted"></i> SKU</span>
                            <span class="spec-value font-monospace" id="productInfoSku">—</span>
                        </div>
                        <div class="spec-item" id="productInfoSackSizeRow" style="display:none;">
                            <span class="spec-label"><i class="fas fa-weight-scale text-muted"></i> Sack Size</span>
                            <span class="spec-value" id="productInfoSackSize">50 kg (1 Sack)</span>
                        </div>
                        <div class="spec-item" id="productInfoStockRow">
                            <span class="spec-label"><i class="fas fa-cubes text-muted"></i> Available Stock</span>
                            <span class="spec-value text-success fw-bold" id="productInfoStockVal">0 pcs</span>
                        </div>
                        <div class="spec-item" id="productInfoExpiryRow">
                            <span class="spec-label"><i class="fas fa-calendar-alt text-muted"></i> Expiry Date</span>
                            <span class="spec-value" id="productInfoExpiryDate">—</span>
                        </div>
                    </div>

                    <!-- Stock Summary Box -->
                    <div class="stock-summary-box" id="stockSummaryBox">
                        <div class="ss-title">Stock Summary</div>
                        <div class="ss-row">
                            <span>Full Sacks</span>
                            <span class="ssv" id="ssFullSacks">110 sacks</span>
                        </div>
                        <div class="ss-row">
                            <span>Open Sacks</span>
                            <span class="ssv" id="ssOpenedSack">15 sacks</span>
                        </div>
                        <div class="ss-row">
                            <span>Loose Stock</span>
                            <span class="ssv" id="ssLooseStock">25.50 kg</span>
                        </div>
                        <div class="ss-row ss-total">
                            <span>Total Stock (kg)</span>
                            <span class="ssv" id="ssTotalStock">5,525.50 kg</span>
                        </div>
                        <div class="ss-row" style="margin-top:4px;">
                            <span>Price per kg</span>
                            <span class="ssv" id="ssPricePerKg">₱35.00</span>
                        </div>
                    </div>

                    <!-- Batches & Expiries Box -->
                    <div class="batches-summary-box mt-3" id="batchesSummaryBox" style="display:none; background:#FFF8F3; border:1.5px solid #F5E6D3; border-radius:10px; padding:12px;">
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-1 mb-2">
                            <span style="font-size:0.72rem; font-weight:800; color:#C2410C; text-transform:uppercase; letter-spacing:0.6px;"><i class="fas fa-layer-group me-1"></i> Active Batches &amp; Expiries</span>
                            <span class="badge bg-warning text-dark" id="batchCountBadge">0 Batches</span>
                        </div>
                        <div id="batchesListContainer" style="max-height:160px; overflow-y:auto;">
                            <!-- Filled dynamically by JS -->
                        </div>
                    </div>

                    <div class="in-stock-badge" id="stockStatusBadge">
                        <i class="fas fa-check-circle"></i> In Stock
                    </div>

                    <div class="weight-guide-box" id="weightGuideBox">
                        <i class="fas fa-info-circle"></i>
                        <div>
                            <strong>WEIGHT GUIDE</strong><br>
                            All weights are automatically converted to grams for accuracy.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CENTER: 1. Choose Quantity -->
        <div class="pos-col pos-col-center" id="posColCenter">
            <div class="pos-col-header">
                <i class="fas fa-layer-group me-1" style="color:#F27A1A;" id="centerColIcon"></i>
                <span id="centerColTitle">1. CHOOSE QUANTITY</span>
            </div>
            <div class="pos-col-body">
                <div id="qtySelectorWrapper">
                    <div id="qtySelectorMount">
                        <div class="text-center py-4 px-3" id="noProductPrompt" style="color:var(--text-muted);">
                            <i class="fas fa-search fs-2 mb-2 d-block" style="color:#f27a1a; opacity:0.6;"></i>
                            <div class="fw-bold" style="font-size:0.92rem; color:var(--text-dark, #1e293b);">No Product Selected</div>
                            <div class="small text-muted" style="font-size:0.78rem;">Search or click a product on the left to choose quantity and weight</div>
                        </div>
                    </div>

                    <!-- Selected Item Bar -->
                    <div class="selected-item-bar" id="selectedItemBar">
                        <div style="font-size:0.7rem;font-weight:800;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:8px;">SELECTED ITEM</div>
                        <div class="sib-top">
                            <div class="sib-img">
                                <img id="sibProductImage" src="" style="display:none;">
                                <i class="fas fa-box-open sib-ph" id="sibProductPlaceholder"></i>
                            </div>
                            <div class="sib-info">
                                <div class="sib-name" id="sibProductName">ANDAT</div>
                                <div class="sib-weight" id="sibWeightText">
                                    Weight: <span id="sibWeightKg">1.00</span> kg
                                    (<span id="sibWeightGrams">1000</span> grams)
                                </div>
                                <div class="sib-weight" id="sibPriceText"><span id="sibPriceLabel">Price per kg</span>: ₱<span id="sibPricePerKg">35.00</span></div>
                            </div>
                            <div class="sib-total">
                                <div>Item Total</div>
                                <div class="sib-total-amount" id="sibItemTotal">₱35.00</div>
                            </div>
                        </div>
                        <div class="sib-actions">
                            <button type="button" class="btn-change-weight" id="btnChangeWeight">
                                <i class="fas fa-pencil-alt"></i> Change Weight
                            </button>
                            <button type="button" class="btn-add-to-cart" id="btnAddToCart">
                                <i class="fas fa-shopping-cart"></i> Add to Cart
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT: Current Order (Cart) -->
        <div class="pos-col pos-col-right" id="posColRight">
            <div class="cart-header">
                <span><i class="fas fa-shopping-cart me-2"></i>CURRENT ORDER (CART)</span>
                <span class="cart-count-badge" id="cartCountBadge">0 ITEMS</span>
            </div>

            <!-- Cart Items -->
            <div class="cart-items-list" id="cartItemsList">
                <div class="cart-empty-state" id="cartEmptyState">
                    <i class="fas fa-shopping-cart"></i>
                    Add products to start a transaction
                </div>
            </div>

            <!-- Totals -->
            <div class="cart-totals" id="cartTotals">
                <div class="cart-total-row">
                    <span class="ctl">Subtotal</span>
                    <span class="ctv" id="cartSubtotal">₱0.00</span>
                </div>
                <div class="cart-discount-row">
                    <span class="ctl">Discount</span>
                    <select class="discount-select" id="discountType">
                        <option value="Regular" data-percent="0">Regular (0%)</option>
                        <option value="Senior Citizen" data-percent="20">Senior Citizen (20%)</option>
                        <option value="PWD" data-percent="10">PWD (10%)</option>
                    </select>
                </div>
                <div class="cart-total-row" id="discountAmountRow" style="display:none;">
                    <span class="ctl">Discount Amount</span>
                    <span class="ctv" style="color:#F27A1A;" id="discountAmountDisplay">-₱0.00</span>
                </div>
                <div class="cart-grand-total">
                    <span class="cgt-label">GRAND TOTAL</span>
                    <span class="cgt-value" id="cartGrandTotal">₱0.00</span>
                </div>
            </div>

            <!-- Payment Panel (hidden until Checkout clicked) -->
            <div class="payment-panel" id="paymentPanel">
                <div class="payment-panel-title">Payment Method</div>
                <div class="pm-grid">
                    <button type="button" class="pm-btn active" data-method="cash">
                        <i class="fas fa-wallet"></i><span>Cash</span>
                    </button>
                </div>
                <select id="paymentMethod" style="display:none;">
                    <option value="cash" selected>Cash</option>
                    <option value="bank_transfer">Bank Transfer</option>
                </select>

                <div class="pay-input-group" id="cashAmountGroup">
                    <label>Cash Received</label>
                    <input type="number" class="form-control" id="cashAmount" placeholder="0.00" min="0" step="0.01">
                </div>

                <div class="ref-group" id="paymentRefGroup">
                    <div class="pay-input-group">
                        <label><i class="fas fa-hashtag me-1"></i>Reference No.</label>
                        <input type="text" class="form-control" id="paymentReference"
                               placeholder="e.g. transaction reference number" autocomplete="off">
                    </div>
                </div>

                <div class="change-row" id="changeRow">
                    <span class="clbl"><i class="fas fa-coins me-1"></i>Change</span>
                    <span class="cval change-positive" id="changeDisplay">₱0.00</span>
                </div>

                <input type="hidden" id="discountPercent" value="0">
                <input type="hidden" id="discountAmount"  value="0">
                <input type="hidden" id="vatAmount"       value="0">
                <input type="hidden" id="totalPayable"    value="0">
                <input type="hidden" id="changeAmount"    value="0">

                <button type="button" class="btn-confirm-checkout" id="saveTransactionBtn" data-loading-text="Processing...">
                    <i class="fas fa-check-circle"></i> Confirm &amp; Complete Sale
                </button>
                <button type="button" class="btn-cancel-checkout" id="btnCancelCheckout">
                    ← Back to Cart
                </button>

                <div id="transaction-messages" class="mt-2"></div>
            </div>

            <!-- Cart Action Buttons -->
            <div class="cart-actions" id="cartActionButtons">
                <button type="button" class="btn-checkout-order" id="btnCheckoutOrder">
                    <i class="fas fa-cash-register"></i> Checkout / Payment
                </button>
                <button type="button" class="btn-clear-cart" id="btnClearCart">
                    <i class="fas fa-trash-alt"></i> Clear Cart
                </button>
            </div>
        </div>

    </div><!-- /.pos-3col -->


    <!-- ── Transaction History Datatable ── -->
    <div class="row mt-3">
        <div class="col-12">
            <div class="history-card">
                <div class="history-card-header">
                    <h6><i class="fas fa-history me-2"></i>Transaction History and Refund</h6>
                </div>
                <div class="history-card-body">
                    <div class="remove-messages"></div>
                    <div class="table-responsive">
                        <table class="table table-hover" id="manageTransactionTable" style="width:100%;">
                            <thead>
                                <tr>
                                    <th>OR Number</th>
                                    <th>Date</th>
                                    <th>Customer</th>
                                    <th>Items</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Cashier</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (SimpleSecurity::hasPermission('returns')): ?>
    <!-- Returns History -->
    <div class="row mt-2">
        <div class="col-12">
            <div class="history-card">
                <div class="history-card-header">
                    <h6><i class="fas fa-undo-alt me-2"></i>Returns &amp; Refunds History</h6>
                </div>
                <div class="history-card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="manageReturnsTable" style="width:100%;">
                            <thead>
                                <tr>
                                    <th>Return No.</th>
                                    <th>OR Number</th>
                                    <th>Date</th>
                                    <th>Reason</th>
                                    <th>Refund Amount</th>
                                    <th>Refund Method</th>
                                    <th>Status</th>
                                    <th>Processed By</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div><!-- /.pos-container -->

<!-- ── Remove Transaction Modal ── -->
<div class="modal fade" id="removeTransactionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-trash me-2"></i>Void Transaction</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="removeTransactionMessages"></div>
                <div class="text-center py-2">
                    <i class="fas fa-exclamation-triangle" style="font-size:2rem;color:var(--danger);margin-bottom:.5rem;display:block;"></i>
                    <p style="font-size:.9rem;color:var(--text);margin:0;">Are you sure you want to void this transaction? Stock will be restored.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="removeTransactionBtn">Confirm Void</button>
            </div>
        </div>
    </div>
</div>

<!-- ── Held Orders List Modal ── -->
<div class="modal fade" id="heldOrdersModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-pause-circle me-2" style="color:#EA580C;"></i>Saved / Held Orders</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="heldOrdersModalBody">
                <div class="text-center text-muted py-4"><i class="fas fa-spinner fa-pulse"></i> Loading...</div>
            </div>
        </div>
    </div>
</div>

<?php if (SimpleSecurity::hasPermission('returns')): ?>
<!-- ── Process Return Modal ── -->
<div class="modal fade" id="processReturnModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-undo-alt me-2"></i>Process Return</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="return-messages"></div>
                <input type="hidden" id="returnTransactionId" value="">
                <p class="mb-2"><strong>OR #: </strong><span id="returnOrNumber"></span></p>
                <div id="returnLinesContainer"></div>
                <div class="row g-3 mt-1">
                    <div class="col-md-6">
                        <label class="form-label">Reason</label>
                        <input type="text" class="form-control" id="returnReason" placeholder="e.g. Wrong item" autocomplete="off">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Refund Method</label>
                        <select class="form-select" id="returnRefundMethod">
                            <option value="cash" selected>Cash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                        </select>
                    </div>
                </div>
                <div class="text-end mt-3">
                    <span class="fw-semibold">Estimated Refund: </span>
                    <span class="fs-5 fw-bold text-success" id="estimatedRefundDisplay">&#8369;0.00</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="submitReturnBtn">Process Return</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once 'includes/footer_sidebar.php'; ?>

<script>window.canProcessReturns = <?php echo json_encode(SimpleSecurity::hasPermission('returns')); ?>;</script>
<script src="custom/js/qty_selector.js?v=<?= time() ?>"></script>
<script src="custom/js/transaction.js?v=<?= time() ?>"></script>
