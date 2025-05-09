<?php
// Prevent direct access to this file
if (!defined('ABSPATH')) {
	exit();
}

// Remove the custom post type registration and replace with taxonomy
function mlds_register_taxonomy() {
	$labels = [
		'name' => __('Demo Tracks', 'music-label-demo-sender'),
		'singular_name' => __('Demo Track', 'music-label-demo-sender'),
		'search_items' => __('Search Demo Tracks', 'music-label-demo-sender'),
		'all_items' => __('All Demo Tracks', 'music-label-demo-sender'),
		'edit_item' => __('Edit Demo Track', 'music-label-demo-sender'),
		'update_item' => __('Update Demo Track', 'music-label-demo-sender'),
		'add_new_item' => __('Add New Demo Track', 'music-label-demo-sender'),
		'menu_name' => __('Demo Tracks', 'music-label-demo-sender'),
	];

	$args = [
		'labels' => $labels,
		'hierarchical' => false,
		'public' => false,
		'show_ui' => true,
		'show_admin_column' => true,
		'query_var' => true,
		'rewrite' => ['slug' => 'demo-track'],
	];

	register_taxonomy('demo_track_type', 'post', $args);
}

add_action('init', 'mlds_register_taxonomy');
