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
    exit;
}

/**
 * Include File
 *
 */

// Constants
require_once get_parent_theme_file_path() . '/inc/quanto-constants.php';

//theme setup
require_once QUANTO_DIR_PATH_INC . 'theme-setup.php';

//essential scripts
require_once QUANTO_DIR_PATH_INC . 'essential-scripts.php';

//template helper
require_once QUANTO_DIR_PATH_INC . 'template-helper.php';
require_once QUANTO_DIR_PATH_INC . 'wp-html-helper.php';

// plugin activation
require_once QUANTO_DIR_PATH_INC . 'Quanto-framework/plugins-activation/quanto-active-plugins.php';

// quanto redux options
require_once QUANTO_DIR_PATH_INC . 'Quanto-framework/quanto-options/quanto-options.php';

// quanto meta options
require_once QUANTO_DIR_PATH_INC . 'Quanto-framework/quanto-meta/quanto-config.php';

// quanto functions
require_once QUANTO_DIR_PATH_INC . 'quanto-functions.php';

// quanto common css
require_once QUANTO_DIR_PATH_INC . 'quanto-commoncss.php';

// quanto breadcrumbs
require_once QUANTO_DIR_PATH_INC . 'quanto-breadcrumbs.php';

// quanto widgets reg
require_once QUANTO_DIR_PATH_INC . 'quanto-widgets-reg.php';

// quanto hooks functions & hooks
require_once QUANTO_DIR_PATH_INC . 'hooks/hooks-functions.php';
require_once QUANTO_DIR_PATH_INC . 'hooks/hooks.php';

if ( ! function_exists( 'cmr_get_unique_enterprise_post_ids' ) ) {
    function cmr_get_unique_enterprise_post_ids() {
        global $wpdb;
        $results = $wpdb->get_results("
            SELECT p.ID, p.post_title 
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
            INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
            INNER JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
            WHERE p.post_type IN ('post', 'cmr_news', 'cmr_media') 
              AND p.post_status = 'publish' 
              AND (t.slug IN ('enterprise-connect', 'enterprise', 'enterprise_connect') OR t.name LIKE '%Enterprise Connect%' OR t.name LIKE '%Enterprise%')
            ORDER BY p.post_date DESC
            LIMIT 500
        ");

        $unique_ids = array();
        $seen_titles = array();
        if ( $results ) {
            foreach ( $results as $row ) {
                $title = trim( $row->post_title );
                if ( ! isset( $seen_titles[ $title ] ) ) {
                    $seen_titles[ $title ] = true;
                    $unique_ids[] = $row->ID;
                }
            }
        }

        if ( count( $unique_ids ) < 12 ) {
            $fallback = $wpdb->get_results("
                SELECT ID, post_title FROM {$wpdb->posts}
                WHERE post_type IN ('post', 'cmr_news', 'cmr_media') AND post_status = 'publish'
                ORDER BY post_date DESC
                LIMIT 30
            ");
            if ( $fallback ) {
                foreach ( $fallback as $row ) {
                    $title = trim( $row->post_title );
                    if ( ! isset( $seen_titles[ $title ] ) && ! in_array( $row->ID, $unique_ids ) ) {
                        $seen_titles[ $title ] = true;
                        $unique_ids[] = $row->ID;
                    }
                }
            }
        }

        return $unique_ids;
    }
}
