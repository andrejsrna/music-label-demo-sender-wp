<?php
// Prevent direct access to this file
if (!defined('ABSPATH')) {
	exit();
}

// Add admin menu
function mlds_admin_menu() {
	add_menu_page(
		__('Demo Sender', 'music-label-demo-sender'),
		__('Demo Sender', 'music-label-demo-sender'),
		'manage_options',
		'mlds-dashboard',
		'mlds_dashboard_page',
		'dashicons-playlist-audio',
		30,
	);
}
add_action('admin_menu', 'mlds_admin_menu');

// Add stats submenu page
function mlds_add_stats_page() {
	add_submenu_page(
		'mlds-dashboard',
		__('Demo Stats', 'music-label-demo-sender'),
		__('Stats', 'music-label-demo-sender'),
		'manage_options',
		'mlds-stats',
		'mlds_stats_page',
	);
}
add_action('admin_menu', 'mlds_add_stats_page');

// Add subscriber management page
function mlds_add_subscriber_management_page() {
	add_submenu_page(
		'mlds-dashboard',
		__('Subscriber Management', 'music-label-demo-sender'),
		__('Subscribers', 'music-label-demo-sender'),
		'manage_options',
		'mlds-subscribers',
		'mlds_subscriber_management_page',
	);
}
add_action('admin_menu', 'mlds_add_subscriber_management_page');

// Add settings page
function mlds_add_settings_page() {
	add_submenu_page(
		'mlds-dashboard',
		__('Demo Sender Settings', 'music-label-demo-sender'),
		__('Settings', 'music-label-demo-sender'),
		'manage_options',
		'mlds-settings',
		'mlds_render_settings_page',
	);
}
add_action('admin_menu', 'mlds_add_settings_page');
