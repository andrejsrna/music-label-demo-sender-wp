<?php

// Register REST API endpoints
function mlds_register_api_endpoints() {
	// Register test endpoint (GET method)
	register_rest_route(
		'mlds/v1',
		'/test',
		array(
			'methods'             => 'GET',
			'callback'            => 'mlds_api_test',
			'permission_callback' => '__return_true',
		)
	);

	// Register endpoint for feedback submission
	register_rest_route(
		'mlds/v1',
		'/feedback',
		array(
			'methods'             => WP_REST_Server::CREATABLE, // POST
			'callback'            => 'mlds_api_submit_feedback',
			'permission_callback' => '__return_true',
			'args'                => array(
				'track_id' => array(
					'required'          => true,
					'type'              => 'integer',
					'sanitize_callback' => 'absint',
				),
				'token'    => array(
					'required'          => false,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'rating'   => array(
					'required'          => true,
					'type'              => 'integer',
					'minimum'           => 1,
					'maximum'           => 5,
					'sanitize_callback' => 'absint',
				),
				'feedback' => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_textarea_field',
				),
				'name'     => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
		)
	);

	// Register endpoint for getting track info
	register_rest_route(
		'mlds/v1',
		'/track-info',
		array(
			'methods'             => 'GET',
			'callback'            => 'mlds_api_get_track_info',
			'permission_callback' => '__return_true',
			'args'                => array(
				'track_id' => array(
					'required'          => true,
					'type'              => 'integer',
					'sanitize_callback' => 'absint',
				),
				'token'    => array(
					'required'          => false,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
		)
	);

	// Register endpoint for adding subscribers
	register_rest_route(
		'mlds/v1',
		'/subscribe',
		array(
			'methods'             => 'POST',
			'callback'            => 'mlds_api_add_subscriber',
			'permission_callback' => '__return_true',
			'args'                => array(
				'email' => array(
					'required'          => true,
					'type'              => 'string',
					'format'            => 'email',
					'sanitize_callback' => 'sanitize_email',
				),
				'name'  => array(
					'required'          => false,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'group' => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
		)
	);

	// Register endpoint for unsubscribing
	register_rest_route(
		'mlds/v1',
		'/unsubscribe',
		array(
			'methods'             => 'POST',
			'callback'            => 'mlds_api_unsubscribe',
			'permission_callback' => '__return_true',
			'args'                => array(
				'email' => array(
					'required'          => true,
					'type'              => 'string',
					'format'            => 'email',
					'sanitize_callback' => 'sanitize_email',
				),
				'token' => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
		)
	);

	// Register endpoint for updating likes/dislikes
	register_rest_route(
		'mlds/v1',
		'/update-reaction',
		array(
			'methods'             => WP_REST_Server::CREATABLE, // POST
			'callback'            => 'mlds_api_update_reaction',
			'permission_callback' => '__return_true',
			'args'                => array(
				'post_id'       => array(
					'required'          => true,
					'type'              => 'integer',
					'sanitize_callback' => 'absint',
				),
				'reaction_type' => array(
					'required'          => true,
					'type'              => 'string',
					'enum'              => array( 'like', 'dislike' ),
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
		)
	);

	// Register endpoint for getting reactions
	register_rest_route(
		'mlds/v1',
		'/reactions',
		array(
			'methods'             => 'GET',
			'callback'            => 'mlds_api_get_reactions',
			'permission_callback' => '__return_true',
			'args'                => array(
				'postId' => array(
					'required'          => true,
					'type'              => 'integer',
					'sanitize_callback' => 'absint',
				),
			),
		)
	);

	// Register endpoint for updating reactions
	register_rest_route(
		'mlds/v1',
		'/reactions',
		array(
			'methods'             => 'POST',
			'callback'            => 'mlds_api_update_reaction',
			'permission_callback' => '__return_true',
			'args'                => array(
				'post_id'       => array(
					'required'          => true,
					'type'              => 'integer',
					'sanitize_callback' => 'absint',
				),
				'reaction_type' => array(
					'required'          => true,
					'type'              => 'string',
					'enum'              => array( 'like', 'dislike' ),
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'mlds_register_api_endpoints' );

// Callback for adding subscribers
function mlds_api_add_subscriber( $request ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'mlds_subscribers';

	// Get parameters
	$email = $request->get_param( 'email' );
	$name  = $request->get_param( 'name' );
	$group = $request->get_param( 'group' );

	// Check if email already exists
	$existing = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT id FROM $table_name WHERE email = %s",
			$email
		)
	);

	if ( $existing ) {
		return new WP_Error(
			'subscriber_exists',
			__( 'This email is already subscribed.', 'music-label-demo-sender' ),
			array( 'status' => 409 )
		);
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
		return new WP_Error(
			'insert_failed',
			__( 'Failed to add subscriber.', 'music-label-demo-sender' ),
			array( 'status' => 500 )
		);
	}

	// Return success response
	return new WP_REST_Response(
		array(
			'message'       => __( 'Successfully subscribed!', 'music-label-demo-sender' ),
			'subscriber_id' => $wpdb->insert_id,
		),
		201
	);
}

// Callback for unsubscribing
function mlds_api_unsubscribe( $request ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'mlds_subscribers';

	// Get parameters
	$email = $request->get_param( 'email' );
	$token = $request->get_param( 'token' );

	// Verify unsubscribe token
	if ( ! wp_verify_nonce( $token, 'unsubscribe_' . $email ) ) {
		return new WP_Error(
			'invalid_token',
			__( 'Invalid unsubscribe token.', 'music-label-demo-sender' ),
			array( 'status' => 403 )
		);
	}

	// Delete subscriber
	$result = $wpdb->delete(
		$table_name,
		array( 'email' => $email ),
		array( '%s' )
	);

	if ( $result === false ) {
		return new WP_Error(
			'delete_failed',
			__( 'Failed to unsubscribe.', 'music-label-demo-sender' ),
			array( 'status' => 500 )
		);
	}

	if ( $result === 0 ) {
		return new WP_Error(
			'not_found',
			__( 'Email not found in our subscriber list.', 'music-label-demo-sender' ),
			array( 'status' => 404 )
		);
	}

	// Return success response
	return new WP_REST_Response(
		array(
			'message' => __( 'Successfully unsubscribed!', 'music-label-demo-sender' ),
		),
		200
	);
}

// Helper function to generate unsubscribe token
function mlds_generate_unsubscribe_token( $email ) {
	return wp_create_nonce( 'unsubscribe_' . $email );
}

// Add CORS headers
function mlds_add_cors_headers() {
	header( 'Access-Control-Allow-Origin: *' );
	header( 'Access-Control-Allow-Methods: POST, GET, OPTIONS' );
	header( 'Access-Control-Allow-Headers: Content-Type' );

	if ( 'OPTIONS' === $_SERVER['REQUEST_METHOD'] ) {
		status_header( 200 );
		exit();
	}
}
add_action( 'init', 'mlds_add_cors_headers' );

// Callback for submitting feedback
function mlds_api_submit_feedback( $request ) {
	$track_id = $request->get_param( 'track_id' );
	$token    = $request->get_param( 'token' );

	// Only verify token if it's provided
	if ( $token ) {
		$stored_token = get_post_meta( $track_id, '_mlds_track_token', true );
		if ( $token !== $stored_token ) {
			return new WP_Error(
				'invalid_token',
				__( 'Invalid feedback token.', 'music-label-demo-sender' ),
				array( 'status' => 403 )
			);
		}
	}

	// Save feedback data
	$feedback_data = array(
		'rating'   => $request->get_param( 'rating' ),
		'feedback' => $request->get_param( 'feedback' ),
		'name'     => $request->get_param( 'name' ),
		'date'     => current_time( 'mysql' ),
		'ip'       => $_SERVER['REMOTE_ADDR'],
	);

	// Save feedback
	$result = add_post_meta( $track_id, '_mlds_feedback', $feedback_data );

	if ( ! $result ) {
		return new WP_Error(
			'save_failed',
			__( 'Failed to save feedback.', 'music-label-demo-sender' ),
			array( 'status' => 500 )
		);
	}

	// Update average rating
	$all_ratings  = get_post_meta( $track_id, '_mlds_feedback' );
	$rating_sum   = 0;
	$rating_count = 0;

	foreach ( $all_ratings as $rating ) {
		if ( isset( $rating['rating'] ) && $rating['rating'] > 0 ) {
			$rating_sum += $rating['rating'];
			++$rating_count;
		}
	}

	if ( $rating_count > 0 ) {
		$average_rating = round( $rating_sum / $rating_count, 1 );
		update_post_meta( $track_id, '_mlds_average_rating', $average_rating );
	}

	return new WP_REST_Response(
		array(
			'message'        => __( 'Thank you for your feedback!', 'music-label-demo-sender' ),
			'average_rating' => $average_rating ?? null,
		),
		201
	);
}

// Callback for getting track information
function mlds_api_get_track_info( $request ) {
	$track_id = $request->get_param( 'track_id' );
	$token    = $request->get_param( 'token' );

	// Only verify token if it's provided
	if ( $token ) {
		$stored_token = get_post_meta( $track_id, '_mlds_track_token', true );
		if ( $token !== $stored_token ) {
			return new WP_Error(
				'invalid_token',
				__( 'Invalid token.', 'music-label-demo-sender' ),
				array( 'status' => 403 )
			);
		}
	}

	// Since track_id is actually the attachment ID, we'll use it directly
	$attachment = get_post( $track_id );

	if ( ! $attachment ) {
		return new WP_Error(
			'track_not_found',
			__( 'Track not found.', 'music-label-demo-sender' ),
			array( 'status' => 404 )
		);
	}

	$track_url = wp_get_attachment_url( $track_id );

	if ( ! $track_url ) {
		return new WP_Error(
			'file_not_found',
			__( 'Track file not found.', 'music-label-demo-sender' ),
			array( 'status' => 404 )
		);
	}

	return new WP_REST_Response(
		array(
			'track_id' => $track_id,
			'title'    => $attachment->post_title,
			'url'      => $track_url,
			'type'     => $attachment->post_mime_type,
		),
		200
	);
}

// Callback for updating likes/dislikes
function mlds_api_update_reaction( $request ) {
	$post_id       = $request->get_param( 'post_id' );
	$reaction_type = $request->get_param( 'reaction_type' );

	// Verify post exists
	if ( ! get_post( $post_id ) ) {
		return new WP_Error(
			'post_not_found',
			__( 'Post not found.', 'music-label-demo-sender' ),
			array( 'status' => 404 )
		);
	}

	// Get current values
	$current_likes    = (int) get_field( 'likes', $post_id ) ?: 0;
	$current_dislikes = (int) get_field( 'dislikes', $post_id ) ?: 0;

	// Update the appropriate field
	if ( $reaction_type === 'like' ) {
		update_field( 'likes', $current_likes + 1, $post_id );
		$new_value = $current_likes + 1;
	} else {
		update_field( 'dislikes', $current_dislikes + 1, $post_id );
		$new_value = $current_dislikes + 1;
	}

	// Return updated values
	return new WP_REST_Response(
		array(
			'message'       => __( 'Reaction updated successfully!', 'music-label-demo-sender' ),
			'likes'         => (int) get_field( 'likes', $post_id ),
			'dislikes'      => (int) get_field( 'dislikes', $post_id ),
			'updated_field' => $reaction_type,
			'new_value'     => $new_value,
		),
		200
	);
}

// Callback for getting reactions
function mlds_api_get_reactions( $request ) {
	$post_id = $request->get_param( 'postId' );

	// Verify post exists
	if ( ! get_post( $post_id ) ) {
		return new WP_Error(
			'post_not_found',
			__( 'Post not found.', 'music-label-demo-sender' ),
			array( 'status' => 404 )
		);
	}

	// Get current values
	$likes    = (int) get_field( 'likes', $post_id ) ?: 0;
	$dislikes = (int) get_field( 'dislikes', $post_id ) ?: 0;

	// Return reaction counts
	return new WP_REST_Response(
		array(
			'likes'    => $likes,
			'dislikes' => $dislikes,
		),
		200
	);
}
