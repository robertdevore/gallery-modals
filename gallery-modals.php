<?php
/**
 * The plugin bootstrap file
 *
 * @link              https://robertdevore.com
 * @since             1.0.0
 * @package           Gallery_Modals
 *
 * @wordpress-plugin
 *
 * Plugin Name: Gallery Modals
 * Description: Add modal popups to WordPress® gallery images with a download button and a link to view image details.
 * Plugin URI:  https://github.com/robertdevore/gallery-modals/
 * Version:     1.0.0
 * Author:      Robert DeVore
 * Author URI:  https://robertdevore.com/
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: gallery-modals
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Current plugin version.
 */
define( 'GALLERY_MODALS_VERSION', '1.0.0' );

/**
 * Enqueue necessary scripts and styles for the modal popup.
 *
 * @since  1.0.0
 * @return void
 */
function gm_enqueue_modal_scripts() {
    // Register and enqueue JS file.
    wp_register_script( 'gm-modal-js', plugin_dir_url( __FILE__ ) . 'assets/gallery-modals.js', [ 'jquery' ], GALLERY_MODALS_VERSION, true );
    wp_enqueue_script( 'gm-modal-js' );

    // Register and enqueue CSS file.
    wp_register_style( 'gallery-modals-css', plugin_dir_url( __FILE__ ) . 'assets/gallery-modals.css', [], GALLERY_MODALS_VERSION );
    wp_enqueue_style( 'gallery-modals-css' );
}
add_action( 'wp_enqueue_scripts', 'gm_enqueue_modal_scripts' );

/**
 * Output the modal structure in the footer.
 *
 * @since  1.0.0
 * @return void
 */
function gm_add_modal_to_footer() {
    ?>
    <div id="gallery-modal" class="gallery-modal">
        <div class="gallery-modal-content">
            <span class="gallery-close">&times;</span>
            <img id="gallery-modal-image" src="" alt="" />
            <h2 id="gallery-modal-title"></h2>
            <p id="gallery-modal-description"></p>
            <?php if ( apply_filters( 'show_gallery_download_button', true ) ) : ?>
                <a id="gallery-download-link" href="" download><?php esc_html_e( 'Download', 'gallery-modals' ); ?></a>
            <?php endif; ?>
            <?php if ( apply_filters( 'show_gallery_details_button', true ) ) : ?>
                <a id="gallery-details-link" href="" target="_blank"><?php esc_html_e( 'View Image Details', 'gallery-modals' ); ?></a>
            <?php endif; ?>
        </div>
    </div>
    <?php
}
add_action( 'wp_footer', 'gm_add_modal_to_footer' );

/**
 * Add data attributes to classic gallery images for the modal popup.
 *
 * @since  1.0.0
 * @return array|string
 */
function gm_add_image_attributes( $html, $id, $size, $icon, $attr ) {
    // Get the image and attachment URLs.
    $image_url      = wp_get_attachment_url( $id );
    $attachment_url = get_attachment_link( $id );
    $image_title    = get_the_title( $id );
    $image_desc     = wp_strip_all_tags( get_post_field( 'post_content', $id ) );

    // Add data attributes for the modal functionality.
    $html = str_replace(
        '<img',
        '<img data-modal-url="' . esc_url( $image_url ) . '" data-attachment-url="' . esc_url( $attachment_url ) . '" data-title="' . esc_attr( $image_title ) . '" data-description="' . esc_attr( $image_desc ) . '"',
        $html
    );
    return $html;
}
add_filter( 'wp_get_attachment_image', 'gm_add_image_attributes', 10, 5 );

/**
 * Filter block gallery output and add necessary data attributes to images.
 *
 * @param string $block_content Content of the block.
 * @param array  $block Block information.
 *
 * @since  1.0.0
 * @return string Modified block content.
 */
function gm_modify_block_gallery_output( $block_content, $block ) {
    // Check if the block is a gallery block.
    if ( 'core/gallery' === $block['blockName'] ) {
        // Load the block content into DOMDocument.
        libxml_use_internal_errors( true );
        $dom = new DOMDocument();
        $dom->loadHTML( mb_convert_encoding( $block_content, 'HTML-ENTITIES', 'UTF-8' ) );
        libxml_clear_errors();

        // Get all <img> elements.
        $images = $dom->getElementsByTagName( 'img' );

        foreach ( $images as $img ) {
            // Get the class attribute to find the image ID.
            $class = $img->getAttribute( 'class' );

            if ( preg_match( '/wp-image-(\d+)/', $class, $matches ) ) {
                $image_id = $matches[1];

                $image_url      = wp_get_attachment_url( $image_id );
                $attachment_url = get_attachment_link( $image_id );
                $image_title    = get_the_title( $image_id );
                $image_desc     = wp_strip_all_tags( get_post_field( 'post_content', $image_id ) );

                // Set data attributes.
                $img->setAttribute( 'data-modal-url', esc_url( $image_url ) );
                $img->setAttribute( 'data-attachment-url', esc_url( $attachment_url ) );
                $img->setAttribute( 'data-title', esc_attr( $image_title ) );
                $img->setAttribute( 'data-description', esc_attr( $image_desc ) );
            }
        }

        // Save the updated HTML.
        $block_content = $dom->saveHTML( $dom->getElementsByTagName( 'body' )->item( 0 ) );
        // Remove <body> tags.
        $block_content = preg_replace( '/^<body>(.*)<\/body>$/is', '$1', $block_content );
    }

    return $block_content;
}
add_filter( 'render_block', 'gm_modify_block_gallery_output', 10, 2 );

