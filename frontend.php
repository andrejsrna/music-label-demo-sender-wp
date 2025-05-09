<?php

// Register the subscriber form shortcode
add_shortcode( 'mlds_subscriber_form', 'mlds_subscriber_form_shortcode' );

// Register the unsubscribe form shortcode
add_shortcode( 'mlds_unsubscribe_form', 'mlds_unsubscribe_form_shortcode' );

// Register the listen modal shortcode
add_shortcode( 'mlds_listen_modal', 'mlds_listen_modal_shortcode' );

// Enqueue necessary scripts and styles
function mlds_enqueue_scripts() {
	wp_enqueue_script( 'jquery' );
	wp_enqueue_style(
		'mlds-frontend-styles',
		plugin_dir_url( __DIR__ ) . 'music-label-demo-sender/css/frontend.css',
		array(),
		'1.0.0'
	);
}
add_action( 'wp_enqueue_scripts', 'mlds_enqueue_scripts' );

// Add shortcode for frontend subscriber registration
function mlds_subscriber_form_shortcode( $atts ) {
	// Normalize attributes
	$atts = shortcode_atts(
		array(
			'group' => 'Default Group',
		),
		$atts,
		'mlds_subscriber_form'
	);

	// Generate unique form ID
	$form_id = 'mlds_subscriber_form_' . wp_rand();

	// Start output buffering
	ob_start();
	?>
	<div class="mlds-subscriber-form-wrapper">
		<form id="<?php echo esc_attr( $form_id ); ?>" class="mlds-subscriber-form" method="post" onsubmit="return false;">
			<?php wp_nonce_field( 'mlds_frontend_subscribe', 'mlds_frontend_nonce' ); ?>
			<input type="hidden" name="action" value="mlds_frontend_subscribe">
			<input type="hidden" name="group" value="<?php echo esc_attr( $atts['group'] ); ?>">

			<div class="mlds-form-group">
				<label for="<?php echo esc_attr( $form_id ); ?>_email">
					<?php _e( 'Email Address', 'music-label-demo-sender' ); ?> <span class="required">*</span>
				</label>
				<input type="email"
						id="<?php echo esc_attr( $form_id ); ?>_email"
						name="email"
						required>
			</div>

			<div class="mlds-form-group">
				<label for="<?php echo esc_attr( $form_id ); ?>_name">
					<?php _e( 'Your Name', 'music-label-demo-sender' ); ?>
				</label>
				<input type="text"
						id="<?php echo esc_attr( $form_id ); ?>_name"
						name="name">
			</div>

			<div class="mlds-form-group privacy-check">
				<label class="checkbox-label">
					<input type="checkbox"
							name="privacy_policy"
							required>
					<span class="checkbox-text">
						<?php _e( 'I agree to the', 'music-label-demo-sender' ); ?>
						<a href="<?php echo esc_url( get_privacy_policy_url() ); ?>" target="_blank">
							<?php _e( 'Privacy Policy', 'music-label-demo-sender' ); ?>
						</a>
					</span>
				</label>
			</div>

			<div class="mlds-form-submit">
				<button type="submit" class="mlds-submit-button">
					<?php _e( 'Subscribe Now', 'music-label-demo-sender' ); ?>
				</button>
				<div class="mlds-form-message"></div>
			</div>
		</form>
	</div>

	<script type="text/javascript">
	(function($) {
		$(document).ready(function() {
			$('#<?php echo esc_js( $form_id ); ?>').on('submit', function(e) {
				e.preventDefault(); // Prevent form submission

				const form = $(this);
				const submitButton = form.find('.mlds-submit-button');
				const messageDiv = form.find('.mlds-form-message');

				// Disable submit button and show loading state
				submitButton.prop('disabled', true).text('<?php _e( 'Subscribing...', 'music-label-demo-sender' ); ?>');

				// Clear previous messages
				messageDiv.removeClass('success error').hide();

				// Collect form data
				const formData = {
					action: 'mlds_frontend_subscribe',
					nonce: form.find('[name="mlds_frontend_nonce"]').val(),
					email: form.find('[name="email"]').val(),
					name: form.find('[name="name"]').val(),
					group: form.find('[name="group"]').val()
				};

				// Send AJAX request
				$.ajax({
					url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
					type: 'POST',
					data: formData,
					success: function(response) {
						if (response.success) {
							messageDiv.addClass('success')
									.html('<?php _e( 'Thank you for subscribing!', 'music-label-demo-sender' ); ?>')
									.fadeIn();
							form.trigger('reset');
						} else {
							messageDiv.addClass('error')
									.html(response.data.message || '<?php _e( 'Error subscribing. Please try again.', 'music-label-demo-sender' ); ?>')
									.fadeIn();
						}
					},
					error: function() {
						messageDiv.addClass('error')
								.html('<?php _e( 'Error subscribing. Please try again.', 'music-label-demo-sender' ); ?>')
								.fadeIn();
					},
					complete: function() {
						// Reset button state
						submitButton.prop('disabled', false).text('<?php _e( 'Subscribe Now', 'music-label-demo-sender' ); ?>');
					}
				});

				return false; // Additional prevention of form submission
			});
		});
	})(jQuery);
	</script>
	<?php
	return ob_get_clean();
}

