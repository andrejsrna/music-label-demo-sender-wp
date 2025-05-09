<?php
// Prevent direct access to this file
if (!defined('ABSPATH')) {
	exit();
}

// Create subscribers table on plugin activation (function definition)
function mlds_create_subscribers_table() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'mlds_subscribers';

	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        email varchar(100) NOT NULL,
        name varchar(100),
        group_name varchar(100),
        date_added datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY email (email),
        KEY group_name (group_name),
        KEY date_added (date_added)
    ) $charset_collate;";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta($sql);

	// Add some sample data if table is empty
	$count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
	if ($count == 0) {
		$sample_data = [
			[
				'email' => 'john@example.com',
				'name' => 'John Doe',
				'group_name' => 'Demo Group 1',
				'date_added' => current_time('mysql'),
			],
			[
				'email' => 'jane@example.com',
				'name' => 'Jane Smith',
				'group_name' => 'Demo Group 1',
				'date_added' => current_time('mysql'),
			],
			[
				'email' => 'bob@example.com',
				'name' => 'Bob Wilson',
				'group_name' => 'Demo Group 2',
				'date_added' => current_time('mysql'),
			],
		];

		foreach ($sample_data as $data) {
			$wpdb->insert($table_name, $data, ['%s', '%s', '%s', '%s']);
		}
	}
}

// Add function to check and create table if it doesn't exist
function mlds_check_db_table() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'mlds_subscribers';

	if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
		mlds_create_subscribers_table();
	}
}
add_action('admin_init', 'mlds_check_db_table');
?> 