/**
 * Add a settings page to the WordPress® dashboard.
 *
 * @since 1.0.0
 * @return void
 */
function gallery_modal_settings_page() {
    add_menu_page(
        esc_html__( 'Gallery Modals Settings', 'gallery-modals' ),
        esc_html__( 'Gallery Modals', 'gallery-modals' ),
        'manage_options',
        'gallery-modals-settings',
        'render_gallery_modal_settings_page',
        'dashicons-admin-customizer',
        100
    );
}
add_action( 'admin_menu', 'gallery_modal_settings_page' );

/**
 * Register settings for color and opacity options.
 *
 * @since 1.0.0
 * @return void
 */
function gallery_modal_register_settings() {
    // Register a new setting group.
    $option_group = 'gallery_modal_settings_group';
    $option_page  = 'gallery-modals-settings';

    // Color options to register.
    $color_options = [
        'gallery_modal_background_color'      => '#ffffff',
        'gallery_overlay_background_color'    => '#000000',
        'gallery_title_text_color'            => '#000000',
        'gallery_main_text_color'             => '#333333',
        'gallery_button_background_color'     => '#0073aa',
        'gallery_button_text_color'           => '#ffffff',
    ];

    // Opacity options to register.
    $opacity_options = [
        'gallery_modal_background_opacity'      => '1',
        'gallery_overlay_background_opacity'    => '0.5',
        'gallery_title_text_opacity'            => '1',
        'gallery_main_text_opacity'             => '1',
        'gallery_button_background_opacity'     => '1',
        'gallery_button_text_opacity'           => '1',
    ];

    // Register each color option.
    foreach ( $color_options as $option_name => $default_value ) {
        register_setting(
            $option_group,
            $option_name,
            [
                'type'              => 'string',
                'default'           => $default_value,
                'sanitize_callback' => 'sanitize_hex_color',
            ]
        );
    }

    // Register each opacity option.
    foreach ( $opacity_options as $option_name => $default_value ) {
        register_setting(
            $option_group,
            $option_name,
            [
                'type'              => 'number',
                'default'           => $default_value,
                'sanitize_callback' => 'gm_sanitize_opacity',
            ]
        );
    }

    // Add settings section.
    add_settings_section(
        'gallery_modal_main_section',
        esc_html__( 'Color and Opacity Settings', 'gallery-modals' ),
        'gallery_modal_section_callback',
        $option_page
    );

    // Define settings.
    $settings = [
        'gallery_modal_background'   => esc_html__( 'Modal Background', 'gallery-modals' ),
        'gallery_overlay_background' => esc_html__( 'Overlay Background', 'gallery-modals' ),
        'gallery_title_text'         => esc_html__( 'Title Text', 'gallery-modals' ),
        'gallery_main_text'          => esc_html__( 'Main Text', 'gallery-modals' ),
        'gallery_button_background'  => esc_html__( 'Button Background', 'gallery-modals' ),
        'gallery_button_text'        => esc_html__( 'Button Text', 'gallery-modals' ),
    ];

    // Output each setting field.
    foreach ( $settings as $prefix => $label ) {
        $color_option   = $prefix . '_color';
        $opacity_option = $prefix . '_opacity';

        add_settings_field(
            $color_option,
            $label,
            'gallery_modal_field_callback',
            $option_page,
            'gallery_modal_main_section',
            [
                'color_option'   => $color_option,
                'opacity_option' => $opacity_option,
            ]
        );
    }
}
add_action( 'admin_init', 'gallery_modal_register_settings' );

/**
 * Section callback function.
 */
function gallery_modal_section_callback() {
    echo '<p>' . esc_html__( 'Customize the appearance of your gallery modals.', 'gallery-modals' ) . '</p>';
}

/**
 * Field callback function.
 */
