<?php
// Prevent direct access to this file
if (!defined('ABSPATH')) {
	exit();
}

// Update stats queries to filter for demo tracks
function mlds_get_quick_stats() {
	$seven_days_ago = date('Y-m-d H:i:s', strtotime('-7 days'));

	// Get total tracks
	$tracks = get_posts([
		'post_type' => 'post',
		'tax_query' => [
			[
				'taxonomy' => 'demo_track_type',
				'field' => 'slug',
				'terms' => 'demo-track',
			],
		],
		'posts_per_page' => -1,
		'fields' => 'ids',
	]);

	// Calculate recent activity
	global $wpdb;
	$recent_emails = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->postmeta}
        WHERE meta_key = '_mlds_email_sent'
        AND meta_value LIKE %s",
			'%' . $wpdb->esc_like($seven_days_ago) . '%',
		),
	);

	$recent_downloads = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->postmeta}
        WHERE meta_key = '_mlds_track_interaction'
        AND meta_value LIKE %s
        AND meta_value LIKE %s",
			'%download%',
			'%' . $wpdb->esc_like($seven_days_ago) . '%',
		),
	);

	// Calculate average rating
	$ratings = [];
	foreach ($tracks as $track_id) {
		$rating = get_post_meta($track_id, '_mlds_average_rating', true);
		if ($rating) {
			$ratings[] = floatval($rating);
		}
	}
	$avg_rating = !empty($ratings) ? array_sum($ratings) / count($ratings) : 0;

	return [
		'total_tracks' => count($tracks),
		'recent_emails' => intval($recent_emails),
		'recent_downloads' => intval($recent_downloads),
		'avg_rating' => $avg_rating,
	];
}

// Display recent activity
function mlds_display_recent_activity() {
	$activities = mlds_get_recent_activities(10);

	if (empty($activities)) {
		echo '<p>' . __('No recent activity.', 'music-label-demo-sender') . '</p>';
		return;
	}

	echo '<ul class="activity-list">';
	foreach ($activities as $activity) { ?>
        <li class="activity-item">
            <span class="dashicons <?php echo esc_attr($activity['icon']); ?> activity-icon"></span>
            <span class="activity-text"><?php echo esc_html($activity['text']); ?></span>
            <span class="activity-date"><?php echo esc_html($activity['date']); ?></span>
        </li>
        <?php }
	echo '</ul>';
}

// Get recent activities
function mlds_get_recent_activities($limit = 10) {
	global $wpdb;

	$activities = [];

	// Get recent emails, downloads, and feedback
	$meta_query = $wpdb->get_results(
		"SELECT p.post_title, pm.meta_key, pm.meta_value, pm.post_id
        FROM {$wpdb->postmeta} pm
        JOIN {$wpdb->posts} p ON p.ID = pm.post_id
        WHERE pm.meta_key IN ('_mlds_email_sent', '_mlds_track_interaction', '_mlds_feedback')
        ORDER BY pm.meta_id DESC
        LIMIT 50",
	);

	foreach ($meta_query as $row) {
		$meta_value = maybe_unserialize($row->meta_value);
		$activity = [];

		switch ($row->meta_key) {
			case '_mlds_email_sent':
				$activity = [
					'date' => $meta_value['date'],
					'icon' => 'dashicons-email',
					'text' => sprintf(
						__('Email sent for "%s"', 'music-label-demo-sender'),
						$row->post_title,
					),
				];
				break;

			case '_mlds_track_interaction':
				if ($meta_value['type'] === 'download') {
					$activity = [
						'date' => $meta_value['date'],
						'icon' => 'dashicons-download',
						'text' => sprintf(
							__('"%s" was downloaded', 'music-label-demo-sender'),
							$row->post_title,
						),
					];
				}
				break;

			case '_mlds_feedback':
				$activity = [
					'date' => $meta_value['date'],
					'icon' => 'dashicons-star-filled',
					'text' => sprintf(
						__('New feedback received for "%s"', 'music-label-demo-sender'),
						$row->post_title,
					),
				];
				break;
		}

		if (!empty($activity)) {
			$activity['date'] =
				human_time_diff(strtotime($activity['date']), current_time('timestamp')) .
				' ' .
				__('ago', 'music-label-demo-sender');
			$activities[] = $activity;
		}
	}

	// Sort by date and limit
	usort($activities, function ($a, $b) {
		return strtotime($b['date']) - strtotime($a['date']);
	});

	return array_slice($activities, 0, $limit);
}

