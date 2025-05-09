<?php
// Prevent direct access to this file
if (!defined('ABSPATH')) {
	exit();
}

// Send demo track emails
function mlds_send_demo_emails($attachment_id, $recipient_groups) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'mlds_subscribers';

	// Get all subscribers from selected groups
	$placeholders = array_fill(0, count($recipient_groups), '%s');
	$query = $wpdb->prepare(
		"SELECT DISTINCT email, name FROM $table_name WHERE group_name IN (" .
			implode(',', $placeholders) .
			')',
		$recipient_groups,
	);
	$subscribers = $wpdb->get_results($query);

	if (empty($subscribers)) {
		return 0;
	}

	$track_title = get_the_title($attachment_id);
	$track_url = wp_get_attachment_url($attachment_id);
	$token = get_post_meta($attachment_id, '_mlds_track_token', true);

	// Create feedback URL
	$feedback_url = add_query_arg(
		[
			'track' => $attachment_id,
			'token' => $token,
		],
		'https://dnbdoctor.com/demo-feedback/',
	);

	// Prepare email content
	$subject = sprintf(__('🎵 New Demo Track: %s', 'music-label-demo-sender'), $track_title);

	// Email template
	ob_start();
	?>
    <!DOCTYPE html>
    <html>

    <head>
        <style>
            :root {
                --toxic-green: #39FF14;
                --toxic-purple: #9932CC;
                --dark-bg: #1a1a1a;
                --light-text: #ffffff;
            }

            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                margin: 0;
                padding: 0;
                background-color: var(--dark-bg);
                color: var(--light-text);
            }

            .container {
                max-width: 600px;
                margin: 0 auto;
                padding: 40px;
                background: rgba(26, 26, 26, 0.95);
                border-radius: 15px;
                box-shadow: 0 0 20px var(--toxic-green),
                0 0 40px var(--toxic-purple);
            }

            .logo {
                text-align: center;
                margin-bottom: 30px;
            }

            .logo img {
                max-width: 200px;
                height: auto;
            }

            .alert-badge {
                background: var(--toxic-purple);
                color: var(--light-text);
                padding: 8px 16px;
                border-radius: 20px;
                display: inline-block;
                margin-bottom: 20px;
                font-weight: bold;
                text-transform: uppercase;
                letter-spacing: 1px;
                font-size: 14px;
                box-shadow: 0 0 10px var(--toxic-purple);
            }
 
            .header {
                text-align: center;
                margin-bottom: 30px;
            }

            .header h1 {
                color: var(--toxic-green);
                font-size: 32px;
                margin: 20px 0;
                text-shadow: 0 0 10px var(--toxic-green);
            }

            .content {
                margin-bottom: 30px;
                font-size: 16px;
                line-height: 1.8;
                color: var(--light-text);
            }

            .cta-button {
                display: inline-block;
                padding: 15px 30px;
                background: linear-gradient(45deg, var(--toxic-purple), var(--toxic-green));
                color: var(--light-text);
                text-decoration: none;
                border-radius: 25px;
                font-weight: bold;
                text-transform: uppercase;
                letter-spacing: 1px;
                margin-top: 20px;
                transition: all 0.3s ease;
                box-shadow: 0 0 15px rgba(57, 255, 20, 0.5);
            }

            .cta-button:hover {
                transform: translateY(-2px);
                box-shadow: 0 0 25px rgba(153, 50, 204, 0.7);
            }

            .footer {
                margin-top: 40px;
                padding-top: 20px;
                border-top: 1px solid rgba(153, 50, 204, 0.3);
                font-size: 12px;
                color: rgba(255, 255, 255, 0.7);
                text-align: center;
            }

            .social-links {
                margin-top: 20px;
                text-align: center;
            }

            .social-links a {
                color: var(--toxic-green);
                text-decoration: none;
                margin: 0 10px;
                font-size: 14px;
            }

            .social-links a:hover {
                color: var(--toxic-purple);
            }
        </style>
    </head>

    <body>
    <div class="container">
        <div class="logo">
            <img src="https://admin.dnbdoctor.com/wp-content/uploads/2023/12/Artboard-66-300x243.png"
                 alt="DNB Doctor Logo">
        </div>

        <div class="header">
            <span class="alert-badge">🎵 New Demo Track 🎵</span>
            <h1><?php echo esc_html($track_title); ?></h1>
        </div>

        <div class="content">
            <?php if (!empty($subscriber->name)): ?>
                <p>Hey <?php echo esc_html($subscriber->name); ?>! 🎧</p>
            <?php endif; ?>

            <p>We' re excited to share a fresh demo track with you! We'd love to hear your feedback on this one.</p>

            <p>Click the button below to listen to the track and share your thoughts. Your feedback is invaluable to us!
            </p>
        </div>

        <div style="text-align: center;">
            <a href="<?php echo esc_url($feedback_url); ?>" class="cta-button">
                <?php _e('Listen & Give Feedback →', 'music-label-demo-sender'); ?>
            </a>
        </div>

        <div class="footer">
            <p><?php _e(
            	'You\'re receiving this because you\'re part of the DNB Doctor family. We appreciate you!',
            	'music-label-demo-sender',
            ); ?>
            </p>

            <div class="social-links">
                <a href="https://facebook.com/dnbdoctor">Facebook</a> |
                <a href="https://instagram.com/dnbdoctor">Instagram</a> |
                <a href="https://twitter.com/dnbdoctor">Twitter</a>
            </div>

            <div class="unsubscribe-footer">
                <?php
                // Generate unsubscribe URL with token
                $unsubscribe_token = wp_create_nonce('unsubscribe_' . $subscriber->email);
                $unsubscribe_url = add_query_arg(
                	[
                		'email' => urlencode($subscriber->email),
                		'token' => $unsubscribe_token,
                	],
                	'https://dnbdoctor.com/unsub',
                );
                ?>
                <p class="unsubscribe-text">
                    <?php _e('Don\'t want to receive these emails?', 'music-label-demo-sender'); ?>
                    <a
                            href="https://dnbdoctor.com/unsub"><?php _e(
                            	'Unsubscribe here',
                            	'music-label-demo-sender',
                            ); ?></a>
                </p>
            </div>
        </div>
    </div>
    <?php echo mlds_add_tracking_pixel($attachment_id); ?>
    </body>

    </html>
    <?php
    $message = ob_get_clean();

    // Email headers
    $headers = [
    	'Content-Type: text/html; charset=UTF-8',
    	'From: DNB Doctor <' . get_bloginfo('admin_email') . '>',
    ];

    // Send emails to each subscriber
    $emails_sent = 0;

    foreach ($subscribers as $subscriber) {
    	// Personalize message if name is available
    	$personalized_message = $message;
    	if (!empty($subscriber->name)) {
    		$personalized_message = str_replace(
    			"We're excited to share",
    			'Hey ' . esc_html($subscriber->name) . "! We're excited to share",
    			$message,
    		);
    	}

    	if (wp_mail($subscriber->email, $subject, $personalized_message, $headers)) {
    		$emails_sent++;

    		// Log email sending
    		add_post_meta($attachment_id, '_mlds_email_sent', [
    			'email' => $subscriber->email,
    			'name' => $subscriber->name,
    			'date' => current_time('mysql'),
    			'status' => 'sent',
    		]);
    	}
    }

    return $emails_sent;
}

