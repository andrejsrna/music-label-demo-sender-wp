<?php
// Prevent direct access to this file
if (!defined('ABSPATH')) {
	exit();
}

// Send demo track emails
function mlds_send_demo_emails($attachment_ids, $recipient_groups, $custom_subject = null) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'mlds_subscribers';

	if (empty($attachment_ids)) {
		return 0;
	}

	$primary_attachment_id = $attachment_ids[0]; // Use the first track for the main link & pixel

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

	// Prepare email content
	$subject = sprintf(
		__('%d New Demo Tracks from DNB Doctor', 'music-label-demo-sender'),
		count($attachment_ids),
	);
	if (!empty($custom_subject)) {
		$subject = $custom_subject;
	}

	// Generate the single feedback URL using the primary attachment ID and its token
	$primary_track_token = get_post_meta($primary_attachment_id, '_mlds_track_token', true);
	$single_feedback_url = add_query_arg(
		[
			'track' => $primary_attachment_id, // This ID will represent the batch
			'token' => $primary_track_token,
		],
		get_option('mlds_feedback_page_url', home_url('/demo-feedback/')),
	);

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
                box-shadow: 0 0 20px var(--toxic-green), 0 0 40px var(--toxic-purple);
            }
            .logo img {
                max-width: 200px;
                height: auto;
            }
            .logo, .header, .track-list-header, .footer, .social-links {
                text-align: center;
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
            .track-list {
                list-style-type: none;
                padding-left: 0;
                margin-bottom: 30px;
            }
            .track-list li {
                background-color: rgba(255, 255, 255, 0.05);
                padding: 10px 15px;
                border-radius: 8px;
                margin-bottom: 8px;
                color: var(--light-text); 
                font-size: 16px;
                text-align: left;
            }
            .track-list-header h2 {
                color: var(--toxic-green);
                font-size: 22px;
                margin-bottom: 15px; 
            }
            .cta-button {
                display: inline-block;
                padding: 15px 30px;
                background: linear-gradient(45deg, var(--toxic-purple), var(--toxic-green));
                color: var(--light-text) !important; /* Ensure text color */
                text-decoration: none;
                border-radius: 25px;
                font-weight: bold;
                text-transform: uppercase;
                letter-spacing: 1px;
                margin-top: 20px; /* Give some space from track list */
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
            <img src="https://admin.dnbdoctor.com/wp-content/uploads/2023/12/Artboard-66-300x243.png" alt="DNB Doctor Logo">
        </div>
        <div class="header">
            <span class="alert-badge">🎵 New Demo Tracks! 🎵</span>
            <h1><?php printf(
            	esc_html__('You\'ve Received %d New Demos', 'music-label-demo-sender'),
            	count($attachment_ids),
            ); ?></h1>
        </div>
        <div class="content">
            <div class="greeting">%%GREETING%%</div>
            <p>We're excited to share some fresh demo tracks with you! Here are the tracks included:</p>
            
            <div class="track-list-header">
                 <h2><?php esc_html_e('Included Tracks:', 'music-label-demo-sender'); ?></h2>
            </div>
            <ul class="track-list">
                <?php foreach ($attachment_ids as $current_attachment_id): ?>
                    <?php $track_title = get_the_title($current_attachment_id); ?>
                    <li><?php echo esc_html($track_title); ?></li>
                <?php endforeach; ?>
            </ul>
            <p>Your feedback on this batch is invaluable to us!</p>
            <div style="text-align: center;">
                <a href="<?php echo esc_url($single_feedback_url); ?>" class="cta-button">
                    <?php esc_html_e(
                    	'Listen & Give Feedback on Batch →',
                    	'music-label-demo-sender',
                    ); ?>
                </a>
            </div>
        </div>
        <div class="footer">
            <p><?php _e(
            	'You\'re receiving this because you\'re part of the DNB Doctor family. We appreciate you!',
            	'music-label-demo-sender',
            ); ?></p>
            <div class="social-links">
                <a href="https://facebook.com/dnbdoctor">Facebook</a> | 
                <a href="https://instagram.com/dnbdoctor">Instagram</a> | 
                <a href="https://twitter.com/dnbdoctor">Twitter</a>
            </div>
            <div class="unsubscribe-footer">
                <?php
                $unsubscribe_token = wp_create_nonce('unsubscribe_' . $subscriber->email);
                $unsubscribe_url = add_query_arg(
                	[
                		'email' => urlencode($subscriber->email),
                		'token' => $unsubscribe_token,
                	],
                	get_option('mlds_unsubscribe_page_url', home_url('/unsub/')),
                );
                ?>
                <p class="unsubscribe-text">
                    <?php _e('Don\'t want to receive these emails?', 'music-label-demo-sender'); ?>
                    <a href="<?php echo esc_url(
                    	$unsubscribe_url,
                    ); ?>"><?php _e('Unsubscribe here', 'music-label-demo-sender'); ?></a>
                </p>
            </div>
        </div>
    </div>
    <?php if (!empty($primary_attachment_id)) {
    	echo mlds_add_tracking_pixel($primary_attachment_id);
    } ?>
    </body>
    </html>
    <?php
    $message_template = ob_get_clean();
    $headers = [
    	'Content-Type: text/html; charset=UTF-8',
    	'From: DNB Doctor <' . get_bloginfo('admin_email') . '>',
    ];
    $emails_sent_count = 0;
    foreach ($subscribers as $subscriber) {
    	$personalized_message = $message_template;
    	$greeting_text = !empty($subscriber->name)
    		? '<p>Hey ' . esc_html($subscriber->name) . '! 🎧</p>'
    		: '<p>Hey there! 🎧</p>'; // Greeting paragraph
    	// More robust greeting section replacement including surrounding divs if needed for styling.
    	$greeting_html =
    		'<div class="greeting-content"><p>' .
    		(!empty($subscriber->name) ? 'Hey ' . esc_html($subscriber->name) : 'Hey there') .
    		'! 🎧</p></div>';
    	$personalized_message = str_replace(
    		'<div class="greeting">%%GREETING%%</div>',
    		$greeting_html,
    		$personalized_message,
    	);

    	if (wp_mail($subscriber->email, $subject, $personalized_message, $headers)) {
    		$emails_sent_count++;
    		foreach ($attachment_ids as $current_attachment_id_for_log) {
    			add_post_meta($current_attachment_id_for_log, '_mlds_email_sent', [
    				'email' => $subscriber->email,
    				'name' => $subscriber->name,
    				'date' => current_time('mysql'),
    				'status' => 'sent',
    			]);
    		}
    	}
    }
    return $emails_sent_count;
}