// Cache management for stats
function mlds_get_cached_stats() {
	$cache_key = 'mlds_stats_data';
	$stats = get_transient($cache_key);

	if (false === $stats) {
		$stats = mlds_generate_stats_data();
		set_transient($cache_key, $stats, HOUR_IN_SECONDS);
	}

	return $stats;
}

// Generate comprehensive stats data
function mlds_generate_stats_data() {
	$tracks = get_posts([
		'post_type' => 'post',
		'tax_query' => [
			[
				'taxonomy' => 'demo_track_type',
				'field' => 'slug',
				'terms' => 'demo-track',
			],
		],
		'posts_per_page' => -1,
		'orderby' => 'date',
		'order' => 'DESC',
	]);

	$stats = [
		'daily_activity' => [],
		'track_stats' => [],
		'total_stats' => [
			'tracks' => count($tracks),
			'emails' => mlds_get_total_emails_sent(),
			'feedback' => mlds_get_total_feedback(),
			'opens' => 0,
			'downloads' => 0,
		],
	];

	// Get last 30 days of activity
	$thirty_days_ago = strtotime('-30 days');

	foreach ($tracks as $track) {
		$track_data = [
			'title' => $track->post_title,
			'emails' => mlds_count_emails_sent($track->ID),
			'opens' => mlds_count_track_opens($track->ID),
			'downloads' => mlds_count_track_downloads($track->ID),
			'feedback' => mlds_count_track_feedback($track->ID),
			'rating' => get_post_meta($track->ID, '_mlds_average_rating', true),
		];

		$stats['track_stats'][] = $track_data;
		$stats['total_stats']['opens'] += $track_data['opens'];
		$stats['total_stats']['downloads'] += $track_data['downloads'];

		// Get daily activity for this track
		$interactions = array_merge(
			get_post_meta($track->ID, '_mlds_track_interaction'),
			get_post_meta($track->ID, '_mlds_email_sent'),
			get_post_meta($track->ID, '_mlds_feedback'),
		);

		foreach ($interactions as $interaction) {
			$date = date('Y-m-d', strtotime($interaction['date']));
			if (!isset($stats['daily_activity'][$date])) {
				$stats['daily_activity'][$date] = [
					'opens' => 0,
					'downloads' => 0,
					'emails' => 0,
					'feedback' => 0,
				];
			}

			if (isset($interaction['type'])) {
				if ($interaction['type'] === 'view') {
					$stats['daily_activity'][$date]['opens']++;
				} elseif ($interaction['type'] === 'download') {
					$stats['daily_activity'][$date]['downloads']++;
				}
			} elseif (isset($interaction['status']) && $interaction['status'] === 'sent') {
				$stats['daily_activity'][$date]['emails']++;
			} elseif (isset($interaction['feedback'])) {
				$stats['daily_activity'][$date]['feedback']++;
			}
		}
	}

	// Sort daily activity by date
	ksort($stats['daily_activity']);

	return $stats;
}

// Helper functions for stats
function mlds_get_total_emails_sent() {
	global $wpdb;
	$count = $wpdb->get_var(
		"SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_mlds_email_sent'",
	);
	return intval($count);
}

function mlds_get_total_feedback() {
	global $wpdb;
	$count = $wpdb->get_var(
		"SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_mlds_feedback'",
	);
	return intval($count);
}

function mlds_count_emails_sent($post_id) {
	return count(get_post_meta($post_id, '_mlds_email_sent'));
}

function mlds_count_track_opens($post_id) {
	$interactions = get_post_meta($post_id, '_mlds_track_interaction');
	return count(
		array_filter($interactions, function ($interaction) {
			return isset($interaction['type']) && $interaction['type'] === 'view';
		}),
	);
}

function mlds_count_track_downloads($post_id) {
	$interactions = get_post_meta($post_id, '_mlds_track_interaction');
	return count(
		array_filter($interactions, function ($interaction) {
			return isset($interaction['type']) && $interaction['type'] === 'download';
		}),
	);
}

function mlds_count_track_feedback($post_id) {
	return count(get_post_meta($post_id, '_mlds_feedback'));
}
