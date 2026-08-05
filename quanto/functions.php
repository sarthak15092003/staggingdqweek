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
