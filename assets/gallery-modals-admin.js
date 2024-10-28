jQuery(document).ready(function($) {
    // Initialize color pickers with change and clear callbacks
    $('.color-picker').wpColorPicker({
        change: function(event, ui) {
            updatePreview($(this));
        },
        clear: function() {
            updatePreview($(this));
        }
    });

    // Listen for opacity changes
    $('input[type="number"][name$="_opacity"]').on('input', function() {
        updatePreview($(this));
    });

    // Listen for changes on the display settings checkboxes
    $('input[type="checkbox"][name^="gallery_modal_hide_"]').on('change', function() {
        updateVisibility();
    });

    function updatePreview(element, newColor = null) {
        let name = element.attr('name');
        let baseName = name.replace('_color', '').replace('_opacity', '');
        let color = newColor || $('input[name="' + baseName + '_color"]').val() || '#000000';
        let opacity = $('input[name="' + baseName + '_opacity"]').val() || '1';
    
        console.log('Updating preview for:', baseName);
        console.log('Color:', color);
        console.log('Opacity:', opacity);
    
        let rgbaColor = hexToRgba(color, opacity);
    
        switch (baseName) {
            case 'gallery_modal_background':
                $('#modal-preview .modal-preview-content').css('background-color', rgbaColor);
                break;
            case 'gallery_overlay_background':
                $('#modal-preview').css('background-color', rgbaColor);
                break;
            case 'gallery_title_text':
                $('#modal-preview h2').css('color', rgbaColor);
                break;
            case 'gallery_main_text':
                $('#modal-preview p').css('color', rgbaColor);
                break;
            case 'gallery_button_background':
                $('#modal-preview #gallery-download-link, #modal-preview #gallery-details-link').css('background-color', rgbaColor);
                break;
            case 'gallery_button_text':
                $('#modal-preview #gallery-download-link, #modal-preview #gallery-details-link').css('color', rgbaColor);
                break;
            case 'gallery_close_button':
                console.log('Updating close button color');
                $('#modal-preview .gallery-close').css('color', rgbaColor);
                break;
            default:
                console.log('No matching case for:', baseName);
        }
    }

    // Function to convert hex to rgba
    function hexToRgba(hex, opacity) {
        // Remove '#' if present
        hex = hex.replace('#', '');

        // Expand shorthand form (e.g. "03F") to full form (e.g. "0033FF")
        if (hex.length === 3) {
            hex = hex.split('').map(function(h) {
                return h + h;
            }).join('');
        }

        let bigint = parseInt(hex, 16);
        let r = (bigint >> 16) & 255;
        let g = (bigint >> 8) & 255;
        let b = bigint & 255;

        return 'rgba(' + r + ',' + g + ',' + b + ',' + opacity + ')';
    }

    // Function to update the visibility of elements based on checkboxes
    function updateVisibility() {
        // Title
        if ($('input[name="gallery_modal_hide_title"]').is(':checked')) {
            $('#modal-preview h2').hide();
        } else {
            $('#modal-preview h2').show();
        }

        // Description
        if ($('input[name="gallery_modal_hide_description"]').is(':checked')) {
            $('#modal-preview p').hide();
        } else {
            $('#modal-preview p').show();
        }

        // Download Button
        if ($('input[name="gallery_modal_hide_download_button"]').is(':checked')) {
            $('#modal-preview #gallery-download-link').hide();
        } else {
            $('#modal-preview #gallery-download-link').show();
        }

        // View Details Button
        if ($('input[name="gallery_modal_hide_details_button"]').is(':checked')) {
            $('#modal-preview #gallery-details-link').hide();
        } else {
            $('#modal-preview #gallery-details-link').show();
        }
    }

    // Initialize preview on page load
    $('.color-picker').each(function() {
        updatePreview($(this));
    });
    $('input[type="number"][name$="_opacity"]').each(function() {
        updatePreview($(this));
    });

    // Initialize visibility on page load
    updateVisibility();
});
