<?php
/*
Plugin Name: Music Label Demo Sender
Description: A plugin to send demo tracks/albums, collect feedback, and track stats.
Version: 1.0
Author: Your Name
*/

// Prevent direct access to this file
if (!defined('ABSPATH')) {
	exit();
}

// Include frontend functionality
require_once plugin_dir_path(__FILE__) . 'frontend.php';
require_once plugin_dir_path(__FILE__) . 'backend.php';
require_once plugin_dir_path(__FILE__) . 'api.php';
require_once plugin_dir_path(__FILE__) . 'includes/taxonomies.php'; // Added for taxonomy registration
require_once plugin_dir_path(__FILE__) . 'admin/menus.php'; // Added for admin menu registration
require_once plugin_dir_path(__FILE__) . 'includes/file-processing.php'; // Added for file processing and email sending
require_once plugin_dir_path(__FILE__) . 'admin/pages/dashboard-page.php'; // Added for dashboard page display
require_once plugin_dir_path(__FILE__) . 'admin/pages/stats-page.php'; // Added for stats page display
require_once plugin_dir_path(__FILE__) . 'admin/pages/subscriber-management-page.php'; // Added for subscriber management page display
require_once plugin_dir_path(__FILE__) . 'includes/stats-helpers.php'; // Added for stats helper functions
require_once plugin_dir_path(__FILE__) . 'includes/subscriber-helpers.php'; // Added for subscriber helper functions
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes.php'; // Added for shortcodes
require_once plugin_dir_path(__FILE__) . 'includes/notifications.php'; // Added for notifications and AJAX handlers
require_once plugin_dir_path(__FILE__) . 'includes/tracking.php'; // Added for tracking interactions
require_once plugin_dir_path(__FILE__) . 'admin/settings.php'; // Added for plugin settings
require_once plugin_dir_path(__FILE__) . 'includes/database.php'; // Added for database operations
require_once plugin_dir_path(__FILE__) . 'includes/admin-assets.php'; // Added for admin scripts and styles
require_once plugin_dir_path(__FILE__) . 'includes/admin-notices.php'; // Added for admin notices
require_once plugin_dir_path(__FILE__) . 'admin/filters.php'; // Added for admin table filters

// Register activation hook (this MUST stay in the main plugin file)
register_activation_hook(__FILE__, 'mlds_create_subscribers_table');
