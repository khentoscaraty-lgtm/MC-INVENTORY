<?php require_once 'includes/header_sidebar.php'; ?>

<style>
    .notif-page-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 20px;
    }
    .notif-page-header h4 {
        font-weight: 800;
        font-size: 1.3rem;
        color: var(--text);
        margin: 0;
    }
    .notif-filters {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 20px;
    }
    .notif-filter-btn {
        background: var(--card-bg);
        border: 1.5px solid var(--border);
        color: var(--text-muted);
        border-radius: 20px;
        padding: 6px 16px;
        font-size: 0.8rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }
    .notif-filter-btn:hover {
        border-color: var(--primary);
        color: var(--primary);
    }
    .notif-filter-btn.active {
        background: var(--primary);
        border-color: var(--primary);
        color: #fff;
    }
    .notif-date-group {
        font-size: 0.78rem;
        font-weight: 700;
        text-transform: uppercase;
        color: var(--text-muted);
        letter-spacing: 0.05em;
        padding: 10px 0 6px;
        border-bottom: 1px solid var(--border-light);
        margin-bottom: 4px;
    }
    .notification-entry-page {
        display: flex;
        align-items: flex-start;
        gap: 14px;
        padding: 14px 16px;
        border-radius: 10px;
        cursor: pointer;
        transition: background 0.15s;
        position: relative;
    }
    .notification-entry-page:hover {
        background: var(--border-lightest);
    }
    .notification-entry-page.unread {
        background: color-mix(in srgb, var(--primary) 6%, var(--card-bg));
    }
    .notification-entry-page.unread:hover {
        background: color-mix(in srgb, var(--primary) 10%, var(--card-bg));
    }
    .notif-icon-circle {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 1rem;
    }
    .notif-content {
        flex: 1;
        min-width: 0;
    }
    .notif-title {
        font-weight: 700;
        font-size: 0.85rem;
        color: var(--text);
        line-height: 1.3;
    }
    .notif-message {
        font-size: 0.8rem;
        color: var(--text-muted);
        margin-top: 2px;
        line-height: 1.4;
    }
    .notif-time {
        font-size: 0.72rem;
        color: var(--text-muted);
        margin-top: 4px;
    }
    .notif-dot {
        width: 9px;
        height: 9px;
        border-radius: 50%;
        background: var(--primary);
        flex-shrink: 0;
        margin-top: 6px;
    }
    .notif-empty {
        text-align: center;
        padding: 60px 20px;
        color: var(--text-muted);
    }
    .notif-empty i {
        font-size: 2.5rem;
        margin-bottom: 12px;
        display: block;
        opacity: 0.4;
    }
    #loadMoreBtn {
        display: none;
    }
</style>

<!-- Breadcrumb -->
<ol class="breadcrumb">
    <li><a href="dashboard_secure.php">Home</a></li>
    <li class="active">Notifications</li>
</ol>

<!-- Page Header -->
<div class="notif-page-header">
    <h4><i class="fas fa-bell me-2" style="color: var(--primary);"></i> Notification Center</h4>
    <button class="btn btn-sm btn-outline-primary" onclick="markAllRead()">
        <i class="fas fa-check-double me-1"></i> Mark All Read
    </button>
</div>

<!-- Filter Buttons -->
<div class="notif-filters">
    <button class="notif-filter-btn active" data-type="all">All</button>
    <button class="notif-filter-btn" data-type="low_stock"><i class="fas fa-exclamation-triangle me-1"></i> Low Stock</button>
    <button class="notif-filter-btn" data-type="expiring"><i class="fas fa-clock me-1"></i> Expiring</button>
    <button class="notif-filter-btn" data-type="order"><i class="fas fa-shopping-cart me-1"></i> Orders</button>
    <button class="notif-filter-btn" data-type="ai_insight"><i class="fas fa-lightbulb me-1"></i> AI Insights</button>
    <button class="notif-filter-btn" data-type="system"><i class="fas fa-info-circle me-1"></i> System</button>
</div>

<!-- Notification List -->
<div class="card" style="border-radius: 14px; border: 1px solid var(--border-light); box-shadow: var(--card-shadow);">
    <div class="card-body p-0" id="notificationContainer">
        <div class="text-center py-5 text-muted">
            <i class="fas fa-spinner fa-spin"></i> Loading notifications...
        </div>
    </div>
</div>

<!-- Load More -->
<div class="text-center mt-3 mb-4">
    <button id="loadMoreBtn" class="btn btn-outline-primary" onclick="loadMoreNotifications()">
        <i class="fas fa-chevron-down me-1"></i> Load More
    </button>
</div>

<script>
var currentType = 'all';
var currentPage = 1;
var isLoading = false;