function gallery_modal_field_callback( $args ) {
    $color_option   = $args['color_option'];
    $opacity_option = $args['opacity_option'];

    $color_value   = get_option( $color_option, '#ffffff' );
    $opacity_value = get_option( $opacity_option, '1' );

    echo '<input type="text" name="' . esc_attr( $color_option ) . '" value="' . esc_attr( $color_value ) . '" class="color-picker" />';
    echo '<label for="' . esc_attr( $opacity_option ) . '"> ' . esc_html__( 'Opacity:', 'gallery-modals' ) . ' </label>';
    echo '<input type="number" name="' . esc_attr( $opacity_option ) . '" value="' . esc_attr( $opacity_value ) . '" min="0" max="1" step="0.01" style="width: 60px;" />';
}

/**
 * Sanitize opacity values between 0 and 1.
 *
 * @param string $opacity The opacity value to sanitize.
 * @return string Sanitized opacity value.
 */
function gm_sanitize_opacity( $opacity ) {
    $opacity = floatval( $opacity );
    if ( $opacity < 0 ) {
        $opacity = 0;
    } elseif ( $opacity > 1 ) {
        $opacity = 1;
    }
    return strval( $opacity );
}

/**
 * Render the Gallery Modal settings page.
 *
 * @since 1.0.0
 * @return void
 */
function render_gallery_modal_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    // Helper function to get RGBA color.
    function get_rgba( $color_option, $opacity_option, $default_color, $default_opacity ) {
        $color   = get_option( $color_option, $default_color );
        $opacity = get_option( $opacity_option, $default_opacity );
        $opacity = floatval( $opacity );
        if ( $opacity < 0 ) {
            $opacity = 0;
        } elseif ( $opacity > 1 ) {
            $opacity = 1;
        }
        // Convert hex color to RGBA.
        $color = str_replace( '#', '', $color );
        if ( strlen( $color ) === 3 ) {
            $r = hexdec( str_repeat( substr( $color, 0, 1 ), 2 ) );
            $g = hexdec( str_repeat( substr( $color, 1, 1 ), 2 ) );
            $b = hexdec( str_repeat( substr( $color, 2, 1 ), 2 ) );
        } else {
            $r = hexdec( substr( $color, 0, 2 ) );
            $g = hexdec( substr( $color, 2, 2 ) );
            $b = hexdec( substr( $color, 4, 2 ) );
        }
        return "rgba($r, $g, $b, $opacity)";
    }

    $overlay_bg        = get_rgba( 'gallery_overlay_background_color', 'gallery_overlay_background_opacity', '#000000', '0.5' );
    $modal_bg          = get_rgba( 'gallery_modal_background_color', 'gallery_modal_background_opacity', '#ffffff', '1' );
    $title_color       = get_rgba( 'gallery_title_text_color', 'gallery_title_text_opacity', '#000000', '1' );
    $main_text_color   = get_rgba( 'gallery_main_text_color', 'gallery_main_text_opacity', '#333333', '1' );
    $button_bg         = get_rgba( 'gallery_button_background_color', 'gallery_button_background_opacity', '#0073aa', '1' );
    $button_text_color = get_rgba( 'gallery_button_text_color', 'gallery_button_text_opacity', '#ffffff', '1' );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Gallery Modal Settings', 'gallery-modals' ); ?></h1>
        <hr />
        <div class="modal-wrapper">
            <form method="post" action="options.php">
                <?php
                settings_errors();
                settings_fields( 'gallery_modal_settings_group' );
                do_settings_sections( 'gallery-modals-settings' );
                submit_button();
                ?>
            </form>
            <div class="modal-wrapper">
                <!-- Preview Section -->
                <div id="modal-preview" style="flex: 1; padding: 40px; display: flex; align-items: center; justify-content: center; border: 1px solid #ddd; background-color: <?php echo esc_attr( $overlay_bg ); ?>;">
                    <div class="modal-preview-content" style="padding: 20px; max-width: 300px; text-align: center; background-color: <?php echo esc_attr( $modal_bg ); ?>;">
                        <h2 style="color: <?php echo esc_attr( $title_color ); ?>;">
                            <?php esc_html_e( 'Modal Title', 'gallery-modals' ); ?>
                        </h2>
                        <p style="color: <?php echo esc_attr( $main_text_color ); ?>;">
                            <?php esc_html_e( 'This is a preview of the modal text.', 'gallery-modals' ); ?>
                        </p>
                        <a href="#" id="gallery-download-link"
                            style="background-color: <?php echo esc_attr( $button_bg ); ?>;
                                    color: <?php echo esc_attr( $button_text_color ); ?>;">
                            <?php esc_html_e( 'Download', 'gallery-modals' ); ?>
                        </a>
                    </div>
                </div>
                <style>
                    .modal-wrapper {
                        display: flex;
                        gap: 20px;
                        flex: 1;
                    }
                    @media (max-width: 768px) {
                        .modal-wrapper {
                            flex-direction: column;
                        }
                        #modal-preview {
                            order: -1;
                        }
                    }
                </style>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Enqueue the admin JavaScript file for the Gallery Modal settings page.
 */
