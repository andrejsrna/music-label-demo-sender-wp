jQuery(document).ready(function ($) {
	let frame;

	$('#mlds-select-media-tracks-button').on('click', function (event) {
		event.preventDefault();

		// If the media frame already exists, reopen it.
		if (frame) {
			frame.open();
			return;
		}

		// Create a new media frame
		frame = wp.media({
			title: wp.i18n.__('Select Audio Tracks', 'music-label-demo-sender'),
			button: {
				text: wp.i18n.__('Use These Tracks', 'music-label-demo-sender'),
			},
			library: {
				type: 'audio', // Only show audio files
			},
			multiple: true, // Allow multiple selections
		});

		// When an image is selected in the media frame...
		frame.on('select', function () {
			const selection = frame.state().get('selection');
			const displayContainer = $('#mlds-selected-media-tracks-display');
			const idsContainer = $('#mlds-media-library-track-ids-container');

			// Clear previous selections
			displayContainer.empty();
			idsContainer.empty();

			let listHtml = '<ul>';
			selection.each(function (attachment) {
				const attachmentProps = attachment.toJSON();
				const title = attachmentProps.title || attachmentProps.filename;
				listHtml += '<li>' + gemeinsame.escapeHTML(title) + '</li>'; // Using a utility for escaping HTML just in case.
				// Replace with a simpler esc_html equivalent if not available.
				// For basic title display, direct output might be fine if titles are trusted.
				// Let's use a simpler jQuery text method for safety for now.

				// More directly:
				const listItem = $('<li></li>').text(title);
				displayContainer.append(listItem); // Append directly for simplicity here if not building a single HTML string.

				// Add hidden input for the ID
				idsContainer.append(
					$('<input>')
						.attr('type', 'hidden')
						.attr('name', 'mlds_media_library_tracks[]')
						.val(attachmentProps.id)
				);
			});
			// If building listHtml:
			// listHtml += '</ul>';
			// displayContainer.html(listHtml);
			// For the direct append method, ensure displayContainer is a <ul> or <ol>
			// Let's adjust displayContainer to be a <ul> directly in the PHP or ensure it is treated as such.
		});

		// Finally, open the media frame
		frame.open();
	});

	// Helper for escaping HTML - if not using a library like lodash/underscore or a dedicated one.
	// WordPress admin typically has ways to do this or relies on server-side escaping for final output.
	// For dynamic client-side display of titles from WP Media, they are generally safe but good practice.
	// This is a very basic version.
	const gemeinsame = {
		escapeHTML: function (str) {
			if (typeof str !== 'string') return '';
			return str.replace(/[&<>'"`]/g, function (match) {
				return {
					'&': '&amp;',
					'<': '&lt;',
					'>': '&gt;',
					'"': '&quot;',
					"'": '&#39;', // HTML entity for single quote
					'`': '&#x60;',
				}[match];
			});
		},
	};
	// If displayContainer is a div, and we want a list:
	// Modify the selection.each loop to build up a <ul> and then set its HTML.
	// Revised frame.on('select', ...) for list display:
	frame.on('select', function () {
		const selection = frame.state().get('selection');
		const displayContainer = $('#mlds-selected-media-tracks-display');
		const idsContainer = $('#mlds-media-library-track-ids-container');

		displayContainer
			.empty()
			.append('<h4>' + wp.i18n.__('Selected Tracks:', 'music-label-demo-sender') + '</h4>');
		const list = $('<ul></ul>');
		idsContainer.empty();

		selection.each(function (attachment) {
			const attachmentProps = attachment.toJSON();
			const title = attachmentProps.title || attachmentProps.filename;

			list.append($('<li></li>').text(title));

			idsContainer.append(
				$('<input>')
					.attr('type', 'hidden')
					.attr('name', 'mlds_media_library_tracks[]')
					.val(attachmentProps.id)
			);
		});
		displayContainer.append(list);
	});
});
