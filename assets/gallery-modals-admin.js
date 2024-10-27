jQuery(document).ready(function($) {
    // Initialize color pickers
    $('.color-picker').wpColorPicker({
        change: function(event, ui) {
            updatePreview($(event.target));
        },
        clear: function(event) {
            updatePreview($(event.target));
        }
    });

    // Listen for opacity changes
    $('input[type="number"][name$="_opacity"]').on('input', function() {
        updatePreview($(this));
    });

    function updatePreview(element) {
        let name = element.attr('name');
        let baseName = name.replace('_color', '').replace('_opacity', '');
        let color = $('input[name="' + baseName + '_color"]').val() || '#000000';
        let opacity = $('input[name="' + baseName + '_opacity"]').val() || '1';

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
                $('#modal-preview #gallery-download-link').css('background-color', rgbaColor);
                break;
            case 'gallery_button_text':
                $('#modal-preview #gallery-download-link').css('color', rgbaColor);
                break;
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

    // Initialize preview on page load
    $('.color-picker').each(function() {
        updatePreview($(this));
    });
    $('input[type="number"][name$="_opacity"]').each(function() {
        updatePreview($(this));
    });
});
