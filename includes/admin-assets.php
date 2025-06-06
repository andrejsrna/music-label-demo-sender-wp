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

// Enqueue scripts for the main plugin dashboard page (track uploads, etc.)
function mlds_main_dashboard_scripts($hook) {
	// Determine the correct hook for your main dashboard page.
	// If your add_menu_page slug is 'mlds-dashboard', the hook is typically 'toplevel_page_mlds-dashboard'.
	// You can find the exact hook by adding: error_log('Current admin page hook: ' . $hook);
	// and then navigating to your dashboard page and checking the PHP error log.
	// Let's assume 'mlds-dashboard' is the slug for the main page where mlds_dashboard_page() is displayed.
	// Or, it might be something like 'music-label-demo-sender/music-label-demo-sender.php' if that's the top-level menu file.
	// For now, we will check against a common pattern for top-level pages.
	// It might also be just 'mlds-dashboard' if it's registered that way. The specific hook for the add_menu_page callback is important.
	// Let's assume the hook for the page rendered by mlds_dashboard_page() might be 'toplevel_page_mlds-dashboard'
	// or the one derived from the file path if the main plugin file itself is the menu slug.

	// A more robust way to get the page hook is to check the global $plugin_page when the function is called.
	// However, for enqueue_scripts, the $hook parameter is the correct one to use.

	// Let's get the hook for the page added by `add_menu_page('Music Label Demo Sender', ..., 'mlds-dashboard', ...)`
	// The main page hook will be 'toplevel_page_mlds-dashboard'
	// The plugin name is 'Music Label Demo Sender' and slug 'mlds-dashboard' as per admin/menus.php

	// The hook for the main dashboard page created by add_menu_page with slug 'mlds-dashboard'
	// is generally 'toplevel_page_mlds-dashboard'.
	if ('toplevel_page_mlds-dashboard' !== $hook) {
		return;
	}

	// Enqueue the WordPress media uploader scripts
	wp_enqueue_media();

	// Enqueue our custom script for media selection
	wp_enqueue_script(
		'mlds-admin-media-select',
		plugin_dir_url(__FILE__) . '../js/mlds-admin-media-select.js', // Path to the new JS file
		['jquery', 'wp-media', 'wp-i18n'], // Dependencies: jQuery, wp-media, and wp-i18n for translations
		'1.0.0',
		true,
	);

	// Required for wp.i18n translations in JS
	wp_set_script_translations('mlds-admin-media-select', 'music-label-demo-sender');
}
add_action('admin_enqueue_scripts', 'mlds_main_dashboard_scripts');
