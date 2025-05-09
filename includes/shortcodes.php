<?php
// Prevent direct access to this file
if (!defined('ABSPATH')) {
	exit();
}

// Register feedback shortcode and form handler
function mlds_register_feedback_form() {
	add_shortcode('mlds_feedback_form', 'mlds_feedback_form_shortcode');

	// Handle form submission
	if (isset($_POST['mlds_feedback_submit']) && isset($_POST['mlds_feedback_nonce'])) {
		if (wp_verify_nonce($_POST['mlds_feedback_nonce'], 'mlds_submit_feedback')) {
			mlds_handle_feedback_submission();
		}
	}
}
add_action('init', 'mlds_register_feedback_form');

// Feedback form shortcode
function mlds_feedback_form_shortcode($atts) {
	// Check if we have valid track and token
	if (!isset($_GET['track']) || !isset($_GET['token'])) {
		return __('Invalid feedback link.', 'music-label-demo-sender');
	}

	$post_id = intval($_GET['track']);
	$token = sanitize_text_field($_GET['token']);

	// Verify token
	$stored_token = get_post_meta($post_id, '_mlds_track_token', true);
	if ($token !== $stored_token) {
		return __('Invalid feedback link.', 'music-label-demo-sender');
	}

	// Get track information
	$track_title = get_the_title($post_id);
	$attachment_id = get_post_meta($post_id, '_mlds_attachment_id', true);

	if (!$attachment_id) {
		return __('Track not found.', 'music-label-demo-sender');
	}

	$track_url = wp_get_attachment_url($attachment_id);

	if (!$track_url) {
		return __('Track file not found.', 'music-label-demo-sender');
	}

	ob_start();
	?>
    <div class="mlds-feedback-form">
        <div class="mlds-logo">
            <img src="https://admin.dnbdoctor.com/wp-content/uploads/2023/12/Artboard-66-300x243.png"
                 alt="DNB Doctor Logo">
        </div>
        <h2><?php echo esc_html(
        	sprintf(__('Feedback for: %s', 'music-label-demo-sender'), $track_title),
        ); ?></h2>

        <!-- Audio Player Section -->
        <div class="mlds-audio-section">
            <audio controls class="mlds-audio-player">
                <source src="<?php echo esc_url($track_url); ?>" type="audio/mpeg">
                <?php _e(
                	'Your browser does not support the audio element.',
                	'music-label-demo-sender',
                ); ?>
            </audio>
            <a href="<?php echo esc_url($track_url); ?>" download class="mlds-download-button">
                <?php _e('Download Track', 'music-label-demo-sender'); ?>
            </a>
        </div>

        <form method="post" class="mlds-form">
            <?php wp_nonce_field('mlds_submit_feedback', 'mlds_feedback_nonce'); ?>
            <input type="hidden" name="track_id" value="<?php echo esc_attr($post_id); ?>">

            <div class="mlds-form-group">
                <label for="mlds_rating"><?php _e('Rating', 'music-label-demo-sender'); ?></label>
                <select name="rating" id="mlds_rating" required>
                    <option value=""><?php _e(
                    	'Select rating...',
                    	'music-label-demo-sender',
                    ); ?></option>
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                        <option value="<?php echo $i; ?>">
                            <?php echo str_repeat('★', $i) . str_repeat('☆', 5 - $i); ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="mlds-form-group">
                <label for="mlds_feedback"><?php _e(
                	'Your Feedback',
                	'music-label-demo-sender',
                ); ?></label>
                <textarea id="mlds_feedback" name="feedback" rows="5" required
                          placeholder="<?php esc_attr_e(
                          	'Please share your thoughts about this track...',
                          	'music-label-demo-sender',
                          ); ?>"></textarea>
            </div>

            <div class="mlds-form-group">
                <label for="mlds_name"><?php _e('Your Name', 'music-label-demo-sender'); ?></label>
                <input type="text" id="mlds_name" name="name" required
                       placeholder="<?php esc_attr_e(
                       	'Enter your name',
                       	'music-label-demo-sender',
                       ); ?>">
            </div>

            <button type="submit" name="mlds_feedback_submit" class="mlds-submit-button">
                <?php _e('Submit Feedback', 'music-label-demo-sender'); ?>
            </button>
        </form>
    </div>

    <style>
        :root {
            --toxic-green: #39FF14;
            --toxic-purple: #9932CC;
            --dark-bg: #1a1a1a;
            --light-text: #ffffff;
        }

        .mlds-feedback-form {
            max-width: 600px;
            margin: 2em auto;
            padding: 30px;
            background: var(--dark-bg);
            border-radius: 15px;
            box-shadow: 0 0 20px var(--toxic-green),
            0 0 40px var(--toxic-purple);
            color: var(--light-text);
        }

        .mlds-logo {
            text-align: center;
            margin-bottom: 2em;
        }

        .mlds-logo img {
            max-width: 200px;
            height: auto;
        }

        .mlds-feedback-form h2 {
            color: var(--toxic-green);
            text-align: center;
            margin-bottom: 1.5em;
            text-shadow: 0 0 10px var(--toxic-green);
        }

        .mlds-form-group {
            margin-bottom: 1.5em;
        }

        .mlds-form-group label {
            display: block;
            margin-bottom: 0.5em;
            font-weight: bold;
            color: var(--toxic-purple);
            text-shadow: 0 0 5px var(--toxic-purple);
        }

        .mlds-form-group input,
        .mlds-form-group textarea,
        .mlds-form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid var(--toxic-purple);
            border-radius: 8px;
            background: rgba(26, 26, 26, 0.8);
            color: var(--light-text);
            transition: all 0.3s ease;
        }

        .mlds-form-group input:focus,
        .mlds-form-group textarea:focus,
        .mlds-form-group select:focus {
            outline: none;
            border-color: var(--toxic-green);
            box-shadow: 0 0 10px var(--toxic-green);
        }

        .mlds-form-group input::placeholder,
        .mlds-form-group textarea::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        .mlds-submit-button {
            background: var(--toxic-purple);
            color: var(--light-text);
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 1em;
        }

        .mlds-submit-button:hover {
            background: var(--toxic-green);
            box-shadow: 0 0 15px var(--toxic-green);
            transform: translateY(-2px);
        }

        /* Custom select styling */
        select#mlds_rating {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%2339FF14'%3e%3cpath d='M7 10l5 5 5-5z'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 20px;
            padding-right: 40px;
        }

        /* Star rating color */
        select#mlds_rating option {
            background-color: var(--dark-bg);
            color: var(--toxic-green);
        }

        /* Audio Player Styles */
        .mlds-audio-section {
            margin-bottom: 2em;
            text-align: center;
        }

        .mlds-audio-player {
            width: 100%;
            margin-bottom: 1em;
            border-radius: 8px;
            background: rgba(26, 26, 26, 0.8);
        }

        .mlds-audio-player::-webkit-media-controls-panel {
            background: var(--dark-bg);
        }

        .mlds-audio-player::-webkit-media-controls-current-time-display,
        .mlds-audio-player::-webkit-media-controls-time-remaining-display {
            color: var(--toxic-green);
        }

        .mlds-audio-player::-webkit-media-controls-play-button,
        .mlds-audio-player::-webkit-media-controls-mute-button {
            background-color: var(--toxic-purple);
            border-radius: 50%;
        }

        .mlds-download-button {
            display: inline-block;
            background: var(--toxic-purple);
            color: var(--light-text);
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            margin-bottom: 2em;
        }

        .mlds-download-button:hover {
            background: var(--toxic-green);
            box-shadow: 0 0 15px var(--toxic-green);
            transform: translateY(-2px);
            color: var(--light-text);
            text-decoration: none;
        }
    </style>
    <?php return ob_get_clean();
}