// Handle frontend subscription AJAX request
function mlds_frontend_subscribe_callback() {
	check_ajax_referer( 'mlds_frontend_subscribe', 'nonce' );

	$email = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';
	$name  = isset( $_POST['name'] ) ? sanitize_text_field( $_POST['name'] ) : '';
	$group = isset( $_POST['group'] ) ? sanitize_text_field( $_POST['group'] ) : 'Default Group';

	if ( ! is_email( $email ) ) {
		wp_send_json_error( array( 'message' => __( 'Please enter a valid email address.', 'music-label-demo-sender' ) ) );
	}

	global $wpdb;
	$table_name = $wpdb->prefix . 'mlds_subscribers';

	// Check if email already exists
	$existing = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT id FROM $table_name WHERE email = %s",
			$email
		)
	);

	if ( $existing ) {
		wp_send_json_error( array( 'message' => __( 'This email is already subscribed.', 'music-label-demo-sender' ) ) );
	}

	// Insert new subscriber
	$result = $wpdb->insert(
		$table_name,
		array(
			'email'      => $email,
			'name'       => $name,
			'group_name' => $group,
			'date_added' => current_time( 'mysql' ),
		),
		array( '%s', '%s', '%s', '%s' )
	);

	if ( $result === false ) {
		wp_send_json_error( array( 'message' => __( 'Error adding subscriber. Please try again.', 'music-label-demo-sender' ) ) );
	}

	wp_send_json_success();
}
add_action( 'wp_ajax_mlds_frontend_subscribe', 'mlds_frontend_subscribe_callback' );
add_action( 'wp_ajax_nopriv_mlds_frontend_subscribe', 'mlds_frontend_subscribe_callback' );

