<?php
/**
 * @Packge     : Quanto
 * @Version    : 1.0
 * @Author     : Mirrortheme
 * @Author URI : https://mirrortheme.com/
 *
 */

// Block direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit();
}

/**
 * Helper function to safely render header post content
 */
if ( ! function_exists( 'quanto_render_header_builder_post' ) ) {
    function quanto_render_header_builder_post( $post_id ) {
        if ( empty( $post_id ) ) {
            return false;
        }
        $header_post = get_post( $post_id );
        if ( ! $header_post ) {
            return false;
        }

        $rendered_content = '';
        if ( defined( 'ELEMENTOR_VERSION' ) && class_exists( '\Elementor\Plugin' ) ) {
            $rendered_content = \Elementor\Plugin::instance()->frontend->get_builder_content_for_display( $header_post->ID );
            if ( empty( trim( (string) $rendered_content ) ) ) {
                $rendered_content = \Elementor\Plugin::instance()->frontend->get_builder_content( $header_post->ID, true );
            }
        }
        
        if ( empty( trim( (string) $rendered_content ) ) && ! empty( $header_post->post_content ) ) {
            $rendered_content = apply_filters( 'the_content', $header_post->post_content );
        }

        if ( ! empty( trim( (string) $rendered_content ) ) ) {
            echo '<header class="header quanto-header-builder-wrap">';
            echo $rendered_content;
            echo '</header>';
            return true;
        }

        return false;
    }
}

// Render logic
$header_rendered = false;

if ( class_exists( 'ReduxFramework' ) ) {

    // 1. Check page specific elementor post meta settings first
    $queried_id = get_queried_object_id();
    if ( empty( $queried_id ) ) {
        $queried_id = get_the_ID();
    }

    if ( ! empty( $queried_id ) && class_exists( '\Elementor\Core\Settings\Manager' ) ) {
        $settings_manager = \Elementor\Core\Settings\Manager::get_settings_managers( 'page' );
        $settings_model   = $settings_manager ? $settings_manager->get_model( $queried_id ) : null;
        $header_style     = $settings_model ? $settings_model->get_settings( 'quanto_header_style' ) : null;
        $builder_option   = $settings_model ? $settings_model->get_settings( 'quanto_header_builder_option' ) : null;

        if ( $header_style === 'header_builder' && ! empty( $builder_option ) ) {
            $header_rendered = quanto_render_header_builder_post( $builder_option );
        }
    }

    // 2. Check Archive / Home / Search specific header option in Redux
    if ( ! $header_rendered && ( is_archive() || is_home() || is_front_page() || is_search() || ( is_single() && get_post_type() === 'post' ) ) ) {
        $archive_header_id = quanto_opt( 'quanto_archive_header_select_options' );
        if ( ! empty( $archive_header_id ) ) {
            $header_rendered = quanto_render_header_builder_post( $archive_header_id );
        }
    }

    // 3. Check Global Header option in Redux
    if ( ! $header_rendered ) {
        $global_header_id = quanto_opt( 'quanto_header_select_options' );
        if ( ! empty( $global_header_id ) ) {
            $header_rendered = quanto_render_header_builder_post( $global_header_id );
        }
    }
}

// 4. Default Fallback Theme Header if no builder header rendered
if ( ! $header_rendered ) {
    quanto_global_header_option();
}
