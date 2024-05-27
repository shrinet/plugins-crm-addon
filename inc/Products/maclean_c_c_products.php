<?php

namespace MacleanCustomCode\Products;

if ( !defined( 'ABSPATH' ) ) {
    exit( 'Direct script access denied.' );
}

class MacleanCCProducts
{

    public function __construct( $master )
    {
        $this->master = $master;
        $this->setup_actions();
        $this->setup_filters();
        $this->setup_menus();
        $this->setup_shortcodes();
    }

    public function setup_actions()
    {
        add_action( "wp_head", array( $this, "redirect_products_and_lowest_categories" ) );
        add_action( "wp_enqueue_scripts", array( $this, "enqueue_scripts" ) );
        add_action( 'save_post', array( $this, 'product_saved' ) );
    }

    public function setup_filters()
    {

    }

    public function setup_menus()
    {
    }

    public function setup_shortcodes()
    {
        add_shortcode( "product_category_page", array( $this, "product_category_page" ) );
        add_shortcode( "product_category_table_dim_drawings", array( $this, "product_category_table_dim_drawings" ) );
        add_shortcode( "product_category_table_description", array( $this, "product_category_table_description" ) );
        add_shortcode( "product_category_table_body", array( $this, "product_category_table_body" ) );
        add_shortcode( "product_category_tables", array( $this, "product_category_tables" ) );
        add_shortcode( "product_category_breadcrumbs", array( $this, "product_category_breadcrumbs" ) );
        add_shortcode( "product_category_description_images", array( $this, "product_category_description_images" ) );
        add_shortcode( "product_category_description", array( $this, "product_category_description" ) );
        add_shortcode( "product_category_title", array( $this, "product_category_title" ) );
        add_shortcode( "product_category_filters", array( $this, "product_category_filters" ) );
        add_shortcode( 'product_print_category_page', array( $this, 'product_print_category_page' ) );

        add_shortcode( "product_category_sort", array( $this, "product_category_sort" ) );
        add_shortcode( "product_category_found_count", array( $this, "product_category_found_count" ) );
        add_shortcode( "product_category_sub_categories", array( $this, "product_category_sub_categories" ) );
        add_shortcode( "product_category_links_tab", array( $this, "product_category_links_tab" ) );
        add_shortcode( "product_category_description_tab", array( $this, "product_category_description_tab" ) );
        add_shortcode( "product_category_table_notes", array( $this, "product_category_table_notes" ) );
    }

    public function product_saved( $post_id ) {
        $post = get_post( $post_id );
        if ( $post->post_type === "product" ) {
            $catalog_number = $post->post_title;
            $cleaned_catalog_number = $this->master->functions->clean_data_strict( $catalog_number );
            update_post_meta( $post_id, "CatalogNumberCleaned", $cleaned_catalog_number );
        }
    }

    public function product_category_table_notes()
    {
        global $product_category_table_data;
        extract( $product_category_table_data );
        ob_start();
        ?>
        <p><?php echo $table_notes; ?></p>
        <?php
        return ob_get_clean();
    }

    public function product_category_description_tab()
    {
        $category = get_queried_object();
        $category_id = $category->term_id;
        return $this->get_category_field( $category_id, "wpcf-description-tab" );
    }

    public function product_category_links_tab()
    {
        $category = get_queried_object();
        $category_id = $category->term_id;
        return $this->get_category_field( $category_id, "wpcf-links-pdfs-tab" );
    }

    public function product_category_sub_categories()
    {
        $category = get_queried_object();
        $sub_categories = $this->get_sub_categories( $category->term_id );
        ob_start();
        ?>
        <div class="product-cats">
            <?php
            foreach ( $sub_categories as $sub_category => $sub_category_img ) { ?>
                <div class="product-cat col-one-fourth product-cat-<?php echo get_term( $sub_category )->term_id; ?>"
                     data-name="<?php echo get_term( $sub_category )->name; ?>"
                     data-catid="<?php echo get_term( $sub_category )->term_id; ?>">
                    <div class="prod-list-wrap">
                        <a style="color: white;" href="<?php echo get_term_link( $sub_category, "product_cat" ); ?>">
                            <div class="cat-name-thumb">
                                <div class="cat-name">
                                    <span><?php echo get_term( $sub_category )->name; ?></span>
                                </div>
                                <div class="cat-thumb">
                                    <img src="<?php echo $sub_category_img; ?>"/>
                                </div>
                                <div class="cat-name">
                                    <a class="button"
                                       href="<?php echo get_term_link( $sub_category, "product_cat" ); ?>">View
                                        Details</a>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            <?php } ?>
            <i aria-hidden="true"></i>
            <i aria-hidden="true"></i>
            <i aria-hidden="true"></i>
        </div>
        <?php
        return ob_get_clean();
    }

