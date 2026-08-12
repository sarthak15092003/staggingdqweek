<?php
/**
 * CMR Enterprise Connect Grid Component
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_shortcode( 'cmr_enterprise_connect_grid', 'cmr_enterprise_connect_grid_shortcode' );

// Register as Native Elementor Widget
add_action( 'elementor/widgets/register', 'cmr_register_enterprise_connect_grid_elementor_widget' );
add_action( 'elementor/widgets/widgets_registered', 'cmr_register_enterprise_connect_grid_elementor_widget' );

function cmr_register_enterprise_connect_grid_elementor_widget( $widgets_manager = null ) {
    if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
        return;
    }

    if ( ! class_exists( 'CMR_Enterprise_Connect_Grid_Elementor_Widget' ) ) {
        class CMR_Enterprise_Connect_Grid_Elementor_Widget extends \Elementor\Widget_Base {

            public function get_name() {
                return 'cmr_enterprise_connect_grid';
            }

            public function get_title() {
                return esc_html__( 'CMR Enterprise Connect Grid', 'quanto' );
            }

            public function get_icon() {
                return 'eicon-posts-grid';
            }

            public function get_categories() {
                return [ 'general' ];
            }

            protected function register_controls() {
                $this->start_controls_section(
                    'content_section',
                    [
                        'label' => esc_html__( 'Content', 'quanto' ),
                        'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
                    ]
                );

                $this->add_control(
                    'link_featured',
                    [
                        'label'       => esc_html__( 'Link Featured', 'quanto' ),
                        'type'        => \Elementor\Controls_Manager::TEXT,
                        'default'     => '#featured',
                    ]
                );

                $this->add_control(
                    'link_latest',
                    [
                        'label'       => esc_html__( 'Link Latest', 'quanto' ),
                        'type'        => \Elementor\Controls_Manager::TEXT,
                        'default'     => '#latest',
                    ]
                );

                $this->add_control(
                    'link_market',
                    [
                        'label'       => esc_html__( 'Link Market Updates', 'quanto' ),
                        'type'        => \Elementor\Controls_Manager::TEXT,
                        'default'     => '#cmr-market-updates',
                    ]
                );

                $this->add_control(
                    'link_reports',
                    [
                        'label'       => esc_html__( 'Link Reports', 'quanto' ),
                        'type'        => \Elementor\Controls_Manager::TEXT,
                        'default'     => '#reports',
                    ]
                );

                $this->add_control(
                    'link_cmr_news',
                    [
                        'label'       => esc_html__( 'Link CMR in news', 'quanto' ),
                        'type'        => \Elementor\Controls_Manager::TEXT,
                        'default'     => '#cmr-in-news',
                    ]
                );

                $this->end_controls_section();
            }

            protected function render() {
                $settings = $this->get_settings_for_display();
                echo cmr_enterprise_connect_grid_shortcode( array(
                    'link_featured' => isset($settings['link_featured']) ? $settings['link_featured'] : '#featured',
                    'link_latest'   => isset($settings['link_latest']) ? $settings['link_latest'] : '#latest',
                    'link_market'   => isset($settings['link_market']) ? $settings['link_market'] : '#cmr-market-updates',
                    'link_reports'  => isset($settings['link_reports']) ? $settings['link_reports'] : '#reports',
                    'link_cmr_news' => isset($settings['link_cmr_news']) ? $settings['link_cmr_news'] : '#cmr-in-news',
                ) );
            }
        }
    }

    if ( is_object( $widgets_manager ) && method_exists( $widgets_manager, 'register' ) ) {
        $widgets_manager->register( new CMR_Enterprise_Connect_Grid_Elementor_Widget() );
    } elseif ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->widgets_manager ) ) {
        \Elementor\Plugin::$instance->widgets_manager->register_widget_type( new CMR_Enterprise_Connect_Grid_Elementor_Widget() );
    }
}

function cmr_enterprise_connect_grid_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'link_enterprise'   => '#enterprise-connect',
        'link_featured'     => '#featured',
        'link_latest'       => '#latest',
        'link_market'       => '#cmr-market-updates',
        'link_reports'      => '#reports',
        'link_cmr_news'     => '#cmr-in-news'
    ), $atts, 'cmr_enterprise_connect_grid' );

    ob_start();

    $unique_ids = cmr_get_unique_enterprise_post_ids();
    $sliced_ids = array_slice( $unique_ids, 0, 6 );

    $query = new WP_Query(); // Empty default
    if ( ! empty( $sliced_ids ) ) {
        $args = array(
            'post_type'      => array( 'post', 'cmr_news', 'cmr_media' ),
            'post__in'       => $sliced_ids,
            'orderby'        => 'post__in', // Maintain the correct date order from SQL
            'posts_per_page' => 6,
        );
        $query = new WP_Query( $args );
    }
    
    // Override max_num_pages so pagination knows exactly how many pages remain
    $query->max_num_pages = ceil( max( 0, count( $unique_ids ) ) / 6 );
    $query->found_posts = count( $unique_ids );

    ?>
    <style>
        .cmr-enterprisecgd-wrapper {
            font-family: 'Instrument Sans', sans-serif;
            max-width: 1280px;
            margin: 0 auto;
            padding: 40px 20px;
            color: #111;
        }

        /* Top Navigation Style */
        .cmr-enterprisecgd-top-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
            margin-bottom: 40px;
            background: #fff;
            position: relative;
            z-index: 99999;
        }
        .cmr-enterprisecgd-nav-title {
            font-size: 16px;
            font-weight: 600;
            color: #111;
        }
        .intel-nav-links {
            display: flex;
            gap: 35px;
            align-items: center;
        }
        .intel-nav-links a {
            color: #111;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        .intel-nav-links a:hover {
            color: #6A35FF;
        }
        .intel-nav-links a.cmr-nav-btn-subscribe:hover {
            background: #111 !important;
            color: #fff !important;
        }

        .intel-nav-fixed-js {
            position: fixed !important;
            left: 0;
            right: 0;
            width: 100% !important;
            z-index: 999999 !important;
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            padding: 15px max(20px, calc(50vw - 640px)) !important;
            margin: 0 !important;
            transition: top 0.2s ease-out !important;
            border-radius: 0;
            border-left: none;
            border-right: none;
            border-top: none;
        }

        /* Header Area */
        .cmr-enterprisecgd-header {
            margin-bottom: 40px;
        }
        .cmr-enterprisecgd-header h1 {
            font-size: 45px;
            font-weight: 600;
            margin: 40px 0 15px 0;
            letter-spacing: -1px;
            color: #111;
        }
        .cmr-enterprisecgd-header p {
            font-size: 16px;
            color: #555;
            margin: 0;
            line-height: 1.5;
        }

        /* Filters and Search */
        .cmr-enterprisecgd-filters-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            flex-wrap: wrap;
            gap: 20px;
        }
        .cmr-enterprisecgd-years {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        .cmr-enterprisecgd-year-btn {
            background: transparent;
            border: 1px solid #eaeaea;
            border-radius: 40px;
            padding: 8px 20px;
            font-size: 14px;
            color: #111;
            cursor: pointer;
            transition: all 0.3s;
            outline: none;
            font-family: inherit;
        }
        .cmr-enterprisecgd-year-btn:hover {
            border-color: #6B3FA0;
            color: #6B3FA0;
        }
        .cmr-enterprisecgd-year-btn.active {
            border-color: #6B3FA0;
            color: #6B3FA0;
        }
        .cmr-enterprisecgd-more-dropdown {
            position: relative;
            display: inline-block;
        }
        .cmr-enterprisecgd-more-btn {
            background: transparent;
            border: 1px solid #eaeaea;
            border-radius: 40px;
            padding: 8px 20px;
            font-size: 14px;
            color: #111;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            font-family: inherit;
        }
        .cmr-enterprisecgd-search {
            position: relative;
            width: 300px;
        }
        .cmr-enterprisecgd-search input {
            width: 100%;
            padding: 10px 40px 10px 20px;
            border: 1px solid #eaeaea;
            border-radius: 40px;
            font-size: 14px;
            outline: none;
            font-family: inherit;
        }
        .cmr-enterprisecgd-search-btn {
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
            background: #6B3FA0;
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        /* Grid */
        .cmr-enterprisecgd-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 40px;
            margin-bottom: 60px;
        }
        
        .cmr-enterprisecgd-card {
            display: flex;
            flex-direction: column;
        }
        
        .cmr-enterprisecgd-card-img-wrap {
            width: 100%;
            height: 240px !important;
            min-height: 240px !important;
            flex-shrink: 0;
            overflow: hidden;
            margin-bottom: 20px;
            background-color: #f4f4f4;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .cmr-enterprisecgd-card-img-wrap img {
            width: 100% !important;
            height: 100% !important;
            object-fit: cover !important;
            transition: transform 0.5s ease;
        }
        .cmr-enterprisecgd-card:hover .cmr-enterprisecgd-card-img-wrap img {
            transform: scale(1.05);
        }

        .cmr-enterprisecgd-card-meta {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: #888;
            margin-bottom: 12px;
            align-items: center;
        }
        .cmr-enterprisecgd-card-label {
            color: #888;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .cmr-enterprisecgd-card-label::before {
            content: "";
            width: 16px;
            height: 1px;
            background: #888;
            display: inline-block;
        }
        .cmr-enterprisecgd-card-label span { margin: 0 4px; }

        .cmr-enterprisecgd-card-title {
            font-size: 18px;
            font-weight: 600;
            line-height: 1.4;
            margin: 0 0 12px 0;
            color: #111;
        }
        .cmr-enterprisecgd-card-title a {
            color: inherit;
            text-decoration: none;
            letter-spacing: 1px;
        }

        .cmr-enterprisecgd-card-excerpt {
            font-size: 14px;
            color: #555;
            line-height: 1.6;
            margin: 0 0 20px 0;
            flex-grow: 1;
        }

        .cmr-enterprisecgd-card-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
            font-weight: 600;
            color: #111;
            text-decoration: none;
            border-bottom: 1px solid #111;
            padding-bottom: 2px;
            align-self: flex-start;
            transition: color 0.3s, border-color 0.3s;
        }
        .cmr-enterprisecgd-card-link:hover {
            color: #6B3FA0;
            border-color: #6B3FA0;
        }

        /* Load More Button */
        .cmr-enterprisecgd-load-more-wrap {
            text-align: center;
        }
        .cmr-enterprisecgd-load-more {
            background: transparent;
            border: 1px solid #ccc;
            border-radius: 40px;
            width: 260px;
            height: 44px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 600;
            color: #111;
            cursor: pointer;
            transition: all 0.3s;
            font-family: inherit;
            box-sizing: border-box;
        }
        .cmr-enterprisecgd-load-more:hover {
            border-color: #6B3FA0;
            color: #6B3FA0;
        }
        
        .cmr-enterprisecgd-loading {
            opacity: 0.5;
            pointer-events: none;
        }

        @media (max-width: 992px) {
            .cmr-enterprisecgd-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 768px) {
            .cmr-enterprisecgd-grid {
                grid-template-columns: 1fr;
            }
            .cmr-enterprisecgd-header h1 {
                font-size: 28px !important;
                margin: 20px 0 10px 0 !important;
            }
            .cmr-enterprisecgd-filters-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            .cmr-enterprisecgd-years {
                overflow-x: auto;
                white-space: nowrap;
                width: 100%;
                padding-bottom: 8px;
                -webkit-overflow-scrolling: touch;
                scrollbar-width: none;
            }
            .cmr-enterprisecgd-years::-webkit-scrollbar {
                display: none;
            }
            .cmr-enterprisecgd-year-btn {
                flex-shrink: 0;
            }
            .cmr-enterprisecgd-search {
                width: 100%;
            }
        }
        .intel-numeric-pagination {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 10px !important;
            margin-top: 30px !important;
        }
        .intel-numeric-pagination .page-numbers {
            padding: 0;
            width: 40px;
            height: 40px;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            border: none;
            border-radius: 50%;
            text-decoration: none;
            color: #333;
            font-size: 16px;
            font-weight: 500;
            background: transparent;
            box-sizing: border-box;
            white-space: nowrap !important;
        }
        .intel-numeric-pagination .page-numbers.current {
            background: #6A35FF;
            color: #fff;
        }
        .intel-numeric-pagination .page-numbers.prev, 
        .intel-numeric-pagination .page-numbers.next {
            width: auto !important;
            padding: 0 15px !important;
            border-radius: 20px !important;
            color: #6A35FF !important;
            white-space: nowrap !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 5px !important;
        }
        .intel-numeric-pagination .page-numbers.dots {
            width: auto !important;
        }
    </style>

    <div class="cmr-enterprisecgd-wrapper">
        
        <!-- Top Nav -->
        <div class="cmr-enterprisecgd-top-nav-wrap" style="margin-bottom: 40px;">
            <div class="cmr-enterprisecgd-top-nav intel-nav-bar">
                <div class="cmr-enterprisecgd-nav-title">Enterprise Connect</div>
                <div class="intel-nav-links">
                    <a href="<?php echo esc_attr($atts['link_featured']); ?>">Featured</a>
                    <a href="<?php echo esc_attr($atts['link_latest']); ?>">Latest</a>
                    <a href="<?php echo esc_attr($atts['link_market']); ?>">Market Updates</a>
                    <a href="<?php echo esc_attr($atts['link_reports']); ?>">Reports</a>
                    <a href="<?php echo esc_attr($atts['link_cmr_news']); ?>">CMR in news</a>
                    <a href="#cmr-footer-card-section" class="cmr-nav-btn-subscribe" style="display: none; align-items: center; justify-content: center; background: #fff; color: #111; font-weight: 600; font-size: 14px; padding: 8px 16px; border-radius: 40px; text-decoration: none; border: 1px solid #111; margin-left: 15px; line-height: 1; transition: all 0.3s ease;">
                        Subscribe now
                        <svg style="margin-left: 6px;" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="7" y1="17" x2="17" y2="7"></line><polyline points="7 7 17 7 17 17"></polyline></svg>
                    </a>
                </div>
            </div>
        </div>

        <!-- Header -->
        <div class="cmr-enterprisecgd-header">
            <h1>Enterprise Connect</h1>
            <p>Explore expert analysis, research reports, and real-time market signals shaping industries and business strategy.</p>
        </div>

        <!-- Filters & Search -->
        <div class="cmr-enterprisecgd-filters-row">
            <div class="cmr-enterprisecgd-years" id="cmr-enterprisecgd-years">
                <button class="cmr-enterprisecgd-year-btn active" data-year="">All</button>
                <button class="cmr-enterprisecgd-year-btn" data-year="2026">2026</button>
                <button class="cmr-enterprisecgd-year-btn" data-year="2025">2025</button>
                <button class="cmr-enterprisecgd-year-btn" data-year="2024">2024</button>
                <button class="cmr-enterprisecgd-year-btn" data-year="2023">2023</button>
                <button class="cmr-enterprisecgd-year-btn" data-year="2022">2022</button>
                <button class="cmr-enterprisecgd-year-btn" data-year="2021">2021</button>
                <div class="cmr-enterprisecgd-more-dropdown" style="position: relative;">
                    <button class="cmr-enterprisecgd-more-btn" id="cmr-enterprisecgd-more-btn">More <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg></button>
                    <div class="cmr-enterprisecgd-more-content" id="cmr-enterprisecgd-more-content" style="display: none; position: absolute; top: 100%; left: 0; background: #fff; border: 1px solid #eaeaea; border-radius: 8px; padding: 10px; z-index: 100; min-width: 120px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); margin-top: 5px;">
                        <button class="cmr-enterprisecgd-year-btn" data-year="2020" style="display:block; width:100%; text-align:left; border:none; border-radius:4px; padding:8px 12px; margin-bottom:4px; background:transparent;">2020</button>
                        <button class="cmr-enterprisecgd-year-btn" data-year="2019" style="display:block; width:100%; text-align:left; border:none; border-radius:4px; padding:8px 12px; margin-bottom:4px; background:transparent;">2019</button>
                        <button class="cmr-enterprisecgd-year-btn" data-year="2018" style="display:block; width:100%; text-align:left; border:none; border-radius:4px; padding:8px 12px; margin-bottom:4px; background:transparent;">2018</button>
                        <button class="cmr-enterprisecgd-year-btn" data-year="2017" style="display:block; width:100%; text-align:left; border:none; border-radius:4px; padding:8px 12px; margin-bottom:4px; background:transparent;">2017</button>
                        <button class="cmr-enterprisecgd-year-btn" data-year="2016" style="display:block; width:100%; text-align:left; border:none; border-radius:4px; padding:8px 12px; background:transparent;">2016</button>
                    </div>
                </div>
            </div>
            <div class="cmr-enterprisecgd-search">
                <form id="cmr-enterprisecgd-search-form" onsubmit="return false;">
                    <input type="text" id="cmr-enterprisecgd-search-input" placeholder="Search by name">
                    <button type="submit" class="cmr-enterprisecgd-search-btn">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    </button>
                </form>
            </div>
        </div>

        <!-- Grid -->
        <div class="cmr-enterprisecgd-grid" id="cmr-enterprisecgd-grid">
            <?php
            if ( $query->have_posts() ) {
                while ( $query->have_posts() ) {
                    $query->the_post();
                    $post_id = get_the_ID();
                    $title = get_the_title();
                    $link = get_permalink();
                    $excerpt = wp_trim_words( get_the_excerpt(), 18 );
                    if ( empty($excerpt) ) {
                        $excerpt = wp_trim_words( get_post_field('post_content', $post_id), 18 );
                    }
                    $bg_image = get_the_post_thumbnail_url( $post_id, 'medium_large' );
                    
                    $content = get_post_field( 'post_content', $post_id );
                    $word_count = str_word_count( strip_tags( $content ) );
                    $read_time = ceil( $word_count / 200 );
                    if ($read_time < 1) $read_time = 1;
                    $date = get_the_date('d M Y');
                    ?>
                    <div class="cmr-enterprisecgd-card">
                        <div class="cmr-enterprisecgd-card-img-wrap">
                            <a href="<?php echo esc_url($link); ?>" style="display: block; width: 100%; height: 100%;">
                                <?php if ( $bg_image ) : ?>
                                    <img src="<?php echo esc_url($bg_image); ?>" alt="<?php echo esc_attr($title); ?>" style="width: 100% !important; height: 100% !important; object-fit: cover !important; margin: 0 !important; padding: 0 !important; display: block !important;">
                                <?php else : ?>
                                    <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: #ccc;">
                                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                                    </div>
                                <?php endif; ?>
                            </a>
                        </div>
                        <div class="cmr-enterprisecgd-card-meta">
                            <div class="cmr-enterprisecgd-card-label">Enterprise Connect <span>|</span> <?php echo esc_html($date); ?></div>
                            <div class="cmr-enterprisecgd-card-time"><?php echo esc_html($read_time); ?> min read</div>
                        </div>
                        <h3 class="cmr-enterprisecgd-card-title">
                            <a href="<?php echo esc_url($link); ?>"><?php echo esc_html($title); ?></a>
                        </h3>
                        <p class="cmr-enterprisecgd-card-excerpt"><?php echo esc_html($excerpt); ?></p>
                        <a href="<?php echo esc_url($link); ?>" class="cmr-enterprisecgd-card-link">
                            Read full Release 
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="7" y1="17" x2="17" y2="7"></line><polyline points="7 7 17 7 17 17"></polyline></svg>
                        </a>
                    </div>
                    <?php
                }
            } else {
                echo '<p>No Enterprise Connect found.</p>';
            }
            $has_more = $query->max_num_pages > 1;
            wp_reset_postdata();
            ?>
        </div>

        <!-- Pagination -->
        <div class="cmr-enterprisecgd-pagination-wrap" style="display: <?php echo ($query->max_num_pages > 1) ? 'block' : 'none'; ?>;">
            <?php if ( $query->max_num_pages > 1 ) : ?>
                <div class="intel-numeric-pagination" style="text-align: center; margin-top: 30px; display: flex; justify-content: center; gap: 10px;">
                    <?php
                    echo paginate_links( array(
                        'base'      => add_query_arg( 'paged', '%#%' ),
                        'format'    => '',
                        'prev_text' => __( '&laquo; Prev' ),
                        'next_text' => __( 'Next &raquo;' ),
                        'total'     => $query->max_num_pages,
                        'current'   => max( 1, get_query_var('paged') ),
                        'type'      => 'plain',
                    ) );
                    ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        let currentPage = 1;
        let currentYear = '';
        let currentSearch = '';
        
        const grid = document.getElementById('cmr-enterprisecgd-grid');
        const loadMoreBtn = document.getElementById('cmr-enterprisecgd-load-more-btn');
        const yearBtns = document.querySelectorAll('.cmr-enterprisecgd-year-btn');
        const searchForm = document.getElementById('cmr-enterprisecgd-search-form');
        const searchInput = document.getElementById('cmr-enterprisecgd-search-input');

        function fetchPosts(isLoadMore = false, resetPage = false) {
            if (resetPage) {
                currentPage = 1;
            }
            if (!isLoadMore) {
                grid.innerHTML = '<p>Loading...</p>';
            }
            
            if (loadMoreBtn) loadMoreBtn.classList.add('cmr-enterprisecgd-loading');
            
            const data = new FormData();
            data.append('action', 'cmr_load_more_enterprise_connect');
            data.append('page', currentPage);
            data.append('year', currentYear);
            data.append('search', currentSearch);

            fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
                method: 'POST',
                body: data
            })
            .then(res => res.json())
            .then(response => {
                if (response.success) {
                    if (!isLoadMore) {
                        grid.innerHTML = response.data.html || '<p>No Enterprise Connect found.</p>';
                    } else {
                        grid.insertAdjacentHTML('beforeend', response.data.html);
                    }
                    
                    const paginationWrap = document.querySelector('.cmr-enterprisecgd-pagination-wrap');
                    if (response.data.pagination) {
                        if (loadMoreBtn) loadMoreBtn.parentElement.style.display = 'none';
                        if (paginationWrap) {
                            paginationWrap.innerHTML = '<div class="intel-numeric-pagination" style="text-align: center; margin-top: 30px; display: flex; justify-content: center; gap: 10px;">' + response.data.pagination + '</div>';
                            paginationWrap.style.display = 'block';
                        }
                    } else if (response.data.has_more) {
                        if (loadMoreBtn) loadMoreBtn.parentElement.style.display = 'block';
                        if (paginationWrap) paginationWrap.style.display = 'none';
                    } else {
                        if (loadMoreBtn) loadMoreBtn.parentElement.style.display = 'none';
                        if (paginationWrap) paginationWrap.style.display = 'none';
                    }
                }
                if (loadMoreBtn) loadMoreBtn.classList.remove('cmr-enterprisecgd-loading');
            })
            .catch(err => {
                console.error(err);
                if (loadMoreBtn) loadMoreBtn.classList.remove('cmr-enterprisecgd-loading');
            });
        }

        // Year Filter
        yearBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                yearBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                currentYear = this.getAttribute('data-year');
                
                const viewAllLink = document.querySelector('.cmr-enterprisecgd-load-more');
                if (viewAllLink) {
                    let baseUrl = viewAllLink.getAttribute('href').split('?')[0];
                    if (currentYear) {
                        viewAllLink.setAttribute('href', baseUrl + '?y=' + currentYear);
                    } else {
                        viewAllLink.setAttribute('href', baseUrl);
                    }
                }
                
                const moreContent = document.getElementById('cmr-enterprisecgd-more-content');
                if (moreContent) {
                    moreContent.style.display = 'none';
                }
                
                fetchPosts(false, true);
            });
        });

        // More Dropdown
        const moreBtn = document.getElementById('cmr-enterprisecgd-more-btn');
        const moreContent = document.getElementById('cmr-enterprisecgd-more-content');
        if (moreBtn && moreContent) {
            moreBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                if (moreContent.style.display === 'none') {
                    moreContent.style.display = 'block';
                } else {
                    moreContent.style.display = 'none';
                }
            });
            document.addEventListener('click', function(e) {
                if (!moreContent.contains(e.target) && e.target !== moreBtn) {
                    moreContent.style.display = 'none';
                }
            });
        }

        // Search
        if (searchForm) {
            searchForm.addEventListener('submit', function(e) {
                e.preventDefault();
                currentSearch = searchInput.value.trim();
                fetchPosts(false, true);
            });
        }

        // Load More
        if (loadMoreBtn) {
            loadMoreBtn.addEventListener('click', function() {
                currentPage++;
                fetchPosts(true);
            });
        }

        // Numeric Pagination Clicks (Capture phase to prevent any full page reload)
        document.addEventListener('click', function(e) {
            const link = e.target.closest('.cmr-enterprisecgd-pagination-wrap a, .intel-numeric-pagination a, a.page-numbers');
            if (link) {
                e.preventDefault();
                e.stopPropagation();
                if (e.stopImmediatePropagation) e.stopImmediatePropagation();

                const href = link.getAttribute('href') || '';
                let targetPage = 1;

                const match = href.match(/(?:paged|page)[=\/](\d+)/);
                if (match) {
                    targetPage = parseInt(match[1], 10);
                } else {
                    const cleanText = link.textContent.replace(/[^0-9]/g, '');
                    if (cleanText !== '') {
                        targetPage = parseInt(cleanText, 10);
                    } else if (link.classList.contains('prev') || link.textContent.indexOf('Prev') !== -1) {
                        targetPage = Math.max(1, currentPage - 1);
                    } else if (link.classList.contains('next') || link.textContent.indexOf('Next') !== -1) {
                        targetPage = currentPage + 1;
                    } else {
                        targetPage = 1;
                    }
                }

                if (isNaN(targetPage) || targetPage < 1) {
                    targetPage = 1;
                }

                currentPage = targetPage;
                fetchPosts(false);

                const gridEl = document.getElementById('cmr-enterprisecgd-grid');
                if (gridEl) {
                    gridEl.scrollIntoView({ behavior: 'smooth' });
                }
            }
        }, true);
    });
    </script>
    <?php
    return ob_get_clean();
}