function mlds_handle_upload() {
	if (
		!isset($_POST['mlds_upload_nonce']) ||
		!wp_verify_nonce($_POST['mlds_upload_nonce'], 'mlds_upload')
	) {
		if (isset($_POST['mlds_upload_nonce'])) {
			set_transient(
				'mlds_admin_notice',
				[
					'type' => 'error',
					'message' => __('Security check failed.', 'music-label-demo-sender'),
				],
				45,
			);
		}
		return;
	}

	if (!current_user_can('manage_options')) {
		set_transient(
			'mlds_admin_notice',
			[
				'type' => 'error',
				'message' => __(
					'You do not have permission to upload files.',
					'music-label-demo-sender',
				),
			],
			45,
		);
		return;
	}

	if (empty($_FILES['mlds_track_file']['name']) || empty($_FILES['mlds_track_file']['name'][0])) {
		set_transient(
			'mlds_admin_notice',
			[
				'type' => 'warning',
				'message' => __(
					'Please select at least one file to upload.',
					'music-label-demo-sender',
				),
			],
			45,
		);
		return;
	}

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

	$all_attachment_ids = [];
	$uploaded_track_titles = [];
	$failed_uploads = [];
	$recipient_groups = array_map('sanitize_text_field', $_POST['mlds_recipient_groups']);
	$custom_email_subject = isset($_POST['mlds_custom_email_subject'])
		? sanitize_text_field(wp_unslash($_POST['mlds_custom_email_subject']))
		: null;

	// Process newly uploaded files from $_FILES['mlds_track_file']
	if (
		!empty($_FILES['mlds_track_file']['name']) &&
		is_array($_FILES['mlds_track_file']['name'])
	) {
		foreach ($_FILES['mlds_track_file']['name'] as $key => $value) {
			if (
				!empty($_FILES['mlds_track_file']['name'][$key]) &&
				$_FILES['mlds_track_file']['error'][$key] === UPLOAD_ERR_OK
			) {
				$file = [
					'name' => sanitize_file_name($_FILES['mlds_track_file']['name'][$key]),
					'type' => $_FILES['mlds_track_file']['type'][$key],
					'tmp_name' => $_FILES['mlds_track_file']['tmp_name'][$key],
					'error' => $_FILES['mlds_track_file']['error'][$key],
					'size' => $_FILES['mlds_track_file']['size'][$key],
				];
				$temp_upload_key = 'mlds_temp_upload_file_' . $key;
				$_FILES[$temp_upload_key] = $file;
				$attachment_id = media_handle_upload($temp_upload_key, 0);
				unset($_FILES[$temp_upload_key]);

				if (!is_wp_error($attachment_id)) {
					$all_attachment_ids[] = $attachment_id;
					$uploaded_track_titles[] = sanitize_text_field(
						pathinfo($file['name'], PATHINFO_FILENAME),
					);
					$token = wp_generate_password(32, false);
					update_post_meta($attachment_id, '_mlds_track_token', $token);
					update_post_meta($attachment_id, '_mlds_recipient_groups', $recipient_groups);
					update_post_meta($attachment_id, '_mlds_upload_date', current_time('mysql'));
				} else {
					$failed_uploads[] =
						$file['name'] . ' (' . $attachment_id->get_error_message() . ')';
				}
			} elseif (!empty($_FILES['mlds_track_file']['name'][$key])) {
				$error_message = 'Unknown upload error';
				if (isset($_FILES['mlds_track_file']['error'][$key])) {
					switch ($_FILES['mlds_track_file']['error'][$key]) {
						case UPLOAD_ERR_INI_SIZE:
							$error_message =
								'The uploaded file exceeds the upload_max_filesize directive in php.ini.';
							break;
						case UPLOAD_ERR_FORM_SIZE:
							$error_message =
								'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.';
							break;
						case UPLOAD_ERR_PARTIAL:
							$error_message = 'The uploaded file was only partially uploaded.';
							break;
						case UPLOAD_ERR_NO_FILE:
							$error_message = 'No file was uploaded.';
							break;
						case UPLOAD_ERR_NO_TMP_DIR:
							$error_message = 'Missing a temporary folder.';
							break;
						case UPLOAD_ERR_CANT_WRITE:
							$error_message = 'Failed to write file to disk.';
							break;
						case UPLOAD_ERR_EXTENSION:
							$error_message = 'A PHP extension stopped the file upload.';
							break;
					}
				}
				$failed_uploads[] =
					sanitize_file_name($_FILES['mlds_track_file']['name'][$key]) .
					' (' .
					$error_message .
					')';
			}
		}
	}

	// Process tracks selected from Media Library
	if (
		!empty($_POST['mlds_media_library_tracks']) &&
		is_array($_POST['mlds_media_library_tracks'])
	) {
		$media_library_track_ids = array_map('absint', $_POST['mlds_media_library_tracks']);
		foreach ($media_library_track_ids as $media_attachment_id) {
			if (
				$media_attachment_id > 0 &&
				get_post_status($media_attachment_id) &&
				strpos(get_post_mime_type($media_attachment_id), 'audio') !== false
			) {
				if (!in_array($media_attachment_id, $all_attachment_ids)) {
					// Avoid duplicates if somehow selected and uploaded
					$all_attachment_ids[] = $media_attachment_id;
					$track_title = get_the_title($media_attachment_id);
					if ($track_title) {
						$uploaded_track_titles[] = $track_title; // Add its title for the notice
					}
					// Ensure token and necessary meta exists for media library tracks as well
					if (!get_post_meta($media_attachment_id, '_mlds_track_token', true)) {
						$token = wp_generate_password(32, false);
						update_post_meta($media_attachment_id, '_mlds_track_token', $token);
					}
					update_post_meta(
						$media_attachment_id,
						'_mlds_recipient_groups',
						$recipient_groups,
					);
					update_post_meta(
						$media_attachment_id,
						'_mlds_upload_date',
						current_time('mysql'),
					); // Update upload date to now
				}
			} else {
				$failed_uploads[] = sprintf(
					__('Invalid Media Library item ID: %d', 'music-label-demo-sender'),
					$media_attachment_id,
				);
			}
		}
	}

	// If tracks were processed, associate them as a batch
	if (!empty($all_attachment_ids)) {
		$primary_attachment_id_for_batch = $all_attachment_ids[0];
		// Store all track IDs of this batch as meta on the primary track
		update_post_meta(
			$primary_attachment_id_for_batch,
			'_mlds_batch_track_ids',
			$all_attachment_ids,
		);
	}

	$total_emails_sent = 0;
	if (!empty($all_attachment_ids)) {
		$total_emails_sent = mlds_send_demo_emails(
			$all_attachment_ids,
			$recipient_groups,
			$custom_email_subject,
		);
	}

	if (!empty($uploaded_track_titles) || !empty($failed_uploads)) {
		$success_message = '';
		$error_message_parts = [];
		if (!empty($uploaded_track_titles)) {
			$success_message = sprintf(
				__(
					'Successfully uploaded %d track(s) (%s) and sent %d email(s) to selected groups.',
					'music-label-demo-sender',
				),
				count($uploaded_track_titles),
				implode(', ', $uploaded_track_titles),
				$total_emails_sent,
			);
		}
		if (!empty($failed_uploads)) {
			$error_message_parts[] = sprintf(
				__('Failed to upload %d track(s): %s.', 'music-label-demo-sender'),
				count($failed_uploads),
				implode(', ', $failed_uploads),
			);
		}
		$notice_type = 'info';
		if (!empty($uploaded_track_titles) && empty($failed_uploads)) {
			$notice_type = 'success';
		} elseif (empty($uploaded_track_titles) && !empty($failed_uploads)) {
			$notice_type = 'error';
		} elseif (!empty($uploaded_track_titles) && !empty($failed_uploads)) {
			$notice_type = 'warning';
		}
		set_transient(
			'mlds_admin_notice',
			[
				'type' => $notice_type,
				'message' => trim($success_message . ' ' . implode(' ', $error_message_parts)),
			],
			45,
		);
	} elseif (empty($all_attachment_ids) && empty($failed_uploads)) {
		set_transient(
			'mlds_admin_notice',
			[
				'type' => 'warning',
				'message' => __('No valid files were processed.', 'music-label-demo-sender'),
			],
			45,
		);
	}
}
add_action('admin_init', 'mlds_handle_upload');