// Handle feedback submission
function mlds_handle_feedback_submission() {
	$track_id = isset($_POST['track_id']) ? intval($_POST['track_id']) : 0;
	if (!$track_id) {
		return;
	}

	// Sanitize input
	$feedback_data = [
		'rating' => isset($_POST['rating']) ? intval($_POST['rating']) : 0,
		'feedback' => isset($_POST['feedback']) ? sanitize_textarea_field($_POST['feedback']) : '',
		'name' => isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '',
		'date' => current_time('mysql'),
		'ip' => sanitize_text_field($_SERVER['REMOTE_ADDR']),
	];

	// Save feedback
	add_post_meta($track_id, '_mlds_feedback', $feedback_data);

	// Update average rating
	$all_ratings = get_post_meta($track_id, '_mlds_feedback');
	$rating_sum = 0;
	$rating_count = 0;

	foreach ($all_ratings as $rating) {
		if (isset($rating['rating']) && $rating['rating'] > 0) {
			$rating_sum += $rating['rating'];
			$rating_count++;
		}
	}

	if ($rating_count > 0) {
		$average_rating = round($rating_sum / $rating_count, 1);
		update_post_meta($track_id, '_mlds_average_rating', $average_rating);
	}

	// Redirect to thanks page
	wp_redirect(home_url('/thanks'));
	exit();
}

// Display feedback success message
function mlds_feedback_messages() {
	if (isset($_GET['feedback']) && $_GET['feedback'] === 'success') { ?>
        <div class="mlds-feedback-message success">
            <p><?php _e('Thank you for your feedback!', 'music-label-demo-sender'); ?></p>
        </div>
        <style>
            .mlds-feedback-message {
                max-width: 600px;
                margin: 2em auto;
                padding: 15px;
                border-radius: 4px;
                text-align: center;
            }

            .mlds-feedback-message.success {
                background: #dff0d8;
                color: #3c763d;
                border: 1px solid #d6e9c6;
            }
        </style>
        <?php }
}
add_action('wp_footer', 'mlds_feedback_messages');
?> 