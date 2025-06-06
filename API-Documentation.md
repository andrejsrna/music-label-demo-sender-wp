# Music Label Demo Sender - API Documentation

## Base URL

```
https://yourdomain.com/wp-json/mlds/v1/
```

## API Endpoints Overview

The Music Label Demo Sender plugin provides a REST API for managing demo track feedback, subscriptions, and reactions. All endpoints support CORS and return JSON responses.

---

## 1. Test Endpoint

### GET `/test`

Simple test endpoint to verify API connectivity.

**Parameters:** None

**Response:**

```json
{
	"message": "API is working!",
	"timestamp": "2024-01-01 12:00:00"
}
```

---

## 2. Feedback Management

### POST `/feedback`

Submit feedback for a demo track.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `track_id` | integer | Yes | Track/attachment ID |
| `token` | string | No | Security token for track access |
| `rating` | integer | Yes | Rating from 1-5 stars |
| `feedback` | string | Yes | Text feedback/review |
| `name` | string | Yes | Reviewer's name |

**Example Request:**

```javascript
fetch('https://dnbdoctor.com/wp-json/mlds/v1/feedback', {
	method: 'POST',
	headers: {
		'Content-Type': 'application/json',
	},
	body: JSON.stringify({
		track_id: 994,
		token: 'xUIlnvmXrxlAiClpP1VTZPEBB3Qxl0PZ',
		rating: 5,
		feedback: 'Amazing track! Love the bassline.',
		name: 'John Doe',
	}),
});
```

**Success Response (201):**

```json
{
	"message": "Thank you for your feedback!",
	"average_rating": 4.2
}
```

**Error Responses:**

- `403`: Invalid token
- `500`: Failed to save feedback

---

## 3. Track Information

### GET `/track-info`

Retrieve track details and download URL.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `track_id` | integer | Yes | Track/attachment ID |
| `token` | string | No | Security token for track access |

**Example Request:**

```javascript
fetch(
	'https://dnbdoctor.com/wp-json/mlds/v1/track-info?track_id=994&token=xUIlnvmXrxlAiClpP1VTZPEBB3Qxl0PZ'
);
```

**Success Response (200):**

```json
{
	"track_id": 994,
	"title": "Neurofunk Banger",
	"url": "https://dnbdoctor.com/wp-content/uploads/track.mp3",
	"type": "audio/mpeg"
}
```

**Error Responses:**

- `403`: Invalid token
- `404`: Track not found or file not found

---

## 4. Subscription Management

### POST `/subscribe`

Add a new subscriber to the mailing list.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `email` | string | Yes | Valid email address |
| `name` | string | No | Subscriber's name |
| `group` | string | Yes | Subscriber group name |

**Example Request:**

```javascript
fetch('https://dnbdoctor.com/wp-json/mlds/v1/subscribe', {
	method: 'POST',
	headers: {
		'Content-Type': 'application/json',
	},
	body: JSON.stringify({
		email: 'fan@example.com',
		name: 'Music Fan',
		group: 'neurofunk',
	}),
});
```

**Success Response (201):**

```json
{
	"message": "Successfully subscribed!",
	"subscriber_id": 123
}
```

**Error Responses:**

- `409`: Email already subscribed
- `500`: Failed to add subscriber

### POST `/unsubscribe`

Remove subscriber from mailing list.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `email` | string | Yes | Email to unsubscribe |
| `token` | string | Yes | Unsubscribe token |

**Example Request:**

```javascript
fetch('https://dnbdoctor.com/wp-json/mlds/v1/unsubscribe', {
	method: 'POST',
	headers: {
		'Content-Type': 'application/json',
	},
	body: JSON.stringify({
		email: 'fan@example.com',
		token: 'unsubscribe_token_here',
	}),
});
```

**Success Response (200):**

```json
{
	"message": "Successfully unsubscribed!"
}
```

**Error Responses:**

- `403`: Invalid token
- `404`: Email not found
- `500`: Failed to unsubscribe

---

## 5. Reaction System

### GET `/reactions`

Get like/dislike counts for a post.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `postId` | integer | Yes | Post ID to get reactions for |

**Example Request:**

```javascript
fetch('https://dnbdoctor.com/wp-json/mlds/v1/reactions?postId=123');
```

**Success Response (200):**

```json
{
	"likes": 45,
	"dislikes": 3
}
```

