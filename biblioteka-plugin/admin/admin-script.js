(function ($) {
    'use strict';

    $(document).ready(function () {
        // Media uploader for cover image.
        $('#biblioteka_upload_btn').on('click', function (e) {
            e.preventDefault();
            var frame = wp.media({
                title: 'Select Book Cover Image',
                button: { text: 'Use This Image' },
                multiple: false,
            });
            frame.on('select', function () {
                var attachment = frame.state().get('selection').first().toJSON();
                $('#book_image_url').val(attachment.url);
                $('#biblioteka_image_preview').html(
                    '<img src="' + attachment.url + '" alt="">'
                );
            });
            frame.open();
        });

        // Confirm delete.
        $('.biblioteka-delete-link').on('click', function (e) {
            if (!confirm('Are you sure you want to delete this book?')) {
                e.preventDefault();
            }
        });
    });
})(jQuery);
