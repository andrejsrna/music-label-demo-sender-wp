<?php
// Prevent direct access to this file
if (!defined('ABSPATH')) {
	exit();
}

// Handle CSV upload and import
function mlds_handle_csv_upload() {
	if (
		!isset($_POST['mlds_csv_nonce']) ||
		!wp_verify_nonce($_POST['mlds_csv_nonce'], 'mlds_csv_upload')
	) {
		wp_die(__('Security check failed', 'music-label-demo-sender'));
	}

	if (!current_user_can('manage_options')) {
		wp_die(__('Permission denied', 'music-label-demo-sender'));
	}

	$file = $_FILES['subscriber_csv'];
	$group_name = sanitize_text_field($_POST['group_name']);

	if ($file['error'] !== UPLOAD_ERR_OK) {
		add_settings_error(
			'mlds_messages',
			'mlds_error',
			__('Error uploading file', 'music-label-demo-sender'),
			'error',
		);
		return;
	}

	$handle = fopen($file['tmp_name'], 'r');
	if ($handle === false) {
		add_settings_error(
			'mlds_messages',
			'mlds_error',
			__('Error reading file', 'music-label-demo-sender'),
			'error',
		);
		return;
	}

	// Skip header row
	$header = fgetcsv($handle);

	global $wpdb;
	$table_name = $wpdb->prefix . 'mlds_subscribers';
	$imported = 0;
	$duplicates = 0;

	while (($data = fgetcsv($handle)) !== false) {
		$email = sanitize_email($data[0]);
		$name = isset($data[1]) ? sanitize_text_field($data[1]) : '';

		if (is_email($email)) {
			$result = $wpdb->insert(
				$table_name,
				[
					'email' => $email,
					'name' => $name,
					'group_name' => $group_name,
				],
				['%s', '%s', '%s'],
			);

			if ($result === false) {
				$duplicates++;
			} else {
				$imported++;
			}
		}
	}

	fclose($handle);

	add_settings_error(
		'mlds_messages',
		'mlds_success',
		sprintf(
			__(
				'Successfully imported %d subscribers (%d duplicates skipped)',
				'music-label-demo-sender',
			),
			$imported,
			$duplicates,
		),
		'success',
	);
}

// Get subscriber groups with counts
function mlds_get_subscriber_groups() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'mlds_subscribers';

	return $wpdb->get_results(
		"SELECT group_name,
                COUNT(*) as subscriber_count,
                MIN(date_added) as date_added
         FROM $table_name
         GROUP BY group_name
         ORDER BY date_added DESC",
	);
}

// Add handler for manual subscriber addition
function mlds_handle_manual_subscriber() {
	if (!isset($_POST['mlds_add_manual']) || !isset($_POST['mlds_manual_nonce'])) {
		return;
	}

	if (!wp_verify_nonce($_POST['mlds_manual_nonce'], 'mlds_manual_subscriber')) {
		wp_die(__('Security check failed', 'music-label-demo-sender'));
	}

	if (!current_user_can('manage_options')) {
		wp_die(__('Permission denied', 'music-label-demo-sender'));
	}

	$email = sanitize_email($_POST['subscriber_email']);
	$name = sanitize_text_field($_POST['subscriber_name']);
	$group_name =
		$_POST['group_name'] === 'new'
			? sanitize_text_field($_POST['new_group_name'])
			: sanitize_text_field($_POST['group_name']);

	if (!is_email($email)) {
		add_settings_error(
			'mlds_messages',
			'mlds_error',
			__('Invalid email address', 'music-label-demo-sender'),
			'error',
		);
		return;
	}

	global $wpdb;
	$table_name = $wpdb->prefix . 'mlds_subscribers';

	$result = $wpdb->insert(
		$table_name,
		[
			'email' => $email,
			'name' => $name,
			'group_name' => $group_name,
		],
		['%s', '%s', '%s'],
	);

	if ($result === false) {
		add_settings_error(
			'mlds_messages',
			'mlds_error',
			__('Email address already exists in the database', 'music-label-demo-sender'),
			'error',
		);
	} else {
		add_settings_error(
			'mlds_messages',
			'mlds_success',
			__('Subscriber added successfully', 'music-label-demo-sender'),
			'success',
		);
	}
}
add_action('admin_init', 'mlds_handle_manual_subscriber');

