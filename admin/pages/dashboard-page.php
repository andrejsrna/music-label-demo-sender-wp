<?php
// Prevent direct access to this file
if (!defined('ABSPATH')) {
	exit();
}

// Update dashboard page to include stats
function mlds_dashboard_page() {
	// Check user capabilities
	if (!current_user_can('manage_options')) {
		return;
	}

	// Handle CSV upload
	if (isset($_POST['mlds_upload_csv']) && isset($_FILES['subscriber_csv'])) {
		mlds_handle_csv_upload();
	}

	// Get quick stats
	$quick_stats = mlds_get_quick_stats();
	$subscriber_groups = mlds_get_subscriber_groups();
	?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

        <div class="mlds-container">
            <div class="mlds-quick-stats">
                <div class="mlds-stat-card">
                    <span class="dashicons dashicons-format-audio"></span>
                    <div class="stat-content">
                        <span class="stat-number"><?php echo $quick_stats['total_tracks']; ?></span>
                        <span class="stat-label"><?php _e(
                        	'Total Tracks',
                        	'music-label-demo-sender',
                        ); ?></span>
                    </div>
                </div>
                <div class="mlds-stat-card">
                    <span class="dashicons dashicons-email"></span>
                    <div class="stat-content">
                        <span class="stat-number"><?php echo $quick_stats[
                        	'recent_emails'
                        ]; ?></span>
                        <span class="stat-label"><?php _e(
                        	'Emails (Last 7 Days)',
                        	'music-label-demo-sender',
                        ); ?></span>
                    </div>
                </div>
                <div class="mlds-stat-card">
                    <span class="dashicons dashicons-download"></span>
                    <div class="stat-content">
                        <span class="stat-number"><?php echo $quick_stats[
                        	'recent_downloads'
                        ]; ?></span>
                        <span class="stat-label"><?php _e(
                        	'Downloads (Last 7 Days)',
                        	'music-label-demo-sender',
                        ); ?></span>
                    </div>
                </div>
                <div class="mlds-stat-card">
                    <span class="dashicons dashicons-star-filled"></span>
                    <div class="stat-content">
                        <span class="stat-number"><?php echo number_format(
                        	$quick_stats['avg_rating'],
                        	1,
                        ); ?></span>
                        <span class="stat-label"><?php _e(
                        	'Average Rating',
                        	'music-label-demo-sender',
                        ); ?></span>
                    </div>
                </div>
            </div>

            <div class="mlds-subscriber-section">
                <h2><?php _e('Subscriber Management', 'music-label-demo-sender'); ?></h2>

                <?php settings_errors('mlds_messages'); ?>

                <div class="mlds-management-grid">
                    <!-- CSV Import Box -->
                    <div class="mlds-upload-box">
                        <h3><?php _e('Import Subscribers', 'music-label-demo-sender'); ?></h3>
                        <form method="post" enctype="multipart/form-data" class="mlds-import-form">
                            <?php wp_nonce_field('mlds_csv_upload', 'mlds_csv_nonce'); ?>
                            <div class="mlds-form-group">
                                <label for="subscriber_csv"><?php _e(
                                	'CSV File',
                                	'music-label-demo-sender',
                                ); ?></label>
                                <input type="file" id="subscriber_csv" name="subscriber_csv" accept=".csv" required>
                                <p class="description">
                                    <?php _e(
                                    	'Upload a CSV file with columns: email, name (optional)',
                                    	'music-label-demo-sender',
                                    ); ?>
                                </p>
                            </div>
                            <div class="mlds-form-group">
                                <label for="group_name_csv"><?php _e(
                                	'Group Name',
                                	'music-label-demo-sender',
                                ); ?></label>
                                <input type="text" id="group_name_csv" name="group_name"
                                       placeholder="<?php esc_attr_e(
                                       	'Enter group name for these subscribers',
                                       	'music-label-demo-sender',
                                       ); ?>"
                                       required>
                            </div>
                            <button type="submit" name="mlds_upload_csv" class="mlds-button">
                                <?php _e('Import Subscribers', 'music-label-demo-sender'); ?>
                            </button>
                        </form>
                    </div>

                    <!-- Manual Entry Box -->
                    <div class="mlds-upload-box">
                        <h3><?php _e('Add Subscriber Manually', 'music-label-demo-sender'); ?></h3>
                        <form method="post" class="mlds-manual-form">
                            <?php wp_nonce_field('mlds_manual_subscriber', 'mlds_manual_nonce'); ?>
                            <div class="mlds-form-group">
                                <label
                                        for="subscriber_email"><?php _e(
                                        	'Email Address',
                                        	'music-label-demo-sender',
                                        ); ?></label>
                                <input type="email" id="subscriber_email" name="subscriber_email"
                                       placeholder="<?php esc_attr_e(
                                       	'Enter email address',
                                       	'music-label-demo-sender',
                                       ); ?>"
                                       required>
                            </div>
                            <div class="mlds-form-group">
                                <label for="subscriber_name"><?php _e(
                                	'Name',
                                	'music-label-demo-sender',
                                ); ?></label>
                                <input type="text" id="subscriber_name" name="subscriber_name"
                                       placeholder="<?php esc_attr_e(
                                       	'Enter name (optional)',
                                       	'music-label-demo-sender',
                                       ); ?>">
                            </div>
                            <div class="mlds-form-group">
                                <label for="group_name_manual"><?php _e(
                                	'Group Name',
                                	'music-label-demo-sender',
                                ); ?></label>
                                <select name="group_name" id="group_name_manual" required>
                                    <option value=""><?php _e(
                                    	'Select or enter new group...',
                                    	'music-label-demo-sender',
                                    ); ?>
                                    </option>
                                    <?php
                                    $existing_groups = mlds_get_subscriber_groups();
                                    foreach ($existing_groups as $group): ?>
                                        <option value="<?php echo esc_attr($group->group_name); ?>">
                                            <?php echo esc_html($group->group_name); ?>
                                        </option>
                                    <?php endforeach;
                                    ?>
                                    <option value="new"><?php _e(
                                    	'+ Create New Group',
                                    	'music-label-demo-sender',
                                    ); ?>
                                    </option>
                                </select>
                                <input type="text" id="new_group_name" name="new_group_name"
                                       placeholder="<?php esc_attr_e(
                                       	'Enter new group name',
                                       	'music-label-demo-sender',
                                       ); ?>"
                                       style="display: none;" class="mlds-new-group-input">
                            </div>
                            <button type="submit" name="mlds_add_manual" class="mlds-button">
                                <?php _e('Add Subscriber', 'music-label-demo-sender'); ?>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Subscriber Groups Table -->
                <div class="mlds-subscriber-groups">
                    <h3><?php _e('Subscriber Groups', 'music-label-demo-sender'); ?></h3>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                        <tr>
                            <th><?php _e('Group Name', 'music-label-demo-sender'); ?></th>
                            <th><?php _e('Subscribers', 'music-label-demo-sender'); ?></th>
                            <th><?php _e('Date Added', 'music-label-demo-sender'); ?></th>
                            <th><?php _e('Actions', 'music-label-demo-sender'); ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($subscriber_groups as $group): ?>
                            <tr id="group-<?php echo esc_attr($group->group_name); ?>">
                                <td><?php echo esc_html($group->group_name); ?></td>
                                <td><?php echo esc_html($group->subscriber_count); ?></td>
                                <td><?php echo esc_html(
                                	date_i18n(
                                		get_option('date_format'),
                                		strtotime($group->date_added),
                                	),
                                ); ?>
                                </td>
                                <td>
                                    <a href="<?php echo esc_url(
                                    	add_query_arg([
                                    		'action' => 'view_subscribers',
                                    		'group' => $group->group_name,
                                    	]),
                                    ); ?>"
                                       class="mlds-button mlds-button-small">
                                        <?php _e('View', 'music-label-demo-sender'); ?>
                                    </a>
                                    <a href="<?php echo esc_url(
                                    	add_query_arg([
                                    		'action' => 'export_subscribers',
                                    		'group' => $group->group_name,
                                    	]),
                                    ); ?>"
                                       class="mlds-button mlds-button-small">
                                        <?php _e('Export', 'music-label-demo-sender'); ?>
                                    </a>
                                    <a href="#" class="mlds-button mlds-button-small delete-group"
                                       data-group="<?php echo esc_attr($group->group_name); ?>"
                                       data-count="<?php echo esc_attr(
                                       	$group->subscriber_count,
                                       ); ?>">
                                        <?php _e('Delete', 'music-label-demo-sender'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Add this JavaScript code for group deletion -->
                <script>
                    jQuery(document).ready(function ($) {
                        $('.delete-group').click(function (e) {
                            e.preventDefault();
                            var groupName = $(this).data('group');
                            var subscriberCount = $(this).data('count');

                            var confirmMessage = subscriberCount > 0
                                ? '<?php _e(
                                	'Are you sure you want to delete this group? This will also remove all',
                                	'music-label-demo-sender',
                                ); ?> ' +
                                subscriberCount + ' <?php _e(
                                	'subscribers in this group.',
                                	'music-label-demo-sender',
                                ); ?>'
                                : '<?php _e(
                                	'Are you sure you want to delete this group?',
                                	'music-label-demo-sender',
                                ); ?>';

                            if (confirm(confirmMessage)) {
                                $.ajax({
                                    url: ajaxurl,
                                    type: 'POST',
                                    data: {
                                        action: 'mlds_delete_group',
                                        group: groupName,
                                        nonce: '<?php echo wp_create_nonce('mlds_group_action'); ?>'
                                    },
                                    success: function (response) {
                                        if (response.success) {
                                            $('#group-' + groupName.replace(/[^a-zA-Z0-9]/g, '_')).fadeOut(400, function () {
                                                $(this).remove();
                                            });
                                        } else {
                                            alert(response.data.message || '<?php _e(
                                            	'Error deleting group',
                                            	'music-label-demo-sender',
                                            ); ?>');
                                        }
                                    },
                                    error: function () {
                                        alert('<?php _e(
                                        	'Error deleting group',
                                        	'music-label-demo-sender',
                                        ); ?>');
                                    }
                                });
                            }
                        });
                    });
                </script>

                <!-- Recent Subscribers List -->
                <div class="mlds-recent-subscribers">
                    <h3><?php _e('Recent Subscribers', 'music-label-demo-sender'); ?></h3>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                        <tr>
                            <th><?php _e('Email', 'music-label-demo-sender'); ?></th>
                            <th><?php _e('Name', 'music-label-demo-sender'); ?></th>
                            <th><?php _e('Group', 'music-label-demo-sender'); ?></th>
                            <th><?php _e('Date Added', 'music-label-demo-sender'); ?></th>
                            <th><?php _e('Actions', 'music-label-demo-sender'); ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        $recent_subscribers = mlds_get_recent_subscribers(20); // Get 20 most recent subscribers
                        foreach ($recent_subscribers as $subscriber): ?>
                            <tr id="subscriber-<?php echo esc_attr(
                            	$subscriber->id,
                            ); ?>" class="subscriber-row">
                                <td class="subscriber-email">
                                    <span class="display-value"><?php echo esc_html(
                                    	$subscriber->email,
                                    ); ?></span>
                                    <input type="email" class="edit-value"
                                           value="<?php echo esc_attr(
                                           	$subscriber->email,
                                           ); ?>" style="display: none;">
                                </td>
                                <td class="subscriber-name">
                                    <span class="display-value"><?php echo esc_html(
                                    	$subscriber->name,
                                    ); ?></span>
                                    <input type="text" class="edit-value"
                                           value="<?php echo esc_attr($subscriber->name); ?>"
                                           style="display: none;">
                                </td>
                                <td class="subscriber-group">
                                    <span class="display-value"><?php echo esc_html(
                                    	$subscriber->group_name,
                                    ); ?></span>
                                    <select class="edit-value" style="display: none;">
                                        <?php foreach (mlds_get_subscriber_groups() as $group): ?>
                                            <option value="<?php echo esc_attr(
                                            	$group->group_name,
                                            ); ?>" <?php selected(
	$group->group_name,
	$subscriber->group_name,
); ?>>
                                                <?php echo esc_html($group->group_name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <?php echo esc_html(
                                    	date_i18n(
                                    		get_option('date_format') .
                                    			' ' .
                                    			get_option('time_format'),
                                    		strtotime($subscriber->date_added),
                                    	),
                                    ); ?>
                                </td>
                                <td class="subscriber-actions">
                                    <button class="mlds-button mlds-button-small edit-subscriber"
                                            data-id="<?php echo esc_attr($subscriber->id); ?>">
                                        <?php _e('Edit', 'music-label-demo-sender'); ?>
                                    </button>
                                    <button class="mlds-button mlds-button-small save-subscriber" style="display: none;"
                                            data-id="<?php echo esc_attr($subscriber->id); ?>">
                                        <?php _e('Save', 'music-label-demo-sender'); ?>
                                    </button>
                                    <button class="mlds-button mlds-button-small cancel-edit" style="display: none;"
                                            data-id="<?php echo esc_attr($subscriber->id); ?>">
                                        <?php _e('Cancel', 'music-label-demo-sender'); ?>
                                    </button>
                                    <button class="mlds-button mlds-button-small delete-subscriber"
                                            data-id="<?php echo esc_attr($subscriber->id); ?>">
                                        <?php _e('Delete', 'music-label-demo-sender'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach;
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mlds-upload-section">
                <h2><?php _e('Upload Tracks/Albums', 'music-label-demo-sender'); ?></h2>
                <form method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('mlds_upload', 'mlds_upload_nonce'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="mlds_track_file"><?php _e(
                                	'Audio Files',
                                	'music-label-demo-sender',
                                ); ?></label>
                            </th>
                            <td>
                                <input type="file" id="mlds_track_file" name="mlds_track_file[]" accept="audio/*"
                                       multiple>
                                <p class="description">
                                    <?php _e(
                                    	'Select one or multiple audio files',
                                    	'music-label-demo-sender',
                                    ); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label><?php _e(
                                	'Send To Groups',
                                	'music-label-demo-sender',
                                ); ?></label>
                            </th>
                            <td>
                                <div class="subscriber-groups-selection">
                                    <?php
                                    $groups = mlds_get_subscriber_groups();
                                    foreach ($groups as $group): ?>
                                        <label class="group-checkbox">
                                            <input type="checkbox" name="mlds_recipient_groups[]"
                                                   value="<?php echo esc_attr(
                                                   	$group->group_name,
                                                   ); ?>">
                                            <?php echo esc_html($group->group_name); ?>
                                            <span class="subscriber-count">
                                                (<?php echo esc_html($group->subscriber_count); ?>
                                                <?php _e(
                                                	'subscribers',
                                                	'music-label-demo-sender',
                                                ); ?>)
                                            </span>
                                        </label>
                                    <?php endforeach;
                                    ?>
                                </div>
                                <?php if (empty($groups)): ?>
                                    <p class="no-groups-message">
                                        <?php _e(
                                        	'No subscriber groups found. Please add subscribers first.',
                                        	'music-label-demo-sender',
                                        ); ?>
                                    </p>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button(__('Send Demo', 'music-label-demo-sender')); ?>
                </form>
            </div>

            <style>
                .subscriber-groups-selection {
                    max-height: 200px;
                    overflow-y: auto;
                    padding: 10px;
                    background: rgba(26, 26, 26, 0.8);
                    border: 2px solid var(--toxic-purple);
                    border-radius: 8px;
                }

                .group-checkbox {
                    display: block;
                    margin-bottom: 10px;
                    color: var(--light-text);
                    cursor: pointer;
                    transition: all 0.3s ease;
                }

                .group-checkbox:hover {
                    color: var(--toxic-green);
                }

                .group-checkbox input[type="checkbox"] {
                    margin-right: 8px;
                    accent-color: var(--toxic-green);
                }

                .subscriber-count {
                    color: var(--toxic-purple);
                    font-size: 0.9em;
                    margin-left: 5px;
                }

                .no-groups-message {
                    color: #ff4444;
                    font-style: italic;
                }
            </style>

            <div class="mlds-recent-activity">
                <h2><?php _e('Recent Activity', 'music-label-demo-sender'); ?></h2>
                <?php mlds_display_recent_activity(); ?>
            </div>
        </div>
    </div>

    <style>
        :root {
            --toxic-green: #39FF14;
            --toxic-purple: #9932CC;
            --dark-bg: #1a1a1a;
            --light-text: #ffffff;
        }

        .mlds-container {
            margin-top: 20px;
        }

        .mlds-upload-section,
        .mlds-stats-section {
            background: #fff;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
            box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04);
        }

        .mlds-stats-container {
            margin-top: 15px;
        }

        .mlds-quick-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .mlds-stat-card {
            background: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            transition: transform 0.2s;
        }

        .mlds-stat-card:hover {
            transform: translateY(-2px);
        }

        .mlds-stat-card .dashicons {
            font-size: 30px;
            width: 30px;
            height: 30px;
            margin-right: 15px;
            color: #0073aa;
        }

        .stat-content {
            display: flex;
            flex-direction: column;
        }

        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #23282d;
            line-height: 1.2;
        }

        .stat-label {
            color: #666;
            font-size: 13px;
        }

        .mlds-recent-activity {
            background: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-top: 30px;
        }

        .activity-list {
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .activity-item {
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            margin-right: 10px;
            color: #0073aa;
        }

        .activity-date {
            color: #666;
            font-size: 12px;
            margin-left: auto;
        }

        .mlds-subscriber-section {
            background: var(--dark-bg);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 0 20px var(--toxic-green),
            0 0 40px var(--toxic-purple);
            margin-bottom: 30px;
            color: var(--light-text);
        }

        .mlds-management-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .mlds-upload-box {
            background: rgba(26, 26, 26, 0.8);
            padding: 25px;
            border-radius: 8px;
            height: 100%;
            border: 1px solid var(--toxic-purple);
        }

        .mlds-upload-box h3 {
            color: var(--toxic-green);
            margin-bottom: 1.5em;
            text-shadow: 0 0 5px var(--toxic-green);
        }

        .mlds-form-group {
            margin-bottom: 20px;
        }

        .mlds-form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--toxic-purple);
            text-shadow: 0 0 5px var(--toxic-purple);
            font-weight: bold;
        }

        .mlds-form-group input[type="text"],
        .mlds-form-group input[type="email"],
        .mlds-form-group input[type="file"],
        .mlds-form-group select {
            width: 100%;
            padding: 10px;
            border: 2px solid var(--toxic-purple);
            border-radius: 8px;
            background: rgba(26, 26, 26, 0.8);
            color: var(--light-text);
            margin-bottom: 5px;
        }

        .mlds-form-group input:focus,
        .mlds-form-group select:focus {
            outline: none;
            border-color: var(--toxic-green);
            box-shadow: 0 0 10px var(--toxic-green);
        }

        .mlds-new-group-input {
            margin-top: 10px;
        }

        .mlds-button {
            background: var(--toxic-purple);
            color: var(--light-text);
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            width: 100%;
        }

        .mlds-button:hover {
            background: var(--toxic-green);
            box-shadow: 0 0 15px var(--toxic-green);
            transform: translateY(-2px);
            color: var(--light-text);
        }

        .mlds-button-small {
            padding: 8px 16px;
            width: auto;
            margin-right: 10px;
        }

        .description {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9em;
            margin-top: 5px;
        }

        /* Table Styles */
        .mlds-subscriber-groups {
            margin-top: 30px;
            background: rgba(26, 26, 26, 0.8);
            padding: 25px;
            border-radius: 8px;
            border: 1px solid var(--toxic-purple);
        }

        .mlds-subscriber-groups h3 {
            color: var(--toxic-green);
            margin-bottom: 1.5em;
            text-shadow: 0 0 5px var(--toxic-green);
        }

        .mlds-subscriber-groups table {
            background: transparent;
            border: none;
            color: var(--light-text);
        }

        .mlds-subscriber-groups th {
            background: rgba(153, 50, 204, 0.3);
            color: var(--light-text);
            padding: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 0.9em;
        }

        .mlds-subscriber-groups td {
            padding: 12px;
            border-bottom: 1px solid rgba(153, 50, 204, 0.2);
        }

        .mlds-subscriber-groups tr:hover td {
            background: rgba(57, 255, 20, 0.1);
        }

        /* Notice Styles */
        .notice {
            margin: 20px 0;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid;
        }

        .notice-success {
            background: rgba(57, 255, 20, 0.1);
            border-color: var(--toxic-green);
            color: var(--light-text);
        }

        .notice-error {
            background: rgba(255, 0, 0, 0.1);
            border-color: #ff0000;
            color: var(--light-text);
        }

        /* Recent Subscribers Styles */
        .mlds-recent-subscribers {
            margin-top: 30px;
            background: rgba(26, 26, 26, 0.8);
            padding: 25px;
            border-radius: 8px;
            border: 1px solid var(--toxic-purple);
        }

        .mlds-recent-subscribers h3 {
            color: var(--toxic-green);
            margin-bottom: 1.5em;
            text-shadow: 0 0 5px var(--toxic-green);
        }

        .subscriber-row td {
            vertical-align: middle;
        }

        .subscriber-actions {
            white-space: nowrap;
        }

        .subscriber-actions .mlds-button-small {
            padding: 5px 10px;
            margin-right: 5px;
            font-size: 12px;
        }

        .edit-value {
            width: 100%;
            padding: 5px;
            border: 2px solid var(--toxic-purple);
            border-radius: 4px;
            background: rgba(26, 26, 26, 0.9);
            color: var(--light-text);
        }

        .edit-value:focus {
            outline: none;
            border-color: var(--toxic-green);
            box-shadow: 0 0 10px var(--toxic-green);
        }

        .subscriber-row.editing {
            background: rgba(57, 255, 20, 0.1);
        }

        .delete-subscriber {
            background: #ff4444;
        }

        .delete-subscriber:hover {
            background: #ff0000;
            box-shadow: 0 0 15px #ff0000;
        }
    </style>

    <script>
        jQuery(document).ready(function ($) {
            $('#group_name_manual').change(function () {
                if ($(this).val() === 'new') {
                    $('#new_group_name').show().prop('required', true);
                } else {
                    $('#new_group_name').hide().prop('required', false);
                }
            });

            // Handle subscriber editing
            $('.edit-subscriber').click(function () {
                const id = $(this).data('id');
                const row = $(`#subscriber-${id}`);

                // Show edit fields
                row.find('.display-value').hide();
                row.find('.edit-value').show();

                // Show/hide buttons
                row.find('.edit-subscriber, .delete-subscriber').hide();
                row.find('.save-subscriber, .cancel-edit').show();

                row.addClass('editing');
            });

            // Handle cancel edit
            $('.cancel-edit').click(function () {
                const id = $(this).data('id');
                const row = $(`#subscriber-${id}`);

                // Reset and hide edit fields
                row.find('.edit-value').each(function () {
                    $(this).val($(this).siblings('.display-value').text().trim());
                });
                row.find('.display-value').show();
                row.find('.edit-value').hide();

                // Show/hide buttons
                row.find('.edit-subscriber, .delete-subscriber').show();
                row.find('.save-subscriber, .cancel-edit').hide();

                row.removeClass('editing');
            });

            // Handle save subscriber
            $('.save-subscriber').click(function () {
                const id = $(this).data('id');
                const row = $(`#subscriber-${id}`);
                const email = row.find('.subscriber-email .edit-value').val();
                const name = row.find('.subscriber-name .edit-value').val();
                const group = row.find('.subscriber-group .edit-value').val();

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'mlds_update_subscriber',
                        nonce: '<?php echo wp_create_nonce('mlds_subscriber_action'); ?>',
                        id: id,
                        email: email,
                        name: name,
                        group: group
                    },
                    success: function (response) {
                        if (response.success) {
                            // Update display values
                            row.find('.subscriber-email .display-value').text(email);
                            row.find('.subscriber-name .display-value').text(name);
                            row.find('.subscriber-group .display-value').text(group);

                            // Reset view
                            row.find('.display-value').show();
                            row.find('.edit-value').hide();
                            row.find('.edit-subscriber, .delete-subscriber').show();
                            row.find('.save-subscriber, .cancel-edit').hide();
                            row.removeClass('editing');

                            // Show success message
                            alert('Subscriber updated successfully');
                        } else {
                            alert(response.data.message || 'Error updating subscriber');
                        }
                    },
                    error: function () {
                        alert('Error updating subscriber');
                    }
                });
            });

            // Handle delete subscriber
            $('.delete-subscriber').click(function () {
                if (!confirm('Are you sure you want to delete this subscriber?')) {
                    return;
                }

                const id = $(this).data('id');
                const row = $(`#subscriber-${id}`);

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'mlds_delete_subscriber',
                        nonce: '<?php echo wp_create_nonce('mlds_subscriber_action'); ?>',
                        id: id
                    },
                    success: function (response) {
                        if (response.success) {
                            row.fadeOut(400, function () {
                                $(this).remove();
                            });
                        } else {
                            alert(response.data.message || 'Error deleting subscriber');
                        }
                    },
                    error: function () {
                        alert('Error deleting subscriber');
                    }
                });
            });
        });
    </script>
    <?php
}