// Modify file upload handler
function mlds_handle_upload() {
	if (
		!isset($_POST['mlds_upload_nonce']) ||
		!wp_verify_nonce($_POST['mlds_upload_nonce'], 'mlds_upload')
	) {
		return;
	}

	if (!current_user_can('manage_options')) {
		return;
	}

	// Check if files were uploaded
	if (empty($_FILES['mlds_track_file']['name'][0])) {
		return;
	}

	// Check if groups were selected
	if (empty($_POST['mlds_recipient_groups'])) {
		set_transient(
			'mlds_admin_notice',
			[
				'type' => 'error',
				'message' => __(
					'Please select at least one subscriber group.',
					'music-label-demo-sender',
				),
			],
			45,
		);
		return;
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$uploaded_files = [];
	$recipient_groups = array_map('sanitize_text_field', $_POST['mlds_recipient_groups']);
	$total_emails_sent = 0;

	foreach ($_FILES['mlds_track_file']['name'] as $key => $value) {
		if ($_FILES['mlds_track_file']['error'][$key] === 0) {
			$file = [
				'name' => $_FILES['mlds_track_file']['name'][$key],
				'type' => $_FILES['mlds_track_file']['type'][$key],
				'tmp_name' => $_FILES['mlds_track_file']['tmp_name'][$key],
				'error' => $_FILES['mlds_track_file']['error'][$key],
				'size' => $_FILES['mlds_track_file']['size'][$key],
			];

			$_FILES['upload_file'] = $file;

			// Upload the file and get the attachment ID
			$attachment_id = media_handle_upload('upload_file', 0);

			if (!is_wp_error($attachment_id)) {
				$track_title = sanitize_text_field(pathinfo($file['name'], PATHINFO_FILENAME));

				// Generate unique token for tracking and feedback
				$token = wp_generate_password(32, false);

				// Store metadata with the attachment
				update_post_meta($attachment_id, '_mlds_track_token', $token);
				update_post_meta($attachment_id, '_mlds_recipient_groups', $recipient_groups);
				update_post_meta($attachment_id, '_mlds_upload_date', current_time('mysql'));

				// Send emails for this track
				$emails_sent = mlds_send_demo_emails($attachment_id, $recipient_groups);
				$total_emails_sent += $emails_sent;

				$uploaded_files[] = $track_title;
			}
		}
	}

	// After successful upload and email sending
	if (!empty($uploaded_files)) {
		set_transient(
			'mlds_admin_notice',
			[
				'type' => 'success',
				'message' => sprintf(
					__(
						'Successfully uploaded %d tracks and sent %d emails to selected groups.',
						'music-label-demo-sender',
					),
					count($uploaded_files),
					$total_emails_sent,
				),
			],
			45,
		);
	}
}
add_action('admin_init', 'mlds_handle_upload');