// Get recent subscribers
function mlds_get_recent_subscribers($limit = 20) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'mlds_subscribers';

	return $wpdb->get_results(
		$wpdb->prepare("SELECT * FROM $table_name ORDER BY date_added DESC LIMIT %d", $limit),
	);
}

// Handle subscriber update AJAX request
function mlds_update_subscriber_callback() {
	check_ajax_referer('mlds_subscriber_action', 'nonce');

	if (!current_user_can('manage_options')) {
		wp_send_json_error(['message' => __('Permission denied', 'music-label-demo-sender')]);
	}

	$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
	$email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
	$name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
	$group = isset($_POST['group']) ? sanitize_text_field($_POST['group']) : '';

	if (!$id || !is_email($email)) {
		wp_send_json_error(['message' => __('Invalid data provided', 'music-label-demo-sender')]);
	}

	global $wpdb;
	$table_name = $wpdb->prefix . 'mlds_subscribers';

	// Check if email already exists for different subscriber
	$existing = $wpdb->get_var(
		$wpdb->prepare("SELECT id FROM $table_name WHERE email = %s AND id != %d", $email, $id),
	);

	if ($existing) {
		wp_send_json_error([
			'message' => __('Email address already exists', 'music-label-demo-sender'),
		]);
	}

	$result = $wpdb->update(
		$table_name,
		[
			'email' => $email,
			'name' => $name,
			'group_name' => $group,
		],
		['id' => $id],
		['%s', '%s', '%s'],
		['%d'],
	);

	if ($result === false) {
		wp_send_json_error([
			'message' => __('Error updating subscriber', 'music-label-demo-sender'),
		]);
	}

	wp_send_json_success();
}
add_action('wp_ajax_mlds_update_subscriber', 'mlds_update_subscriber_callback');

// Handle subscriber delete AJAX request
function mlds_delete_subscriber_callback() {
	check_ajax_referer('mlds_subscriber_action', 'nonce');

	if (!current_user_can('manage_options')) {
		wp_send_json_error(['message' => __('Permission denied', 'music-label-demo-sender')]);
	}

	$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

	if (!$id) {
		wp_send_json_error(['message' => __('Invalid subscriber ID', 'music-label-demo-sender')]);
	}

	global $wpdb;
	$table_name = $wpdb->prefix . 'mlds_subscribers';

	$result = $wpdb->delete($table_name, ['id' => $id], ['%d']);

	if ($result === false) {
		wp_send_json_error([
			'message' => __('Error deleting subscriber', 'music-label-demo-sender'),
		]);
	}

	wp_send_json_success();
}
add_action('wp_ajax_mlds_delete_subscriber', 'mlds_delete_subscriber_callback');

// Bulk delete subscribers
function mlds_bulk_delete_subscribers($subscriber_ids) {
	if (!current_user_can('manage_options')) {
		return;
	}

	global $wpdb;
	$table_name = $wpdb->prefix . 'mlds_subscribers';

	$ids = implode(',', array_map('intval', $subscriber_ids));
	$wpdb->query("DELETE FROM $table_name WHERE id IN ($ids)");

	add_settings_error(
		'mlds_messages',
		'subscribers_deleted',
		sprintf(
			__('%d subscribers deleted successfully.', 'music-label-demo-sender'),
			count($subscriber_ids),
		),
		'success',
	);
}

// Bulk change group
function mlds_bulk_change_group($subscriber_ids, $new_group) {
	if (!current_user_can('manage_options')) {
		return;
	}

	global $wpdb;
	$table_name = $wpdb->prefix . 'mlds_subscribers';

	$ids = implode(',', array_map('intval', $subscriber_ids));
	$wpdb->query(
		$wpdb->prepare("UPDATE $table_name SET group_name = %s WHERE id IN ($ids)", $new_group),
	);

	add_settings_error(
		'mlds_messages',
		'group_updated',
		sprintf(
			__('Group updated for %d subscribers.', 'music-label-demo-sender'),
			count($subscriber_ids),
		),
		'success',
	);
}

