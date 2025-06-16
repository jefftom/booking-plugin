<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Display messages
if (isset($_GET['message'])) {
    $message = '';
    switch ($_GET['message']) {
        case 'approved':
            $message = 'Booking approved successfully.';
            break;
        case 'denied':
            $message = 'Booking denied successfully.';
            break;
        case 'deleted':
            $message = 'Booking deleted successfully.';
            break;
    }
    if ($message) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
    }
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline">Booking Requests</h1>
    
    <ul class="subsubsub">
        <li class="all">
            <a href="<?php echo admin_url('admin.php?page=booking-requests'); ?>" 
               class="<?php echo empty($_GET['status']) ? 'current' : ''; ?>">
                All <span class="count">(<?php echo BR_Database::get_bookings_count(); ?>)</span>
            </a> |
        </li>
        <li class="pending">
            <a href="<?php echo admin_url('admin.php?page=booking-requests&status=pending'); ?>"
               class="<?php echo (isset($_GET['status']) && $_GET['status'] === 'pending') ? 'current' : ''; ?>">
                Pending <span class="count">(<?php echo BR_Database::get_bookings_count('pending'); ?>)</span>
            </a> |
        </li>
        <li class="approved">
            <a href="<?php echo admin_url('admin.php?page=booking-requests&status=approved'); ?>"
               class="<?php echo (isset($_GET['status']) && $_GET['status'] === 'approved') ? 'current' : ''; ?>">
                Approved <span class="count">(<?php echo BR_Database::get_bookings_count('approved'); ?>)</span>
            </a> |
        </li>
        <li class="denied">
            <a href="<?php echo admin_url('admin.php?page=booking-requests&status=denied'); ?>"
               class="<?php echo (isset($_GET['status']) && $_GET['status'] === 'denied') ? 'current' : ''; ?>">
                Denied <span class="count">(<?php echo BR_Database::get_bookings_count('denied'); ?>)</span>
            </a>
        </li>
    </ul>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Guest Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Check-in</th>
                <th>Check-out</th>
                <th>Total Price</th>
                <th>Status</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($bookings)) : ?>
                <?php foreach ($bookings as $booking) : ?>
                    <tr>
                        <td><?php echo $booking->id; ?></td>
                        <td>
                            <strong>
                                <a href="<?php echo admin_url('admin.php?page=booking-requests&action=view&booking_id=' . $booking->id); ?>">
                                    <?php echo esc_html($booking->guest_name); ?>
                                </a>
                            </strong>
                        </td>
                        <td>
                            <a href="mailto:<?php echo esc_attr($booking->email); ?>">
                                <?php echo esc_html($booking->email); ?>
                            </a>
                        </td>
                        <td><?php echo esc_html($booking->phone); ?></td>
                        <td><?php echo date('M j, Y', strtotime($booking->checkin_date)); ?></td>
                        <td><?php echo date('M j, Y', strtotime($booking->checkout_date)); ?></td>
                        <td>€<?php echo number_format($booking->total_price, 0, ',', '.'); ?></td>
                        <td>
                            <?php
                            $status_class = '';
                            switch ($booking->status) {
                                case 'pending':
                                    $status_class = 'notice-warning';
                                    break;
                                case 'approved':
                                    $status_class = 'notice-success';
                                    break;
                                case 'denied':
                                    $status_class = 'notice-error';
                                    break;
                            }
                            ?>
                            <span class="notice <?php echo $status_class; ?>" style="padding: 2px 8px; margin: 0;">
                                <?php echo ucfirst($booking->status); ?>
                            </span>
                        </td>
                        <td><?php echo date('M j, Y', strtotime($booking->created_at)); ?></td>
                        <td>
                            <?php if ($booking->status === 'pending') : ?>
                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=booking-requests&action=approve&booking_id=' . $booking->id), 'booking_action'); ?>" 
                                   class="button button-small button-primary"
                                   onclick="return confirm('Are you sure you want to approve this booking?');">
                                    Approve
                                </a>
                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=booking-requests&action=deny&booking_id=' . $booking->id), 'booking_action'); ?>" 
                                   class="button button-small"
                                   onclick="return confirm('Are you sure you want to deny this booking?');">
                                    Deny
                                </a>
                            <?php endif; ?>
                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=booking-requests&action=delete&booking_id=' . $booking->id), 'booking_action'); ?>" 
                               class="button button-small button-link-delete"
                               onclick="return confirm('Are you sure you want to delete this booking?');">
                                Delete
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="10">No bookings found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <?php
    // Pagination
    if ($total_bookings > $per_page) {
        $total_pages = ceil($total_bookings / $per_page);
        echo '<div class="tablenav bottom">';
        echo '<div class="tablenav-pages">';
        echo paginate_links(array(
            'base' => add_query_arg('paged', '%#%'),
            'format' => '',
            'prev_text' => '&laquo;',
            'next_text' => '&raquo;',
            'total' => $total_pages,
            'current' => $paged
        ));
        echo '</div>';
        echo '</div>';
    }
    ?>
</div>