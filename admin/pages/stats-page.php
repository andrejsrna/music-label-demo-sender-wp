<?php
// Prevent direct access to this file
if (!defined('ABSPATH')) {
	exit();
}

// Update stats page to include charts
function mlds_stats_page() {
	if (!current_user_can('manage_options')) {
		return;
	}

	$stats = mlds_get_cached_stats();
	?>
    <div class="wrap">
        <h1><?php _e('Demo Track Statistics', 'music-label-demo-sender'); ?></h1>

        <div class="mlds-stats-overview">
            <div class="mlds-stat-box">
                <h3><?php _e('Total Tracks', 'music-label-demo-sender'); ?></h3>
                <span class="stat-number"><?php echo $stats['total_stats']['tracks']; ?></span>
            </div>
            <div class="mlds-stat-box">
                <h3><?php _e('Total Emails', 'music-label-demo-sender'); ?></h3>
                <span class="stat-number"><?php echo $stats['total_stats']['emails']; ?></span>
            </div>
            <div class="mlds-stat-box">
                <h3><?php _e('Total Opens', 'music-label-demo-sender'); ?></h3>
                <span class="stat-number"><?php echo $stats['total_stats']['opens']; ?></span>
            </div>
            <div class="mlds-stat-box">
                <h3><?php _e('Total Feedback', 'music-label-demo-sender'); ?></h3>
                <span class="stat-number"><?php echo $stats['total_stats']['feedback']; ?></span>
            </div>
        </div>

        <div class="mlds-charts-container">
            <div class="mlds-chart-box">
                <h2><?php _e('Daily Activity', 'music-label-demo-sender'); ?></h2>
                <canvas id="dailyActivityChart"></canvas>
            </div>

            <div class="mlds-chart-box">
                <h2><?php _e('Track Performance', 'music-label-demo-sender'); ?></h2>
                <canvas id="trackPerformanceChart"></canvas>
            </div>
        </div>

        <!-- Feedback Data Section -->
        <div class="mlds-feedback-data">
            <h2><?php _e('Track Feedback Data', 'music-label-demo-sender'); ?></h2>

            <?php
            $tracks = get_posts([
            	'post_type' => 'attachment',
            	'post_mime_type' => 'audio',
            	'posts_per_page' => -1,
            	'orderby' => 'date',
            	'order' => 'DESC',
            	'meta_query' => [
            		[
            			'key' => '_mlds_feedback',
            			'compare' => 'EXISTS',
            		],
            	],
            ]);

            if (!empty($tracks)): ?>
                <div class="mlds-feedback-tracks">
                    <?php foreach ($tracks as $track):
                    	$feedback_data = get_post_meta($track->ID, '_mlds_feedback');
                    	if (!empty($feedback_data)):
                    		$avg_rating = get_post_meta(
                    			$track->ID,
                    			'_mlds_average_rating',
                    			true,
                    		); ?>
                            <div class="mlds-feedback-track">
                                <div class="track-header">
                                    <h3><?php echo esc_html($track->post_title); ?></h3>
                                    <div class="track-rating">
                                        <span class="rating-number"><?php echo number_format(
                                        	$avg_rating,
                                        	1,
                                        ); ?></span>
                                        <span class="rating-stars">
                                            <?php
                                            $full_stars = floor($avg_rating);
                                            $half_star = $avg_rating - $full_stars >= 0.5;

                                            for ($i = 1; $i <= 5; $i++) {
                                            	if ($i <= $full_stars) {
                                            		echo '<span class="star full">★</span>';
                                            	} elseif ($i == $full_stars + 1 && $half_star) {
                                            		echo '<span class="star half">★</span>';
                                            	} else {
                                            		echo '<span class="star empty">☆</span>';
                                            	}
                                            }
                                            ?>
                                        </span>
                                        <span class="rating-count">
                                            (<?php echo count(
                                            	$feedback_data,
                                            ); ?>                 <?php _e(
                 	'reviews',
                 	'music-label-demo-sender',
                 ); ?>)
                                        </span>
                                    </div>
                                </div>

                                <div class="feedback-list">
                                    <?php foreach ($feedback_data as $feedback): ?>
                                        <div class="feedback-item">
                                            <div class="feedback-header">
                                                <div class="feedback-meta">
                                                    <span class="feedback-author"><?php echo esc_html(
                                                    	$feedback['name'],
                                                    ); ?></span>
                                                    <span class="feedback-date">
                                                        <?php echo esc_html(
                                                        	date_i18n(
                                                        		get_option('date_format') .
                                                        			' ' .
                                                        			get_option('time_format'),
                                                        		strtotime($feedback['date']),
                                                        	),
                                                        ); ?>
                                                    </span>
                                                </div>
                                                <div class="feedback-rating">
                                                    <?php for ($i = 1; $i <= 5; $i++) {
                                                    	if ($i <= $feedback['rating']) {
                                                    		echo '<span class="star full">★</span>';
                                                    	} else {
                                                    		echo '<span class="star empty">☆</span>';
                                                    	}
                                                    } ?>
                                                </div>
                                            </div>
                                            <div class="feedback-content">
                                                <?php echo esc_html($feedback['feedback']); ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php
                    	endif;
                    endforeach; ?>
                </div>
            <?php else: ?>
                <p class="no-tracks-message"><?php _e(
                	'No demo tracks with feedback found.',
                	'music-label-demo-sender',
                ); ?></p>
            <?php endif;
            ?>
        </div>

        <table class="wp-list-table widefat fixed striped">
            <thead>
            <tr>
                <th><?php _e('Track', 'music-label-demo-sender'); ?></th>
                <th><?php _e('Emails Sent', 'music-label-demo-sender'); ?></th>
                <th><?php _e('Opens', 'music-label-demo-sender'); ?></th>
                <th><?php _e('Downloads', 'music-label-demo-sender'); ?></th>
                <th><?php _e('Feedback', 'music-label-demo-sender'); ?></th>
                <th><?php _e('Average Rating', 'music-label-demo-sender'); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php
            $all_tracks = get_posts([
            	'post_type' => 'attachment',
            	'post_mime_type' => 'audio',
            	'posts_per_page' => -1,
            	'orderby' => 'date',
            	'order' => 'DESC',
            ]);
            foreach ($all_tracks as $track): ?>
                <?php
                $emails_sent = mlds_count_emails_sent($track->ID);
                $opens = mlds_count_track_opens($track->ID);
                $downloads = mlds_count_track_downloads($track->ID);
                $feedback_count = mlds_count_track_feedback($track->ID);
                $avg_rating = get_post_meta($track->ID, '_mlds_average_rating', true);
                ?>
                <tr>
                    <td><?php echo esc_html($track->post_title); ?></td>
                    <td><?php echo $emails_sent; ?></td>
                    <td><?php echo $opens; ?></td>
                    <td><?php echo $downloads; ?></td>
                    <td><?php echo $feedback_count; ?></td>
                    <td><?php echo $avg_rating ? number_format($avg_rating, 1) . ' ★' : '-'; ?></td>
                </tr>
            <?php endforeach;?>
            </tbody>
        </table>
    </div>

    <style>
        .mlds-stats-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }

        .mlds-stat-box {
            background: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #0073aa;
        }

        .mlds-charts-container {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
            margin: 20px 0;
        }

        .mlds-chart-box {
            background: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        @media (min-width: 1200px) {
            .mlds-charts-container {
                grid-template-columns: 1fr 1fr;
            }
        }

        /* Feedback Data Styles */
        .mlds-feedback-data {
            margin-top: 30px;
            background: var(--dark-bg);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 0 20px var(--toxic-green),
            0 0 40px var(--toxic-purple);
        }

        .mlds-feedback-data h2 {
            color: var(--toxic-green);
            margin-bottom: 1.5em;
            text-shadow: 0 0 10px var(--toxic-green);
        }

        .mlds-feedback-track {
            background: rgba(43, 43, 43, 0.5); /* Semi-transparent background */
            -webkit-backdrop-filter: blur(10px); /* Frosted glass effect for Safari */
            backdrop-filter: blur(10px); /* Frosted glass effect */
            border-radius: 15px; /* Rounded corners */
            border: 1px solid rgba(255, 255, 255, 0.1); /* Subtle border */
            margin-bottom: 20px;
            overflow: hidden;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.2); /* Soft shadow */
        }

        .track-header {
            padding: 20px;
            background: rgba(153, 50, 204, 0.1);
            border-bottom: 1px solid var(--toxic-purple);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .track-header h3 {
            color: var(--toxic-green);
            margin: 0;
            text-shadow: 0 0 5px var(--toxic-green);
        }

        .track-rating {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .rating-number {
            font-size: 24px;
            font-weight: bold;
            color: var(--toxic-green);
        }

        .rating-stars {
            color: var(--toxic-green);
            font-size: 20px;
        }

        .rating-count {
            color: var(--toxic-purple);
            font-size: 0.9em;
        }

        .feedback-list {
            padding: 20px;
        }

        .feedback-item {
            background: rgba(50, 50, 50, 0.4); /* Slightly different transparency */
            -webkit-backdrop-filter: blur(5px);
            backdrop-filter: blur(5px);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .feedback-item:last-child {
            border-bottom: none;
            padding-bottom: 15px;
        }

        .feedback-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .feedback-meta {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .feedback-author {
            color: var(--toxic-green);
            font-weight: bold;
        }

        .feedback-date {
            color: var(--toxic-purple);
            font-size: 0.9em;
        }

        .feedback-rating {
            color: var(--toxic-green);
        }

        .feedback-content {
            color: var(--light-text);
            line-height: 1.6;
            margin-top: 10px;
        }

        .star {
            display: inline-block;
            transition: transform 0.2s;
        }

        .star.full {
            color: var(--toxic-green);
            text-shadow: 0 0 5px var(--toxic-green);
        }

        .star.half {
            position: relative;
            color: var(--toxic-green);
        }

        .star.half:after {
            content: '☆';
            position: absolute;
            left: 0;
            color: var(--toxic-purple);
            width: 50%;
            overflow: hidden;
        }

        .star.empty {
            color: var(--toxic-purple);
        }

        .no-tracks-message {
            color: var(--light-text);
            text-align: center;
            font-style: italic;
            padding: 20px;
        }

        @media (max-width: 768px) {
            .track-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .feedback-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .feedback-meta {
                flex-direction: column;
                gap: 5px;
            }
        }
    </style>
    <?php
}