function gallery_modal_enqueue_admin_scripts( $hook_suffix ) {
    // Only load on the Gallery Modals settings page.
    if ( 'toplevel_page_gallery-modals-settings' !== $hook_suffix ) {
        return;
    }

    // Enqueue WordPress color picker assets.
    wp_enqueue_style( 'wp-color-picker' );
    wp_enqueue_script( 'wp-color-picker' );

    // Enqueue custom admin JavaScript for real-time color updates.
    wp_enqueue_script(
        'gallery-modals-admin-js',
        plugin_dir_url( __FILE__ ) . 'assets/gallery-modals-admin.js',
        [ 'jquery', 'wp-color-picker' ],
        false,
        true
    );
}
add_action( 'admin_enqueue_scripts', 'gallery_modal_enqueue_admin_scripts' );

/**
 * Enqueue the frontend CSS in the admin for the Gallery Modal settings page.
 *
 * @since 1.0.0
 * @param string $hook_suffix The current admin page.
 */
function gallery_modal_enqueue_admin_styles( $hook_suffix ) {
    // Only load on the Gallery Modals settings page.
    if ( 'toplevel_page_gallery-modals-settings' !== $hook_suffix ) {
        return;
    }

    // Enqueue the frontend CSS.
    wp_enqueue_style( 'gallery-modals-css', plugin_dir_url( __FILE__ ) . 'assets/gallery-modals.css', [], GALLERY_MODALS_VERSION );

    // Additional CSS for admin preview layout.
    wp_add_inline_style( 'gallery-modals-css', '
        /* Settings page specific adjustments */
        #modal-preview {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
            border: 1px solid #ddd;
        }

        #modal-preview .modal-preview-content {
            padding: 20px;
            max-width: 300px;
            text-align: center;
            background-color: #ffffff;
            border-radius: 8px;
        }
    ' );
}
add_action( 'admin_enqueue_scripts', 'gallery_modal_enqueue_admin_styles' );

/**
 * Output inline CSS for the modal based on settings.
 *
 * @since 1.0.0
 * @return void
 */
function gallery_modal_inline_styles() {
    // Helper function to get RGBA color.
    function get_rgba( $color_option, $opacity_option, $default_color, $default_opacity ) {
        $color   = get_option( $color_option, $default_color );
        $opacity = get_option( $opacity_option, $default_opacity );
        $opacity = floatval( $opacity );
        if ( $opacity < 0 ) {
            $opacity = 0;
        } elseif ( $opacity > 1 ) {
            $opacity = 1;
        }
        // Convert hex color to RGBA.
        $color = str_replace( '#', '', $color );
        if ( strlen( $color ) === 3 ) {
            $r = hexdec( str_repeat( substr( $color, 0, 1 ), 2 ) );
            $g = hexdec( str_repeat( substr( $color, 1, 1 ), 2 ) );
            $b = hexdec( str_repeat( substr( $color, 2, 1 ), 2 ) );
        } else {
            $r = hexdec( substr( $color, 0, 2 ) );
            $g = hexdec( substr( $color, 2, 2 ) );
            $b = hexdec( substr( $color, 4, 2 ) );
        }
        return "rgba($r, $g, $b, $opacity)";
    }

    $overlay_bg        = get_rgba( 'gallery_overlay_background_color', 'gallery_overlay_background_opacity', '#000000', '0.5' );
    $modal_bg          = get_rgba( 'gallery_modal_background_color', 'gallery_modal_background_opacity', '#ffffff', '1' );
    $title_color       = get_rgba( 'gallery_title_text_color', 'gallery_title_text_opacity', '#000000', '1' );
    $main_text_color   = get_rgba( 'gallery_main_text_color', 'gallery_main_text_opacity', '#333333', '1' );
    $button_bg         = get_rgba( 'gallery_button_background_color', 'gallery_button_background_opacity', '#0073aa', '1' );
    $button_text_color = get_rgba( 'gallery_button_text_color', 'gallery_button_text_opacity', '#ffffff', '1' );
    ?>
    <style>
        #gallery-modal {
            background-color: <?php echo esc_attr( $overlay_bg ); ?>;
        }
        #gallery-modal .gallery-modal-content {
            background-color: <?php echo esc_attr( $modal_bg ); ?>;
        }
        #gallery-modal h2 {
            color: <?php echo esc_attr( $title_color ); ?>;
        }
        #gallery-modal p {
            color: <?php echo esc_attr( $main_text_color ); ?>;
        }
        #gallery-modal a {
            background-color: <?php echo esc_attr( $button_bg ); ?>;
            color: <?php echo esc_attr( $button_text_color ); ?>;
        }
    </style>
    <?php
}
add_action( 'wp_head', 'gallery_modal_inline_styles' );
