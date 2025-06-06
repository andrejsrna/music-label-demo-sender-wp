<?php
/**
 * Quick Fix for Feedback URL
 *
 * This script fixes the feedback page URL setting to use the correct path.
 * Upload this file to your WordPress root and access it once to fix the issue.
 * Then delete this file.
 *
 * Usage: https://dnbdoctor.com/fix-feedback-url.php
 */

// Load WordPress
require_once 'wp-load.php';

// Check if user is admin
if (!current_user_can('manage_options')) {
	die('Access denied. You must be an administrator to run this script.');
}

echo '<h1>Feedback URL Fix Script</h1>';

// Get current setting
$current_url = get_option('mlds_feedback_page_url', home_url('/demo-feedback/'));
echo '<p><strong>Current feedback URL:</strong> ' . esc_html($current_url) . '</p>';

// Check if it contains /music/demo-feedback
if (strpos($current_url, '/music/demo-feedback') !== false) {
	// Fix the URL
	$correct_url = str_replace('/music/demo-feedback', '/demo-feedback', $current_url);

	// Update the option
	$updated = update_option('mlds_feedback_page_url', $correct_url);

	if ($updated) {
		echo "<p style='color: green;'><strong>✓ Fixed!</strong> Updated to: " .
			esc_html($correct_url) .
			'</p>';
		echo '<p>The feedback URL has been corrected. New emails will now use the correct path.</p>';
	} else {
		echo "<p style='color: orange;'><strong>ℹ Info:</strong> URL was already set to: " .
			esc_html($correct_url) .
			'</p>';
	}
} else {
	echo "<p style='color: blue;'><strong>ℹ Info:</strong> URL is already correct: " .
		esc_html($current_url) .
		'</p>';
}

// Also check unsubscribe URL just in case
$unsubscribe_url = get_option('mlds_unsubscribe_page_url', home_url('/unsub/'));
echo '<p><strong>Unsubscribe URL:</strong> ' . esc_html($unsubscribe_url) . '</p>';

if (strpos($unsubscribe_url, '/music/unsub') !== false) {
	$correct_unsub_url = str_replace('/music/unsub', '/unsub', $unsubscribe_url);
	update_option('mlds_unsubscribe_page_url', $correct_unsub_url);
	echo "<p style='color: green;'><strong>✓ Also fixed unsubscribe URL!</strong> Updated to: " .
		esc_html($correct_unsub_url) .
		'</p>';
}

echo '<hr>';
echo '<h2>Next Steps:</h2>';
echo '<ol>';
echo '<li>Delete this fix script file from your server</li>';
echo "<li>Test the feedback page: <a href='" .
	esc_url(
		get_option('mlds_feedback_page_url') . '?track=994&token=xUIlnvmXrxlAiClpP1VTZPEBB3Qxl0PZ',
	) .
	"' target='_blank'>Test Feedback Link</a></li>";
echo '<li>Send a new test email to verify the links work correctly</li>';
echo '</ol>';

echo '<h2>Settings Verification:</h2>';
echo '<p>You can also verify these settings in WordPress Admin:</p>';
echo '<ul>';
echo '<li>Go to WordPress Admin → Demo Sender → Settings</li>';
echo "<li>Check that 'Feedback Page URL' shows: " .
	esc_html(get_option('mlds_feedback_page_url')) .
	'</li>';
echo '</ul>';
?> 