    public function get_sub_categories( $term_id, $taxonomy = "product_cat" )
    {
        $sub_categories = array();
        $categories = get_terms(
            array(
                'taxonomy' => $taxonomy,
                'parent' => $term_id,
                'hide_empty' => false
            )
        );
        foreach ( $categories as $category ) {
            $img = $this->get_category_field( $category->term_id, "wpcf-images" );
            if ( $img === "" || $img === false ) {
                $img = home_url() . "/wp-content/uploads/categories/img-coming-soon.png";
            }
            $sub_categories[ $category->term_id ] = $img;
        }
        return $sub_categories;
    }

    public function product_category_filters()
    {
        $category = get_queried_object();
        $filters_settings = $this->get_category_field( $category->term_id, "wpcf-filters-to-show-in-left-rail", false );
        if ( !is_array( $filters_settings ) ) {
            $filters_settings = array();
        }
        $filters = $this->get_filters( $category->term_id, implode( ",", $filters_settings ) );
        ob_start();
        ?>
        <div class="filter-group">
            <h3>Filter By:</h3>
            <?php foreach ( $filters as $filter => $values ) { 
                ksort( $values ); ?>
                <div class="filter">
                    <span><?php echo $filter; ?></span>
                    <ul style="display: none">
                        <?php foreach ( $values as $value => $ids ) { ?>
                            <li>
                                <label class="check-wrap">
                                    <?php echo $value; ?>
                                    <input class="product-data-filter" type="checkbox" value="<?php echo $value; ?>"
                                           data-filter="<?php echo $filter; ?>"
                                           data-catids="<?php echo implode( ",", $ids ); ?>"><span
                                            class="checkmark"></span>
                                </label>
                            </li>
                        <?php } ?>
                    </ul>
                </div>
            <?php } ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function product_print_category_page()
    {
        ob_start();
        ?>
        <div class="ae-print-product-page">
            <button onclick="window.print()"><?= __( 'Print this Page' ); ?></button>
        </div>
        <?php
        return ob_get_clean();
    }

    public function get_filters( $term_id, $preselected_filters )
    {
        $filters = array();
        $posts = get_posts(
            array(
                'post_type' => 'product',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'tax_query' => array(
                    array(
                        'taxonomy' => 'product_cat',
                        'field' => 'term_id',
                        'terms' => $term_id,
                    )
                )
            )
        );
        $levels_up = 0;
        $term = get_term( $term_id );
        if ( is_wp_error( $term ) ) {
            return array();
        }
        $parent_id = $term->parent;
        while ( $parent_id !== 0 ) {
            $term = get_term( $parent_id );
            if ( is_wp_error( $term ) ) {
                return array();
            }
            $parent_id = $term->parent;
            $levels_up++;
        }
        if ( $levels_up === 0 ) {
            $levels_down = 1;
        } else if ( $levels_up === 1 ) {
            $levels_down = 2;
        } else if ( $levels_up === 2 ) {
            $levels_down = 3;
        } else if ( $levels_up === 3 ) {
            $levels_down = 4;
        } else if ( $levels_up === 4 ) {
            $levels_down = 5;
        } else if ( $levels_up === 5 ) {
            $levels_down = 6;
        }
        if ( $preselected_filters !== "" && $preselected_filters !== false ) {
            $preselected_filters = explode( ",", $preselected_filters );
            foreach ( $posts as $post ) {
                $custom_data = get_post_custom( $post->ID );
                foreach ( $custom_data as $key => $value ) {
                    if ( in_array( $key, $preselected_filters ) ) {
                        if ( strpos( $key, "_" ) !== false || strpos( $key, "-" ) !== false ) {
                            continue;
                        }
                        if ( $key === "CatalogNumber" || $key === "CatalogNumberCleaned" || $key === "PartPDF" || $key === "PartDescription" || $key === "Description" ) {
                            continue;
                        }
                        if ( !array_key_exists( $key, $filters ) || !is_array( $filters[ $key ] ) ) {
                            $filters[ $key ] = array();
                        }
                        if ( !is_array( $filters[ $key ][ $value[ 0 ] ] ) ) {
                            $filters[ $key ][ $value[ 0 ] ] = array();
                        }
                        $lowest = $this->get_categories_down( $post->ID, $levels_down );
                        if ( !in_array( $lowest, $filters[ $key ][ $value[ 0 ] ] ) ) {
                            $filters[ $key ][ $value[ 0 ] ][] = $lowest;
                        }
                    }
                }
            }
        } else {
            foreach ( $posts as $post ) {
                $custom_data = get_post_custom( $post->ID );
                foreach ( $custom_data as $key => $value ) {
                    if ( strpos( $key, "_" ) !== false || strpos( $key, "-" ) !== false ) {
                        continue;
                    }
                    if ( $key === "CatalogNumber" || $key === "CatalogNumberCleaned" || $key === "PartPDF" || $key === "PartDescription" || $key === "Description" ) {
                        continue;
                    }
                    if ( !array_key_exists( $key, $filters ) || !is_array( $filters[ $key ] ) ) {
                        $filters[ $key ] = array();
                    }
                    if ( !array_key_exists( $value[ 0 ], $filters[ $key ] ) || !is_array( $filters[ $key ][ $value[ 0 ] ] ) ) {
                        $filters[ $key ][ $value[ 0 ] ] = array();
                    }
                    $lowest = $this->get_categories_down( $post->ID, $levels_down );
                    if ( !in_array( $lowest, $filters[ $key ][ $value[ 0 ] ] ) ) {
                        $filters[ $key ][ $value[ 0 ] ][] = $lowest;
                    }
                }
            }
        }
        return $filters;
    }

    public function get_categories_down( $post_id, $levels_down = 0 )
    {
        $product_cats = get_the_terms( $post_id, 'product_cat' );
        if ( $product_cats ) {
            foreach ( $product_cats as $product_cat ) {
                $ids[ $product_cat->parent ] = $product_cat->term_id;
            }
        }
        if ( count( $ids ) === 0 ) {
            return null;
        }
        $classification = $ids[ 0 ];
        for ( $i = 0; $i < $levels_down; $i++ ) {
            if ( array_key_exists( $classification, $ids ) ) {
                $classification = $ids[ $classification ];
            }
        }
        return $classification;
    }

    public function product_category_found_count()
    {
        $category = get_queried_object();
        $sub_categories = $this->get_sub_categories( $category->term_id );
        ob_start();
        ?>
        <div class="prod-count">
            <span class="count"><?php echo count( $sub_categories ); ?></span> Product Lines Found
        </div>
        <?php
        return ob_get_clean();
    }

    public function redirect_products_and_lowest_categories()
    {
        if ( is_singular( "product" ) ) {
            global $post;
            $term = get_term( $this->get_the_lowest_category( $post->ID )->parent, "product_cat" );
            wp_redirect( get_term_link( $term ) );
            exit();
        } else if ( is_tax( "product_cat" ) ) {
            $category = get_queried_object();
            if ( $this->get_category_field( $category->term_id, "wpcf-is-parts-table" ) === "1" ) {
                wp_redirect( get_term_link( $category->parent, "product_cat" ) );
                exit();
            }
        }
        if ( is_tax( 'product_cat' ) ) {
            ?>
            <script>
                var maclean_add_to_cart_url = "<?php echo home_url( "?add-to-cart=" ); ?>";
            </script>
            <?php
        }
    }

    public function get_the_lowest_category( $post_id )
    {
        $product_cats = get_the_terms( $post_id, 'product_cat' );
        $parents = array();
        $ids = array();
        if ( $product_cats ) {
            foreach ( $product_cats as $category ) {
                $ids[] = $category->term_id;
                if ( $category->parent ) $parents[] = $category->parent;
            }
        }
        $low_categories = array_diff( $ids, $parents );
        return get_term( array_shift( $low_categories ) );
    }

    public function product_category_sort()
    {
        ob_start();
        ?>
        <div class="flex">
            Sort By:
            <span class="select-wrap">
					<select class="product-cats-sort">
						<option value="id">- please select -</option>
						<option value="name-asc">Name (Ascending)</option>
						<option value="name-desc">Name (Descending)</option>
					</select>
				</span>
        </div>
        <?php
        return ob_get_clean();
    }

    public function product_category_description()
    {
        $category = get_queried_object();
        $category_description = $this->get_category_field( $category->term_id, "wpcf-description" );
        ob_start();
        ?>
        <p><?php echo $category_description; ?></p>
        <?php
        return ob_get_clean();
    }

    public function product_category_description_images()
    {
        $category = get_queried_object();
        $category_name = $category->name;
        $category_img_urls = $this->get_category_field( $category->term_id, "wpcf-images", false );
        if ( !is_array( $category_img_urls ) ) {
            $category_img_urls = array();
        }
        ob_start();
        ?>
        <ul id="image-gallery" class="gallery list-unstyled cS-hidden">
            <?php foreach ( $category_img_urls as $category_img_url ) { ?>
                <li data-thumb="<?php echo $category_img_url; ?>"><img src="<?php echo $category_img_url; ?>"></li>
            <?php } ?>
        </ul>
        <?php
        return ob_get_clean();
    }

    public function product_category_title()
    {
        $category = get_queried_object();
        ob_start();
        ?>
        <h1><?php echo $category->name; ?></h1>
        <?php
        return ob_get_clean();
    }

    public function product_category_page( $atts )
    {

        $atts = shortcode_atts( array( "show-first-level" => "0" ), $atts );
        if ( $atts[ "show-first-level" ] === "1" ) {
            ob_start();
            echo $this->product_category_top_level_page();
            return ob_get_clean();
        } else {
            if ( array_key_exists( "fl_builder", $_GET ) ) {
                return "";
            }
            $category = get_queried_object();
            $category_id = $category->term_id;
            $should_show_tables = $this->get_category_field( $category_id, "wpcf-show-part-tables" );
            ob_start();
            if ( $should_show_tables === "1" ) {
                echo $this->product_category_table_display_page();
            } else {
                echo $this->product_category_listing_page();
            }
            return ob_get_clean();
        }
    }

    public function product_category_top_level_page()
    {
        ob_start();
        $taxonomy = "product_cat";
        $top_level_terms = get_terms( array( 'taxonomy' => $taxonomy, 'parent' => 0, 'hide_empty' => false ) );
        foreach ( $top_level_terms as $tlt ) {
            if ( $tlt->name === "Uncategorized" ) {
                continue;
            }
            $cat_imgs = $this->get_category_field( $tlt->term_id, "wpcf-images", false );
            if ( count( $cat_imgs ) === 0 || strlen( $cat_imgs[ 0 ] ) < 1 ) {
                $cat_img = "/wp-content/uploads/categories/img-coming-soon.png";
            } else {
                $cat_img = $cat_imgs[ 0 ];
            }
            ?>
            <div class="sub-cat">
                <a style="color: white;" href="<?php echo get_term_link( $tlt, $taxonomy ); ?>">
                    <div class="cat-name-thumb">
                        <div class="cat-name">
                            <span><?php echo $tlt->name; ?></span>
                        </div>
                        <div class="cat-thumb">
                            <img src="<?php echo $cat_img; ?>"/>
                        </div>
                        <div class="cat-name">
                            <a class="button" href="<?php echo get_term_link( $tlt, $taxonomy ); ?>">View Details</a>
                        </div>
                    </div>
                </a>
            </div>
            <?php
        }
        return ob_get_clean();
    }

    public function product_category_table_display_page()
    {
        return do_shortcode( '[fl_builder_insert_layout slug="product-category-display" type="fl-builder-template" site="1"]' );
    }

    public function product_category_listing_page()
    {
        $term = get_queried_object();
        if ( get_term_meta( $term->term_id, "wpcf-should-not-show-filters", true ) == true ) {
            return do_shortcode( '[fl_builder_insert_layout slug="product-category-listing-no-filter-new" type="fl-builder-template" site="1"]' );
        } else {
            return do_shortcode( '[fl_builder_insert_layout slug="product-category-listing" type="fl-builder-template" site="1"]' );
        }
    }

    public function product_category_table_dim_drawings()
    {
        global $product_category_table_data;
        extract( $product_category_table_data );
        global $product_category_table;
        ob_start();
        ?>
        <div class="slick-container">
            <?php foreach ( $table_dim_drawing_img_urls as $table_dim_drawing_img_url ) { ?>
                <?php if ($table_dim_drawing_img_url !== "" && $table_dim_drawing_img_url !== null) { ?>
                    <div class="fl-photo fl-photo-align-center" itemscope="" itemtype="https://schema.org/ImageObject">
                        <div class="fl-photo-content fl-photo-img-jpg">
                            <img class="fl-photo-img size-full" src="<?php echo $table_dim_drawing_img_url; ?>"
                                alt="<?php echo $product_category_table . ' Dim Drawing Image'; ?>" itemprop="image"
                                title="<?php echo $product_category_table . " Dim Drawing Image"; ?>">
                        </div>
                    </div>
                <?php } ?>
            <?php } ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function product_category_table_description()
    {
        // ROGER
        global $product_category_table_data;
        global $product_category_table;
        extract( $product_category_table_data );
        ob_start();
        ?>
        <p><?php echo $table_description; ?></p>
        <p><button class="fullscreen-table" data-tablename="<?php echo $this->master->functions->clean_data_strict( strtolower( $product_category_table ) ); ?>" data-tabletitle="<?php echo $product_category_table; ?>">View Table In Fullscreen</button></p>
        <?php
        return ob_get_clean();
    }

    public function product_category_table_body()
    {
        global $product_category_table_data;
        extract( $product_category_table_data );
        return $table_body;
    }

    public function product_category_tables()
    {
        $category = get_queried_object();
        $table_indexes = $this->get_tables( $category->term_id );
        $html = '<div class="product_table_container">';
        foreach ( $table_indexes as $index => $table_data_arr ) {
            foreach ( $table_data_arr as $table_name => $table_data ) {
                global $product_category_table_data;
                $product_category_table_data = $table_data;
                global $product_category_table;
                $product_category_table = $table_name;
                $html .= do_shortcode( '[fl_builder_insert_layout slug="product-category-parts-table" type="fl-builder-template" site="1"]' );
            }
        }
        return $html . "</div>";
    }

    public function get_tables( $term_id )
    {
        if ( $term_id === "" ) {
            return array();
        }
        $tables = array();
        $table_sort_order_ids = array_map( function ( $e ) {
            return explode( " | ", $e )[ 0 ];
        }, $this->get_category_field( $term_id, "wpcf-part-table-sort-order", false ) );
        $sub_categories = get_terms(
            array(
                'taxonomy' => "product_cat",
                'parent' => $term_id,
                'hide_empty' => false
            )
        );
        $counter = 0;
        foreach ( $sub_categories as $sub_category ) {
            $searched = array_search( $sub_category->term_id, $table_sort_order_ids );
            if ( $searched === false ) {
                $searched = 100 + $counter;
                $counter++;
            }
            $tables[ $searched ][ $sub_category->name ] = array();
            $tables[ $searched ][ $sub_category->name ][ "table_dim_drawing_img_urls" ] = $this->get_category_field( $sub_category->term_id, "wpcf-table-dimension-drawings", false );
            $tables[ $searched ][ $sub_category->name ][ "table_description" ] = $this->get_category_field( $sub_category->term_id, "wpcf-table-description" );
            if ( $tables[ $searched ][ $sub_category->name ][ "table_description" ] === false ) {
                $tables[ $searched ][ $sub_category->name ][ "table_description" ] = "";
            }
            $tables[ $searched ][ $sub_category->name ][ "table_notes" ] = $this->get_category_field( $sub_category->term_id, "wpcf-table-notes" );
            $post_args = array(
                'post_type' => 'product',
                'post_status' => 'publish',
                'ignore_sticky_posts' => 1,
                'posts_per_page' => -1,
                'tax_query' => array(
                    array(
                        'taxonomy' => 'product_cat',
                        'field' => 'term_id',
                        'terms' => $sub_category->term_id,
                        'operator' => 'IN'
                    )
                )
            );
            $products = get_posts( $post_args );
            ob_start();
            if ( $this->get_category_field( $sub_category->term_id, "wpcf-has-english-metric-distinction" ) === "1" ) {
                ?>
                <div class="radio-product-toggle-wrapper">
                    <input class="radio-toggle-product-tables" data-tableid="<?php echo $sub_category->term_id; ?>" type="radio"
                           name="english_metric_distinction-<?php echo $sub_category->term_id; ?>" data-type-show="english"
                           data-type-hide="metric" checked="checked"><span>English</span>
                    <input class="radio-toggle-product-tables" data-tableid="<?php echo $sub_category->term_id; ?>" type="radio"
                           name="english_metric_distinction-<?php echo $sub_category->term_id; ?>" data-type-show="metric"
                           data-type-hide="english"><span>Metric</span>
                </div>
                <?php
            }
            ?>

            <?php if ( $this->get_category_field( $sub_category->term_id, "wpcf-table-has-heading" ) === "1" ) { ?>
                <div>
                    <h3 class="divTableHeaderText"><?php echo $this->get_category_field( $sub_category->term_id, "wpcf-table-heading" ); ?></h3>
                </div>
            <?php } ?>
            <?php if ( $this->get_category_field( $sub_category->term_id, "wpcf-has-english-metric-distinction" ) === "1" ) { ?>
                <table class="datatable-products <?php echo $this->master->functions->clean_data_strict( strtolower( $sub_category->name ) ); ?>-table" id="<?php echo $sub_category->term_id; ?>-english">
                    <?php $counter = 0; ?>
                    <?php foreach ( $products as $product ) { ?>
                    <?php if ( $counter === 0 ) { ?>
                    <thead>
                    <tr>
                        <th>Catalog Number</th>
                        <?php
                        echo $this->get_table_header( $sub_category->term_id, "english" );
                        echo $this->get_logged_in_table_header( $sub_category->term_id, $product->ID );
                        if ( is_user_logged_in() ) { ?>
                            <!-- <th></th> -->
                        <?php } ?>
                    </tr>
                    </thead>
                    <tbody>
                    <?php }
                    $meta = get_post_meta( $product->ID, "PartPDF", true );
                    if ( $meta !== false && $meta !== null && $meta !== "" ) {
                        $catalog_cell = "<a target='_blank' href='" . home_url( "$meta" ) . "'>" . get_the_title( $product->ID ) . "</a>";
                    } else {
                        $catalog_cell = get_the_title( $product->ID );
                    }
                    ?>
                    <tr id="<?php echo get_the_title( $product->ID ); ?>">
                        <td><?php echo $catalog_cell; ?></td>
                        <?php echo $this->get_table_row( $sub_category->term_id, $product->ID, "english" ); ?>
                        <?php echo $this->get_logged_in_table_rows( $sub_category->term_id, $product->ID ); ?>
                        <?php if ( is_user_logged_in() ) { ?>
                            <!-- <td class="add-to-cart-button">
                                <?php echo $this->get_add_to_quote( $product->ID ); ?>
                            </td> -->
                        <?php } ?>
                    </tr>
                    <?php $counter++; ?>
                    <?php } ?>
                    </tbody>
                </table>
                <table class="datatable-products <?php echo $this->master->functions->clean_data_strict( strtolower( $sub_category->name ) ); ?>-table" id="<?php echo $sub_category->term_id; ?>-metric" style="display: none;">
                    <?php $counter = 0; ?>
                    <?php foreach ( $products

                    as $product ) { ?>
                    <?php if ( $counter === 0 ) { ?>
                    <thead>
                    <tr>
                        <th>Catalog Number</th>
                        <?php
                        echo $this->get_table_header( $sub_category->term_id, "metric" );
                        echo $this->get_logged_in_table_header( $sub_category->term_id, $product->ID );
                        if ( is_user_logged_in() ) { ?>
                            <!-- <th> </th> -->
                        <?php } ?>
                    </tr>
                    </thead>
                    <tbody>
                    <?php }
                    $meta = get_post_meta( $product->ID, "PartPDF", true );
                    if ( $meta !== false && $meta !== null && $meta !== "" ) {
                        $catalog_cell = "<a target='_blank' href='" . home_url( "$meta" ) . "'>" . get_the_title( $product->ID ) . "</a>";
                    } else {
                        $catalog_cell = get_the_title( $product->ID );
                    }
                    ?>
                    <tr id="<?php echo get_the_title( $product->ID ); ?>">
                        <td><?php echo $catalog_cell; ?></td>
                        <?php echo $this->get_table_row( $sub_category->term_id, $product->ID, "metric" ); ?>
                        <?php echo $this->get_logged_in_table_rows( $sub_category->term_id, $product->ID ); ?>
                        <?php if ( is_user_logged_in() ) { ?>
                            <!-- <td class="add-to-cart-button">
                                <?php echo $this->get_add_to_quote( $product->ID ); ?>
                            </td> -->
                        <?php } ?>
                    </tr>
                    <?php $counter++; ?>
                    <?php } ?>
                    </tbody>
                </table>
            <?php } else { ?>
                <table class="datatable-products <?php echo $this->master->functions->clean_data_strict( strtolower( $sub_category->name ) ); ?>-table" id="<?php echo $sub_category->term_id; ?>">
                    <?php $counter = 0; ?>
                    <?php foreach ( $products as $product ) { ?>
                    <?php if ( $counter === 0 ) { ?>
                    <thead>
                    <tr>
                        <th>Catalog Number</th>
                        <?php
                        echo $this->get_table_header( $sub_category->term_id );
                        echo $this->get_logged_in_table_header( $sub_category->term_id, $product->ID );
                        if ( is_user_logged_in() ) { ?>
                            <!-- <th></th> -->
                        <?php } ?>
                    </tr>
                    </thead>
                    <tbody>
                    <?php }
                    $meta = get_post_meta( $product->ID, "PartPDF", true );
                    if ( $meta !== false && $meta !== null && $meta !== "" ) {
                        $catalog_cell = "<a target='_blank' href='" . home_url( "$meta" ) . "'>" . get_the_title( $product->ID ) . "</a>";
                    } else {
                        $catalog_cell = get_the_title( $product->ID );
                    }
                    ?>
                    <tr id="<?php echo get_the_title( $product->ID ); ?>">
                        <td><?php echo $catalog_cell; ?></td>
                        <?php echo $this->get_table_row( $sub_category->term_id, $product->ID ); ?>
                        <?php echo $this->get_logged_in_table_rows( $sub_category->term_id, $product->ID ); ?>
                        <?php if ( is_user_logged_in() ) { ?>
                            <!-- <td class="add-to-cart-button">
                                <?php echo $this->get_add_to_quote( $product->ID ); ?>
                            </td> -->
                        <?php } ?>
                    </tr>
                    <?php $counter++; ?>
                    <?php } ?>
                    </tbody>
                </table>
            <?php } ?>
            <?php
            $tables[ $searched ][ $sub_category->name ][ "table_body" ] = ob_get_clean();
        }
        ksort( $tables );
        $tables = array_values( $tables );
        return $tables;
    }

    public function get_table_header( $category_id, $distinction = "" )
    {
        $headers = $this->get_headers( $category_id );
        ob_start();
        foreach ( $headers[ "sort" ] as $header ) {
            if ( $distinction !== "" && !in_array( $header, $headers[ $distinction ] ) ) {
                continue;
            }
            if ( in_array( $header, $headers[ "universal" ] ) ) {
                $class = "U";
                $display = "";
            } else if ( in_array( $header, $headers[ "metric" ] ) ) {
                $class = "M";
                $display = "";
            } else if ( in_array( $header, $headers[ "english" ] ) ) {
                $class = "E";
                $display = "";
            }
            if ($header != "" && $header != null) {
                if ( in_array( $header, $headers[ "bolded" ] ) ) {
                    $header = "<b>" . $header . "</b>";
                }
            ?>
                <th class="<?php echo $class; ?>" <?php echo $display; ?>><?php echo print_r($header, true); ?></th>
            <?php }?>
            <?php
        }
        return ob_get_clean();
    }

    public function get_headers( $category_id )
    {
        $sorted_headers = $this->get_category_field( $category_id, "wpcf-column-master-sort-order", false );
        $universal = $this->get_table_columns( $category_id, "universal" );
        $universal_headers = array_diff( $universal, $sorted_headers );
        $metric = $this->get_table_columns( $category_id, "metric" );
        $metric_headers = array_diff( $metric, $sorted_headers, $universal_headers );
        $english = $this->get_table_columns( $category_id, "english" );
        $english_headers = array_diff( $english, $sorted_headers, $universal_headers, $metric );
        $bolded = $this->get_table_columns( $category_id, "bolded" );
        $bolded_headers = array_diff( $english, $sorted_headers, $universal_headers, $metric, $english );
        return array( "sort" => array_merge( $sorted_headers, $universal_headers, $english_headers, $metric_headers, $bolded_headers ), "universal" => $universal, "metric" => $metric, "english" => $english, "bolded" => $bolded );
    }

    public function get_category_field( $category_id, $field_name, $single = true )
    {
        return get_term_meta( $category_id, $field_name, $single );
    }

    public function get_table_columns( $category_id, $distinction = "universal" )
    {
        if ( $distinction !== "" ) {
            $distinction .= "-";
        }
        return $this->get_category_field( $category_id, "wpcf-{$distinction}table-columns", false );
    }

    public function get_logged_in_table_header( $term_id, $product_id )
    {
        $string = "";
        if ( is_user_logged_in() ) {
            $string .= "<th>Price</th>";
            $string .= "<th>Lead Time</th>";
        }
        return $string;
    }

    public function get_table_row( $category_id, $product_id, $distinction = "" )
    {
        $headers = $this->get_headers( $category_id );
        $values = $this->get_table_column_values_for_product( $category_id, $product_id, $headers[ "sort" ] );
        ob_start();
        foreach ( $values as $key => $value ) {
            if ( $distinction !== "" && !in_array( $key, $headers[ $distinction ] ) ) {
                continue;
            }
            $class = "U";
            $display = "";
            if ( in_array( $key, $headers[ "universal" ] ) ) {
                $class = "U";
                $display = "";
            } else if ( in_array( $key, $headers[ "metric" ] ) ) {
                $class = "M";
                $display = "";
            } else if ( in_array( $key, $headers[ "english" ] ) ) {
                $class = "E";
                $display = "";
            }
            if ( in_array( $key, $headers[ "bolded" ] ) ) {
                $value = "<b>" . $value . "</b>";
            }
            ?>
            <?php if ($key != "" && $key != null) { ?>
                <td class="<?php echo $class; ?>" <?php echo $display; ?>><?php echo $value; ?></td>
            <?php } ?>
            <?php
        }
        return ob_get_clean();
    }

    public function get_table_column_values_for_product( $category_id, $product_id, $headers )
    {
        foreach ( $headers as $header ) {
            $data[ $header ] = $this->get_product_field( $product_id, $header );
        }
        return $data;
    }

    public function get_product_field( $product_id, $field_name, $single = true )
    {
        return get_post_meta( $product_id, $field_name, $single );
    }

    public function get_logged_in_table_rows( $term_id, $product_id )
    {
        $string = "";
        if ( is_user_logged_in() ) {
            $catalog_number = get_post_meta( $product_id, "CatalogNumberCleaned", true );
            $string .= "<td>" . $this->get_price_and_availablility_link( $catalog_number ) . "</td>";
            $string .= "<td>" . $this->get_price_and_availablility_lead_time( $catalog_number ) . "</td>";
        }
        return $string;
    }

    public function get_price_and_availablility_link( $catalog_number ) {
        return "<a target='_blank' href='" . home_url( "/mpservicenet?catalogInput=" . $catalog_number . "#/price-availability" ) . "'>$</a>";
    }

    public function get_price_and_availablility_lead_time( $catalog_number ) {
        global $wpdb;
        $sql = "SELECT mp.lt FROM mp_item AS mp WHERE mp.cleaned_catalog_no = '$catalog_number' LIMIT 1";
        $results = $wpdb->get_results( $sql );
        if ( count( $results ) > 0 ) {
            return $results[0]->lt;
        }
        return "N/A";
    }

    public function get_add_to_quote( $product_id )
    {
        ?>
        <form class="cart" action="<?php echo home_url( $_SERVER[ "REQUEST_URI" ] ); ?>" method="post"
              enctype="multipart/form-data">
            <div class="quantity">
                <label class="screen-reader-text" for="quantity_5cf66dedaccba">Quantity</label>
                <input type="number" id="quantity_5cf66dedaccba" class="input-text qty text" step="1" min="1" max=""
                       name="quantity" value="1" title="Qty" size="4" inputmode="numeric">
            </div>
            <div class="clear"></div>
            <div class="simple_add_to_quote button_add_to_quote">
                <button class="single_adq_button  button alt" id="add_to_quote"
                        data-product-id="<?php echo $product_id; ?>" data-product-type="simple"
                        data-button="simple_add_to_quote" data-is_quote="1" type="button"> Add to quote
                </button>
            </div>
            <div class="clear"></div>
            <script style="display: none;" >
                jQuery( '.single_add_to_cart_button:not(#add_to_quote)' ).remove();
            </script>
        </form>
        <?php
    }

    public function product_category_breadcrumbs()
    {
        if ( isset( $_GET[ "fl_builder" ] ) ) {
            return "BREADCRUMBS WILL APPEAR HERE";
        }
        ob_start();
        ?>
        <ul class="breadcrumbs">
            <li>
                <a href="<?php echo home_url( '/products/' ); ?>">Products</a>
            </li>
            <?php
            $term = get_queried_object();
            $tmpTerm = $term;
            $tmpCrumbs = array();
            while ( $tmpTerm->parent > 0 ) {
                $tmpTerm = get_term( $tmpTerm->parent, "product_cat" );
                $tmpCrumbs[ get_term_link( $tmpTerm, "product_cat" ) ] = $tmpTerm->name;
            }
            $tmpCrumbs = array_reverse( $tmpCrumbs );
            foreach ( $tmpCrumbs as $crumb_link => $crumb_name ) {
                ?>
                <li>
                    <i class="ua-icon ua-icon-chevron-right" aria-hidden="true"></i> <a
                            href="<?php echo $crumb_link; ?>"><?php echo $crumb_name; ?></a>
                </li>
                <?php
            }
            ?>
            <li>
                <i class="ua-icon ua-icon-chevron-right" aria-hidden="true"></i> <a
                        href="<?php echo get_term_link( $term, "product_cat" ); ?>"><?php echo $term->name; ?></a>
            </li>
            <?php
            ?>
        </ul>
        <?php
        return ob_get_clean();
    }

    public function enqueue_scripts()
    {
        if ( is_tax( 'product_cat' ) ) {
            wp_enqueue_script( "product-functions-script", $this->master->url . 'assets/shared/js/product-functions.js', array( 'jquery', 'maclean-functions-script' ) );
        }
    }
}