function loadPageNotifications(page, type, append) {
    if (isLoading) return;
    isLoading = true;

    if (!append) {
        $('#notificationContainer').html('<div class="text-center py-5 text-muted"><i class="fas fa-spinner fa-spin"></i> Loading...</div>');
    }

    $.ajax({
        url: 'php_action/fetchNotifications.php',
        data: { mode: 'page', page: page, type: type },
        dataType: 'json',
        success: function(data) {
            isLoading = false;
            if (!data || !data.success) {
                if (data && data.session_expired) {
                    $('#notificationContainer').html('<div class="text-center py-5 text-muted"><i class="fas fa-sign-in-alt fa-2x mb-2 text-warning"></i><div>Session expired. Please <a href="login_secure.php" style="color:var(--primary);font-weight:600;">log in again</a>.</div></div>');
                } else if (!append) {
                    $('#notificationContainer').html('<div class="text-center py-4 text-muted">' + (data && data.message ? escHtml(data.message) : 'Could not load notifications.') + '</div>');
                }
                return;
            }

            if (data.notifications.length === 0 && !append) {
                $('#notificationContainer').html(
                    '<div class="notif-empty">' +
                    '<i class="fas fa-bell-slash"></i>' +
                    '<div style="font-size: 0.95rem; font-weight: 600;">No notifications</div>' +
                    '<div style="font-size: 0.82rem; margin-top: 4px;">You\'re all caught up!</div>' +
                    '</div>'
                );
                $('#loadMoreBtn').hide();
                return;
            }

            var html = '';
            var lastGroup = append ? $('#notificationContainer').data('lastGroup') || '' : '';

            data.notifications.forEach(function(n) {
                // Date group header
                if (n.date_group !== lastGroup) {
                    lastGroup = n.date_group;
                    html += '<div class="notif-date-group px-3">' + escHtml(n.date_group) + '</div>';
                }

                var unreadClass = n.is_read == 0 ? ' unread' : '';
                var link = n.link || '';
                var iconBg = n.notif_bg || 'rgba(122,122,122,0.12)';
                var iconColor = n.color || '#7A7A7A';
                var icon = n.icon || 'fa-bell';

                html += '<div class="notification-entry-page' + unreadClass + '" data-id="' + n.notification_id + '" data-link="' + escAttr(link) + '" onclick="markReadAndGo(' + n.notification_id + ', \'' + escAttr(link) + '\')">';
                html += '  <div class="notif-icon-circle" style="background: ' + iconBg + ';">';
                html += '    <i class="fas ' + escHtml(icon) + '" style="color: ' + iconColor + ';"></i>';
                html += '  </div>';
                html += '  <div class="notif-content">';
                html += '    <div class="notif-title">' + escHtml(n.title) + '</div>';
                html += '    <div class="notif-message">' + escHtml(n.message) + '</div>';
                html += '    <div class="notif-time"><i class="far fa-clock me-1"></i>' + escHtml(n.time_ago) + '</div>';
                html += '  </div>';
                if (n.is_read == 0) {
                    html += '  <span class="notif-dot"></span>';
                }
                html += '</div>';
            });

            if (append) {
                $('#notificationContainer').append(html);
            } else {
                $('#notificationContainer').html(html);
            }
            $('#notificationContainer').data('lastGroup', lastGroup);

            // Show/hide load more
            if (data.has_more) {
                $('#loadMoreBtn').show();
            } else {
                $('#loadMoreBtn').hide();
            }
        },
        error: function(xhr) {
            isLoading = false;
            if (!append) {
                var isExpired = xhr.responseJSON && xhr.responseJSON.session_expired;
                if (isExpired) {
                    $('#notificationContainer').html('<div class="text-center py-5 text-muted"><i class="fas fa-sign-in-alt fa-2x mb-2 text-warning"></i><div>Session expired. Please <a href="login_secure.php" style="color:var(--primary);font-weight:600;">log in again</a>.</div></div>');
                } else {
                    $('#notificationContainer').html('<div class="text-center py-4 text-muted">Could not load notifications.</div>');
                }
            }
        }
    });
}

function loadMoreNotifications() {
    currentPage++;
    loadPageNotifications(currentPage, currentType, true);
}

function markReadAndGo(notifId, link) {
    // Mark as read
    $.post('php_action/markNotificationRead.php', { notificationId: notifId });

    // Update UI
    var $entry = $('[data-id="' + notifId + '"]');
    $entry.removeClass('unread');
    $entry.find('.notif-dot').remove();

    // Navigate
    if (link) {
        window.location.href = link;
    }
}

function markAllRead() {
    $.post('php_action/markAllNotificationsRead.php', function(data) {
        if (data.success) {
            $('.notification-entry-page').removeClass('unread');
            $('.notif-dot').remove();
            // Also update bell count
            if (typeof loadNotifications === 'function') loadNotifications();
        }
    }, 'json');
}

function escHtml(str) {
    if (!str) return '';
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
}

function escAttr(str) {
    if (!str) return '';
    return str.replace(/'/g, "\\'").replace(/"/g, '&quot;');
}
</script>

<?php require_once 'includes/footer_sidebar.php'; ?>

<script>
$(document).ready(function() {
    loadPageNotifications(1, 'all', false);

    // Filter buttons
    $('.notif-filter-btn').on('click', function() {
        $('.notif-filter-btn').removeClass('active');
        $(this).addClass('active');
        currentType = $(this).data('type');
        currentPage = 1;
        loadPageNotifications(1, currentType, false);
    });
});
</script>