// Unsubscribe form shortcode
function mlds_unsubscribe_form_shortcode( $atts ) {
	// Get email from URL if present
	$email = isset( $_GET['email'] ) ? sanitize_email( $_GET['email'] ) : '';
	$token = isset( $_GET['token'] ) ? sanitize_text_field( $_GET['token'] ) : '';

	// Generate unique form ID
	$form_id = 'mlds_unsubscribe_form_' . wp_rand();

	ob_start();
	?>
	<div class="mlds-subscriber-form-wrapper">
		<div class="mlds-form-header">
			<h2><?php _e( 'Unsubscribe', 'music-label-demo-sender' ); ?></h2>
			<p class="mlds-header-text"><?php _e( 'We\'re sorry to see you go! Please confirm your email below.', 'music-label-demo-sender' ); ?></p>
		</div>
		<form id="<?php echo esc_attr( $form_id ); ?>" class="mlds-subscriber-form" method="post" onsubmit="return false;">
			<?php wp_nonce_field( 'mlds_frontend_unsubscribe', 'mlds_unsubscribe_nonce' ); ?>
			<input type="hidden" name="action" value="mlds_frontend_unsubscribe">
			<input type="hidden" name="token" value="<?php echo esc_attr( $token ); ?>">

			<div class="mlds-form-group">
				<label for="<?php echo esc_attr( $form_id ); ?>_email">
					<?php _e( 'Email Address', 'music-label-demo-sender' ); ?> <span class="required">*</span>
				</label>
				<input type="email"
						id="<?php echo esc_attr( $form_id ); ?>_email"
						name="email"
						value="<?php echo esc_attr( $email ); ?>"
						required>
			</div>

			<div class="mlds-form-group">
				<label for="<?php echo esc_attr( $form_id ); ?>_reason">
					<?php _e( 'Reason (optional)', 'music-label-demo-sender' ); ?>
				</label>
				<textarea id="<?php echo esc_attr( $form_id ); ?>_reason"
						name="reason"
						rows="3"
						placeholder="<?php esc_attr_e( 'Please let us know why you\'re unsubscribing...', 'music-label-demo-sender' ); ?>"></textarea>
			</div>

			<div class="mlds-form-submit">
				<button type="submit" class="mlds-submit-button mlds-unsubscribe-button">
					<?php _e( 'Unsubscribe', 'music-label-demo-sender' ); ?>
				</button>
				<div class="mlds-form-message"></div>
			</div>
		</form>
	</div>

	<script type="text/javascript">
	(function($) {
		$(document).ready(function() {
			$('#<?php echo esc_js( $form_id ); ?>').on('submit', function(e) {
				e.preventDefault();

				const form = $(this);
				const submitButton = form.find('.mlds-submit-button');
				const messageDiv = form.find('.mlds-form-message');

				submitButton.prop('disabled', true).text('<?php _e( 'Processing...', 'music-label-demo-sender' ); ?>');
				messageDiv.removeClass('success error').hide();

				const formData = {
					action: 'mlds_frontend_unsubscribe',
					nonce: form.find('[name="mlds_unsubscribe_nonce"]').val(),
					email: form.find('[name="email"]').val(),
					reason: form.find('[name="reason"]').val(),
					token: form.find('[name="token"]').val()
				};

				$.ajax({
					url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
					type: 'POST',
					data: formData,
					success: function(response) {
						if (response.success) {
							messageDiv.addClass('success')
									.html('<?php _e( 'You have been successfully unsubscribed.', 'music-label-demo-sender' ); ?>')
									.fadeIn();
							form.find('input, textarea').prop('disabled', true);
							submitButton.hide();
						} else {
							messageDiv.addClass('error')
									.html(response.data.message || '<?php _e( 'Error processing request. Please try again.', 'music-label-demo-sender' ); ?>')
									.fadeIn();
							submitButton.prop('disabled', false).text('<?php _e( 'Unsubscribe', 'music-label-demo-sender' ); ?>');
						}
					},
					error: function() {
						messageDiv.addClass('error')
								.html('<?php _e( 'Error processing request. Please try again.', 'music-label-demo-sender' ); ?>')
								.fadeIn();
						submitButton.prop('disabled', false).text('<?php _e( 'Unsubscribe', 'music-label-demo-sender' ); ?>');
					}
				});

				return false;
			});
		});
	})(jQuery);
	</script>
	<?php
	return ob_get_clean();
}

// Handle unsubscribe AJAX request
function mlds_frontend_unsubscribe_callback() {
	check_ajax_referer( 'mlds_frontend_unsubscribe', 'nonce' );

	$email  = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';
	$reason = isset( $_POST['reason'] ) ? sanitize_textarea_field( $_POST['reason'] ) : '';
	$token  = isset( $_POST['token'] ) ? sanitize_text_field( $_POST['token'] ) : '';

	if ( ! is_email( $email ) ) {
		wp_send_json_error( array( 'message' => __( 'Please enter a valid email address.', 'music-label-demo-sender' ) ) );
	}

	global $wpdb;
	$table_name = $wpdb->prefix . 'mlds_subscribers';

	// Check if email exists
	$subscriber = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM $table_name WHERE email = %s",
			$email
		)
	);

	if ( ! $subscriber ) {
		wp_send_json_error( array( 'message' => __( 'This email is not in our subscriber list.', 'music-label-demo-sender' ) ) );
	}

	// Store unsubscribe reason if provided
	if ( ! empty( $reason ) ) {
		$wpdb->insert(
			$wpdb->prefix . 'mlds_unsubscribe_feedback',
			array(
				'email'             => $email,
				'reason'            => $reason,
				'date_unsubscribed' => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s' )
		);
	}

	// Delete subscriber
	$result = $wpdb->delete(
		$table_name,
		array( 'email' => $email ),
		array( '%s' )
	);

	if ( $result === false ) {
		wp_send_json_error( array( 'message' => __( 'Error processing unsubscribe request. Please try again.', 'music-label-demo-sender' ) ) );
	}

	wp_send_json_success();
}
add_action( 'wp_ajax_mlds_frontend_unsubscribe', 'mlds_frontend_unsubscribe_callback' );
add_action( 'wp_ajax_nopriv_mlds_frontend_unsubscribe', 'mlds_frontend_unsubscribe_callback' );

