<?php
/**
 * Fetch and Import DQWeek Posts directly from Chrome.
 * Uses WP REST API to fetch data in batches.
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
    die("wp-load.php not found");
}

$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 10;
$do_categories = isset($_GET['categories']) ? true : false;

echo "<h1>DQWeek Importer (Staging: egz1w2tn78-staging.onrocket.site)</h1>";

if ($do_categories) {
    echo "<h2>Importing Categories (Page $page)</h2>";
    $url = "https://www.dqweek.com/wp-json/wp/v2/categories?per_page=$per_page&page=$page";
    
    $response = wp_remote_get($url, ['timeout' => 30]);
    if (is_wp_error($response)) {
        die("Error fetching: " . $response->get_error_message());
    }
    
    $body = wp_remote_retrieve_body($response);
    $categories = json_decode($body, true);
    
    if (empty($categories) || isset($categories['code'])) {
        die("No more categories found or error: " . print_r($categories, true));
    }
    
    foreach ($categories as $cat) {
        $name = sanitize_text_field($cat['name']);
        $slug = sanitize_title($cat['slug']);
        
        $term = get_term_by('slug', $slug, 'category');
        if (!$term) {
            $res = wp_insert_term($name, 'category', ['slug' => $slug]);
            if (!is_wp_error($res)) {
                echo "<p style='color:green'>Created category: $name</p>";
            } else {
                echo "<p style='color:red'>Error creating category $name: " . $res->get_error_message() . "</p>";
            }
        } else {
            echo "<p style='color:orange'>Category exists: $name</p>";
        }
    }
    
    $next_page = $page + 1;
    echo "<p><a href='?categories=1&page=$next_page&per_page=$per_page'>Next Page of Categories >></a></p>";
    echo "<p><a href='?page=1&per_page=10'>Start Importing Posts >></a></p>";
    
} else {
    echo "<h2>Importing Posts (Page $page, $per_page per page)</h2>";
    $url = "https://www.dqweek.com/wp-json/wp/v2/posts?per_page=$per_page&page=$page&_embed";
    
    $response = wp_remote_get($url, ['timeout' => 60]);
    if (is_wp_error($response)) {
        die("Error fetching: " . $response->get_error_message());
    }
    
    $body = wp_remote_retrieve_body($response);
    $posts = json_decode($body, true);
    
    if (empty($posts) || isset($posts['code'])) {
        die("No more posts found or error: " . print_r($posts, true));
    }
    
    foreach ($posts as $post) {
        $title = sanitize_text_field($post['title']['rendered']);
        $content = wp_kses_post($post['content']['rendered']);
        $slug = sanitize_title($post['slug']);
        $date = $post['date'];
        
        $existing = get_page_by_path($slug, OBJECT, 'post');
        if ($existing) {
            echo "<p style='color:orange'>Post exists: $title</p>";
            continue;
        }
        
        $cat_ids = [];
        if (isset($post['_embedded']['wp:term'])) {
            foreach ($post['_embedded']['wp:term'] as $terms) {
                foreach ($terms as $term) {
                    if ($term['taxonomy'] === 'category') {
                        $cat_slug = sanitize_title($term['slug']);
                        $cat_name = sanitize_text_field($term['name']);
                        
                        $existing_term = get_term_by('slug', $cat_slug, 'category');
                        if (!$existing_term) {
                            $new_term = wp_insert_term($cat_name, 'category', ['slug' => $cat_slug]);
                            if (!is_wp_error($new_term)) {
                                $cat_ids[] = $new_term['term_id'];
                            }
                        } else {
                            $cat_ids[] = $existing_term->term_id;
                        }
                    }
                }
            }
        }
        
        $post_data = [
            'post_title'    => $title,
            'post_content'  => $content,
            'post_status'   => 'publish',
            'post_type'     => 'post',
            'post_name'     => $slug,
            'post_date'     => date('Y-m-d H:i:s', strtotime($date)),
            'post_category' => $cat_ids,
        ];
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            echo "<p style='color:red'>Error creating post $title: " . $post_id->get_error_message() . "</p>";
        } else {
            echo "<p style='color:green'>Created post: $title</p>";
            
            if (isset($post['_embedded']['wp:featuredmedia'][0]['source_url'])) {
                $image_url = esc_url_raw($post['_embedded']['wp:featuredmedia'][0]['source_url']);
                require_once ABSPATH . 'wp-admin/includes/image.php';
                require_once ABSPATH . 'wp-admin/includes/file.php';
                require_once ABSPATH . 'wp-admin/includes/media.php';

                $media_id = media_sideload_image($image_url, $post_id, $title, 'id');
                if (!is_wp_error($media_id)) {
                    set_post_thumbnail($post_id, $media_id);
                }
            }
        }
    }
    
    $next_page = $page + 1;
    echo "<p><a href='?page=$next_page&per_page=$per_page' style='font-size:24px; font-weight:bold;'>Next Page >></a></p>";
    
    // Auto-redirect to next page
    echo "<script>
        setTimeout(function() {
            window.location.href = '?page=$next_page&per_page=$per_page';
        }, 2000);
    </script>";
}
