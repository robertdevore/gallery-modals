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
    wp_register_style( 'gallery-modal-css', plugin_dir_url( __FILE__ ) . 'assets/gallery-modals.css', [], GALLERY_MODALS_VERSION );
    wp_enqueue_style( 'gallery-modal-css' );
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
            <?php if ( apply_filters( 'show_gallery_download_button', true ) ) : ?>
                <a id="gallery-download-link" href="" download><?php esc_html_e( 'Download', 'gallery-modal-popup' ); ?></a>
            <?php endif; ?>
            <?php if ( apply_filters( 'show_gallery_details_button', true ) ) : ?>
                <a id="gallery-details-link" href="" target="_blank"><?php esc_html_e( 'View Image Details', 'gallery-modal-popup' ); ?></a>
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

    // Add data attributes for the modal functionality.
    $html = str_replace( '<img', '<img data-modal-url="' . esc_url( $image_url ) . '" data-attachment-url="' . esc_url( $attachment_url ) . '"', $html );
    return $html;
}
add_filter( 'wp_get_attachment_image', 'gm_add_image_attributes', 10, 5 );

/**
 * Filter block gallery output and add necessary data attributes to images.
 *
 * @param string $block_content Content of the block.
 * @param array  $block Block information.
 *
 * @TODO Make gallery block check filterable to include additional blocks.
 * 
 * @since  1.0.0
 * @return string Modified block content.
 */
function gm_modify_block_gallery_output( $block_content, $block ) {
    // Check if the block is a gallery block.
    if ( 'core/gallery' === $block['blockName'] && ! empty( $block['innerBlocks'] ) ) {
        // Load each image in the gallery block and add data attributes.
        foreach ( $block['innerBlocks'] as $inner_block ) {
            if ( isset( $inner_block['attrs']['id'] ) ) {
                $image_id       = $inner_block['attrs']['id'];
                $image_url      = wp_get_attachment_url( $image_id );
                $attachment_url = get_attachment_link( $image_id );

                // Replace the <img> tag with the necessary data attributes.
                $block_content = str_replace(
                    'wp-image-' . $image_id,
                    'wp-image-' . $image_id . '" data-modal-url="' . esc_url( $image_url ) . '" data-attachment-url="' . esc_url( $attachment_url ),
                    $block_content
                );
            }
        }
    }
    return $block_content;
}
add_filter( 'render_block', 'gm_modify_block_gallery_output', 10, 2 );

/**
 * Disables WordPress.org update checks to prevent overrides by repository versions.
 *
 * @param object $transient The transient object containing plugin update information.
 *
 * @return object Filtered transient object with specified plugin update removed, if present.
 */
function disable_wporg_plugin_updates( $transient ) {

    // Check if transient response is empty
    if ( empty( $transient->response ) ) {
        return $transient;
    }

    // Get the plugin slug.
    $plugin_slug = plugin_basename( __FILE__ );

    // Remove update response.
    if ( isset( $transient->response[ $plugin_slug ] ) ) {
        unset( $transient->response[ $plugin_slug ] );
    }

    return $transient;
}
add_filter( 'site_transient_update_plugins', 'disable_wporg_plugin_updates' );
