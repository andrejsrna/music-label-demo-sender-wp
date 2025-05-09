<?php
// Prevent direct access to this file
if (!defined('ABSPATH')) {
	exit();
}

// Register plugin settings
function mlds_register_settings() {
	register_setting('mlds_options', 'mlds_unsubscribe_page', [
		'type' => 'integer',
		'description' =>
			'ID of the page containing the unsubscribe form (legacy, prefer Unsubscribe Page URL below).',
		'sanitize_callback' => 'absint',
		'default' => 0,
	]);

	register_setting('mlds_options', 'mlds_feedback_page_url', [
		'type' => 'string',
		'description' => 'Full URL of the page where users submit demo feedback.',
		'sanitize_callback' => 'esc_url_raw',
		'default' => home_url('/demo-feedback/'),
	]);

	register_setting('mlds_options', 'mlds_unsubscribe_page_url', [
		'type' => 'string',
		'description' => 'Full URL of the page where users can unsubscribe.',
		'sanitize_callback' => 'esc_url_raw',
		'default' => home_url('/unsub/'),
	]);

	// Add more settings here as needed, for example:
	/*
	register_setting('mlds_options', 'mlds_sender_email', [
		'type' => 'string',
		'description' => 'Default sender email address for demo track notifications.',
		'sanitize_callback' => 'sanitize_email',
		'default' => get_option('admin_email'),
	]);

	register_setting('mlds_options', 'mlds_email_subject', [
		'type' => 'string',
		'description' => 'Default subject line for demo track emails.',
		'sanitize_callback' => 'sanitize_text_field',
		'default' => __('New Demo Track Submission', 'music-label-demo-sender'),
	]);
	*/
}
add_action('admin_init', 'mlds_register_settings');

// Add settings section to the plugin's settings page
function mlds_add_settings_section() {
	add_settings_section(
		'mlds_general_settings',
		__('General Settings', 'music-label-demo-sender'),
		'mlds_general_settings_callback',
		'mlds-settings', // This is the slug of the settings page this section appears on
	);

	add_settings_field(
		'mlds_feedback_page_url',
		__('Feedback Page URL', 'music-label-demo-sender'),
		'mlds_feedback_page_url_callback',
		'mlds-settings',
		'mlds_general_settings',
	);

	add_settings_field(
		'mlds_unsubscribe_page_url',
		__('Unsubscribe Page URL', 'music-label-demo-sender'),
		'mlds_unsubscribe_page_url_callback',
		'mlds-settings',
		'mlds_general_settings',
	);

	add_settings_field(
		'mlds_unsubscribe_page',
		__('Unsubscribe Page (Legacy)', 'music-label-demo-sender'),
		'mlds_unsubscribe_page_callback',
		'mlds-settings',
		'mlds_general_settings',
	);

	// Add more fields here for the new settings, for example:
	/*
	add_settings_field(
		'mlds_sender_email',
		__('Sender Email Address', 'music-label-demo-sender'),
		'mlds_sender_email_callback',
		'mlds-settings',
		'mlds_general_settings'
	);

	add_settings_field(
		'mlds_email_subject',
		__('Default Email Subject', 'music-label-demo-sender'),
		'mlds_email_subject_callback',
		'mlds-settings',
		'mlds_general_settings'
	);
	*/
}
add_action('admin_init', 'mlds_add_settings_section');

// Settings section callback
function mlds_general_settings_callback() {
	echo '<p>' .
		__(
			'Configure general settings for the Music Label Demo Sender plugin.',
			'music-label-demo-sender',
		) .
		'</p>';
}

// Callback for Feedback Page URL
function mlds_feedback_page_url_callback() {
	$feedback_url = get_option('mlds_feedback_page_url', home_url('/demo-feedback/'));
	echo '<input type="url" id="mlds_feedback_page_url" name="mlds_feedback_page_url" value="' .
		esc_attr($feedback_url) .
		'" class="regular-text code" placeholder="' .
		esc_attr(home_url('/demo-feedback/')) .
		'" />';
	echo '<p class="description">' .
		__(
			'Enter the full URL for the demo feedback page. Used in emails.',
			'music-label-demo-sender',
		) .
		'</p>';
}

// Callback for Unsubscribe Page URL
function mlds_unsubscribe_page_url_callback() {
	$unsubscribe_url = get_option('mlds_unsubscribe_page_url', home_url('/unsub/'));
	echo '<input type="url" id="mlds_unsubscribe_page_url" name="mlds_unsubscribe_page_url" value="' .
		esc_attr($unsubscribe_url) .
		'" class="regular-text code" placeholder="' .
		esc_attr(home_url('/unsub/')) .
		'" />';
	echo '<p class="description">' .
		__(
			'Enter the full URL for the unsubscribe page. Used in emails.',
			'music-label-demo-sender',
		) .
		'</p>';
}

// Unsubscribe page setting callback
function mlds_unsubscribe_page_callback() {
	$unsubscribe_page = get_option('mlds_unsubscribe_page');
	wp_dropdown_pages([
		'name' => 'mlds_unsubscribe_page',
		'echo' => 1,
		'show_option_none' => __('&mdash; Select &mdash;'),
		'option_none_value' => '0',
		'selected' => $unsubscribe_page,
	]);
	echo '<p class="description">' .
		__(
			'Select the page containing the [mlds_unsubscribe_form] shortcode.',
			'music-label-demo-sender',
		) .
		'</p>';
}

// Example callback functions for new settings fields
/*
function mlds_sender_email_callback() {
    $sender_email = get_option('mlds_sender_email', get_option('admin_email'));
    echo '<input type="email" id="mlds_sender_email" name="mlds_sender_email" value="' . esc_attr($sender_email) . '" class="regular-text" />';
    echo '<p class="description">' . __('Enter the default email address for sending demo notifications.', 'music-label-demo-sender') . '</p>';
}

function mlds_email_subject_callback() {
    $email_subject = get_option('mlds_email_subject', __('New Demo Track Submission', 'music-label-demo-sender'));
    echo '<input type="text" id="mlds_email_subject" name="mlds_email_subject" value="' . esc_attr($email_subject) . '" class="regular-text" />';
    echo '<p class="description">' . __('Enter the default subject line for demo track emails.', 'music-label-demo-sender') . '</p>';
}
*/

// Note: The settings page itself (where these sections and fields are displayed)
// is typically added via add_submenu_page() in the admin/menus.php file.
// For example, like this:
/*
function mlds_add_settings_submenu_page() {
    add_submenu_page(
        'mlds-dashboard', // Parent slug
        __('Demo Sender Settings', 'music-label-demo-sender'), // Page title
        __('Settings', 'music-label-demo-sender'), // Menu title
        'manage_options', // Capability
        'mlds-settings', // Menu slug (must match the one used in add_settings_section and add_settings_field)
        'mlds_render_settings_page' // Function to display the page content
    );
}
add_action('admin_menu', 'mlds_add_settings_submenu_page');

function mlds_render_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('mlds_options'); // Option group registered in mlds_register_settings
            do_settings_sections('mlds-settings'); // Page slug where sections are added
            submit_button('Save Settings');
            ?>
        </form>
    </div>
    <?php
}
*/
?> 