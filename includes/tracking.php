<?php
// Prevent direct access to this file
if (!defined('ABSPATH')) {
	exit();
}

// Track email opens and downloads (view action from main link)
function mlds_track_interaction() {
	if (isset($_GET['track']) && isset($_GET['token'])) {
		$post_id = intval($_GET['track']);
		$token = sanitize_text_field($_GET['token']);

		// Verify token
		$stored_token = get_post_meta($post_id, '_mlds_track_token', true);
		if ($token === $stored_token) {
			// Log interaction
			add_post_meta($post_id, '_mlds_track_interaction', [
				'type' => 'view', // This is a general view/access, not necessarily an email open
				'date' => current_time('mysql'),
				'ip' => sanitize_text_field($_SERVER['REMOTE_ADDR']),
			]);
		}
	}
}
add_action('init', 'mlds_track_interaction');

// Handle tracking pixel (email open) and download tracking
function mlds_track_actions() {
	if (isset($_GET['mlds_track']) && isset($_GET['action']) && isset($_GET['token'])) {
		$post_id = intval($_GET['mlds_track']);
		$action = sanitize_text_field($_GET['action']);
		$token = sanitize_text_field($_GET['token']);

		// Verify token
		if ($token === get_post_meta($post_id, '_mlds_track_token', true)) {
			$interaction_type = '';
			if ($action === 'pixel') {
				$interaction_type = 'open'; // Specifically email open via pixel
			} elseif ($action === 'download') {
				$interaction_type = 'download';
			}

			if ($interaction_type) {
				// Log interaction
				add_post_meta($post_id, '_mlds_track_interaction', [
					'type' => $interaction_type,
					'date' => current_time('mysql'),
					'ip' => sanitize_text_field($_SERVER['REMOTE_ADDR']),
				]);
			}

			// If this is a pixel request, output a transparent GIF
			if ($action === 'pixel') {
				header('Content-Type: image/gif');
				header('Cache-Control: no-cache');
				die(base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'));
			}
		}
	}
}
add_action('init', 'mlds_track_actions');