// AJAX handler for subscriber operations
function mlds_handle_subscriber_ajax() {
	check_ajax_referer('mlds_subscriber_action', 'nonce');

	if (!current_user_can('manage_options')) {
		wp_send_json_error(['message' => __('Permission denied', 'music-label-demo-sender')]);
	}

	$action = isset($_POST['subscriber_action']) ? $_POST['subscriber_action'] : '';

	switch ($action) {
		case 'add':
		case 'update':
			$subscriber_id = isset($_POST['subscriber_id']) ? intval($_POST['subscriber_id']) : 0;
			$email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
			$name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
			$group = isset($_POST['group']) ? sanitize_text_field($_POST['group']) : '';

			if (!is_email($email)) {
				wp_send_json_error([
					'message' => __('Invalid email address', 'music-label-demo-sender'),
				]);
			}

			global $wpdb;
			$table_name = $wpdb->prefix . 'mlds_subscribers';

			// Check for duplicate email
			$existing = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM $table_name WHERE email = %s AND id != %d",
					$email,
					$subscriber_id,
				),
			);

			if ($existing) {
				wp_send_json_error([
					'message' => __('Email address already exists', 'music-label-demo-sender'),
				]);
			}

			$data = [
				'email' => $email,
				'name' => $name,
				'group_name' => $group,
			];

			if ($action === 'add') {
				$wpdb->insert($table_name, $data);
				$subscriber_id = $wpdb->insert_id;
			} else {
				$wpdb->update($table_name, $data, ['id' => $subscriber_id]);
			}

			wp_send_json_success([
				'id' => $subscriber_id,
				'message' => __('Subscriber saved successfully', 'music-label-demo-sender'),
			]);
			break;

		case 'delete':
			$subscriber_id = isset($_POST['subscriber_id']) ? intval($_POST['subscriber_id']) : 0;

			if (!$subscriber_id) {
				wp_send_json_error([
					'message' => __('Invalid subscriber ID', 'music-label-demo-sender'),
				]);
			}

			global $wpdb;
			$table_name = $wpdb->prefix . 'mlds_subscribers';

			$wpdb->delete($table_name, ['id' => $subscriber_id], ['%d']);

			wp_send_json_success([
				'message' => __('Subscriber deleted successfully', 'music-label-demo-sender'),
			]);
			break;

		case 'get':
			$subscriber_id = isset($_POST['subscriber_id']) ? intval($_POST['subscriber_id']) : 0;

			if (!$subscriber_id) {
				wp_send_json_error([
					'message' => __('Invalid subscriber ID', 'music-label-demo-sender'),
				]);
			}

			global $wpdb;
			$table_name = $wpdb->prefix . 'mlds_subscribers';

			$subscriber = $wpdb->get_row(
				$wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $subscriber_id),
			);

			if (!$subscriber) {
				wp_send_json_error([
					'message' => __('Subscriber not found', 'music-label-demo-sender'),
				]);
			}

			wp_send_json_success(['subscriber' => $subscriber]);
			break;

		default:
			wp_send_json_error(['message' => __('Invalid action', 'music-label-demo-sender')]);
	}
}
add_action('wp_ajax_mlds_subscriber_action', 'mlds_handle_subscriber_ajax');

// Add AJAX handler for group deletion
function mlds_delete_group_callback() {
	check_ajax_referer('mlds_group_action', 'nonce');

	if (!current_user_can('manage_options')) {
		wp_send_json_error(['message' => __('Permission denied', 'music-label-demo-sender')]);
	}

	$group_name = isset($_POST['group']) ? sanitize_text_field($_POST['group']) : '';

	if (empty($group_name)) {
		wp_send_json_error(['message' => __('Invalid group name', 'music-label-demo-sender')]);
	}

	global $wpdb;
	$table_name = $wpdb->prefix . 'mlds_subscribers';

	// Delete all subscribers in the group
	$result = $wpdb->delete($table_name, ['group_name' => $group_name], ['%s']);

	if ($result !== false) {
		wp_send_json_success([
			'message' => sprintf(
				__(
					'Group "%s" and its subscribers have been deleted successfully.',
					'music-label-demo-sender',
				),
				$group_name,
			),
		]);
	} else {
		wp_send_json_error(['message' => __('Error deleting group', 'music-label-demo-sender')]);
	}
}
add_action('wp_ajax_mlds_delete_group', 'mlds_delete_group_callback');
