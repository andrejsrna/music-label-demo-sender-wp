<?php
// Prevent direct access to this file
if (!defined('ABSPATH')) {
	exit();
}

// Add filter to admin menu to show only demo tracks
function mlds_admin_menu_filter() {
	global $typenow;

	if ($typenow == 'post') {
		$selected = isset($_GET['demo_track_type']) ? $_GET['demo_track_type'] : '';
		$info = get_taxonomy('demo_track_type');
		wp_dropdown_categories([
			'show_option_all' => __("Show All {$info->label}"),
			'taxonomy' => 'demo_track_type',
			'name' => 'demo_track_type',
			'orderby' => 'name',
			'selected' => $selected,
			'hierarchical' => true,
			'depth' => 3,
			'show_count' => true,
			'hide_empty' => true,
		]);
	}
}
add_action('restrict_manage_posts', 'mlds_admin_menu_filter');

// Convert category ID to taxonomy term in query
function mlds_convert_id_to_term_in_query($query) {
	global $pagenow;
	$qv = &$query->query_vars;

	if (
		$pagenow == 'edit.php' &&
		isset($qv['demo_track_type']) &&
		is_numeric($qv['demo_track_type'])
	) {
		$term = get_term_by('id', $qv['demo_track_type'], 'demo_track_type');
		$qv['demo_track_type'] = $term->slug;
	}
}
add_filter('parse_query', 'mlds_convert_id_to_term_in_query');
