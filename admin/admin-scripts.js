jQuery(document).ready(function($) {
    'use strict';
    
    // Handle bulk actions
    $('.br-bulk-action').on('click', function(e) {
        e.preventDefault();
        
        var action = $('#bulk-action-selector-top').val();
        if (action === '-1') {
            alert('Please select a bulk action');
            return;
        }
        
        var selected = [];
        $('input[name="booking_ids[]"]:checked').each(function() {
            selected.push($(this).val());
        });
        
        if (selected.length === 0) {
            alert('Please select at least one booking');
            return;
        }
        
        var confirmMessage = '';
        switch(action) {
            case 'approve':
                confirmMessage = 'Are you sure you want to approve ' + selected.length + ' booking(s)?';
                break;
            case 'deny':
                confirmMessage = 'Are you sure you want to deny ' + selected.length + ' booking(s)?';
                break;
            case 'delete':
                confirmMessage = 'Are you sure you want to delete ' + selected.length + ' booking(s)? This cannot be undone.';
                break;
        }
        
        if (!confirm(confirmMessage)) {
            return;
        }
        
        // Show spinner
        var $button = $(this);
        $button.prop('disabled', true).after('<span class="br-spinner"></span>');
        
        // Perform bulk action
        $.ajax({
            url: br_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'br_bulk_action',
                nonce: br_ajax.nonce,
                bulk_action: action,
                booking_ids: selected
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message || 'An error occurred');
                    $button.prop('disabled', false).next('.br-spinner').remove();
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
                $button.prop('disabled', false).next('.br-spinner').remove();
            }
        });
    });
    
    // Handle individual status updates
    $('.br-update-status').on('change', function() {
        var $select = $(this);
        var bookingId = $select.data('booking-id');
        var newStatus = $select.val();
        
        // Show spinner
        $select.prop('disabled', true).after('<span class="br-spinner"></span>');
        
        $.ajax({
            url: br_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'br_update_status',
                nonce: br_ajax.nonce,
                booking_id: bookingId,
                status: newStatus
            },
            success: function(response) {
                if (response.success) {
                    // Update status badge
                    var $statusBadge = $select.closest('tr').find('.br-status');
                    $statusBadge.removeClass('br-status-pending br-status-approved br-status-denied')
                               .addClass('br-status-' + newStatus)
                               .text(newStatus.charAt(0).toUpperCase() + newStatus.slice(1));
                    
                    // Show success message
                    showNotice('Status updated successfully', 'success');
                } else {
                    alert(response.data.message || 'Failed to update status');
                    $select.val($select.data('original-value'));
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
                $select.val($select.data('original-value'));
            },
            complete: function() {
                $select.prop('disabled', false).next('.br-spinner').remove();
            }
        });
    });
    
    // Handle export
    $('.br-export-btn').on('click', function(e) {
        e.preventDefault();
        
        if (confirm('Export all bookings to CSV?')) {
            window.location.href = br_ajax.ajax_url + '?action=br_export_bookings&nonce=' + br_ajax.nonce;
        }
    });
    
    // Date range filter
    $('#br-date-filter').on('submit', function(e) {
        e.preventDefault();
        
        var dateFrom = $('#date-from').val();
        var dateTo = $('#date-to').val();
        
        if (dateFrom && dateTo && dateFrom > dateTo) {
            alert('End date must be after start date');
            return;
        }
        
        // Build URL with filters
        var url = new URL(window.location.href);
        if (dateFrom) url.searchParams.set('date_from', dateFrom);
        if (dateTo) url.searchParams.set('date_to', dateTo);
        
        window.location.href = url.toString();
    });
    
    // Select all checkbox
    $('#cb-select-all-1, #cb-select-all-2').on('change', function() {
        var isChecked = $(this).prop('checked');
        $('input[name="booking_ids[]"]').prop('checked', isChecked);
    });
    
    // Gravity Forms settings toggle
    $('#br_booking_enabled').on('change', function() {
        if ($(this).is(':checked')) {
            $('.br-field-mapping, .br-pricing-display').show();
        } else {
            $('.br-field-mapping, .br-pricing-display').hide();
        }
    });
    
    // Show notice function
    function showNotice(message, type) {
        var noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
        var $notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');
        
        $('.wrap h1').after($notice);
        
        // Auto dismiss after 3 seconds
        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
    }
    
    // Calendar view toggle
    $('.br-view-toggle').on('click', function(e) {
        e.preventDefault();
        
        var view = $(this).data('view');
        $('.br-view-toggle').removeClass('current');
        $(this).addClass('current');
        
        if (view === 'calendar') {
            $('.br-list-view').hide();
            $('.br-calendar-view').show();
        } else {
            $('.br-calendar-view').hide();
            $('.br-list-view').show();
        }
        
        // Save preference
        $.post(br_ajax.ajax_url, {
            action: 'br_save_view_preference',
            nonce: br_ajax.nonce,
            view: view
        });
    });
    
    // Quick edit functionality
    $('.br-quick-edit').on('click', function(e) {
        e.preventDefault();
        
        var $row = $(this).closest('tr');
        var bookingId = $(this).data('booking-id');
        
        // Toggle edit mode
        $row.find('.br-view-mode').toggle();
        $row.find('.br-edit-mode').toggle();
    });
    
    // Save quick edit
    $('.br-save-edit').on('click', function(e) {
        e.preventDefault();
        
        var $row = $(this).closest('tr');
        var bookingId = $(this).data('booking-id');
        var data = {
            action: 'br_quick_update',
            nonce: br_ajax.nonce,
            booking_id: bookingId,
            admin_notes: $row.find('.br-edit-notes').val()
        };
        
        $.post(br_ajax.ajax_url, data, function(response) {
            if (response.success) {
                $row.find('.br-view-notes').text(data.admin_notes);
                $row.find('.br-view-mode').show();
                $row.find('.br-edit-mode').hide();
                showNotice('Updated successfully', 'success');
            }
        });
    });
    
    // Cancel quick edit
    $('.br-cancel-edit').on('click', function(e) {
        e.preventDefault();
        
        var $row = $(this).closest('tr');
        $row.find('.br-view-mode').show();
        $row.find('.br-edit-mode').hide();
    });
    
    // Print booking details
    $('.br-print-booking').on('click', function(e) {
        e.preventDefault();
        window.print();
    });
});