// Create unsubscribe feedback table on plugin activation
function mlds_create_unsubscribe_feedback_table() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'mlds_unsubscribe_feedback';

	// Check if table exists first using SHOW TABLES
	$table_exists = $wpdb->get_var(
		$wpdb->prepare(
			'SHOW TABLES LIKE %s',
			$table_name
		)
	);

	if ( ! $table_exists ) {
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            email varchar(100) NOT NULL,
            reason text,
            date_unsubscribed datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY email (email),
            KEY date_unsubscribed (date_unsubscribed)
        ) $charset_collate;";

		include_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}
}
add_action( 'admin_init', 'mlds_create_unsubscribe_feedback_table' );

// Listen Modal shortcode
function mlds_listen_modal_shortcode( $atts ) {
	// Generate unique modal ID
	$modal_id = 'mlds_listen_modal_' . wp_rand();

	ob_start();
	?>
	<div class="mlds-listen-wrapper">
		<button class="mlds-listen-button" data-modal="<?php echo esc_attr( $modal_id ); ?>">
			🎧 Listen Now
		</button>

		<div id="<?php echo esc_attr( $modal_id ); ?>" class="mlds-modal">
			<div class="mlds-modal-content">
				<span class="mlds-modal-close">&times;</span>
				<h2>Listen to DNB Doctor</h2>
				<div class="mlds-streaming-links">
					<a href="https://soundcloud.com/dnbdoctor" target="_blank" class="mlds-stream-button soundcloud">
						<svg viewBox="0 0 24 24" class="service-icon"><path d="M1.175 12.225c-.051 0-.094.046-.101.1l-.233 2.154.233 2.105c.007.058.05.098.101.098.05 0 .09-.04.099-.098l.255-2.105-.27-2.154c-.009-.06-.04-.1-.09-.1m-.899.828c-.06 0-.091.037-.104.094L0 14.479l.165 1.308c.013.057.045.093.104.093.057 0 .091-.039.104-.093l.196-1.308-.196-1.332c-.013-.057-.047-.094-.104-.094M2.669 11.822c-.041-.052-.091-.078-.153-.078-.069 0-.116.029-.153.078-.054.093-.1.227-.135.41l-.21 2.347.224 2.384c.012.058.058.087.122.087.061 0 .112-.029.135-.087l.255-2.384-.254-2.347c-.011-.058-.06-.087-.122-.087m1.595-.028c-.069 0-.117.03-.154.079-.041.09-.091.22-.136.407l-.202 2.296.218 2.38c.018.088.06.132.138.132.068 0 .115-.03.15-.079l.255-2.433-.254-2.296c-.016-.088-.059-.132-.138-.132m1.671-.023c-.078 0-.131.029-.164.089-.043.095-.088.226-.133.411l-.189 2.228.211 2.371c.018.088.069.133.164.133.077 0 .13-.029.164-.088l.234-2.416-.234-2.228c-.018-.088-.069-.133-.164-.133m1.671-.023c-.084 0-.145.03-.175.089-.046.095-.087.226-.132.411l-.178 2.161.199 2.364c.018.089.074.134.175.134.084 0 .145-.029.175-.089l.215-2.409-.215-2.161c-.018-.089-.074-.134-.175-.134m1.956-.067c-.081 0-.143.03-.193.09-.031.088-.076.219-.126.402l-.156 2.136.178 2.347c.018.088.078.132.193.132.085 0 .145-.029.175-.088l.203-2.391-.203-2.136c-.018-.089-.078-.133-.193-.133m1.677-.032c-.095 0-.165.03-.203.09-.031.088-.072.219-.121.402l-.146 2.116.166 2.334c.018.089.085.133.203.133.096 0 .165-.029.203-.089l.184-2.378-.184-2.116c-.018-.089-.085-.133-.203-.133m1.765-.039c-.101 0-.176.03-.213.09-.027.088-.068.219-.117.402l-.135 2.093.156 2.321c.018.089.092.134.213.134.102 0 .176-.029.214-.089l.164-2.366-.164-2.093c-.018-.089-.092-.134-.213-.134m1.758-.052c-.108 0-.187.029-.223.09-.025.087-.064.218-.112.401l-.125 2.073.146 1.609.002.694c.001.088.098.134.222.134.11 0 .189-.029.226-.089l.154-2.348-.154-2.073c-.018-.089-.098-.134-.223-.134m1.783.165c-.028.085-.061.217-.107.4l-.115 2.053.135 2.324c.018.089.104.135.233.135.117 0 .197-.029.235-.089l.143-2.37-.143-2.053c-.018-.089-.105-.134-.235-.134-.111 0-.194.045-.233.089l.002-.012.012-.032M1.775 16.147c-.117 0-.202-.086-.202-.194V12.76c0-.107.085-.193.202-.193s.201.086.201.193v3.193c0 .108-.084.194-.201.194M4.029 16.147c-.118 0-.202-.086-.202-.194V12.76c0-.107.084-.193.202-.193.117 0 .201.086.201.193v3.193c0 .108-.084.194-.201.194M6.282 16.147c-.117 0-.202-.086-.202-.194V12.76c0-.107.085-.193.202-.193s.201.086.201.193v3.193c0 .108-.084.194-.201.194M8.535 16.147c-.117 0-.202-.086-.202-.194V12.76c0-.107.085-.193.202-.193s.201.086.201.193v3.193c0 .108-.084.194-.201.194M10.789 16.147c-.117 0-.202-.086-.202-.194V12.76c0-.107.085-.193.202-.193s.201.086.201.193v3.193c0 .108-.084.194-.201.194M13.042 16.147c-.117 0-.202-.086-.202-.194V12.76c0-.107.085-.193.202-.193s.201.086.201.193v3.193c0 .108-.084.194-.201.194M15.296 16.147c-.117 0-.202-.086-.202-.194V12.76c0-.107.085-.193.202-.193s.201.086.201.193v3.193c0 .108-.084.194-.201.194M17.549 16.147c-.117 0-.202-.086-.202-.194V12.76c0-.107.085-.193.202-.193s.201.086.201.193v3.193c0 .108-.084.194-.201.194M19.803 16.147c-.117 0-.202-.086-.202-.194V12.76c0-.107.085-.193.202-.193s.201.086.201.193v3.193c0 .108-.084.194-.201.194M22.057 16.147c-.117 0-.202-.086-.202-.194V12.76c0-.107.085-.193.202-.193s.201.086.201.193v3.193c0 .108-.084.194-.201.194"/></svg>
						SoundCloud
					</a>
					<a href="https://open.spotify.com/playlist/2GD72ly17HcWc9OAEtdUBP?si=22189a0e625f4768" target="_blank" class="mlds-stream-button spotify">
						<svg viewBox="0 0 24 24" class="service-icon"><path d="M12 0C5.4 0 0 5.4 0 12s5.4 12 12 12 12-5.4 12-12S18.66 0 12 0zm5.521 17.34c-.24.359-.66.48-1.021.24-2.82-1.74-6.36-2.101-10.561-1.141-.418.122-.779-.179-.899-.539-.12-.421.18-.78.54-.9 4.56-1.021 8.52-.6 11.64 1.32.42.18.479.659.301 1.02zm1.44-3.3c-.301.42-.841.6-1.262.3-3.239-1.98-8.159-2.58-11.939-1.38-.479.12-1.02-.12-1.14-.6-.12-.48.12-1.021.6-1.141C9.6 9.9 15 10.561 18.72 12.84c.361.181.54.78.241 1.2zm.12-3.36C15.24 8.4 8.82 8.16 5.16 9.301c-.6.179-1.2-.181-1.38-.721-.18-.601.18-1.2.72-1.381 4.26-1.26 11.28-1.02 15.721 1.621.539.3.719 1.02.419 1.56-.299.421-1.02.599-1.559.3z"/></svg>
						Spotify
					</a>
					<a href="https://tidal.com/browse/artist/42587754?u" target="_blank" class="mlds-stream-button tidal">
						<svg viewBox="0 0 24 24" class="service-icon"><path d="M12.012 3.992L8.008 7.996 4.004 3.992 0 7.996 4.004 12l4.004-4.004L12.012 12l-4.004 4.004 4.004 4.004 4.004-4.004L12.012 12l4.004-4.004-4.004-4.004zM16.016 7.996l4.004-4.004L24.024 7.996l-4.004 4.004z"/></svg>
						TIDAL
					</a>
					<a href="https://www.youtube.com/@dnbdoctor1" target="_blank" class="mlds-stream-button youtube">
						<svg viewBox="0 0 24 24" class="service-icon"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
						YouTube
					</a>
					<a href="https://deezer.page.link/8LkCsRmPWNVLgbGm9" target="_blank" class="mlds-stream-button deezer">
						<svg viewBox="0 0 24 24" class="service-icon"><path d="M18.81 4.16v3.03H24V4.16h-5.19zM6.27 8.38v3.027h5.189V8.38h-5.19zm12.54 0v3.027H24V8.38h-5.19zM6.27 12.594v3.027h5.189v-3.027h-5.19zm6.271 0v3.027h5.19v-3.027h-5.19zm6.27 0v3.027H24v-3.027h-5.19zM0 16.81v3.029h5.19v-3.03H0zm6.27 0v3.029h5.189v-3.03h-5.19zm6.271 0v3.029h5.19v-3.03h-5.19zm6.27 0v3.029H24v-3.03h-5.19z"/></svg>
						Deezer
					</a>
				</div>
			</div>
		</div>
	</div>

	<style>
		.mlds-listen-button {
			background: linear-gradient(45deg, var(--toxic-purple), var(--toxic-green));
			color: white;
			border: none;
			padding: 15px 30px;
			border-radius: 25px;
			font-size: 18px;
			font-weight: bold;
			cursor: pointer;
			transition: all 0.3s ease;
			text-transform: uppercase;
			letter-spacing: 1px;
			box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
		}

		.mlds-listen-button:hover {
			transform: translateY(-2px);
			box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
		}

		.mlds-modal {
			display: none;
			position: fixed;
			z-index: 9999;
			left: 0;
			top: 0;
			width: 100%;
			height: 100%;
			background-color: rgba(0, 0, 0, 0.9);
			backdrop-filter: blur(5px);
		}

		.mlds-modal-content {
			background: var(--dark-bg);
			margin: 5% auto;
			padding: 40px;
			width: 90%;
			max-width: 600px;
			border-radius: 15px;
			position: relative;
			box-shadow: 0 0 20px var(--toxic-green),
						0 0 40px var(--toxic-purple);
		}

		.mlds-modal-content h2 {
			color: var(--toxic-green);
			text-align: center;
			margin-bottom: 30px;
			font-size: 28px;
			text-shadow: 0 0 10px var(--toxic-green);
		}

		.mlds-modal-close {
			position: absolute;
			right: 20px;
			top: 20px;
			color: var(--light-text);
			font-size: 28px;
			font-weight: bold;
			cursor: pointer;
			transition: all 0.3s ease;
		}

		.mlds-modal-close:hover {
			color: var(--toxic-green);
		}

		.mlds-streaming-links {
			display: grid;
			gap: 15px;
			grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
		}

		.mlds-stream-button {
			display: flex;
			align-items: center;
			justify-content: center;
			gap: 10px;
			padding: 15px 20px;
			border-radius: 8px;
			color: white;
			font-weight: bold;
			text-decoration: none;
			transition: all 0.3s ease;
		}

		.service-icon {
			width: 24px;
			height: 24px;
			fill: currentColor;
		}

		.mlds-stream-button.soundcloud {
			background-color: #ff5500;
		}

		.mlds-stream-button.spotify {
			background-color: #1DB954;
		}

		.mlds-stream-button.tidal {
			background-color: #000000;
		}

		.mlds-stream-button.youtube {
			background-color: #FF0000;
		}

		.mlds-stream-button.deezer {
			background-color: #00C7F2;
		}

		.mlds-stream-button:hover {
			transform: translateY(-2px);
			filter: brightness(1.1);
			box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
		}

		@media (max-width: 768px) {
			.mlds-modal-content {
				margin: 10% auto;
				padding: 20px;
				width: 95%;
			}

			.mlds-streaming-links {
				grid-template-columns: 1fr;
			}
		}
	</style>

	<script>
	jQuery(document).ready(function($) {
		// Open modal
		$('.mlds-listen-button').click(function() {
			const modalId = $(this).data('modal');
			$(`#${modalId}`).fadeIn(300);
			$('body').css('overflow', 'hidden');
		});

		// Close modal
		$('.mlds-modal-close').click(function() {
			$(this).closest('.mlds-modal').fadeOut(300);
			$('body').css('overflow', 'auto');
		});

		// Close modal when clicking outside
		$('.mlds-modal').click(function(e) {
			if (e.target === this) {
				$(this).fadeOut(300);
				$('body').css('overflow', 'auto');
			}
		});

		// Close modal on escape key
		$(document).keyup(function(e) {
			if (e.key === "Escape") {
				$('.mlds-modal').fadeOut(300);
				$('body').css('overflow', 'auto');
			}
		});
	});
	</script>
	<?php
	return ob_get_clean();
}
