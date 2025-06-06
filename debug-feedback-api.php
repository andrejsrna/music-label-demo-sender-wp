<?php
/**
 * Feedback API Diagnostic Script
 *
 * This script helps diagnose issues with the feedback system.
 * Place this file in your WordPress root and access it directly to run diagnostics.
 *
 * Usage: https://dnbdoctor.com/debug-feedback-api.php?track_id=994&token=xUIlnvmXrxlAiClpP1VTZPEBB3Qxl0PZ
 */

// Load WordPress
require_once 'wp-load.php';

// Get parameters
$track_id = isset($_GET['track_id']) ? intval($_GET['track_id']) : 994;
$token = isset($_GET['token'])
	? sanitize_text_field($_GET['token'])
	: 'xUIlnvmXrxlAiClpP1VTZPEBB3Qxl0PZ';

header('Content-Type: application/json');

$diagnostics = [
	'timestamp' => current_time('mysql'),
	'requested_track_id' => $track_id,
	'requested_token' => $token,
	'tests' => [],
];

// Test 1: Check if track/attachment exists
$diagnostics['tests']['track_exists'] = [
	'test' => 'Check if track/attachment exists',
	'result' => null,
	'details' => [],
];

$attachment = get_post($track_id);
if ($attachment) {
	$diagnostics['tests']['track_exists']['result'] = 'PASS';
	$diagnostics['tests']['track_exists']['details'] = [
		'id' => $attachment->ID,
		'title' => $attachment->post_title,
		'type' => $attachment->post_mime_type,
		'status' => $attachment->post_status,
	];
} else {
	$diagnostics['tests']['track_exists']['result'] = 'FAIL';
	$diagnostics['tests']['track_exists']['details'] = ['error' => 'Track/attachment not found'];
}

// Test 2: Check stored token
$diagnostics['tests']['token_verification'] = [
	'test' => 'Check stored token vs provided token',
	'result' => null,
	'details' => [],
];

$stored_token = get_post_meta($track_id, '_mlds_track_token', true);
if ($stored_token) {
	if ($stored_token === $token) {
		$diagnostics['tests']['token_verification']['result'] = 'PASS';
		$diagnostics['tests']['token_verification']['details'] = [
			'stored_token_exists' => true,
			'tokens_match' => true,
		];
	} else {
		$diagnostics['tests']['token_verification']['result'] = 'FAIL';
		$diagnostics['tests']['token_verification']['details'] = [
			'stored_token_exists' => true,
			'tokens_match' => false,
			'stored_token' => $stored_token,
			'provided_token' => $token,
		];
	}
} else {
	$diagnostics['tests']['token_verification']['result'] = 'FAIL';
	$diagnostics['tests']['token_verification']['details'] = [
		'stored_token_exists' => false,
		'error' => 'No token found for this track',
	];
}

// Test 3: Check file accessibility
$diagnostics['tests']['file_accessibility'] = [
	'test' => 'Check if track file is accessible',
	'result' => null,
	'details' => [],
];

if ($attachment) {
	$track_url = wp_get_attachment_url($track_id);
	if ($track_url) {
		$diagnostics['tests']['file_accessibility']['result'] = 'PASS';
		$diagnostics['tests']['file_accessibility']['details'] = [
			'url' => $track_url,
			'file_exists' => file_exists(get_attached_file($track_id)),
		];
	} else {
		$diagnostics['tests']['file_accessibility']['result'] = 'FAIL';
		$diagnostics['tests']['file_accessibility']['details'] = [
			'error' => 'Could not generate file URL',
		];
	}
} else {
	$diagnostics['tests']['file_accessibility']['result'] = 'SKIP';
	$diagnostics['tests']['file_accessibility']['details'] = ['error' => 'No attachment to test'];
}

// Test 4: Test API endpoints
$diagnostics['tests']['api_endpoints'] = [
	'test' => 'Test API endpoint accessibility',
	'result' => null,
	'details' => [],
];

$base_url = home_url('/wp-json/mlds/v1/');

// Test the test endpoint
$test_url = $base_url . 'test';
$test_response = wp_remote_get($test_url);

if (!is_wp_error($test_response)) {
	$test_body = wp_remote_retrieve_body($test_response);
	$test_data = json_decode($test_body, true);

	$diagnostics['tests']['api_endpoints']['result'] = 'PASS';
	$diagnostics['tests']['api_endpoints']['details']['test_endpoint'] = [
		'url' => $test_url,
		'status' => wp_remote_retrieve_response_code($test_response),
		'response' => $test_data,
	];
} else {
	$diagnostics['tests']['api_endpoints']['result'] = 'FAIL';
	$diagnostics['tests']['api_endpoints']['details']['test_endpoint'] = [
		'url' => $test_url,
		'error' => $test_response->get_error_message(),
	];
}

