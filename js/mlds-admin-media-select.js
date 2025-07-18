jQuery(document).ready(function ($) {
	var frame;

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

		// When tracks are selected in the media frame...
		frame.on('select', function () {
			var selection = frame.state().get('selection');
			var displayContainer = $('#mlds-selected-media-tracks-display');
			var idsContainer = $('#mlds-media-library-track-ids-container');

			displayContainer
				.empty()
				.append(
					'<h4>' + wp.i18n.__('Selected Tracks:', 'music-label-demo-sender') + '</h4>'
				);
			var list = $('<ul></ul>');
			idsContainer.empty();

			selection.each(function (attachment) {
				var attachmentProps = attachment.toJSON();
				var title = attachmentProps.title || attachmentProps.filename;

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

		// Finally, open the media frame
		frame.open();
	});
});
