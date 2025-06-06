<?php
// Prevent direct access to this file
if (!defined('ABSPATH')) {
	exit();
}

// Add tracking pixel to email template
function mlds_add_tracking_pixel($post_id) {
	$track_url = add_query_arg(
		[
			'mlds_track' => $post_id,
			'action' => 'pixel',
			'token' => get_post_meta($post_id, '_mlds_track_token', true),
		],
		home_url('/'),
	);

	return '<img src="' . esc_url($track_url) . '" width="1" height="1" style="display:none">';
}

// Add notification metabox to posts
function mlds_add_notification_metabox() {
	add_meta_box(
		'mlds_notification_metabox',
		__('Email Notification', 'music-label-demo-sender'),
		'mlds_render_notification_metabox',
		'post',
		'side',
		'high',
	);

	// Localize the script with new data
	wp_localize_script('jquery', 'mldsNotification', [
		'ajaxurl' => admin_url('admin-ajax.php'),
	]);
}
add_action('add_meta_boxes', 'mlds_add_notification_metabox');

// Render notification metabox
function mlds_render_notification_metabox($post) {
	// Add nonce for security
	wp_nonce_field('mlds_send_notification', 'mlds_notification_nonce');

	// Get subscriber groups
	global $wpdb;
	$groups = mlds_get_subscriber_groups(); // This function is in subscriber-helpers.php, ensure it's loaded

	// Check if notification was already sent
	$notification_sent = get_post_meta($post->ID, '_mlds_notification_sent', true);
	?>
    <div class="mlds-notification-box">
        <?php if ($notification_sent): ?>
            <p class="notification-sent">
                <span class="dashicons dashicons-yes-alt"></span>
                <?php _e('Notification sent on:', 'music-label-demo-sender'); ?>
                <br>
                <?php echo date_i18n(
                	get_option('date_format') . ' ' . get_option('time_format'),
                	strtotime($notification_sent),
                ); ?>
            </p>
        <?php endif; ?>

        <div class="notification-groups">
            <p><strong><?php _e(
            	'Select subscriber groups:',
            	'music-label-demo-sender',
            ); ?></strong></p>
            <?php if (!empty($groups)): ?>
                <?php foreach ($groups as $group): ?>
                    <label class="group-checkbox">
                        <input type="checkbox" name="mlds_notification_groups[]"
                               value="<?php echo esc_attr($group->group_name); ?>">
                        <?php echo esc_html($group->group_name); ?>
                        <span class="subscriber-count">
                            (<?php echo esc_html($group->subscriber_count); ?>
                            <?php _e('subscribers', 'music-label-demo-sender'); ?>)
                        </span>
                    </label>
                <?php endforeach; ?>
                <button type="button" class="button button-primary" id="mlds_send_notification">
                    <?php _e('Send Notification', 'music-label-demo-sender'); ?>
                </button>
                <span class="spinner" style="float: none; margin: 4px 0 0 4px;"></span>
            <?php else: ?>
                <p class="no-groups">
                    <?php _e('No subscriber groups found.', 'music-label-demo-sender'); ?>
                </p>
            <?php endif; ?>
        </div>
    </div>

    <style>
        .mlds-notification-box {
            margin: -6px -12px -12px;
            padding: 12px;
        }

        .notification-sent {
            color: #46b450;
            margin: 0 0 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .notification-sent .dashicons {
            font-size: 20px;
            width: 20px;
            height: 20px;
            margin-right: 5px;
        }

        .notification-groups {
            margin-top: 10px;
        }

        .group-checkbox {
            display: block;
            margin-bottom: 8px;
            cursor: pointer;
        }

        .group-checkbox:hover {
            color: #0073aa;
        }

        .subscriber-count {
            color: #666;
            font-size: 0.9em;
            margin-left: 3px;
        }

        .no-groups {
            color: #dc3232;
            font-style: italic;
            margin: 0;
        }

        #mlds_send_notification {
            margin-top: 10px;
            width: 100%;
        }

        .spinner.is-active {
            visibility: visible;
        }
    </style>

    <script>
        jQuery(document).ready(function ($) {
            $('#mlds_send_notification').click(function () {
                const button = $(this);
                const spinner = button.next('.spinner');
                const groups = [];

                $('input[name="mlds_notification_groups[]"]:checked').each(function () {
                    groups.push($(this).val());
                });

                if (groups.length === 0) {
                    alert('<?php _e(
                    	'Please select at least one subscriber group.',
                    	'music-label-demo-sender',
                    ); ?>');
                    return;
                }

                button.prop('disabled', true);
                spinner.addClass('is-active');

                $.ajax({
                    url: mldsNotification.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'mlds_send_post_notification',
                        post_id: '<?php echo $post->ID; ?>',
                        groups: groups,
                        nonce: '<?php echo wp_create_nonce('mlds_send_notification'); ?>'
                    },
                    success: function (response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert(response.data.message || '<?php _e(
                            	'Error sending notification.',
                            	'music-label-demo-sender',
                            ); ?>');
                            button.prop('disabled', false);
                            spinner.removeClass('is-active');
                        }
                    },
                    error: function () {
                        alert('<?php _e(
                        	'Error sending notification.',
                        	'music-label-demo-sender',
                        ); ?>');
                        button.prop('disabled', false);
                        spinner.removeClass('is-active');
                    }
                });
            });
        });
    </script>
    <?php
}

