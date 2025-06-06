<?php
// Prevent direct access to this file
if (!defined('ABSPATH')) {
	exit();
}

/**
 * Subscriber Management Helper Functions
 */

// Get filtered subscribers with pagination
function mlds_get_filtered_subscribers(
	$group_filter = '',
	$search = '',
	$orderby = 'date_added',
	$order = 'DESC',
	$paged = 1,
	$per_page = 20,
) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'mlds_subscribers';

	$where_conditions = ['1=1'];
	$where_values = [];

	// Group filter
	if (!empty($group_filter)) {
		$where_conditions[] = 'group_name = %s';
		$where_values[] = $group_filter;
	}

	// Search filter
	if (!empty($search)) {
		$where_conditions[] = '(email LIKE %s OR name LIKE %s)';
		$search_term = '%' . $wpdb->esc_like($search) . '%';
		$where_values[] = $search_term;
		$where_values[] = $search_term;
	}

	// Build WHERE clause
	$where_clause = implode(' AND ', $where_conditions);

	// Validate orderby
	$allowed_orderby = ['email', 'name', 'group_name', 'date_added', 'id'];
	if (!in_array($orderby, $allowed_orderby)) {
		$orderby = 'date_added';
	}

	// Validate order
	$order = strtoupper($order);
	if (!in_array($order, ['ASC', 'DESC'])) {
		$order = 'DESC';
	}

	// Calculate offset
	$offset = ($paged - 1) * $per_page;

	// Build query
	$query = "SELECT * FROM $table_name WHERE $where_clause ORDER BY $orderby $order LIMIT %d OFFSET %d";
	$where_values[] = $per_page;
	$where_values[] = $offset;

	// Prepare and execute query
	if (!empty($where_values)) {
		$prepared_query = $wpdb->prepare($query, $where_values);
	} else {
		$prepared_query = $query;
	}

	return $wpdb->get_results($prepared_query);
}

// Count filtered subscribers
function mlds_count_filtered_subscribers($group_filter = '', $search = '') {
	global $wpdb;
	$table_name = $wpdb->prefix . 'mlds_subscribers';

	$where_conditions = ['1=1'];
	$where_values = [];

	// Group filter
	if (!empty($group_filter)) {
		$where_conditions[] = 'group_name = %s';
		$where_values[] = $group_filter;
	}

	// Search filter
	if (!empty($search)) {
		$where_conditions[] = '(email LIKE %s OR name LIKE %s)';
		$search_term = '%' . $wpdb->esc_like($search) . '%';
		$where_values[] = $search_term;
		$where_values[] = $search_term;
	}

	// Build WHERE clause
	$where_clause = implode(' AND ', $where_conditions);

	// Build query
	$query = "SELECT COUNT(*) FROM $table_name WHERE $where_clause";

	// Prepare and execute query
	if (!empty($where_values)) {
		$prepared_query = $wpdb->prepare($query, $where_values);
	} else {
		$prepared_query = $query;
	}

	return intval($wpdb->get_var($prepared_query));
}

// Get subscriber groups with counts
function mlds_get_subscriber_groups() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'mlds_subscribers';

	$query = "SELECT group_name, COUNT(*) as subscriber_count, MIN(date_added) as date_added FROM $table_name GROUP BY group_name ORDER BY subscriber_count DESC";

	return $wpdb->get_results($query);
}

// Bulk delete subscribers
function mlds_bulk_delete_subscribers($subscriber_ids) {
	if (empty($subscriber_ids) || !is_array($subscriber_ids)) {
		return false;
	}

	global $wpdb;
	$table_name = $wpdb->prefix . 'mlds_subscribers';

	// Sanitize IDs
	$subscriber_ids = array_map('intval', $subscriber_ids);
	$ids_placeholder = implode(',', array_fill(0, count($subscriber_ids), '%d'));

	$query = "DELETE FROM $table_name WHERE id IN ($ids_placeholder)";
	$prepared_query = $wpdb->prepare($query, $subscriber_ids);

	$result = $wpdb->query($prepared_query);

	// Add admin notice
	if ($result !== false) {
		add_action('admin_notices', function () use ($result) {
			echo '<div class="notice notice-success is-dismissible"><p>' .
				sprintf(
					__('%d subscribers deleted successfully.', 'music-label-demo-sender'),
					$result,
				) .
				'</p></div>';
		});
	} else {
		add_action('admin_notices', function () {
			echo '<div class="notice notice-error is-dismissible"><p>' .
				__('Error deleting subscribers.', 'music-label-demo-sender') .
				'</p></div>';
		});
	}

	return $result;
}

// Bulk change subscriber group
function mlds_bulk_change_group($subscriber_ids, $new_group) {
	if (empty($subscriber_ids) || !is_array($subscriber_ids) || empty($new_group)) {
		return false;
	}

	global $wpdb;
	$table_name = $wpdb->prefix . 'mlds_subscribers';

	// Sanitize IDs
	$subscriber_ids = array_map('intval', $subscriber_ids);
	$new_group = sanitize_text_field($new_group);

	// Handle "new" group creation
	if ($new_group === 'new' && isset($_POST['new_group_name'])) {
		$new_group = sanitize_text_field($_POST['new_group_name']);
		if (empty($new_group)) {
			add_action('admin_notices', function () {
				echo '<div class="notice notice-error is-dismissible"><p>' .
					__('Please enter a new group name.', 'music-label-demo-sender') .
					'</p></div>';
			});
			return false;
		}
	}

	$ids_placeholder = implode(',', array_fill(0, count($subscriber_ids), '%d'));

	$query = "UPDATE $table_name SET group_name = %s WHERE id IN ($ids_placeholder)";
	$values = array_merge([$new_group], $subscriber_ids);
	$prepared_query = $wpdb->prepare($query, $values);

	$result = $wpdb->query($prepared_query);

	// Add admin notice
	if ($result !== false) {
		add_action('admin_notices', function () use ($result, $new_group) {
			echo '<div class="notice notice-success is-dismissible"><p>' .
				sprintf(
					__(
						'%d subscribers moved to group "%s" successfully.',
						'music-label-demo-sender',
					),
					$result,
					$new_group,
				) .
				'</p></div>';
		});
	} else {
		add_action('admin_notices', function () {
			echo '<div class="notice notice-error is-dismissible"><p>' .
				__('Error updating subscriber groups.', 'music-label-demo-sender') .
				'</p></div>';
		});
	}

	return $result;
}

// Get recent subscribers
function mlds_get_recent_subscribers($limit = 20) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'mlds_subscribers';

	return $wpdb->get_results(
		$wpdb->prepare("SELECT * FROM $table_name ORDER BY date_added DESC LIMIT %d", $limit),
	);
}

// Get subscriber statistics
function mlds_get_subscriber_stats() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'mlds_subscribers';

	$stats = [];

	// Total subscribers
	$stats['total'] = intval($wpdb->get_var("SELECT COUNT(*) FROM $table_name"));

	// Subscribers by group
	$stats['by_group'] = $wpdb->get_results(
		"SELECT group_name, COUNT(*) as count FROM $table_name GROUP BY group_name ORDER BY count DESC",
	);

	// Recent subscribers (last 30 days)
	$stats['recent'] = intval(
		$wpdb->get_var(
			"SELECT COUNT(*) FROM $table_name WHERE date_added >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
		),
	);

	return $stats;
}

/**
 * AJAX Handlers and Legacy Functions
 * (Keeping these for backward compatibility)
 */

// Handle manual subscriber addition
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

// Handle group deletion AJAX request
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