### POST `/reactions` or `/update-reaction`

Update like/dislike count for a post.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `post_id` | integer | Yes | Post ID to update |
| `reaction_type` | string | Yes | Either "like" or "dislike" |

**Example Request:**

```javascript
fetch('https://dnbdoctor.com/wp-json/mlds/v1/reactions', {
	method: 'POST',
	headers: {
		'Content-Type': 'application/json',
	},
	body: JSON.stringify({
		post_id: 123,
		reaction_type: 'like',
	}),
});
```

**Success Response (200):**

```json
{
	"message": "Reaction updated successfully!",
	"likes": 46,
	"dislikes": 3,
	"updated_field": "like",
	"new_value": 46
}
```

---

## Error Handling

All endpoints return standard HTTP status codes:

- `200` - Success
- `201` - Created
- `400` - Bad Request (invalid parameters)
- `403` - Forbidden (invalid token/permissions)
- `404` - Not Found
- `409` - Conflict (duplicate entry)
- `500` - Internal Server Error

**Error Response Format:**

```json
{
	"code": "error_code",
	"message": "Human readable error message",
	"data": {
		"status": 400
	}
}
```

---

## Security Features

1. **CORS Headers**: All endpoints include CORS headers for cross-origin requests
2. **Input Sanitization**: All parameters are sanitized using WordPress functions
3. **Token Verification**: Track access uses secure tokens
4. **Nonce Protection**: Unsubscribe requests use WordPress nonces

---

## Common Issues & Troubleshooting

### Frontend Not Loading

**Issue**: Demo feedback page shows "Loading..." but doesn't display content.

**Possible Causes:**

1. **Invalid Token**: The token in the URL doesn't match the stored track token
2. **Missing Track**: Track ID 994 doesn't exist or isn't accessible
3. **CORS Issues**: Frontend can't access the API endpoints
4. **JavaScript Errors**: Check browser console for errors

**Debug Steps:**

1. Test the track-info endpoint directly:

    ```
    GET https://dnbdoctor.com/wp-json/mlds/v1/track-info?track_id=994&token=xUIlnvmXrxlAiClpP1VTZPEBB3Qxl0PZ
    ```

2. Check if the API is accessible:

    ```
    GET https://dnbdoctor.com/wp-json/mlds/v1/test
    ```

3. Verify the track exists in WordPress admin
4. Check browser console for JavaScript errors
5. Verify CORS headers are present in response

### Token Issues

**Issue**: Getting "Invalid token" errors.

**Solution**: Ensure tokens are properly generated and stored:

```php
// Check if token exists
$stored_token = get_post_meta($track_id, '_mlds_track_token', true);
if (empty($stored_token)) {
	// Generate new token
	$new_token = wp_generate_password(32, false);
	update_post_meta($track_id, '_mlds_track_token', $new_token);
}
```

### Database Connection Issues

**Issue**: Getting database errors when submitting feedback.

**Solution**: Verify WordPress database tables exist and are accessible:

```php
// Check if subscribers table exists
global $wpdb;
$table_name = $wpdb->prefix . 'mlds_subscribers';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
```

---

## Development Notes

- All endpoints use WordPress REST API framework
- Feedback is stored as post meta with key `_mlds_feedback`
- Average ratings are calculated and cached as `_mlds_average_rating`
- Subscribers are stored in custom table `wp_mlds_subscribers`
- Reactions use Advanced Custom Fields (ACF) to store like/dislike counts

---

## Testing Examples

### Test Complete Feedback Flow

```javascript
// 1. Get track info
const trackInfo = await fetch(
	'https://dnbdoctor.com/wp-json/mlds/v1/track-info?track_id=994&token=xUIlnvmXrxlAiClpP1VTZPEBB3Qxl0PZ'
);

// 2. Submit feedback
const feedback = await fetch('https://dnbdoctor.com/wp-json/mlds/v1/feedback', {
	method: 'POST',
	headers: { 'Content-Type': 'application/json' },
	body: JSON.stringify({
		track_id: 994,
		token: 'xUIlnvmXrxlAiClpP1VTZPEBB3Qxl0PZ',
		rating: 5,
		feedback: 'Great track!',
		name: 'Test User',
	}),
});

// 3. Check reactions
const reactions = await fetch('https://dnbdoctor.com/wp-json/mlds/v1/reactions?postId=994');
```
