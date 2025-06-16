<?php
/**
 * Admin Dashboard View
 */

// Get filters
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$paged = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
$per_page = 20;

// Get bookings
$args = array(
    'status' => $status_filter,
    'search' => $search,
    'limit' => $per_page,
    'offset' => ($paged - 1) * $per_page
);

$result = BR_Admin_Dashboard::get_bookings($args);
$bookings = $result['bookings'];
$total_items = $result['total'];
$total_pages = ceil($total_items / $per_page);

// Get status counts
global $wpdb;
$table_name = $wpdb->prefix . 'booking_requests';
$status_counts = $wpdb->get_results("
    SELECT status, COUNT(*) as count 
    FROM $table_name 
    GROUP BY status
", OBJECT_K);

$all_count = array_sum(wp_list_pluck($status_counts, 'count'));
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Booking Requests', 'booking-requests'); ?></h1>
    <a href="#" class="page-title-action br-export-btn"><?php _e('Export CSV', 'booking-requests'); ?></a>
    
    <?php if (isset($_GET['updated'])): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Booking status updated successfully.', 'booking-requests'); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['deleted'])): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Booking deleted successfully.', 'booking-requests'); ?></p>
        </div>
    <?php endif; ?>
    
    <hr class="wp-header-end">
    
    <!-- Status filters -->
    <ul class="subsubsub">
        <li class="all">
            <a href="<?php echo remove_query_arg('status'); ?>" class="<?php echo empty($status_filter) ? 'current' : ''; ?>">
                <?php _e('All', 'booking-requests'); ?> 
                <span class="count">(<?php echo $all_count; ?>)</span>
            </a> |
        </li>
        <li class="pending">
            <a href="<?php echo add_query_arg('status', 'pending'); ?>" class="<?php echo $status_filter === 'pending' ? 'current' : ''; ?>">
                <?php _e('Pending', 'booking-requests'); ?> 
                <span class="count">(<?php echo isset($status_counts['pending']) ? $status_counts['pending']->count : 0; ?>)</span>
            </a> |
        </li>
        <li class="approved">
            <a href="<?php echo add_query_arg('status', 'approved'); ?>" class="<?php echo $status_filter === 'approved' ? 'current' : ''; ?>">
                <?php _e('Approved', 'booking-requests'); ?> 
                <span class="count">(<?php echo isset($status_counts['approved']) ? $status_counts['approved']->count : 0; ?>)</span>
            </a> |
        </li>
        <li class="denied">
            <a href="<?php echo add_query_arg('status', 'denied'); ?>" class="<?php echo $status_filter === 'denied' ? 'current' : ''; ?>">
                <?php _e('Denied', 'booking-requests'); ?> 
                <span class="count">(<?php echo isset($status_counts['denied']) ? $status_counts['denied']->count : 0; ?>)</span>
            </a>
        </li>
    </ul>
    
    <!-- Search box -->
    <form method="get">
        <input type="hidden" name="page" value="booking-requests">
        <?php if ($status_filter): ?>
            <input type="hidden" name="status" value="<?php echo esc_attr($status_filter); ?>">
        <?php endif; ?>
        <p class="search-box">
            <label class="screen-reader-text" for="booking-search-input"><?php _e('Search bookings:', 'booking-requests'); ?></label>
            <input type="search" id="booking-search-input" name="s" value="<?php echo esc_attr($search); ?>">
            <input type="submit" id="search-submit" class="button" value="<?php _e('Search', 'booking-requests'); ?>">
        </p>
    </form>
    
    <!-- Bookings table -->
    <form id="bookings-form" method="post">
        <div class="tablenav top">
            <div class="alignleft actions bulkactions">
                <label for="bulk-action-selector-top" class="screen-reader-text"><?php _e('Select bulk action', 'booking-requests'); ?></label>
                <select name="action" id="bulk-action-selector-top">
                    <option value="-1"><?php _e('Bulk Actions', 'booking-requests'); ?></option>
                    <option value="approve"><?php _e('Approve', 'booking-requests'); ?></option>
                    <option value="deny"><?php _e('Deny', 'booking-requests'); ?></option>
                    <option value="delete"><?php _e('Delete', 'booking-requests'); ?></option>
                </select>
                <input type="submit" id="doaction" class="button action br-bulk-action" value="<?php _e('Apply', 'booking-requests'); ?>">
            </div>
            
            <?php if ($total_pages > 1): ?>
                <div class="tablenav-pages">
                    <span class="displaying-num"><?php printf(_n('%s item', '%s items', $total_items, 'booking-requests'), $total_items); ?></span>
                    <?php
                    echo paginate_links(array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;',
                        'total' => $total_pages,
                        'current' => $paged
                    ));
                    ?>
                </div>
            <?php endif; ?>
        </div>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <td class="manage-column column-cb check-column">
                        <label class="screen-reader-text" for="cb-select-all-1"><?php _e('Select All', 'booking-requests'); ?></label>
                        <input id="cb-select-all-1" type="checkbox">
                    </td>
                    <th scope="col" class="manage-column"><?php _e('Guest Name', 'booking-requests'); ?></th>
                    <th scope="col" class="manage-column"><?php _e('Contact', 'booking-requests'); ?></th>
                    <th scope="col" class="manage-column"><?php _e('Dates', 'booking-requests'); ?></th>
                    <th scope="col" class="manage-column"><?php _e('Weekly Rate', 'booking-requests'); ?></th>
                    <th scope="col" class="manage-column"><?php _e('Status', 'booking-requests'); ?></th>
                    <th scope="col" class="manage-column"><?php _e('Submitted', 'booking-requests'); ?></th>
                    <th scope="col" class="manage-column"><?php _e('Actions', 'booking-requests'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($bookings)): ?>
                    <tr>
                        <td colspan="8"><?php _e('No bookings found.', 'booking-requests'); ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($bookings as $booking): ?>
                        <?php
                        $checkin = new DateTime($booking->checkin_date);
                        $checkout = new DateTime($booking->checkout_date);
                        $nights = $checkin->diff($checkout)->days;
                        ?>
                        <tr>
                            <th scope="row" class="check-column">
                                <label class="screen-reader-text" for="cb-select-<?php echo $booking->id; ?>">
                                    <?php printf(__('Select %s', 'booking-requests'), $booking->first_name . ' ' . $booking->last_name); ?>
                                </label>
                                <input id="cb-select-<?php echo $booking->id; ?>" type="checkbox" name="booking_ids[]" value="<?php echo $booking->id; ?>">
                            </th>
                            <td>
                                <strong>
                                    <a href="<?php echo add_query_arg(array('action' => 'view', 'id' => $booking->id)); ?>">
                                        <?php echo esc_html($booking->first_name . ' ' . $booking->last_name); ?>
                                    </a>
                                </strong>
                            </td>
                            <td>
                                <a href="mailto:<?php echo esc_attr($booking->email); ?>"><?php echo esc_html($booking->email); ?></a><br>
                                <?php echo esc_html($booking->phone); ?>
                            </td>
                            <td>
                                <?php echo $checkin->format('d/m/Y'); ?> → <?php echo $checkout->format('d/m/Y'); ?><br>
                                <small><?php printf(_n('%d night', '%d nights', $nights, 'booking-requests'), $nights); ?></small>
                            </td>
                            <td>€<?php echo number_format($booking->weekly_rate, 0, ',', '.'); ?></td>
                            <td>
                                <span class="br-status br-status-<?php echo $booking->status; ?>">
                                    <?php echo ucfirst($booking->status); ?>
                                </span>
                            </td>
                            <td><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($booking->submitted_at)); ?></td>
                            <td>
                                <?php if ($booking->status === 'pending'): ?>
                                    <?php $nonce = wp_create_nonce('br_admin_action'); ?>
                                    <a href="<?php echo add_query_arg(array('action' => 'approve', 'id' => $booking->id, '_wpnonce' => $nonce)); ?>" class="button button-small">
                                        <?php _e('Approve', 'booking-requests'); ?>
                                    </a>
                                    <a href="<?php echo add_query_arg(array('action' => 'deny', 'id' => $booking->id, '_wpnonce' => $nonce)); ?>" class="button button-small">
                                        <?php _e('Deny', 'booking-requests'); ?>
                                    </a>
                                <?php else: ?>
                                    <a href="<?php echo add_query_arg(array('action' => 'view', 'id' => $booking->id)); ?>" class="button button-small">
                                        <?php _e('View', 'booking-requests'); ?>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </form>
</div>

<style>
.br-status {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 600;
}
.br-status-pending {
    background: #f0ad4e;
    color: #fff;
}
.br-status-approved {
    background: #5cb85c;
    color: #fff;
}
.br-status-denied {
    background: #d9534f;
    color: #fff;
}
</style>