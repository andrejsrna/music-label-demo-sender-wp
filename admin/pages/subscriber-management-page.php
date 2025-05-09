<?php
// Prevent direct access to this file
if (!defined('ABSPATH')) {
	exit();
}

// Subscriber management page
function mlds_subscriber_management_page() {
	if (!current_user_can('manage_options')) {
		return;
	}

	// Handle bulk actions
	if (isset($_POST['action']) && isset($_POST['subscriber'])) {
		$action = sanitize_text_field($_POST['action']);
		$subscribers = array_map('intval', $_POST['subscriber']);

		if ($action === 'delete' && !empty($subscribers)) {
			mlds_bulk_delete_subscribers($subscribers);
		} elseif (
			$action === 'change_group' &&
			!empty($subscribers) &&
			isset($_POST['new_group'])
		) {
			$new_group = sanitize_text_field($_POST['new_group']);
			mlds_bulk_change_group($subscribers, $new_group);
		}
	}

	// Get filters
	$group_filter = isset($_GET['group']) ? sanitize_text_field($_GET['group']) : '';
	$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
	$orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'date_added';
	$order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'DESC';
	$paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
	$per_page = 20;

	// Get subscribers with filters
	$subscribers = mlds_get_filtered_subscribers(
		$group_filter,
		$search,
		$orderby,
		$order,
		$paged,
		$per_page,
	);
	$total_subscribers = mlds_count_filtered_subscribers($group_filter, $search);
	$groups = mlds_get_subscriber_groups();
	?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php _e(
        	'Subscriber Management',
        	'music-label-demo-sender',
        ); ?></h1>
        <a href="#" class="page-title-action add-subscriber-button">
            <?php _e('Add New Subscriber', 'music-label-demo-sender'); ?>
        </a>

        <!-- Search and Filter Form -->
        <form method="get" class="search-filter-form">
            <input type="hidden" name="page" value="mlds-subscribers">

            <div class="tablenav top">
                <div class="alignleft actions">
                    <select name="group">
                        <option value=""><?php _e(
                        	'All Groups',
                        	'music-label-demo-sender',
                        ); ?></option>
                        <?php foreach ($groups as $group): ?>
                            <option value="<?php echo esc_attr(
                            	$group->group_name,
                            ); ?>" <?php selected($group_filter, $group->group_name); ?>>
                                <?php echo esc_html($group->group_name); ?>
                                (<?php echo esc_html($group->subscriber_count); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <input type="search" name="s" value="<?php echo esc_attr($search); ?>"
                           placeholder="<?php esc_attr_e(
                           	'Search subscribers...',
                           	'music-label-demo-sender',
                           ); ?>">

                    <?php submit_button(
                    	__('Filter', 'music-label-demo-sender'),
                    	'action',
                    	false,
                    	false,
                    ); ?>
                </div>
            </div>
        </form>

        <!-- Subscribers List -->
        <form method="post" id="subscribers-filter">
            <div class="tablenav top">
                <div class="alignleft actions bulkactions">
                    <select name="action">
                        <option value="-1"><?php _e(
                        	'Bulk Actions',
                        	'music-label-demo-sender',
                        ); ?></option>
                        <option value="delete"><?php _e(
                        	'Delete',
                        	'music-label-demo-sender',
                        ); ?></option>
                        <option value="change_group"><?php _e(
                        	'Change Group',
                        	'music-label-demo-sender',
                        ); ?></option>
                    </select>

                    <select name="new_group" style="display: none;">
                        <?php foreach ($groups as $group): ?>
                            <option value="<?php echo esc_attr($group->group_name); ?>">
                                <?php echo esc_html($group->group_name); ?>
                            </option>
                        <?php endforeach; ?>
                        <option value="new"><?php _e(
                        	'+ Create New Group',
                        	'music-label-demo-sender',
                        ); ?></option>
                    </select>

                    <input type="text" name="new_group_name" class="new-group-input" style="display: none;"
                           placeholder="<?php esc_attr_e(
                           	'Enter new group name',
                           	'music-label-demo-sender',
                           ); ?>">

                    <?php submit_button(
                    	__('Apply', 'music-label-demo-sender'),
                    	'action',
                    	false,
                    	false,
                    ); ?>
                </div>

                <?php
                $total_pages = ceil($total_subscribers / $per_page);
                if ($total_pages > 1) {
                	echo '<div class="tablenav-pages">';
                	echo paginate_links([
                		'base' => add_query_arg('paged', '%#%'),
                		'format' => '',
                		'prev_text' => __('&laquo;'),
                		'next_text' => __('&raquo;'),
                		'total' => $total_pages,
                		'current' => $paged,
                	]);
                	echo '</div>';
                }
                ?>
            </div>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                <tr>
                    <td class="manage-column column-cb check-column">
                        <input type="checkbox" id="cb-select-all-1">
                    </td>
                    <?php
                    $columns = [
                    	'email' => __('Email', 'music-label-demo-sender'),
                    	'name' => __('Name', 'music-label-demo-sender'),
                    	'group_name' => __('Group', 'music-label-demo-sender'),
                    	'date_added' => __('Date Added', 'music-label-demo-sender'),
                    ];

                    foreach ($columns as $column_key => $column_label) {

                    	$current_order = $orderby === $column_key ? $order : 'ASC';
                    	$order_link = add_query_arg([
                    		'orderby' => $column_key,
                    		'order' => $current_order === 'ASC' ? 'DESC' : 'ASC',
                    	]);
                    	?>
                        <th scope="col"
                            class="manage-column column-<?php echo esc_attr(
                            	$column_key,
                            ); ?> sortable <?php echo $orderby === $column_key ? 'sorted' : ''; ?>">
                            <a href="<?php echo esc_url($order_link); ?>">
                                <span><?php echo esc_html($column_label); ?></span>
                                <span class="sorting-indicator"></span>
                            </a>
                        </th>
                        <?php
                    }
                    ?>
                </tr>
                </thead>

                <tbody>
                <?php if (!empty($subscribers)): ?>
                    <?php foreach ($subscribers as $subscriber): ?>
                        <tr id="subscriber-<?php echo esc_attr($subscriber->id); ?>">
                            <th scope="row" class="check-column">
                                <input type="checkbox" name="subscriber[]"
                                       value="<?php echo esc_attr($subscriber->id); ?>">
                            </th>
                            <td class="column-email">
                                <strong>
                                    <a href="#" class="edit-subscriber"
                                       data-id="<?php echo esc_attr($subscriber->id); ?>">
                                        <?php echo esc_html($subscriber->email); ?>
                                    </a>
                                </strong>
                                <div class="row-actions">
                                        <span class="edit">
                                            <a href="#" class="edit-subscriber"
                                               data-id="<?php echo esc_attr($subscriber->id); ?>">
                                                <?php _e('Edit', 'music-label-demo-sender'); ?>
                                            </a> |
                                        </span>
                                    <span class="delete">
                                            <a href="#" class="delete-subscriber"
                                               data-id="<?php echo esc_attr($subscriber->id); ?>">
                                                <?php _e('Delete', 'music-label-demo-sender'); ?>
                                            </a>
                                        </span>
                                </div>
                            </td>
                            <td class="column-name"><?php echo esc_html($subscriber->name); ?></td>
                            <td class="column-group"><?php echo esc_html(
                            	$subscriber->group_name,
                            ); ?></td>
                            <td class="column-date">
                                <?php echo esc_html(
                                	date_i18n(
                                		get_option('date_format') . ' ' . get_option('time_format'),
                                		strtotime($subscriber->date_added),
                                	),
                                ); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5"><?php _e(
                        	'No subscribers found.',
                        	'music-label-demo-sender',
                        ); ?></td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </form>
    </div>

    <!-- Add/Edit Subscriber Dialog -->
    <div id="subscriber-dialog" title="<?php esc_attr_e(
    	'Subscriber',
    	'music-label-demo-sender',
    ); ?>"
         style="display:none;">
        <form id="subscriber-form">
            <input type="hidden" name="subscriber_id" id="subscriber_id" value="">
            <div class="form-field">
                <label for="subscriber_email"><?php _e(
                	'Email',
                	'music-label-demo-sender',
                ); ?></label>
                <input type="email" name="email" id="subscriber_email" required>
            </div>
            <div class="form-field">
                <label for="subscriber_name"><?php _e('Name', 'music-label-demo-sender'); ?></label>
                <input type="text" name="name" id="subscriber_name">
            </div>
            <div class="form-field">
                <label for="subscriber_group"><?php _e(
                	'Group',
                	'music-label-demo-sender',
                ); ?></label>
                <select name="group" id="subscriber_group">
                    <?php foreach ($groups as $group): ?>
                        <option value="<?php echo esc_attr($group->group_name); ?>">
                            <?php echo esc_html($group->group_name); ?>
                        </option>
                    <?php endforeach; ?>
                    <option value="new"><?php _e(
                    	'+ Create New Group',
                    	'music-label-demo-sender',
                    ); ?></option>
                </select>
                <input type="text" name="new_group_name" id="new_group_name" style="display: none;"
                       placeholder="<?php esc_attr_e(
                       	'Enter new group name',
                       	'music-label-demo-sender',
                       ); ?>">
            </div>
        </form>
    </div>

    <style>
        .search-filter-form {
            margin: 1em 0;
        }

        .form-field {
            margin-bottom: 1em;
        }

        .form-field label {
            display: block;
            margin-bottom: 0.5em;
            font-weight: bold;
        }

        .form-field input[type="text"],
        .form-field input[type="email"],
        .form-field select {
            width: 100%;
            padding: 5px;
        }

        .new-group-input {
            margin-left: 5px;
            vertical-align: middle;
        }

        .column-email {
            width: 30%;
        }

        .column-name {
            width: 20%;
        }

        .column-group {
            width: 20%;
        }

        .column-date {
            width: 20%;
        }
    </style>
    <?php
}
