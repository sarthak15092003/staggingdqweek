<?php
/**
 * Migration Bridge Endpoint for DQWeek WordPress Site
 */

// Load WordPress bootstrap
$wp_load_paths = [
    __DIR__ . '/../../../wp-load.php',
    __DIR__ . '/../../../../wp-load.php',
    $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php'
];

$wp_loaded = false;
foreach ($wp_load_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $wp_loaded = true;
        break;
    }
}

if (!$wp_loaded) {
    http_response_code(500);
    echo json_encode(['error' => 'wp-load.php not found']);
    exit;
}

// Security Secret Key Check
define('IMPORT_SECRET_KEY', 'dqweek_migration_secret_2026');

$headers = getallheaders();
$auth_key = isset($_SERVER['HTTP_X_IMPORT_KEY']) ? $_SERVER['HTTP_X_IMPORT_KEY'] : (isset($_POST['secret_key']) ? $_POST['secret_key'] : (isset($_GET['secret_key']) ? $_GET['secret_key'] : ''));

if ($auth_key !== IMPORT_SECRET_KEY) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access. Invalid or missing secret key.']);
    exit;
}

header('Content-Type: application/json');

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'status';

if ($action === 'status') {
    echo json_encode([
        'status' => 'ready',
        'site_name' => get_bloginfo('name'),
        'site_url' => get_site_url(),
        'post_count' => wp_count_posts()->publish
    ]);
    exit;
}

if ($action === 'debug_header') {
    $home_page = get_page_by_path('home');
    $page_id = $home_page ? $home_page->ID : 45;
    $template = get_post_meta($page_id, '_wp_page_template', true);
    
    $redux_active = class_exists('ReduxFramework');
    $elementor_active = defined('ELEMENTOR_VERSION');
    
    $header_options = function_exists('quanto_opt') ? quanto_opt('quanto_header_options') : null;
    $header_select = function_exists('quanto_opt') ? quanto_opt('quanto_header_select_options') : null;
    
    $post_meta_header_style = get_post_meta($page_id, '_quanto_header_style', true);
    $post_meta_header_builder = get_post_meta($page_id, '_quanto_header_builder_option', true);

    $header_post_12 = get_post(12);

    $builder_display = \Elementor\Plugin::instance()->frontend->get_builder_content_for_display(12);
    $builder_content = \Elementor\Plugin::instance()->frontend->get_builder_content(12, true);
    echo json_encode([
        'home_page_id' => $page_id,
        'get_builder_content_for_display_len' => strlen($builder_display),
        'get_builder_content_len' => strlen($builder_content),
        'apply_filters_the_content_len' => strlen($raw_content),
    ]);
    exit;
}

if ($action === 'fix_home_template') {
    $home_page = get_page_by_path('home');
    $page_id = $home_page ? $home_page->ID : 45;
    
    // Change template from elementor_header_footer to default
    update_post_meta($page_id, '_wp_page_template', 'default');
    
    echo json_encode([
        'success' => true,
        'page_id' => $page_id,
        'new_template' => get_post_meta($page_id, '_wp_page_template', true)
    ]);
    exit;
}

if ($action === 'create_category') {
    $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
    $slug = isset($_POST['slug']) ? sanitize_title($_POST['slug']) : '';
    
    if (empty($name)) {
        echo json_encode(['error' => 'Category name required']);
        exit;
    }
    
    $term = get_term_by('slug', $slug, 'category');
    if ($term) {
        echo json_encode(['success' => true, 'term_id' => $term->term_id, 'existed' => true]);
        exit;
    }
    
    $res = wp_insert_term($name, 'category', ['slug' => $slug]);
    if (is_wp_error($res)) {
        echo json_encode(['error' => $res->get_error_message()]);
    } else {
        echo json_encode(['success' => true, 'term_id' => $res['term_id'], 'existed' => false]);
    }
    exit;
}

if ($action === 'create_post') {
    $title = isset($_POST['title']) ? wp_strip_all_tags($_POST['title']) : '';
    $content = isset($_POST['content']) ? $_POST['content'] : '';
    $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';
    $slug = isset($_POST['slug']) ? sanitize_title($_POST['slug']) : '';
    $category_slug = isset($_POST['category']) ? sanitize_title($_POST['category']) : '';
    $image_url = isset($_POST['image_url']) ? esc_url_raw($_POST['image_url']) : '';
    
    if (empty($title)) {
        echo json_encode(['error' => 'Post title required']);
        exit;
    }

    // Check if post already exists by slug or title
    if (!empty($slug)) {
        $existing = get_page_by_path($slug, OBJECT, 'post');
        if ($existing) {
            echo json_encode(['success' => true, 'post_id' => $existing->ID, 'existed' => true]);
            exit;
        }
    }

    $cat_ids = [];
    if (!empty($category_slug)) {
        $term = get_term_by('slug', $category_slug, 'category');
        if (!$term) {
            $cat_name = ucwords(str_replace('-', ' ', $category_slug));
            $new_term = wp_insert_term($cat_name, 'category', ['slug' => $category_slug]);
            if (!is_wp_error($new_term)) {
                $cat_ids[] = $new_term['term_id'];
            }
        } else {
            $cat_ids[] = $term->term_id;
        }
    }

    $post_data = [
        'post_title'    => $title,
        'post_content'  => $content,
        'post_status'   => 'publish',
        'post_type'     => 'post',
        'post_name'     => $slug,
        'post_category' => $cat_ids,
    ];

    if (!empty($date)) {
        $post_data['post_date'] = date('Y-m-d H:i:s', strtotime($date));
    }

    $post_id = wp_insert_post($post_data);

    if (is_wp_error($post_id)) {
        echo json_encode(['error' => $post_id->get_error_message()]);
        exit;
    }

    // Attach Featured Image if provided
    if (!empty($image_url) && $post_id) {
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $media_id = media_sideload_image($image_url, $post_id, $title, 'id');
        if (!is_wp_error($media_id)) {
            set_post_thumbnail($post_id, $media_id);
        }
    }

    echo json_encode(['success' => true, 'post_id' => $post_id, 'existed' => false]);
    exit;
}