add_action( 'wp_ajax_cmr_load_more_enterprise_connect', 'cmr_load_more_enterprise_connect_ajax' );
add_action( 'wp_ajax_nopriv_cmr_load_more_enterprise_connect', 'cmr_load_more_enterprise_connect_ajax' );

function cmr_load_more_enterprise_connect_ajax() {
    $paged  = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $year   = isset($_POST['year']) ? sanitize_text_field($_POST['year']) : '';
    $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
    
    if ( empty($year) && empty($search) ) {
        $unique_ids = cmr_get_unique_enterprise_post_ids();
        $offset = ( ($paged - 1) * 6 );
        $sliced_ids = array_slice( $unique_ids, $offset, 6 );
        
        if ( empty($sliced_ids) ) {
            wp_send_json_success(array('html' => '', 'has_more' => false, 'pagination' => ''));
        }
        
        $args = array(
            'post_type'      => array( 'post', 'cmr_news', 'cmr_media' ),
            'post__in'       => $sliced_ids,
            'orderby'        => 'post__in',
            'posts_per_page' => 6,
        );
        $query = new WP_Query( $args );
        $total_pages = ceil( max( 0, count( $unique_ids ) ) / 6 );
    } else {
        $offset = ( ($paged - 1) * 6 );
        global $wpdb;

        $year_cond = '';
        if ( !empty($year) ) {
            $year_cond = $wpdb->prepare( " AND YEAR(p.post_date) = %d ", $year );
        }
        
        $search_cond = '';
        if ( !empty($search) ) {
            $search_like = '%' . $wpdb->esc_like( $search ) . '%';
            $search_cond = $wpdb->prepare( " AND (p.post_title LIKE %s OR p.post_content LIKE %s) ", $search_like, $search_like );
        }

        $sql = "
            SELECT DISTINCT p.ID 
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
            INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
            INNER JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
            WHERE p.post_type IN ('post', 'cmr_news', 'cmr_media') 
              AND p.post_status = 'publish' 
              AND (t.slug IN ('enterprise-connect', 'enterprise', 'enterprise_connect') OR t.name LIKE '%Enterprise Connect%' OR t.name LIKE '%Enterprise%')
              {$year_cond}
              {$search_cond}
            ORDER BY p.post_date DESC
        ";
        $results = $wpdb->get_results( $sql );
        $all_ids = array();
        if ( $results ) {
            foreach ( $results as $r ) {
                $all_ids[] = $r->ID;
            }
        }

        $total_posts = count( $all_ids );
        $total_pages = ceil( $total_posts / 6 );
        $sliced_ids = array_slice( $all_ids, $offset, 6 );

        if ( empty($sliced_ids) ) {
            wp_send_json_success(array('html' => '', 'has_more' => false, 'pagination' => ''));
        }

        $args = array(
            'post_type'      => array( 'post', 'cmr_news', 'cmr_media' ),
            'post__in'       => $sliced_ids,
            'orderby'        => 'post__in',
            'posts_per_page' => 6,
        );
        $query = new WP_Query( $args );
    }

    ob_start();
    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            $post_id = get_the_ID();
            $title = get_the_title();
            $link = get_permalink();
            $excerpt = wp_trim_words( get_the_excerpt(), 18 );
            if ( empty($excerpt) ) {
                $excerpt = wp_trim_words( get_post_field('post_content', $post_id), 18 );
            }
            $bg_image = get_the_post_thumbnail_url( $post_id, 'medium_large' );
            $content = get_post_field( 'post_content', $post_id );
            $word_count = str_word_count( strip_tags( $content ) );
            $read_time = ceil( $word_count / 200 );
            if ($read_time < 1) $read_time = 1;
            $date = get_the_date('d M Y');
            ?>
            <div class="cmr-enterprisecgd-card">
                <div class="cmr-enterprisecgd-card-img-wrap">
                    <a href="<?php echo esc_url($link); ?>" style="display: block; width: 100%; height: 100%;">
                        <?php if ( $bg_image ) : ?>
                            <img src="<?php echo esc_url($bg_image); ?>" alt="<?php echo esc_attr($title); ?>" style="width: 100% !important; height: 100% !important; object-fit: cover !important; margin: 0 !important; padding: 0 !important; display: block !important;">
                        <?php else : ?>
                            <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: #ccc;">
                                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                            </div>
                        <?php endif; ?>
                    </a>
                </div>
                <div class="cmr-enterprisecgd-card-meta">
                    <div class="cmr-enterprisecgd-card-label">Enterprise Connect <span>|</span> <?php echo esc_html($date); ?></div>
                    <div class="cmr-enterprisecgd-card-time"><?php echo esc_html($read_time); ?> min read</div>
                </div>
                <h3 class="cmr-enterprisecgd-card-title">
                    <a href="<?php echo esc_url($link); ?>"><?php echo esc_html($title); ?></a>
                </h3>
                <p class="cmr-enterprisecgd-card-excerpt"><?php echo esc_html($excerpt); ?></p>
                <a href="<?php echo esc_url($link); ?>" class="cmr-enterprisecgd-card-link">
                    Read full Release 
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="7" y1="17" x2="17" y2="7"></line><polyline points="7 7 17 7 17 17"></polyline></svg>
                </a>
            </div>
            <?php
        }
    }
    $html = ob_get_clean();
    wp_reset_postdata();

    $pagination = '';
    if ( $total_pages > 1 ) {
        $pagination = paginate_links( array(
            'base'      => '%_%',
            'format'    => '?paged=%#%',
            'total'     => $total_pages,
            'current'   => $paged,
            'prev_text' => '&laquo; Prev',
            'next_text' => 'Next &raquo;',
            'type'      => 'plain',
        ) );
    }

    wp_send_json_success( array(
        'html'        => $html,
        'has_more'    => $paged < $total_pages,
        'pagination'  => $pagination,
        'total_pages' => $total_pages
    ) );
}
