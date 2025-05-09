jQuery(document).ready(function($) {
    // Initialize dialog
    const dialog = $('#subscriber-dialog').dialog({
        autoOpen: false,
        modal: true,
        width: 400,
        buttons: {
            Save: function() {
                saveSubscriber();
            },
            Cancel: function() {
                dialog.dialog('close');
            }
        },
        close: function() {
            subscriberForm[0].reset();
            $('#subscriber_id').val('');
            $('#new_group_name').hide();
        }
    });

    const subscriberForm = $('#subscriber-form');

    // Handle group selection change
    $('#subscriber_group').change(function() {
        if ($(this).val() === 'new') {
            $('#new_group_name').show().prop('required', true);
        } else {
            $('#new_group_name').hide().prop('required', false);
        }
    });

    // Handle bulk action selection
    $('select[name="action"]').change(function() {
        const action = $(this).val();
        if (action === 'change_group') {
            $('select[name="new_group"]').show();
        } else {
            $('select[name="new_group"]').hide();
        }
    });

    // Handle new group selection in bulk actions
    $('select[name="new_group"]').change(function() {
        if ($(this).val() === 'new') {
            $('.new-group-input').show().prop('required', true);
        } else {
            $('.new-group-input').hide().prop('required', false);
        }
    });

    // Handle bulk actions submit
    $('#subscribers-filter').submit(function(e) {
        const action = $('select[name="action"]').val();
        if (action === 'delete') {
            if (!confirm(mldsAdmin.confirmBulkDelete)) {
                e.preventDefault();
            }
        }
    });

    // Add new subscriber button
    $('.add-subscriber-button').click(function(e) {
        e.preventDefault();
        dialog.dialog('option', 'title', 'Add New Subscriber');
        dialog.dialog('open');
    });

    // Edit subscriber
    $('.edit-subscriber').click(function(e) {
        e.preventDefault();
        const id = $(this).data('id');
        dialog.dialog('option', 'title', 'Edit Subscriber');
        
        // Get subscriber data
        $.ajax({
            url: mldsAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'mlds_subscriber_action',
                subscriber_action: 'get',
                subscriber_id: id,
                nonce: mldsAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    const subscriber = response.data.subscriber;
                    $('#subscriber_id').val(subscriber.id);
                    $('#subscriber_email').val(subscriber.email);
                    $('#subscriber_name').val(subscriber.name);
                    $('#subscriber_group').val(subscriber.group_name);
                    dialog.dialog('open');
                } else {
                    alert(response.data.message);
                }
            }
        });
    });

    // Delete subscriber
    $('.delete-subscriber').click(function(e) {
        e.preventDefault();
        if (!confirm(mldsAdmin.confirmDelete)) {
            return;
        }

        const id = $(this).data('id');
        const row = $(`#subscriber-${id}`);

        $.ajax({
            url: mldsAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'mlds_subscriber_action',
                subscriber_action: 'delete',
                subscriber_id: id,
                nonce: mldsAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    row.fadeOut(400, function() {
                        $(this).remove();
                    });
                } else {
                    alert(response.data.message);
                }
            }
        });
    });

    // Save subscriber
    function saveSubscriber() {
        const formData = {
            action: 'mlds_subscriber_action',
            subscriber_action: $('#subscriber_id').val() ? 'update' : 'add',
            subscriber_id: $('#subscriber_id').val(),
            email: $('#subscriber_email').val(),
            name: $('#subscriber_name').val(),
            group: $('#subscriber_group').val() === 'new' ? $('#new_group_name').val() : $('#subscriber_group').val(),
            nonce: mldsAdmin.nonce
        };

        $.ajax({
            url: mldsAdmin.ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message);
                }
            }
        });
    }

    // Handle form submission
    subscriberForm.submit(function(e) {
        e.preventDefault();
        saveSubscriber();
    });
}); 