// Test track-info endpoint
$track_info_url = $base_url . 'track-info?track_id=' . $track_id . '&token=' . urlencode($token);
$track_info_response = wp_remote_get($track_info_url);

if (!is_wp_error($track_info_response)) {
	$track_info_body = wp_remote_retrieve_body($track_info_response);
	$track_info_data = json_decode($track_info_body, true);

	$diagnostics['tests']['api_endpoints']['details']['track_info_endpoint'] = [
		'url' => $track_info_url,
		'status' => wp_remote_retrieve_response_code($track_info_response),
		'response' => $track_info_data,
	];
} else {
	$diagnostics['tests']['api_endpoints']['details']['track_info_endpoint'] = [
		'url' => $track_info_url,
		'error' => $track_info_response->get_error_message(),
	];
}

// Test 5: Check existing feedback
$diagnostics['tests']['existing_feedback'] = [
	'test' => 'Check existing feedback for track',
	'result' => 'INFO',
	'details' => [],
];

$existing_feedback = get_post_meta($track_id, '_mlds_feedback');
$average_rating = get_post_meta($track_id, '_mlds_average_rating', true);

$diagnostics['tests']['existing_feedback']['details'] = [
	'feedback_count' => count($existing_feedback),
	'average_rating' => $average_rating ?: 'No rating yet',
	'recent_feedback' => array_slice($existing_feedback, -3), // Last 3 feedback entries
];

// Test 6: Check WordPress configuration
$diagnostics['tests']['wp_config'] = [
	'test' => 'Check WordPress configuration',
	'result' => 'INFO',
	'details' => [
		'wp_version' => get_bloginfo('version'),
		'site_url' => home_url(),
		'rest_api_enabled' => function_exists('rest_get_url_prefix'),
		'rest_url' => rest_url(),
		'plugin_active' => is_plugin_active('music-label-demo-sender/music-label-demo-sender.php'),
		'current_theme' => get_template(),
		'debug_mode' => defined('WP_DEBUG') && WP_DEBUG,
	],
];

// Test 7: Database connectivity
$diagnostics['tests']['database'] = [
	'test' => 'Check database connectivity',
	'result' => null,
	'details' => [],
];

global $wpdb;
$table_name = $wpdb->prefix . 'mlds_subscribers';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;

if ($table_exists) {
	$subscriber_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
	$diagnostics['tests']['database']['result'] = 'PASS';
	$diagnostics['tests']['database']['details'] = [
		'subscribers_table_exists' => true,
		'subscriber_count' => intval($subscriber_count),
	];
} else {
	$diagnostics['tests']['database']['result'] = 'FAIL';
	$diagnostics['tests']['database']['details'] = [
		'subscribers_table_exists' => false,
		'error' => 'Subscribers table not found',
	];
}

// Recommendations based on test results
$diagnostics['recommendations'] = [];

if ($diagnostics['tests']['track_exists']['result'] === 'FAIL') {
	$diagnostics[
		'recommendations'
	][] = "Track ID $track_id does not exist. Verify the correct track ID in the email link.";
}

if ($diagnostics['tests']['token_verification']['result'] === 'FAIL') {
	if (!$stored_token) {
		$diagnostics[
			'recommendations'
		][] = "No token found for track $track_id. Generate a new token using: update_post_meta($track_id, '_mlds_track_token', wp_generate_password(32, false));";
	} else {
		$diagnostics['recommendations'][] =
			"Token mismatch. The provided token doesn't match the stored token. Check if the email link is correct or regenerate the token.";
	}
}

if ($diagnostics['tests']['file_accessibility']['result'] === 'FAIL') {
	$diagnostics['recommendations'][] =
		'Track file is not accessible. Check if the file exists and WordPress can generate the URL.';
}

if ($diagnostics['tests']['api_endpoints']['result'] === 'FAIL') {
	$diagnostics['recommendations'][] =
		'API endpoints are not accessible. Check if WordPress REST API is enabled and the plugin is active.';
}

if ($diagnostics['tests']['database']['result'] === 'FAIL') {
	$diagnostics['recommendations'][] =
		'Database table is missing. The plugin may not have been properly activated. Try deactivating and reactivating the plugin.';
}

// Output results
echo json_encode($diagnostics, JSON_PRETTY_PRINT);