// AJAX handler for sending post notification
function mlds_send_post_notification_callback() {
	check_ajax_referer('mlds_send_notification', 'nonce');

	$post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
	$groups =
		isset($_POST['groups']) && is_array($_POST['groups'])
			? array_map('sanitize_text_field', $_POST['groups'])
			: [];

	if (!$post_id || empty($groups)) {
		wp_send_json_error([
			'message' => __('Missing post ID or recipient groups.', 'music-label-demo-sender'),
		]);
	}

	if (!current_user_can('edit_post', $post_id)) {
		wp_send_json_error(['message' => __('Permission denied.', 'music-label-demo-sender')]);
	}

	// Assuming mlds_send_demo_emails is the function that sends emails
	// This function is in 'includes/file-processing.php'
	// It might need adjustments if it expects an attachment ID directly instead of post ID
	// For now, let's assume we can get the attachment ID from the post ID if needed
	$attachment_id = get_post_meta($post_id, '_mlds_attachment_id', true);
	if (!$attachment_id) {
		// If not a demo track with a specific attachment, maybe it's a general post notification
		// Adjust logic as needed. For now, we proceed as if it might be a general post.
		// Or, strictly require an attachment ID if this metabox is only for demo tracks.
	}

	// If mlds_send_demo_emails is designed for tracks with attachments, and this is a general post,
	// a different email sending function might be needed.
	// This is a placeholder for the actual email sending logic.

	$emails_sent_count = 0;
	if (function_exists('mlds_send_demo_emails')) {
		// We need to ensure mlds_send_demo_emails can be called with $post_id or derive $attachment_id correctly
		// And that it handles the case where it might be a general post notification
		// The original mlds_send_demo_emails($attachment_id, $recipient_groups) expects an attachment_id.
		// If this metabox can be on posts that are not "demo tracks" or don't have a direct attachment_id for this purpose,
		// this will need careful handling.
		// For now, we'll assume $attachment_id is what mlds_send_demo_emails needs if available.
		if ($attachment_id) {
			$emails_sent_count = mlds_send_demo_emails($attachment_id, $groups);
		} else {
			// Potentially handle sending notifications for posts without a direct demo attachment
			// This part would require a new or modified email function
			// For now, we'll simulate success if no attachment but groups are present
			// $emails_sent_count = count_subscribers_in_groups($groups); // Placeholder
			wp_send_json_error([
				'message' => __(
					'This post does not have an associated demo track attachment ID.',
					'music-label-demo-sender',
				),
			]);
			return;
		}
	} else {
		wp_send_json_error([
			'message' => __('Email sending function not found.', 'music-label-demo-sender'),
		]);
		return;
	}

	if ($emails_sent_count > 0) {
		update_post_meta($post_id, '_mlds_notification_sent', current_time('mysql'));
		wp_send_json_success([
			'message' => sprintf(
				__('Notification sent to %d recipients.', 'music-label-demo-sender'),
				$emails_sent_count,
			),
		]);
	} else {
		wp_send_json_error([
			'message' => __(
				'No emails were sent. Are there subscribers in the selected groups?',
				'music-label-demo-sender',
			),
		]);
	}
}
add_action('wp_ajax_mlds_send_post_notification', 'mlds_send_post_notification_callback');

// Add background email processing function / notification status check
function mlds_check_notification_status_callback() {
	check_ajax_referer('mlds_notification_status_nonce', 'nonce'); // Assuming a nonce for this action

	$post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
	if (!$post_id || !current_user_can('edit_post', $post_id)) {
		wp_send_json_error([
			'message' => __(
				'Invalid request or insufficient permissions.',
				'music-label-demo-sender',
			),
		]);
	}

	$status = get_post_meta($post_id, '_mlds_notification_status', true);
	$details = get_post_meta($post_id, '_mlds_notification_details', true);

	wp_send_json_success([
		'status' => $status ? $status : 'pending',
		'details' => $details ? $details : [],
	]);
}
add_action('wp_ajax_mlds_check_notification_status', 'mlds_check_notification_status_callback');
