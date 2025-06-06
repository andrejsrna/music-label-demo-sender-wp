<?php
// Prevent direct access to this file
if (!defined('ABSPATH')) {
	exit();
}

// Display admin notices
function mlds_admin_notices() {
	$notice = get_transient('mlds_admin_notice');
	if ($notice) { ?>
        <div class="notice notice-<?php echo esc_attr($notice['type']); ?> is-dismissible">
            <p><?php echo esc_html($notice['message']); ?></p>
        </div>
        <?php delete_transient('mlds_admin_notice');}
}
add_action('admin_notices', 'mlds_admin_notices');
