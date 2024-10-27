jQuery(document).ready(function( $ ) {
    // When a gallery image is clicked
    $('body').on('click', 'img[data-modal-url]', function( e ) {
        e.preventDefault();

        // Get the image source and details URL
        var imageUrl = $(this).data('modal-url');
        var detailsUrl = $(this).data('attachment-url');
        var imageTitle = $(this).data('title');
        var imageDescription = $(this).data('description');

        // Update the modal content
        $('#gallery-modal-image').attr('src', imageUrl);
        $('#gallery-download-link').attr('href', imageUrl);
        $('#gallery-details-link').attr('href', detailsUrl);

        // Update title and description
        $('#gallery-modal-title').text( imageTitle );
        $('#gallery-modal-description').text( imageDescription );

        // Show the modal with fade-in effect
        $('#gallery-modal').css({
            display: 'flex',
            opacity: 0,
        }).animate({
            opacity: 1,
        }, 300);
    });

    // Close the modal when the close button is clicked
    $('.gallery-close').click(function() {
        $('#gallery-modal').animate({
            opacity: 0,
        }, 300, function() {
            $(this).hide();
        });
    });

    // Close modal when clicking outside the modal content area
    $(document).click(function(event) {
        if ( $(event.target).is('#gallery-modal') ) {
            $('#gallery-modal').animate({
                opacity: 0,
            }, 300, function() {
                $(this).hide();
            });
        }
    });
});
