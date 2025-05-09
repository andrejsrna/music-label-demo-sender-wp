<?php
// Prevent direct access to this file
if (!defined('ABSPATH')) {
	exit();
}

// Add scripts for charts on the stats page
function mlds_admin_scripts_charts($hook) {
	// Only load on the specific stats page
	// The hook for a top-level page is 'toplevel_page_{menu_slug}'
	// The hook for a submenu page is '{parent_slug}_page_{submenu_slug}' or '{menu_title}_page_{submenu_slug}'
	// Based on admin/menus.php, the stats page slug is 'mlds-stats' and parent is 'mlds-dashboard'
	if ('mlds-dashboard_page_mlds-stats' !== $hook && 'demo-sender_page_mlds-stats' !== $hook) {
		// Checking both possible hook name patterns
		return;
	}

	// Enqueue Chart.js
	wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', [], '3.7.0', true);

	// Enqueue our custom script for charts
	// Ensure the path to admin-charts.js is correct if it also moves or is specific to this plugin's structure
	wp_enqueue_script(
		'mlds-admin-charts',
		plugin_dir_url(__FILE__) . '../js/admin-charts.js', // Adjusted path assuming js folder is at the plugin root
		['chartjs'],
		'1.0.0',
		true,
	);

	// Pass data to JavaScript
	// Ensure mlds_get_cached_stats() is available (it should be in includes/stats-helpers.php)
	if (function_exists('mlds_get_cached_stats')) {
		wp_localize_script('mlds-admin-charts', 'mldsChartData', [
			'stats' => mlds_get_cached_stats(),
		]);
	}
}
add_action('admin_enqueue_scripts', 'mlds_admin_scripts_charts');

// Enqueue necessary scripts and styles for the subscriber management page
function mlds_admin_subscriber_page_scripts($hook) {
	// Only load on the specific subscriber management page
	// Based on admin/menus.php, the subscriber page slug is 'mlds-subscribers' and parent is 'mlds-dashboard'
	if (
		'mlds-dashboard_page_mlds-subscribers' !== $hook &&
		'demo-sender_page_mlds-subscribers' !== $hook
	) {
		// Checking both possible hook name patterns
		return;
	}

	wp_enqueue_script('jquery-ui-dialog');
	wp_enqueue_style('wp-jquery-ui-dialog');

	// Ensure the path to subscriber-management.js is correct
	wp_enqueue_script(
		'mlds-subscriber-management',
		plugin_dir_url(__FILE__) . '../js/subscriber-management.js', // Adjusted path
		['jquery', 'jquery-ui-dialog'],
		'1.0.0',
		true,
	);

	wp_localize_script('mlds-subscriber-management', 'mldsAdmin', [
		'ajaxurl' => admin_url('admin-ajax.php'),
		'nonce' => wp_create_nonce('mlds_subscriber_action'), // Ensure this nonce matches the one checked in AJAX handlers
		'confirmDelete' => __(
			'Are you sure you want to delete this subscriber?',
			'music-label-demo-sender',
		),
		'confirmBulkDelete' => __(
			'Are you sure you want to delete the selected subscribers?',
			'music-label-demo-sender',
		),
	]);
}
add_action('admin_enqueue_scripts', 'mlds_admin_subscriber_page_scripts');
?> 