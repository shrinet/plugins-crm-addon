<?php

namespace MacleanCustomCode\Representatives;

use DateTime;

if ( ! defined( 'ABSPATH' ) ) {
    exit( 'Direct script access denied.' );
}

class MacleanCCRepresentatives
{
    protected $master;

    public function __construct( $master )
    {
        $this->master = $master;
        $this->setup_actions();
        $this->setup_filters();
        $this->setup_shortcodes();
        $this->add_accounts_columns();
    }

    public function setup_actions()
    {
        add_action( "wp_ajax_get_quote_comment", array( $this, "get_quote_comment" ) );

        add_action( "wp_ajax_get_price_and_availability_summary_json", array( $this, "get_price_and_availability_summary_json" ) );
        add_action( "wp_ajax_price_and_availability_id_ajax", array( $this, "price_and_availability_id_ajax" ) );
        add_action( "wp_ajax_price_and_availability_account_ajax", array( $this, "price_and_availability_account_ajax" ) );
        add_action( "wp_ajax_price_and_availability_production_schedule_ajax", array( $this, "price_and_availability_production_schedule_ajax" ) );

        add_action( "wp_ajax_orders_account_ajax", array( $this, "orders_account_ajax" ) );
        add_action( "wp_ajax_orders_id_ajax", array( $this, "orders_id_ajax" ) );
        add_action( "wp_ajax_orders_packaging_ajax", array( $this, "orders_packaging_ajax" ) );


        add_action( "wp_ajax_quotes_account_ajax", array( $this, "quotes_account_ajax" ) );
        add_action( "wp_ajax_quotes_id_ajax", array( $this, "quotes_id_ajax" ) );

        add_action( "wp_enqueue_scripts", function() {
            if ( is_page( 'mpservicenet' ) ) {
                $accountsData = [];
                if ( is_user_logged_in() ) {
                    $user_id      = wp_get_current_user()->ID;
                    $accounts     = $this->post_account_ids( "user_" . $user_id, true );
                    foreach ( $accounts as $accountId ) {
                        $account = get_post( $accountId );
                        if ( ! is_object( $account ) || $account->post_type !== "account" ) {
                            continue;
                        }
    
                        $accountName   = $account->post_title;
                        $accountNumber = get_field( "cust", $account->ID );
                        $customerName  = get_field( "customername", $account->ID );
    
                        $accountsData[] = [
                            'name'         => $accountName,
                            'number'       => $accountNumber,
                            'customerName' => $customerName
                        ];
                    } 
                }
                 
                wp_localize_script( 'mpservicenet', 'mpservicenet_params', array(
                    'homeURL'  => home_url(),
                    'mpsURL'   => home_url( '/mpservicenet/' ),
                    'accounts' => $accountsData
                ) );
                wp_enqueue_script( 'mpservicenet' );
            }            
        });  
    }

    public function get_quote_comment()
    {
        global $wpdb;
        $comments = implode( "<br/>", $wpdb->get_col( $wpdb->prepare( "SELECT comment FROM mp_quote_comment WHERE quoteno = '%s' AND item = '%s' and status = 'publish'", array( $_POST[ 'quote_no' ], $_POST[ 'item' ] ) ) ) );
        if ( strlen( $comments ) < 1 ) {
            $comments = "NO COMMENTS FOUND";
        }
        ?>
        <div class="modal" style="display:none;">
            <?php echo $comments; ?>
        </div>
        <?php
        die();
    }

    public function get_plant_name( $s )
    {
        if ( $s === "D" ) {
            return "Distribution Center";
        } else if ( $s === "F" ) {
            return "Franklin Park";
        } else if ( $s === "N" ) {
            return "Newberry";
        } else if ( $s === "P" ) {
            return "Pelham";
        } else if ( $s === "R" ) {
            return "Alabaster";
        } else if ( $s === "T" ) {
            return "Trenton";
        } else {
            return "N/A";
        }
    }

    public function not_null( $item )
    {
        $item = trim( $item );
        if ( $item === NULL || $item === "NULL" || $item === "null" ) {
            return "";
        }
        return $item;
    }

    public function setup_filters()
    {
        add_filter( 'query_vars', function ( $query_vars ) {
            $query_vars[] = 'rep_page';
            $query_vars[] = 'rep_page_url_part_one';
            $query_vars[] = 'rep_page_url_part_two';
            $query_vars[] = 'rep_page_url_part_three';
            $query_vars[] = 'rep_page_catalog_numbers';
            $query_vars[] = 'rep_page_account_numbers';
            return $query_vars;
        } );
        add_filter( 'wp_nav_menu_objects', array( $this, 'change_logout_menu_item' ) );
    }

    public function setup_shortcodes()
    {
        add_shortcode( "representatives_export_to_excel", array( $this, "export_to_excel" ) );
        add_shortcode( "representatives_order_summary_table_orders", array( $this, "order_summary_table_orders" ) );
        add_shortcode( "representatives_quote_status_table", array( $this, "quote_status_table" ) );
        add_shortcode( "representatives_quote_line_items_table", array( $this, "quote_line_items_table" ) );
        add_shortcode( "representatives_order_status_table", array( $this, "order_status_table" ) );
        add_shortcode( "representatives_order_line_items_table", array( $this, "order_line_items_table" ) );

        add_shortcode( "representatives_quotes_quote_summary_header", array( $this, "quotes_quote_summary_header" ) );
        add_shortcode( "representatives_quotes_quote_details_header", array( $this, "quotes_quote_details_header" ) );
        add_shortcode( "representatives_orders_order_summary_header", array( $this, "orders_order_summary_header" ) );
        add_shortcode( "representatives_orders_order_details_header", array( $this, "orders_order_details_header" ) );
        add_shortcode( "representatives_orders_order_packaging_header", array( $this, "orders_order_packaging_header" ) );

        add_shortcode( 'mpservicenet', array( $this, 'mpservicenet_shortcode' ) );

        // [fl_builder_insert_layout slug="mpservicenet" type="fl-builder-template" site="1"]
        add_shortcode( 'mpservicenet_breadcrumbs', array( $this, 'mpservicenet_breadcrumbs_shortcode' ) );
        add_shortcode( 'mpservicenet_filters', array( $this, 'mpservicenet_filters_shortcode' ) );
        add_shortcode( 'mpservicenet_content', array( $this, 'mpservicenet_content_shortcode' ) );

        // [fl_builder_insert_layout slug="mpservicenet-content" type="fl-builder-template" site="1"]
        add_shortcode( 'mpservicenet_content_navigation', array( $this, 'mpservicenet_content_navigation_shortcode' ) );
        add_shortcode( 'mpservicenet_content_display', array( $this, 'mpservicenet_content_display_shortcode' ) );

        // [fl_builder_insert_layout slug="mpservicenet-price-availability-account" type="fl-builder-template" site="1"]
        add_shortcode( 'mpservicenet_price_availability_account_header', array( $this, 'mpservicenet_price_availability_account_header_shortcode' ) );
        add_shortcode( 'mpservicenet_price_availability_account_listing', array( $this, 'mpservicenet_price_availability_account_listing_shortcode' ) );

        // [fl_builder_insert_layout slug="mpservicenet-price-availability-id" type="fl-builder-template" site="1"]
        add_shortcode( 'mpservicenet_price_availability_id_header', array( $this, 'mpservicenet_price_availability_id_header_shortcode' ) );
        add_shortcode( 'mpservicenet_price_availability_id_packaging_info', array( $this, 'mpservicenet_price_availability_id_packaging_info_shortcode' ) );
        add_shortcode( 'mpservicenet_price_availability_id_availability_info', array( $this, 'mpservicenet_price_availability_id_availability_info_shortcode' ) );
        add_shortcode( 'mpservicenet_price_availability_id_schedule_info', array( $this, 'mpservicenet_price_availability_id_schedule_info_shortcode' ) );

        add_shortcode( 'mpservicenet_order_packaging_info', array( $this, 'mpservicenet_order_packaging_info_shortcode' ) );
        add_shortcode( 'mpservicenet_order_packaging_line_items', array( $this, 'mpservicenet_order_packaging_line_items_shortcode' ) );

        add_shortcode( "representatives_quote_summary_table_quotes", array( $this, "quote_summary_table_quotes" ) );
        add_shortcode( 'mpservicenet_quote_catalog_packaging_info', array( $this, 'mpservicenet_quote_catalog_packaging_info_shortcode' ) );
    }

    public function order_summary_table_orders()
    {
        ob_start();
        ?>
        <div id="order-account-listing" class="table-wrapper">
            <p>
                <button class="fullscreen-table" data-tablename="order-account-listing" data-tabletitle="ORDER SUMMARY">
                    View Table In Fullscreen
                </button>
            </p>
            <table id="order-account-listing-table" class="order-account-listing-table">
                <thead>
                <tr>
                    <th><?= __( 'Order #' ); ?></th>
                    <th><?= __( 'PO #' ); ?></th>  
                    <th><?= __( 'Account' ); ?></th>                  
                    <th><?= __( 'Shipment #/Status' ); ?></th>
                    <th><?= __( 'Line Item' ); ?></th>
                    <th><?= __( 'Catalog #' ); ?></th>
                    <th><?= __( 'Price' ); ?></th>
                    <th><?= __( 'Qty' ); ?></th>
                    <th><?= __( 'Customer Name' ); ?></th>
                    <th><?= __( 'PO Date' ); ?></th>
                    <th><?= __( 'Ship To' ); ?></th>
                    <th><?= __( 'City State Zip' ); ?></th>
                    <th><?= __( 'Original Acknowledge Date' ); ?></th>
                    <th><?= __( 'Current Available Date' ); ?></th>
                    <th><?= __( 'Date Shipped' ); ?></th>
                    <th><?= __( 'Pro #' ); ?></th>
                    <th><?= __( 'Track' ); ?></th>                    
                </tr>
                </thead>
            </table>
        </div>
        <?php
        return ob_get_clean();
    }

    public function quote_summary_table_quotes()
    {
        ob_start();
        ?>
        <div class="table-wrapper">
            <p>
                <button class="fullscreen-table" data-tablename="quote-summary" data-tabletitle="QUOTE SUMMARY">View
                    Table In Fullscreen
                </button>
            </p>
            <table id="quote-summary-table" class="quote-summary-table">
                <thead>
                <tr>
                    <th class="quote-number">MPS Quote</th>
                    <th class="account-number">Account #</th>
                    <th class="end-user">End user</th>
                    <th class="firm-price-period">Expiration Date</th>
                    <th class="catalog-number">Catalog #</th>
                    <th class="price">Price</th>
                    <th class="quote-quantity">QTY</th>
                    <th class="order-quantity">QTY Ordered to Date</th>
                </tr>
                </thead>
            </table>
        </div>
        <?php
        return ob_get_clean();
    }

    public function order_status_table()
    {
        ob_start();
        ?>
        <div class="table-wrapper" id="order-id-status">
            <p>
                <button class="fullscreen-table" data-tablename="order-status" data-tabletitle="ORDER STATUS">View Table
                    In Fullscreen
                </button>
            </p>
            <table id="order-status-table" class="order-status-table">
                <thead>
                <tr>
                    <th>Customer #</th>
                    <th>PO #</th>
                    <th>PO Date</th>
                    <th>Shipping Info</th>
                </tr>
                </thead>
            </table>
        </div>
        <?php
        return ob_get_clean();
    }

    public function order_line_items_table()
    {
        ob_start();
        ?>
        <div class="table-wrapper" id="order-id-line-items">
            <p>
                <button class="fullscreen-table" data-tablename="order-line-items" data-tabletitle="ORDER LINE ITEMS">
                    View Table In Fullscreen
                </button>
            </p>
            <table id="order-line-items-table" class="order-line-items-table">
                <thead>
                <tr>
                    <th>Order #</th>
                    <th>Line Item</th>
                    <th>Shipment #/Status</th>
                    <th>Catalog #</th>
                    <th>Customer Part #</th>
                    <th>Price</th>
                    <th>Qty</th>
                    <th>Extended Price</th>                    
                    <th>Orig Ack Date</th>
                    <th>Current Avail Date</th>
                    <th>Date Shipped</th>
                    <th>Invoice # </th>

                </tr>
                </thead>
            </table>
        </div>
        <?php
        return ob_get_clean();
    }

    public function quote_status_table()
    {
        ob_start();
        ?>
        <div class="table-wrapper">
            <p>
                <button class="fullscreen-table" data-tablename="quote-status" data-tabletitle="QUOTE STATUS">View Table
                    In Fullscreen
                </button>
            </p>
            <table id="quote-status-table" class="quote-status-table">
                <thead>
                <tr>
                    <th>Customer #</th>
                    <th>MPS Quote</th>
                    <th>End user</th>
                    <th>Effective Date</th>
                    <th>Expiration Date</th>
                </tr>
                </thead>
            </table>
        </div>
        <?php
        return ob_get_clean();
    }

    public function quote_line_items_table()
    {
        ob_start();
        ?>
        <div class="table-wrapper">
            <p>
                <button class="fullscreen-table" data-tablename="quote-line-items" data-tabletitle="QUOTE LINE ITEMS">
                    View Table In Fullscreen
                </button>
            </p>
            <table id="quote-line-items" class="quote-line-items-table">
                <thead>
                <tr>
                    <th>Line Item</th>
                    <th>Qty Quoted</th>
                    <th>Catalog #</th>  
                    <th>Customer Part #</th>
                    <th>UPC Code</th>
                    <th>Description</th>
                    <th>Price</th>
                    <th>Current Lead Time</th>
                    <th>Standard Pack Qty</th>
                </tr>
                </thead>
            </table>
        </div>
        <?php
        return ob_get_clean();
    }

    public function catalog_table_price_and_availability_data( $catalogNumbers )
    {
        global $wp_query;
        global $wpdb;

        $catalogNumbers = array_filter( explode( ",", $catalogNumbers ), function ( $e ) {
            return ! empty( $e );
        } );
        $where          = array_map( function ( $e ) {
            $e = $this->master->functions->clean_data_strict( $e );
            return "catalogno LIKE '$e%' ";
        }, $catalogNumbers );

        if ( count( $where ) <= 0 ) {
            return [];
        }
        $sql     = "SELECT mp.*, p.post_id as ITEM_ID FROM mp_item AS mp LEFT JOIN wp_postmeta AS p ON p.meta_value = mp.catalogno WHERE (" . implode( " OR ", $where ) . ") AND p.meta_key = 'CatalogNumberCleaned' ORDER BY catalogno";
        $results = $wpdb->get_results( $sql );

        $data = [];
        foreach ( $results as $result ) {
            $catalogLink = home_url(
                '/mpservicenet/' . $wp_query->query_vars[ 'rep_page' ]
                . '/' . $wp_query->query_vars[ 'rep_page_url_part_one' ]
                . '/packaging-info/' . $result->catalogno . '/'
            );
            $child       = $this->master->products->get_the_lowest_category( intval( $result->ITEM_ID ) );
            $productLink = '#';
            if ( $child !== null || $result->ITEM_ID === NULL ) {
                $productLink = get_term_link( get_term( $child->parent, 'product_cat' ) );
                if ( is_wp_error( $productLink ) )
                    $productLink = '#';
            }
            $price = ltrim( $result->listpric, '0' );

            array_push( $data, [
                'product-link'   => $productLink,
                'catalog-number' => $result->catalogno,
                'list-price'     => $price === '' ? '0.00' : $price,
                'lead-time'      => $result->lt,
                'catalog-link'   => $catalogLink
            ] );
        }
        return $data;
    }

    public function get_price_and_availability_summary_json()
    {
        $catalogNumbers = $_POST[ 'catalogNumbers' ];

        $data = $this->catalog_table_price_and_availability_data( $catalogNumbers );

        echo json_encode( $data );

        wp_die();
    }

    public function export_to_excel()
    {
        ob_start();
        ?>
        <div class="package-print gray-btn">
            <a class="export_to_excel">Export to Microsoft Excel</a>
        </div>
        <?php
        return ob_get_clean();
    }

    public function post_account_ids( $post_id, $combine_groups_into = true, $field_key = 'accounts' )
    {
        $ids = get_field( $field_key, $post_id );
        if ( $ids === null || $ids === false || $ids === "" ) {
            $ids = array();
        } else if ( ! is_array( $ids ) ) {
            $ids = array( $ids );
        }
        if ( ! $combine_groups_into ) {
            return array_unique( $ids );
        }
        $field = get_field( 'account_groupings', $post_id );
        if ( $field === null || $field === false || $field === "" ) {
            $field = array();
        } else if ( ! is_array( $field ) ) {
            $field = array( $field );
        }
        foreach ( $field as $group_id ) {
            $accounts = get_field( 'accounts', $group_id );
            if ( $accounts === null || $accounts === false || $accounts === "" ) {
                $accounts = array();
            } else if ( ! is_array( $accounts ) ) {
                $accounts = array( $accounts );
            }
            $ids = array_unique( array_merge( $ids, $accounts ) );
        }
        return $ids;
    }

    public function display_account_numbers( $post_id, $combine_groups_into = true, $echo = true, $field_key = 'accounts', $value = "", $check = "accounts" )
    {
        $ids    = $this->post_account_ids( $post_id, $combine_groups_into, $field_key );
        $accnts = array();
        foreach ( $ids as $id ) {
            $field = get_field( 'cust', $id );
            $mod_begin = "";
            $mod_end = "";
            if ( $check === "accounts" && $value !== "" ) {
                if ( $value == $id ) {
                    $mod_begin = "<b style='color: red; font-size: 1.35em;'>";
                    $mod_end = "</b>";
                }
            }
            if ( $field !== false && $field !== NULL ) {
                $accnts[ $field ] = $mod_begin . $field . $mod_end;
            }
        }
        ksort( $accnts );
        $accnts = array_values( $accnts );
        if ( $echo ) {
            echo implode( ", ", $accnts );
        } else {
            return implode( ", ", $accnts );
        }
    }

    public function display_account_groupings( $post_id, $field_key = 'account_groupings', $echo = false, $value = "", $check = "accounts" )
    {
        $groups = get_field( $field_key, $post_id );
        $grps = array();
        foreach ( $groups as $group ) {
            $modifier = "";
            $mod_begin = "";
            $mod_end = "";
            if ( $check !== "accounts" && $value !== "" ) {
                if ( $value === $group ) {
                    $mod_begin = "<b style='color: red; font-size: 1.35em;'>";
                    $mod_end = "</b>";
                }
            }
            if ( $check === "accounts" && $value !== "" ) {
                if ( in_array( $value, get_field( "accounts", $group ) ) ) {
                    $title = get_post( $value )->post_title;
                    $modifier = " - <b style='color: red; font-size: 1.35em;'>" . substr( $title, 0, strpos( $title, " -" ) + 1 ) . "</b>";
                }
            }
            $post = get_post( $group );
            if ( !is_wp_error( $post ) ) {
                $grps[ $post->post_title ] = $mod_begin . $post->post_title . $mod_end . $modifier;
            }
        } 
        ksort( $grps );
        $grps = array_values( $grps );
        if ( $echo ) {
            echo implode( "<br/><br/>", $grps );
        } else {
            return implode( "<br/><br/>", $grps );
        }
    }

    public function display_discount_factor( $post_id )
    {
        echo get_field( 'fact', $post_id );
    }

    public function display_account_number( $post_id )
    {
        echo get_field( 'cust', $post_id );
    }

    public function add_accounts_columns()
    {
        if ( is_admin() ) {
            $class = $this;
            add_filter( 'manage_account_posts_columns', function ( $defaults ) {
                $defaults[ 'account_number' ]  = 'Account Number';
                $defaults[ 'discount_factor' ] = 'Discount Factor';
                return $defaults;
            }, 10 );
            add_action( 'manage_account_posts_custom_column', function ( $column_name, $post_id ) use ( $class ) {
                if ( $column_name == 'account_number' ) {
                    $class->display_account_number( $post_id );
                }
                if ( $column_name == 'discount_factor' ) {
                    $class->display_discount_factor( $post_id );
                }
            }, 10, 2 );
            add_filter( 'manage_account_grouping_posts_columns', function ( $defaults ) {
                $defaults[ 'account_numbers' ] = 'Account Numbers';
                return $defaults;
            }, 10 );
            add_action( 'manage_account_grouping_posts_custom_column', function ( $column_name, $post_id ) use ( $class ) {
                if ( $column_name == 'account_numbers' ) {
                    $class->display_account_numbers( $post_id, false );
                }
            }, 10, 2 );
            add_filter( 'manage_users_columns', function ( $columns ) {
                $columns[ 'account_numbers' ] = 'Account Numbers';
                return $columns;
            }, 10, 1 );
            add_action( 'manage_users_custom_column', function ( $value, $column_name, $user_id ) use ( $class ) {
                if ( 'account_numbers' == $column_name ) {
                    return $class->display_account_numbers( "user_" . $user_id, true, false );
                }
                return $value;
            }, 10, 3 );
        }
    }

    // =================================================================================================================
    // MPSERVICENET UI
    // =================================================================================================================

    public function mpservicenet_shortcode()
    {
        if ( ! is_user_logged_in() ) {
            wp_redirect( home_url( "/login/?redirect_to=" . $_SERVER[ "REQUEST_URI" ] ) );
            exit();
        }   

        ob_start();
        ?>
        <div id="mpservicenet" style="display: none">
            <?= do_shortcode( '[fl_builder_insert_layout slug="mpservicenet-1" type="fl-builder-template" site="1"]' );
            // [mpservicenet_breadcrumbs_shortcode]
            // [mpservicenet_filters_shortcode]
            // [mpservicenet_content_shortcode]
            ?>
        </div>
        <?php
        return ob_get_clean();
    }

    // [fl_builder_insert_layout slug="mpservicenet" type="fl-builder-template" site="1"]

    public function mpservicenet_breadcrumbs_shortcode()
    {
        ob_start();
        ?>
        <div class="breadcrumbs" id="mps-breadcrumbs" data-homeurl="<?= home_url(); ?>"
             data-mpsurl="<?= home_url( '/mpservicenet#' ); ?>">
            <nav>
                <ul id="breadcrumbs-list" data-bind="foreach: crumbs">
                    <li>
                        <a data-bind="attr: { href: url }, text: label"></a>
                        <i class="ua-icon ua-icon-chevron-right" aria-hidden="true"></i>
                    </li>
                </ul>
            </nav>
        </div>
        <?php
        return ob_get_clean();
    }

    public function mpservicenet_filters_shortcode()
    {
        ob_start();
        ?>
        <div id="mps-announcements" data-bind="visible: visible">
            <?= do_shortcode( '[fl_builder_insert_layout slug="mpservicenet-info-box-module" type="fl-builder-template" site="1"]' ); ?>
        </div>
        <div id="mps-filters" class="filters" data-bind="visible: visible, event: { keypress: on.keypress }">
            <div id="account-filters" class="account-filters mpsnet" data-bind="visible: visibility.sectionUpper">
                <div>
                    <p id="account-filter-title" class="title"
                       data-bind="visible: visibility.title"><?= __( 'SEARCH & SELECTION' ); ?></p>
                    <p class="subtitle"><?= __( 'ACCOUNT' ); ?></p>
                </div>
                <div>
                    <div class="filter-upper">
                        <input id="account-filter-text-input" type="text" isp_ignore placeholder="Search for Account"
                               data-bind="value: userInput.textUpper">
                        <a id="account-filter-button" data-bind="click: on.click.filterUpper" class="button">
                            <?= __( 'Search' ); ?>
                        </a>
                    </div>
                    <div class="account-options">
                        <ul id="account-filter-list-radio" class="account-filter-list"
                            data-bind="
                                visible: useRadios,
                                foreach: accounts,
                                css: { 'no-scroll-bar': accounts().length < 6 }">
                            <li>
                                <label>
                                    <input type="radio"
                                           data-bind="attr: { id: 'account_radio_' + number }, value: number, checked: $parent.userInput.selectedAccountRadio"/>
                                    <span class="account-filter-label" data-bind="text: name"></span>
                                </label>
                            </li>
                        </ul>
                        <ul id="account-filter-list-checkbox" class="account-filter-list"
                            data-bind="
                                visible: ! useRadios(),
                                foreach: accounts,
                                css: { 'no-scroll-bar': accounts().length < 6 }">
                            <li>
                                <label>
                                    <input type="checkbox"
                                           data-bind="attr: { id: 'account_checkbox_' + number }, value: number, checked: $parent.userInput.selectedAccountCheckboxes"/>
                                    <span class="account-filter-label" data-bind="text: name"></span>
                                </label>
                            </li>
                        </ul>
                        <!-- ko if: accounts().length === 0 -->
                        <div class="account-not-found-message">
                            <p>The requested account does not exist or you do not have access. Please contact your administrator.</p>
                        </div>
                        <!-- /ko -->
                    </div>
                    <div class="filter-lower">
                        <a id="account-filter-submit" class="button"
                           data-bind="visible: visibility.buttonSubmitUpper, click: on.click.submitUpper"><?= __( 'Go' ); ?></a>
                    </div>
                    <div class="filter-deselect-account-items" data-bind="visible: ! useRadios()">
                        <div class="deselect-items">
                            <ul data-bind="foreach: userInput.selectedAccountCheckboxes">
                                <li data-bind="click: $parent.on.click.deselectAccount">
                                    <span data-bind="text: $data"></span>
                                </li>
                            </ul>
                        </div>
                        <div class="controls" data-bind="visible: userInput.selectedAccountCheckboxes().length > 0">
                            <button data-bind="click: on.click.deselectAllAccounts"><?= __( 'Deselect All' ); ?></button>
                        </div>
                    </div>
                </div>
            </div>
            <div id="id-filters" class="id-filters" data-bind="visible: visibility.sectionLower">
                <div>
                    <p id="id-filters-subtitle" class="subtitle" data-bind="text: text.subtitle"></p>
                </div>
                <div>
                    <div class="filter-types">
                        <ul id="id-filter-type-list" data-bind="visible: visibility.filterInputTypes">
                            <li class="filter-type" data-bind="visible: visibility.filterInputTypeCatalogNumber">
                                <input type="radio" name="filter-type" id="filter-type-catalog-number"
                                       value="catalog-number"
                                       data-bind="checked: userInput.filterType">
                                <label for="filter-type-catalog-number"><?= __( 'Catalog #' ); ?></label>
                            </li>
                            <li class="filter-type" data-bind="visible: visibility.filterInputTypeQuoteNumber">
                                <input type="radio" name="filter-type" id="filter-type-quote-number"
                                       value="quote-number"
                                       data-bind="checked: userInput.filterType">
                                <label for="filter-type-quote-number"><?= __( 'Quote #' ); ?></label>
                            </li>
                            <li class="filter-type" data-bind="visible: visibility.filterInputTypeOrderNumber">
                                <input type="radio" name="filter-type" id="filter-type-order-number"
                                       value="order-number"
                                       data-bind="checked: userInput.filterType">
                                <label for="filter-type-order-number"><?= __( 'Order #' ); ?></label>
                            </li>
                            <li class="filter-type" data-bind="visible: visibility.filterInputTypePoNumber">
                                <input type="radio" name="filter-type" id="filter-type-po-number" value="po-number"
                                       data-bind="checked: userInput.filterType">
                                <label for="filter-type-po-number"><?= __( 'PO #' ); ?></label>
                            </li>
                        </ul>
                    </div>
                    <div class="filter-text">
                        <input id="id-filter-text-input" isp_ignore type="text" data-bind="value: userInput.textLower">
                        <div class="id-filter-instructions">
                            <p><?= __( 'Separate multiple part numbers with a comma.' ); ?></p>
                        </div>
                    </div>
                    <div id="id-filter-date" class="date-filter" data-bind="visible: visibility.filterInputDate">
                        <h5><?= __( 'Issued Date' ); ?></h5>
                        <div class="date-picker from-date">
                            <label for="from-date-input"><?= __( 'From' ); ?></label>
                            <div class="date-wrap account-number-required"
                                 data-title=" Please Select An Account First!">
                                <input id="from-date-input" class="from-date" type="date"
                                       data-bind="value: userInput.dateFrom">
                            </div>
                        </div>
                        <div class="date-picker to-date">
                            <label for="to-date-input"><?= __( 'To' ); ?></label>
                            <div class="date-wrap">
                                <input id="to-date-input" class="to-date" type="date"
                                       data-bind="value: userInput.dateTo">
                            </div>
                        </div>
                    </div>
                    <div id="id-filter-status" class="status-input" data-bind="visible: visibility.filterInputStatus">
                        <select data-bind="value: userInput.status">
                            <option value=""><?= __( 'Open & Shipped' ); ?></option>
                            <option value="open"><?= __( 'Open' ); ?></option>
                            <option value="shipped"><?= __( 'Shipped' ); ?></option>
                        </select>
                    </div>
                </div>
                <div class="filter-submit-lower">
                    <a id="id-submit-button" class="order-status-filter-submit button"
                       data-bind="visible: visibility.buttonSubmitLower, text: text.buttonSubmitLower, click: on.click.submitLower"></a>
                </div>
            </div>
            <div id="pa-info-links" style="display: none" data-bind="visible: visibility.sectionLinks">
                <div class="title">
                    <p><?= __( 'PRICE AND AVAILABILITY INFORMATION' ); ?></p>
                </div>
                <div class="body">
                    <a id="pa-info-links-catalog-page" class="pa-info-link"
                       data-bind="visible: infoLinks.catalogPage, attr: { href: infoLinks.catalogPage }"><?= __( 'Catalog Page' ); ?></a>
                    <a id="pa-info-links-order-status" class="pa-info-link"
                       data-bind="visible: infoLinks.orderStatus, attr: { href: infoLinks.orderStatus }"><?= __( 'Order Status by Catalog #' ); ?></a>
                    <a id="pa-info-links-quote-detail" class="pa-info-link"
                       data-bind="visible: infoLinks.quoteDetail, attr: { href: infoLinks.quoteDetail }"><?= __( 'Quote Detail by Catalog #' ); ?></a>
                    <a id="pa-info-links-product-drawing" class="pa-info-link"
                       data-bind="visible: infoLinks.productDrawing, attr: { href: infoLinks.productDrawing }"><?= __( 'Product Drawing' ); ?></a>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function mpservicenet_content_shortcode()
    {
        ob_start();
        ?>
        <div class="content mpsnet">
            <?= do_shortcode( '[fl_builder_insert_layout slug="mpservicenet-content" type="fl-builder-template" site="1"]' );
            // [mpservicenet_content_navigation_shortcode]
            // [mpservicenet_content_display_shortcode]
            ?>
        </div>
        <?php
        return ob_get_clean();
    }

    // [fl_builder_insert_layout slug="mpservicenet-content" type="fl-builder-template" site="1"]

    public function mpservicenet_content_navigation_shortcode()
    {
        ob_start();
        ?>
        <div id="mps-navigation" class="navigation mpsnet">
            <nav>
                <ul data-bind="foreach: tabs">
                    <li>
                        <a class="mps-navigation-button button"
                           data-bind="click: $parent.toggleActive,
                                      attr: { href: '#' + key, title: name },
                                      css: $parent.selectedTab() === $data ? 'mps-navigation-button-active' : '',
                                      text: name"></a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php
        return ob_get_clean();
    }

    public function mpservicenet_content_display_shortcode()
    {
        ob_start();
        ?>
        <div class="display content_pane">
            <div id="mps-pages">
                <div id="landing-page" class="page" style="display: none;" data-bind="visible: visible">
                    <?= do_shortcode( '[fl_builder_insert_layout slug="mpservicenet-landing-page" type="fl-builder-template" site="1"]' ); ?>
                </div>
                <div id="price-availability-default-page" class="page" style="display: none;"
                     data-bind="visible: visible">
                    <?= do_shortcode( '[fl_builder_insert_layout slug="mpservicenet-price-availability-default" type="fl-builder-template" site="1"]' ); ?>
                </div>
                <div id="price-availability-catalog-summary-page" class="page" style="display: none;"
                     data-bind="visible: visible">
                    <?= do_shortcode( '[fl_builder_insert_layout slug="mpservicenet-price-availability-account" type="fl-builder-template" site="1"]' ); ?>
                </div>
                <div id="price-availability-packaging-information-page" class="page" style="display: none;"
                     data-bind="visible: visible">
                    <?= do_shortcode( '[fl_builder_insert_layout slug="mpservicenet-price-availability-id" type="fl-builder-template" site="1"]' ); ?>
                </div>
                <div id="quote-default-page" class="page" style="display: none;" data-bind="visible: visible">
                    <?= do_shortcode( '[fl_builder_insert_layout slug="mpservicenet-quote-default" type="fl-builder-template" site="1"]' ); ?>
                </div>
                <div id="quote-account-page" class="page" style="display: none;" data-bind="visible: visible">
                    <?= do_shortcode( '[fl_builder_insert_layout slug="mpservicenet-quote-account" type="fl-builder-template" site="1"]' ); ?>
                </div>
                <div id="quote-id-page" class="page" style="display: none;" data-bind="visible: visible">
                    <?= do_shortcode( '[fl_builder_insert_layout slug="mpservicenet-quote-id" type="fl-builder-template" site="1"]' ); ?>
                </div>
                <div id="order-default-page" class="page" style="display: none;" data-bind="visible: visible">
                    <?= do_shortcode( '[fl_builder_insert_layout slug="mpservicenet-order-default" type="fl-builder-template" site="1"]' ); ?>
                </div>
                <div id="order-account-page" class="page" style="display: none;" data-bind="visible: visible">
                    <?= do_shortcode( '[fl_builder_insert_layout slug="mpservicenet-order-account" type="fl-builder-template" site="1"]' ); ?>
                </div>
                <div id="order-id-page" class="page" style="display: none;" data-bind="visible: visible">
                    <?= do_shortcode( '[fl_builder_insert_layout slug="mpservicenet-order-id" type="fl-builder-template" site="1"]' ); ?>
                </div>
                <div id="order-packaging-page" class="page" style="display: none;" data-bind="visible: visible">
                    <?= do_shortcode( '[fl_builder_insert_layout slug="mpservicenet-order-packaging" type="fl-builder-template" site="1"]' ); ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    // [fl_builder_insert_layout slug="mpservicenet-price-availability-account" type="fl-builder-template" site="1"]

    public function mpservicenet_price_availability_account_header_shortcode()
    {
        ob_start();
        ?>
        <div id="price-availability-account-header">
            <div class="text">
                <h3><span class="label">CATALOG SUMMARY LIST FOR </span><span class="value"
                                                                              data-bind="text: accountNumber"></span>
                </h3>
                <p class="results-count"><span class="value" data-bind="text: resultsCount"></span> RESULTS FOUND</p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function mpservicenet_price_availability_account_listing_shortcode()
    {
        ob_start();
        ?>
        <div id="price-availability-account-listing">
            <div class="table-wrapper">
                <p>
                    <button class="fullscreen-table" data-tablename="price-availability-account-listing"
                            data-tabletitle="CATALOG SUMMARY LIST">View Table In Fullscreen
                    </button>
                </p>
                <table id="price-availability-account-listing-table"
                       class="price-availability-account-listing-table removeLastColAE">
                    <thead>
                    <tr>
                        <th class="catalog-number">CATALOG #</th>
                        <th class="list-price">LIST PRICE</th>
                        <th class="standard-discount-price">STANDARD DISCOUNT PRICE</th>
                        <th class="lead-time">LEAD TIME</th>
                        <th class="details"></th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    // [fl_builder_insert_layout slug="mpservicenet-price-availability-id" type="fl-builder-template" site="1"]

    public function mpservicenet_price_availability_id_header_shortcode()
    {
        ob_start();
        ?>
        <div id="price-availability-id-header">
            <div class="text">
                <h3><span class="label">PACKAGING INFORMATION FOR </span><span class="value"
                                                                               data-bind="text: catalogNumber"></span>
                </h3>
                <a id="back-to-catalog-summary" data-bind="click: onClickBackToCatalogSummary">BACK TO CATALOG
                    SUMMARY</a>
            </div>
            <div class="export">
                <!-- <a id="pa-packaging-print-button" class="button">PRINT THIS PAGE</a> -->
                <?php echo do_shortcode( "[representatives_export_to_excel]" ); ?>
            </div>

        </div>
        <?php
        return ob_get_clean();
    }

    public function mpservicenet_price_availability_id_packaging_info_shortcode()
    {
        ob_start();
        ?>
        <div id="price-availability-id-packaging-info" class="side-table">
            <div class="table-wrapper">
                <p>
                    <button class="fullscreen-table" data-tablename="price-availability-id-packaging-info"
                            data-tabletitle="PACKAGING INFORMATION">View Table In Fullscreen
                    </button>
                </p>
                <table id="price-availability-packaging-info-table"
                       class="side-table price-availability-id-packaging-info-table">
                    <thead>
                    <tr>
                        <th>STANDARD PACKAGE QUANTITY</th>
                        <th>PALLET QUANTITY</th>
                        <th>WEIGHT / EA.</th>
                        <th>UOM</th>                     
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function mpservicenet_price_availability_id_availability_info_shortcode()
    {
        ob_start();
        ?>
        <div id="price-availability-id-availability-info" class="side-table">
            <div class="table-wrapper">
                <p>
                    <button class="fullscreen-table" data-tablename="price-availability-id-availability-info"
                            data-tabletitle="AVAILABILITY INFORMATION">View Table In Fullscreen
                    </button>
                </p>
                <table id="availability-info-table" class="price-availability-id-availability-info-table">
                    <thead>
                    <tr>
                        <th>CATALOG #</th>
                        <th>STOCK STATUS</th>
                        <th>DESCRIPTION</th>
                        <th>LIST PRICE</th>
                        <th>STANDARD DISCOUNT PRICE</th>
                        <th>QUANTITY IN STOCK</th>
                        <th>MFG. LEAD TIME</th>

                    </tr>
                    </thead>
                </table>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function mpservicenet_price_availability_id_schedule_info_shortcode()
    {
        ob_start();
        ?>
        <div id="price-availability-id-schedule-info">
            <div class="table-wrapper">
                <div class="text">
                    <h3><span class="label">FOR REPRESENTATIVE PRODUCTION SCHEDULE</span></h3>
                </div>
                <p>
                    <button class="fullscreen-table" data-tablename="price-availability-id-schedule-info"
                            data-tabletitle="PRODUCTION SCHEDULE INFORMATION">View Table In Fullscreen
                    </button>
                </p>
                <table id="schedule-info-table" class="price-availability-id-schedule-info-table">
                    <thead>
                    <tr>
                        <th>AVAILABLE TO PROMISE DATE</th>
                        <th>QTY</th>
                        <th>TOTAL</th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function mpservicenet_order_packaging_info_shortcode()
    {
        ob_start();
        ?>
        <div id="order-packaging-info" class="side-table">
            <div class="table-wrapper">
                <p>
                    <button class="fullscreen-table" data-tablename="order-packaging-info"
                            data-tabletitle="ORDER PACKAGING INFORMATION">View Table In Fullscreen
                    </button>
                </p>
                <table id="order-packaging-info-table" class="order-packaging-info-table">
                    <thead>
                    <tr>
                        <th>STANDARD PACKAGE QUANTITY</th>
                        <th>PALLET QUANTITY</th>
                        <th>WEIGHT / EA.</th>
                        <th>UOM</th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function mpservicenet_order_packaging_line_items_shortcode()
    {
        ob_start();
        ?>
        <div class="table-wrapper" id="order-packaging-line-items">
            <p>
                <button class="fullscreen-table" data-tablename="order-packaging-line-items"
                        data-tabletitle="ORDER LINE ITEMS">View Table In Fullscreen
                </button>
            </p>
            <table id="order-packaging-line-items-table" class="order-packaging-line-items-table">
                <thead>
                <tr>
                    <th>Order #</th>
                    <th>PO #</th>
                    <th>Status</th>
                    <th>Line Item</th>
                    <th>Price</th>
                    <th>Qty</th>
                    <th>Orig Ack Date</th>
                    <th>Current Avail Date</th>
                    <th>Date Shipped</th>
                    <th>Pro #</th>
                    <th>Tracking</th>
                </tr>
                </thead>
            </table>
        </div>
        <?php
        return ob_get_clean();
    }

    public function mpservicenet_quote_catalog_packaging_info_shortcode()
    {
        ob_start();
        ?>
        <div id="quote-catalog-packaging-info" data-bind="visible: visibility.catalogPackagingTable">
            <div class="table-wrapper">
                <div class="text">
                    <h3><span class="label">PACKAGING INFORMATION</span></h3>
                </div>
                <p>
                    <button class="fullscreen-table" data-tablename="quote-catalog-packaging-info"
                            data-tabletitle="QUOTE PACKAGING INFORMATION">View Table In Fullscreen
                    </button>
                </p>
                <table id="quote-catalog-packaging-info-table" class="quote-catalog-packaging-info-table">
                    <thead>
                    <tr>
                        <th><?= __( 'CATALOG #' ); ?></th>
                        <th><?= __( 'STANDARD PACKAGE QUANTITY' ); ?></th>
                        <th><?= __( 'PALLET QUANTITY' ); ?></th>
                        <th><?= __( 'WEIGHT / EA.' ); ?></th>
                        <th><?= __( 'UOM' ); ?></th>
                        <th><?= __( 'STOCK STATUS' ); ?></th>
                        <th><?= __( 'STD DISC PRICE' ); ?></th>
                        <th><?= __( 'QTY IN STOCK' ); ?></th>
                        <th><?= __( 'CURRENT LEAD TIME' ); ?></th>                        
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function quotes_quote_summary_header()
    {
        ob_start();
        ?>
        <div id="quote-summary-account-header">
            <div class="text">
                <h3>
                    <span class="label">QUOTE SUMMARY LIST FOR </span>
                    <span class="value" data-bind="text: accountNumber"></span>
                </h3>
                <p class="results-count"><span class="value" data-bind="text: resultsCount"></span> RESULTS FOUND</p>
            </div>
            <?php echo do_shortcode( "[representatives_export_to_excel]" ); ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function quotes_quote_details_header()
    {
        ob_start();
        ?>
        <div class="package-head-print">
            <div class="package-head">
                <h3><span>QUOTE DETAILS FOR </span><span data-bind="text: quoteNumber"></span></h3>
                <div class="back-btn">
                    <i class="ua-icon ua-icon-chevron-small-left" aria-hidden="true"> </i>
                    <a id="back-to-accounts-button" class="back_to_catalog_page"
                       data-bind="click: onClickBackToQuoteSummary">Back to Quote Summary List</a></div>
            </div>
            <?php echo do_shortcode( "[representatives_export_to_excel]" ); ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function orders_order_summary_header()
    {
        ob_start();
        ?>
        <div class="package-head-print">
            <div class="package-head">
                <h3><span>ORDER SUMMARY LIST FOR </span><span data-bind="text: accountNumbers"></span></h3>
                <p class="results-count"><span data-bind="text: resultsCount"></span> RESULTS FOUND</p>
            </div>
            <?php //echo do_shortcode("[representatives_export_to_excel]");
            ?>
        </div>

        <?php
        return ob_get_clean();
    }

    public function orders_order_details_header()
    {
        ob_start();
        ?>
        <div id="orders-id-header">
            <div class="text">
                <h3><span>ORDER DETAILS FOR </span><span data-bind="text: poNumber"></span></h3>
                <a data-bind="click: onClickBackToOrderSummary">BACK TO ORDER SUMMARY LIST</a>
            </div>
            <div class="export">
                <?php echo do_shortcode( "[representatives_export_to_excel]" ); ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function orders_order_packaging_header()
    {
        ob_start();
        ?>
        <div id="orders-packaging-header">
            <div class="text">
                <h3><span>PACKAGING INFORMATION FOR </span><span data-bind="text: catalogNumber"></span></h3>
                <a data-bind="click: onClickBackToOrderDetails">BACK TO ORDER DETAILS</a>
            </div>
            <div class="export">
                <?php echo do_shortcode( "[representatives_export_to_excel]" ); ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    // =================================================================================================================
    // MPSERVICENET AJAX
    // =================================================================================================================

    // helpers

    function parse_date_yyyymmdd( ?string $date ): ?DateTime
    {
        if ( ! $date || ! strlen( $date ) )
            return null;

        if ( strlen( $date ) !== 8 )
            return null;

        $dateWithSeparators =
            substr( $date, 0, 4 )
            . '-' . substr( $date, 4, 2 )
            . '-' . substr( $date, 6, 2 );

        return DateTime::createFromFormat(
            'Y-m-d',
            $dateWithSeparators
        );
    }

    // ajax functions

    public function price_and_availability_id_ajax()
    {
        global $wpdb;
        $account_numbers = $_POST[ "account_numbers" ];
        $catalog_numbers = $_POST[ "catalog_numbers" ];

        
        $can_see_inv = $this->can_see_inventory();

        $accountCheckResults = $this->check_access_and_filter_accounts( $account_numbers );
        if ( ! $accountCheckResults[ 'has_access' ] ) {
            echo json_encode( 'Permission Denied.' );
            wp_die();
        }
        $account_numbers = $accountCheckResults[ 'allowed_accounts_csv' ];

        //$sql                                       = "SELECT mp.*, p.post_id AS ITEM_ID, p3.meta_value as Factor FROM mp_item AS mp LEFT JOIN wp_postmeta AS p ON p.meta_value = mp.catalogno LEFT JOIN wp_postmeta AS p2 ON p2.meta_value = '$account_numbers' LEFT JOIN wp_postmeta AS p3 ON p3.post_id = p2.post_id WHERE catalogno IN ('$catalog_numbers') AND p.meta_key = 'CatalogNumberCleaned' AND p2.meta_key = 'cust' AND p3.meta_key = 'fact' ORDER BY catalogno";
        $sql = "SELECT mp.*, p.post_id AS ITEM_ID, p3.meta_value as Factor FROM mp_item AS mp LEFT JOIN wp_postmeta AS p ON p.meta_key = 'CatalogNumberCleaned' AND p.meta_value = mp.catalogno LEFT JOIN wp_postmeta AS p2 ON p2.meta_key = 'cust' AND p2.meta_value = '$account_numbers' LEFT JOIN wp_postmeta AS p3 ON p3.meta_key = 'fact' AND p3.post_id = p2.post_id WHERE catalogno IN ('$catalog_numbers') ORDER BY catalogno";
        
        $maclean_price_availability_packaging_info = $wpdb->get_results( $sql );
        if ( count( $maclean_price_availability_packaging_info ) > 0 ) {
            $maclean_price_availability_packaging_info = $maclean_price_availability_packaging_info[ 0 ];
        }
        $json_data   = array();
        $json_data[] = array(
            "standard-package-quantity" => ltrim( $maclean_price_availability_packaging_info->stdpk, "0" ),
            "pallet-quantity"           => ltrim( $maclean_price_availability_packaging_info->skidqty, "0" ),
            "weight-ea"                 => rtrim( ltrim( $maclean_price_availability_packaging_info->weight, "0" ), "0" ),
            "unit-of-measure"           => $maclean_price_availability_packaging_info->d
        );
        $list_price = ltrim( $maclean_price_availability_packaging_info->listpric, "0" );
        if ( $list_price === ".00" ) {
            $list_price = "0.00";
            $std_price = "0.00";
        } else {
            $std_price = $maclean_price_availability_packaging_info->listpric * floatval( $maclean_price_availability_packaging_info->Factor );
        }
        $inv_val = ltrim( $maclean_price_availability_packaging_info->instock, "0" );
        $json_data[] = array(
            "catalog-number"          => strtoupper( str_replace( "_", " ", $_POST[ "catalog_numbers" ] ) ),
            "stock-status"            => $maclean_price_availability_packaging_info->stk === "Y" ? "stock" : "nonstock",
            "description"             => $maclean_price_availability_packaging_info->description,
            "list-price"              => $list_price,
            "standard-discount-price" => $std_price,
            "price-last-updated"      => $maclean_price_availability_packaging_info->pricdate,
            "quantity-in-stock"       => $can_see_inv ? ($inv_val === "" ? "0" : $inv_val) : "Not Available.",
            "plant-primary-ship-from" => $this->get_plant_name( $maclean_price_availability_packaging_info->p ),
            "mfg-lead-time"           => $maclean_price_availability_packaging_info->lt,
            "secondary-inv-location"  => '', // $maclean_price_availability_packaging_info->factory,
            "manufacturing-code"      => $maclean_price_availability_packaging_info->a
        );
        if ( get_field( "access_production_sched", "user_" . wp_get_current_user()->ID ) == true ) {
            $counter   = 1;
            $date      = "dateavl";
            $quantity  = "qtyavl";
            $qty_total = 0;
            if ( $maclean_price_availability_packaging_info->{"$date$counter"} !== null && $maclean_price_availability_packaging_info->{"$date$counter"} !== '' ) {
                $arrays = array();
                while ( $maclean_price_availability_packaging_info->{"$date$counter"} !== null && $maclean_price_availability_packaging_info->{"$date$counter"} !== '' ) {
                    $qty_total += intval( $maclean_price_availability_packaging_info->{"$quantity$counter"} );
                    $arrays[]  = array(
                        "Available To Promise Date" => $maclean_price_availability_packaging_info->{"$date$counter"},
                        "Qty"                       => intval( $maclean_price_availability_packaging_info->{"$quantity$counter"} ),
                        "Total"                     => $qty_total
                    );
                    $counter++;
                }
                $json_data[] = $arrays;
            } else {
                $json_data[] = array();
            }
        } else {
            $json_data[] = array();
        }

        $json_data[] = $this->get_sidebar_data_links( $_POST[ 'catalog_numbers' ] );

        echo json_encode( $json_data );
        die();
    }

    public function price_and_availability_production_schedule_ajax()
    {
        global $wpdb;
        $account_numbers = $_POST[ "account_numbers" ];
        $catalog_numbers = $_POST[ "catalog_numbers" ];

        $accountCheckResults = $this->check_access_and_filter_accounts( $account_numbers );
        if ( ! $accountCheckResults[ 'has_access' ] ) {
            echo json_encode( 'Permission Denied.' );
            wp_die();
        }
        $account_numbers = $accountCheckResults[ 'allowed_accounts_csv' ];

        $sql = "SELECT mp.*, p.post_id AS ITEM_ID, p3.meta_value as Factor FROM mp_item AS mp LEFT JOIN wp_postmeta AS p ON p.meta_value = mp.catalogno LEFT JOIN wp_postmeta AS p2 ON p2.meta_value = '$account_numbers' LEFT JOIN wp_postmeta AS p3 ON p3.post_id = p2.post_id WHERE catalogno IN ('$catalog_numbers') AND p.meta_key = 'CatalogNumberCleaned' AND p2.meta_key = 'cust' AND p3.meta_key = 'fact' ORDER BY catalogno";
        $maclean_price_availability_packaging_info = $wpdb->get_results( $sql );
        if ( count( $maclean_price_availability_packaging_info ) > 0 ) {
            $maclean_price_availability_packaging_info = $maclean_price_availability_packaging_info[ 0 ];
        }
        $json_data   = array();
        if ( get_field( "access_production_sched", "user_" . wp_get_current_user()->ID ) == true ) {
            $json_data[] = "true";
            $counter   = 1;
            $date      = "dateavl";
            $quantity  = "qtyavl";
            $qty_total = 0;
            if ( $maclean_price_availability_packaging_info->{"$date$counter"} !== null && $maclean_price_availability_packaging_info->{"$date$counter"} !== '' ) {
                $arrays = array();
                while ( $maclean_price_availability_packaging_info->{"$date$counter"} !== null && $maclean_price_availability_packaging_info->{"$date$counter"} !== '' ) {
                    $qty_total += intval( $maclean_price_availability_packaging_info->{"$quantity$counter"} );
                    $arrays[]  = array(
                        "availPromiseDate"           => $maclean_price_availability_packaging_info->{"$date$counter"},
                        "qty"                       => intval( $maclean_price_availability_packaging_info->{"$quantity$counter"} ),
                        "total"                     => $qty_total
                    );
                    $counter++;
                }
                $json_data[] = $arrays;
            } else {
                $json_data[] = array();
            }
        } else {
            $json_data[] = "false";
            $json_data[] = array();
        }
        echo json_encode( $json_data );
        die();
    }

    public function catalog_table_price_and_availability_data_from_ajax()
    {
        global $wpdb;

        $account_numbers = $_POST[ "account_numbers" ];
        $fact = "n/a";

        $catalogNumbers  = array_filter( explode( ",", $_POST[ "catalog_numbers" ] ), function ( $e ) {
            return ! empty( $e );
        } );

        $accountCheckResults = $this->check_access_and_filter_accounts( $account_numbers );
        if ( ! $accountCheckResults[ 'has_access' ] ) {
            echo json_encode( 'Permission Denied.' );
            wp_die();
        }
        $account_numbers = $accountCheckResults[ 'allowed_accounts_csv' ];

        $where = array_map( function ( $e ) {
            $e = $this->master->functions->clean_data_strict( $e );
            return "cleaned_catalog_no LIKE '$e%' ";
        }, $catalogNumbers );
        if ( count( $where ) <= 0 ) {
            return array( $catalogNumbers, $where );
        }
        $sql = "SELECT mp.*, p.post_id AS ITEM_ID, p3.meta_value as Factor FROM mp_item AS mp LEFT JOIN wp_postmeta AS p ON p.meta_value = mp.catalogno AND p.meta_key = 'CatalogNumberCleaned' LEFT JOIN wp_postmeta AS p2 ON p2.meta_value = '$account_numbers' AND p2.meta_key = 'cust' LEFT JOIN wp_postmeta AS p3 ON p3.post_id = p2.post_id AND p3.meta_key = 'fact' WHERE (" . implode( " OR ", $where ) . ") ORDER BY catalogno";
        $results = $wpdb->get_results( $sql );
        $data    = [];
        foreach ( $results as $result ) {
            $child       = $this->master->products->get_the_lowest_category( intval( $result->ITEM_ID ) );
            $productLink = '#';
            if ( $child !== null || $result->ITEM_ID === NULL ) {
                $productLink = get_term_link( get_term( $child->parent, 'product_cat' ) );
                if ( is_wp_error( $productLink ) )
                    $productLink = '#';
            }
            if ( $accountCheckResults[ 'has_access' ] ) {
                $fact = $result->Factor;
            }
            $price = ltrim( $result->listpric, '0' );            
            if ( $fact !== "n/a" && ".00" !== $price ) {
                $standard_discount_price = round( floatval( $price ) * floatval( $fact ), 2);
            } else {
                $standard_discount_price = "0.00";
            }      
            array_push( $data, [
                'product-link'   => $productLink,
                'catalog-number' => $result->catalogno,
                'standard-discount-price' => $standard_discount_price,
                'list-price'     => ( $price === '' || $price === '.00' ) ? '0.00' : $price,
                'lead-time'      => $result->lt
            ] );
        }
        return $data;
    }

    public function price_and_availability_account_ajax()
    {
        echo json_encode( $this->catalog_table_price_and_availability_data_from_ajax() );
        die();
    }

    public function query_packaging_information_for_catalog_numbers_exact(
        array $catalog_numbers,
        bool $use_cleaned_catalog_numbers = true
    ) {
        global $wpdb;

        if ( ! count( $catalog_numbers ) )
            return [];

        $catalog_column = $use_cleaned_catalog_numbers ? 'mp_item.cleaned_catalog_no' : 'mp_item.catalogno';
        $catalog_wheres = array_map( function ( $catalog_number ) use ( $catalog_column ) {
            return "$catalog_column = '$catalog_number'";
        }, $catalog_numbers );

        $sql = "
            SELECT
                mp_item.cleaned_catalog_no,
                mp_item.catalogno,
                mp_item.d,
                mp_item.skidqty,
                mp_item.stdpk,
                mp_item.weight
            FROM
                mp_item
            WHERE "
            . implode( ' OR ', $catalog_wheres ) . " 
            ORDER BY
                catalogno ASC";

        $results = $wpdb->get_results( $sql );
        if ( ! $results )
            return array();

        $data_assoc = array();

        foreach ( $results as $result ) {
            if ( ! array_key_exists( $result->cleaned_catalog_no, $data_assoc ) )
                $data_assoc[ $result->cleaned_catalog_no ] = array(
                    'catalog_number'            => $result->catalogno,
                    'pallet_quantity'           => ltrim( $result->skidqty, '0' ),
                    'unit_of_measure'           => $result->d,
                    'standard_package_quantity' => ltrim( $result->stdpk, '0' ),
                    'weight_ea'                 => strval( floatval( ltrim( $result->weight, '0' ) ) )
                );
        }

        return $data_assoc;
    }

    public function query_order_packaging_information(
        string $account_number,
        string $po_number,
        string $catalog_number
    ) {
        global $wpdb;

        $data = array(
            'packaging_info' => array(),
            'line_items'      => array()
        );

        if ( ! $po_number || ! $catalog_number || !$account_number)
            return $data;

        $data[ 'packaging_info' ] = array_values(
            $this->query_packaging_information_for_catalog_numbers_exact(
                [ $catalog_number ]
            )
        );
        $sql = "SELECT mo.*, pm.post_id as item_id FROM mp_order AS mo LEFT JOIN wp_postmeta AS pm ON pm.meta_value = mo.catalogno AND pm.meta_key = 'CatalogNumberCleaned' WHERE mo.pono = '$po_number' AND mo.cleaned_catalog_no = '$catalog_number' AND mo.cust='$account_number' ORDER BY mo.item";

        $results = $wpdb->get_results( $sql );
        if ( !$results )
            return $data;

        foreach ( $results as $result ) {
            if ( $result->status === "Shipped" ) {
                $tracking = "<a class='order-tracking-link' data-blno='{$result->bfwdslshlnum}' data-carrier='{$result->scac}' data-orderno='{$result->ordernum}' data-prono='{$result->prono}'>Tracking<div class='modal' style='display: none;'><tr>
                    <td valign='top' align='left'>
                    <br><b>Carrier Name: </b>{$result->scac}
                    <br><b>BL#: </b>{$result->bfwdslshlnum}
                    <br><b>Pro Number: </b>{$result->prono}                 
                    </td>
                    </tr></div></a>";
            } else {
                $tracking = " ";
            }

            array_push( $data[ 'line_items' ], array(
                'orderNumber'      => $result->ordernum,
                'poNumber'         => $result->pono,
                'status'           => $result->status,
                'lineItem'         => ltrim( $result->item, '0' ),
                'price'            => strval( floatval( ltrim( $result->price, '0' ) ) ),
                'quantity'         => strval( intval( $result->qty ) ),                
                'origAckDate'      => $result->ackdate,
                'currentAvailDate' => $result->avldate,
                'dateShipped'      => $result->shpdate,
                'proNumber'        => $result->prono,
                'track'            => $tracking
            ) );
        }

        return $data;
    }

    public function orders_account_ajax()
    {
        global $wpdb;
        
        // get post data
        $account_numbers = isset( $_POST[ 'accounts' ] ) ? ( array ) $_POST[ 'accounts' ] : array();
        $catalog_numbers = isset( $_POST[ 'catalogs' ] ) ? ( array ) $_POST[ 'catalogs' ] : array();
        $order_numbers   = isset( $_POST[ 'orders' ] ) ? ( array ) $_POST[ 'orders' ] : array();
        $po_numbers      = isset( $_POST[ 'pos' ] ) ? ( array ) $_POST[ 'pos' ] : array();
        $date_from       = isset( $_POST[ 'from' ] ) ? $_POST[ 'from' ] : '';
        $date_to         = isset( $_POST[ 'to' ] ) ? $_POST[ 'to' ] : '';
        $status         = isset( $_POST[ 'status' ] ) ? $_POST[ 'status' ] : '';

        // sanitize
        $account_numbers = array_map( 'esc_sql', $account_numbers );
        $catalog_numbers = array_map( 'esc_sql', array_map( array( $this->master->functions, "clean_data_strict" ), $catalog_numbers ) );
        $order_numbers   = array_map( 'esc_sql', $order_numbers );
        $po_numbers      = array_map( 'esc_sql', $po_numbers );
        $date_from       = esc_sql( $date_from );
        $date_to         = esc_sql( $date_to );
        $status         = esc_sql( $status );

        // parse dates
        $date_from = str_replace( '-', '', $date_from );
        $date_to   = str_replace( '-', '', $date_to );

        // check for account access permissions
        $account_check_results = $this->check_access_and_filter_accounts(
            implode( ',', $account_numbers )
        );
        if ( ! $account_check_results[ 'has_access' ] ) {
            echo json_encode( 'Permission Denied.' );
            wp_die();
        }

        // use set of permitted accounts for query
        $account_numbers = array_map(
            'esc_sql',
            explode( ',', $account_check_results[ 'allowed_accounts_csv' ] )
        );
        if ( ! count( $account_numbers ) ) {
            echo json_encode( array() );
            wp_die();
        }

        // build where clauses
        $wheres_catalog = array_map(
            function ( $catalog_number ) {
                return "o.cleaned_catalog_no LIKE '$catalog_number%'";
            },
            $catalog_numbers
        );
        $wheres_order   = array_map(
            function ( $order_number ) {
                return "o.ordernum LIKE '$order_number%'";
            },
            $order_numbers
        );
        $wheres_po      = array_map(
            function ( $po_number ) {
                return "o.pono LIKE '$po_number%'";
            },
            $po_numbers
        );

        $wheres = array();
        if ( count( $wheres_catalog ) )
            array_push( $wheres, '(' . implode( ' OR ', $wheres_catalog ) . ')' );
        if ( count( $wheres_order ) )
            array_push( $wheres, '(' . implode( ' OR ', $wheres_order ) . ')' );
        if ( count( $wheres_po ) )
            array_push( $wheres, '(' . implode( ' OR ', $wheres_po ) . ')' );
        if ( $date_from )
            array_push( $wheres, "STR_TO_DATE( o.podate, '%m/%d/%Y' ) >= '$date_from'" );
        if ( $date_to )
            array_push( $wheres, "STR_TO_DATE( o.podate, '%m/%d/%Y' ) <= '$date_to'" );
        //if ( $status ) {
            if ( strtoupper( $status ) === "OPEN" ) {
                $sqlGetOpenOrders = "
                    SELECT distinct o.pono FROM mp_order as o JOIN mp_order as o2 ON ((o.s <> o2.s AND o.id <> o2.id AND o2.pono = o.pono) OR o.pono = o2.pono) WHERE o.cust IN ( '" . implode( "', '", $account_numbers ) . "' )" . " AND o.s = 'O' ORDER BY o.pono";
                
                $results_open_orders = $wpdb->get_results( $sqlGetOpenOrders);
                $open_orders = array();
                $i = 0;
                foreach ( $results_open_orders as $open_order ){
                    $open_orders[$i++] = $open_order->pono;
                }
                $status = ", 'Open' As status";                
                array_push( $wheres, "pono IN ( '". implode( "', '", $open_orders) . "' )" );
            } else if ( strtoupper( $status ) === "SHIPPED" ) {
                $sqlGetOpenOrders = "
                    SELECT distinct o.pono FROM mp_order as o JOIN mp_order as o2 ON ((o.s <> o2.s AND o.id <> o2.id AND o2.pono = o.pono) OR o.pono = o2.pono) WHERE o.cust IN ( '" . implode( "', '", $account_numbers ) . "' )" . " AND o.s = 'O' ORDER BY o.pono";
                
                $results_open_orders = $wpdb->get_results( $sqlGetOpenOrders);
                $open_orders = array();
                $j = 0;
                foreach ( $results_open_orders as $open_order ){
                    $open_orders[$j++] = $open_order->pono;
                }
                $status = ", 'Shipped' As status";                
                array_push( $wheres, "pono NOT IN ( '". implode( "', '", $open_orders) . "' )" );
            } else {
                $status = ", IF ( SUM(CASE WHEN o.s = 'O' THEN 1 ELSE 0 END) > 0, 'Open', 'Shipped' ) AS status";
                array_push( $wheres, "s <> ''" );
            }
            
        //}
        
        $sql = "
            SELECT
                o.*,
                pm2.meta_value as cust_name". $status ."                               
            FROM
                mp_order as o
            LEFT JOIN
                    wp_postmeta AS pm ON 
                    pm.meta_value = o.cust 
                    AND pm.meta_key = 'cust' 
            LEFT JOIN 
                wp_postmeta AS pm2 ON 
                    pm2.post_id = pm.post_id 
                    AND pm2.meta_key = 'customername'
            WHERE 
                o.cust IN ( '" . implode( "', '", $account_numbers ) . "' )" .               
            ( count( $wheres ) ? ' AND ' . implode( ' AND ', $wheres ) : '' ) . "
            GROUP BY
                o.pono                
            ORDER BY
                o.pono";

        $results = $wpdb->get_results( $sql );

        $data_assoc = array();

        foreach ( $results as $result ) {
            if ( $result->status === "Shipped" ) {
                $track = "<a class='order-tracking-link' data-blno='{$result->bfwdslshlnum}' data-carrier='{$result->scac}' data-orderno='{$result->ordernum}' data-prono='{$result->prono}'>Tracking<div class='modal' style='display: none;'><tr>
                    <td valign='top' align='left'>
                    <br><b>Carrier Name: </b>{$result->scac}
                    <br><b>BL#: </b>{$result->bfwdslshlnum}
                    <br><b>Pro Number: </b>{$result->prono}                 
                    </td>
                    </tr></div></a>";
            } else {
                $track = " ";
            }
            // remove letters from last two characters in order num
            $ordernum    = $result->ordernum;
            $check_count = 2;
            if ( ( $length = strlen( $ordernum ) ) > $check_count ) {
                $split_index = $length - $check_count;
                $first       = substr( $ordernum, 0, $split_index );
                $second      = substr( $ordernum, $split_index, $check_count );
                $second      = preg_replace( '/[a-zA-Z]+/g', '', $second );
                $ordernum    = $first . $second;
            }

            // key by pono-ordernum to avoid duplicates after cleaning order num above
            $key = "$result->pono-$ordernum";
            $price = ltrim( trim( $result->price ), "0" );
            if ( strlen( $price ) === 0 ) {
                $price = "0.00";
            } else {
                if ( $price[0] === "." ) {
                    $price = "0" . $price;
                }
            }
            if ( ! array_key_exists( $key, $data_assoc ) ) {
                $data_assoc[ $key ] = array(
                    'orderNo'      => $result->ordernum,
                    'poNo'         => $result->pono,
                    'orderNoShort' => $ordernum,
                    'status'       => $result->status,
                    'custNo'       => $result->cust,
                    'status'       => $result->status,
                    'lineItem'     => $result->item,
                    'catNo'        => $result->catalogno,
                    'price'        => $price,
                    'qty'          => ltrim( trim( $result->qty ), "0"),
                    'custName'     => $result->cust_name,
                    'poDate'       => $result->podate,
                    'shipTo'       => $result->shipto,
                    'cityStZip'    => $result->citystzip,
                    'origAckDate'  => $result->ackdate,
                    'currAvailDate'=> $result->avldate,
                    'dateShipped'  => $result->shpdate,
                    'proNo'        => $result->prono,
                    'track'        => $track,

                );
            }
        }

        echo json_encode( array_values( $data_assoc ) );
        die();
    }

    public function orders_id_ajax()
    {
        global $wpdb;
        $po_number = $_POST[ "po" ];

        $account_numbers = array_filter( explode( ",", $_POST[ "account" ] ), function ( $e ) {
            return ! empty( $e );
        } );
        $account_numbers = implode( ',', $account_numbers );

        $accountCheckResults = $this->check_access_and_filter_accounts( $account_numbers );
        if ( ! $accountCheckResults[ 'has_access' ] ) {
            echo json_encode( 'Permission Denied.' );
            wp_die();
        }
        $account_numbers = $accountCheckResults[ 'allowed_accounts_csv' ];
        $account_numbers = explode( ',', $account_numbers );

        $catalog_where = "";
        if ( array_key_exists( "catNo", $_POST ) && $_POST[ "catNo" ] !== "" ) {
            $class = $this;
            $catalog_where = ' and ( ' . implode( ' or ', array_map( function( $e ) { return "mo.cleaned_catalog_no LIKE '$e%' "; }, array_map( function( $e )  use( $class ) { return $class->master->functions->clean_data_strict( $e ); }, explode( ",", $_POST[ 'catNo' ] ) ) ) ) . ' ) ';
        }

        $sql                           = "SELECT mo.*, pm.post_id as item_id FROM mp_order AS mo LEFT JOIN wp_postmeta AS pm ON pm.meta_value = mo.catalogno AND pm.meta_key = 'CatalogNumberCleaned' WHERE mo.pono = '$po_number' and mo.cust IN ('" . implode( "','", explode( ",", $_POST[ "account" ] ) ) . "') $catalog_where ORDER BY mo.item";
        $maclean_order_details_results = $wpdb->get_results( $sql );
        $catalogs                      = array();
        $data_array                    = array();
        $orders_processed              = array();
        $second_array                  = array();
        foreach ( $maclean_order_details_results as $result ) {
            $order_number = $result->ordernum;
            $po_number    = $result->pono;
            if ( ! in_array( $po_number, $orders_processed ) ) {
                $month = substr( $result->podate, 0, 2 );
                $day   = substr( $result->entdate, 3, 2 );
                $year  = substr( $result->entdate, 6, 4 );
                if ( strlen( $year ) === 2 ) {
                    $year = "20" . $year;
                }
                $order_link         = home_url( "/mpservicenet/orders/order-detail/?poNo=" . $result->pono . "&customerNumbers=" . $result->cust );
                $shipping_info      = $this->not_null( $result->shipto ) . "<br/>" . $this->not_null( $result->shipline2 ) . "<br/>" . $this->not_null( $result->shipline3 ) . "<br/>" . $this->not_null( $result->shipline4 ) . "<br/>" . $this->not_null( $result->citystzip );
                $shipping_info      = str_replace( "<br/><br/>", "<br/>", $shipping_info );
                $data_array[]       = array(
                    "Customer #"    => $result->cust,
                    "PO #"          => $result->pono,
                    "PO Date"       => $month !== "" && $month !== null ? implode( "/", array( $month, $day, $year ) ) : "",
                    "Shipping Info" => $shipping_info,
                    "ShippingInfoLineReturned" => $shipping_info,
                    "OrderLink"     => $order_link,
                    "Month"         => $month,
                    "Day"           => $day,
                    "Year"          => $year,
                );
                $orders_processed[] = $po_number;
            }
            $status = $result->s === "O" ? "Open" : "Shipped";
            if ( $status === "Shipped" ) {
                $tracking = "<a class='order-tracking-link' data-blno='{$result->bfwdslshlnum}' data-carrier='{$result->scac}' data-orderno='{$result->ordernum}' data-prono='{$result->prono}'>Tracking<div class='modal' style='display: none;'><tr>
                    <td valign='top' align='left'>
                    <br><b>Carrier Name: </b>{$result->scac}
                    <br><b>BL#: </b>{$result->bfwdslshlnum}
                    <br><b>Pro Number: </b>{$result->prono}                 
                    </td>
                    </tr></div></a>";
            } else {
                $tracking = " ";
            }
            $invoice = ""; //"<a class='order-invoice-link' data-orderno='{$result->ordernum}'>Invoice</a>";
            $item_id = NULL;
            $qty = (ltrim( trim( $result->qty ), "0" ) === "" ? "0" : ltrim( trim( $result->qty ), "0" ));
            if ( array_key_exists( $result->catalogno, $catalogs ) ) {
                $item_id = $catalogs[ $result->catalogno ];
            } else {
                $item_id                        = $result->item_id;
                $catalogs[ $result->catalogno ] = $item_id;
            }
            if ( $item_id === NULL ) {
                $child = NULL;
            } else {
                $child = $this->master->products->get_the_lowest_category( $item_id );
            }
            if ( ! is_wp_error( $child ) && $child !== null && $item_id !== NULL ) {
                $term         = get_term( $child->parent, "product_cat" );
                $product_link = get_term_link( $term );
            } else {
                $product_link = "#";
            }
            $ship_date    = "";
            $packing_slip = "";
            if ( $result->shpdate !== "NULL" ) {
                $month = substr( $result->shpdate, 0, 2 );
                $day   = substr( $result->shpdate, 3, 2 );
                $year  = substr( $result->shpdate, 6, 4 );
                if ( strlen( $year ) === 2 ) {
                    $year = "20" . $year;
                }
                $ship_date    = $month !== "" && $month !== null ? implode( "/", array( $month, $day, $year ) ) : "";
                //$packing_slip = home_url( "/wp-content/uploads/packing-slips/" ) . "POPL_" . $result->cust . "_" . $year . $month . $day . ".pdf";

                if ( file_exists( __DIR__ . "/../../../../uploads/packing-slips/" . "POPL_" . $result->cust . "_" . $result->ordernum . ".pdf" ) ) {
                    $packing_slip = home_url( "/wp-content/uploads/packing-slips/" ) . "POPL_" . $result->cust . "_" . $result->ordernum . ".pdf";
                }elseif(file_exists( __DIR__ . "/../../../../uploads/packing-slips/" . "POPL_" . $result->cust . "_" . $year . $month . $day . ".pdf")){ 
                    $packing_slip = home_url( "/wp-content/uploads/packing-slips/" ) . "POPL_" . $result->cust . "_" . $year . $month . $day . ".pdf";
                }else{
                    $packing_slip = "";
                }
            }

            $second_array[] = array(
                "Order #"              => $result->ordernum,
                "Line Item"            => ltrim( $result->item, "0" ),
                "Status"               => $status,
                "Catalog #"            => $result->catalogno,
                'catalogNumberCleaned' => $result->cleaned_catalog_no,
                "shouldRenderLink"     => count($wpdb->get_col( "SELECT catalogno FROM mp_item WHERE catalogno = '" . $result->catalogno . "'" ) ) > 0 ? "true" : "false",
                "Cust Part"            => $result->stockno,
                "Price"                => ltrim( $result->price, "0" ),
                "Qty"                  => $qty,
                "Orig Ack Date"        => $result->ackdate,
                "Current Avail Date"   => $result->avldate,
                "Date Shipped"         => $ship_date,
                "Tracking"             => $tracking,
                "Packing Slip"         => $packing_slip,
                "Invoice"              => $invoice,
                "ProductLink"          => $product_link
            );
        }
        $data_array[] = $second_array;
        echo json_encode( $data_array );
        die();
    }

    public function orders_packaging_ajax()
    {
        // get post data
        $account_number = isset( $_POST[ 'account' ] ) ? $_POST[ 'account' ] : '';
        $po_number      = isset( $_POST[ 'po' ] ) ? $_POST[ 'po' ] : '';
        $catalog_number = isset( $_POST[ 'catalog' ] ) ? $_POST[ 'catalog' ] : '';

        // sanitize
        $account_number = esc_sql( $account_number );
        $po_number      = esc_sql( $po_number );
        $catalog_number = esc_sql( $catalog_number );

        $accountCheckResults = $this->check_access_and_filter_accounts( $account_number );
        if ( ! $account_number || ! $accountCheckResults[ 'has_access' ] ) {
            echo json_encode( 'Permission Denied.' );
            wp_die();
        }

        echo json_encode( $this->query_order_packaging_information(
            $account_number,
            $po_number,
            $catalog_number
        ) );
        die();
    }

    public function quotes_account_ajax()
    {
        global $wpdb;

        // get post data
        $account_numbers = isset( $_POST[ 'accounts' ] ) ? ( array ) $_POST[ 'accounts' ] : array();
        $catalog_numbers = isset( $_POST[ 'catalogs' ] ) ? ( array ) $_POST[ 'catalogs' ] : array();
        $quote_numbers   = isset( $_POST[ 'quotes' ] ) ? ( array ) $_POST[ 'quotes' ] : array();
        $date_from       = isset( $_POST[ 'from' ] ) ? $_POST[ 'from' ] : '';
        $date_to         = isset( $_POST[ 'to' ] ) ? $_POST[ 'to' ] : '';

        // sanitize
        $account_numbers = array_map( 'esc_sql', $account_numbers );
        $catalog_numbers = array_map( 'esc_sql', array_map( array( $this->master->functions, "clean_data_strict" ), $catalog_numbers ) );
        $quote_numbers   = array_map( 'esc_sql', $quote_numbers );
        $date_from       = esc_sql( $date_from );
        $date_to         = esc_sql( $date_to );

        // parse dates
        $date_from = str_replace( '-', '', $date_from );
        $date_to   = str_replace( '-', '', $date_to );

        // check for account access permissions
        $account_check_results = $this->check_access_and_filter_accounts(
            implode( ',', $account_numbers )
        );
        if ( ! $account_check_results[ 'has_access' ] ) {
            echo json_encode( 'Permission Denied.' );
            wp_die();
        }

        // use set of permitted accounts for query
        $account_numbers = array_map(
            'esc_sql',
            explode( ',', $account_check_results[ 'allowed_accounts_csv' ] )
        );
        if ( ! count( $account_numbers ) ) {
            echo json_encode( array() );
            wp_die();
        }

        // create catalog number where clauses
        $wheres_catalog_number = array_map(
            function ( $catalog_number ) {
                return "q.cleaned_catalog_no LIKE '$catalog_number%'";
            },
            $catalog_numbers
        );

        // create quote number where clauses
        $wheres_quote_number = array_map(
            function ( $quote_number ) {
                return "q.quoteno LIKE '$quote_number%'";
            },
            $quote_numbers
        );

        $wheres_catalog_number_string = implode( ' OR ', $wheres_catalog_number );
        $wheres_quote_number_string   = implode( ' OR ', $wheres_quote_number );

        // build where clauses
        $wheres = array();
        if ( $wheres_catalog_number_string )
            array_push( $wheres, "($wheres_catalog_number_string)" );
        if ( $wheres_quote_number_string )
            array_push( $wheres, "($wheres_quote_number_string)" );
        if ( $date_from )
            array_push( $wheres, "q.expireby >= '$date_from'" );
        if ( $date_to )
            array_push( $wheres, "q.expireby <= '$date_to'" );

        // group bys
        $group_bys = array();
        array_push( $group_bys, 'q.quoteno' );
        array_push( $group_bys, 'q.enduser' );
        array_push( $group_bys, 'q.cleaned_catalog_no' );
        $group_by_string = rtrim( implode( ',', $group_bys ), ',' );

        $sql =
            "SELECT
                q.enduser,
                q.entdate,
                q.expireby,
                q.acceptby,
                q.quoteno,
                q.catalogno,
                q.cleaned_catalog_no,
                q.cust,
                q.price,
                q.customername,
                q.qtqty,
                q.ordqty
            FROM
                mp_quote AS q
            WHERE "
            . "q.cust IN (" . "'" . implode( "', '", $account_numbers ) . "'" . " ) "
            . ( count( $wheres ) ? " AND " . implode( ' AND ', $wheres ) : "" )
            . " GROUP BY "
            . $group_by_string
            . "
            ORDER BY q.quoteno";
            
        $results = $wpdb->get_results( $sql );

        $data_array = array(
            'quotes' => array()
        );

        $unique_catalog_numbers = array();
        foreach ( $results as $result ) {

            $uniqer = $result->quoteno . "|" . $result->catalogno;
            if ( count( $catalog_numbers ) < 1 ) {
                $uniqer = $result->quoteno;
            }
            $expire_date = $this->parse_date_yyyymmdd( $result->expireby );

            $data_array[ 'quotes' ][ $uniqer ] = array(
                    'accountName'    => $result->customername,
                    'accountNumber'  => $result->cust,
                    'catalogNumber'  => $result->catalogno,
                    'endUser'        => $result->enduser,
                    'expirationDate' => $expire_date->format( 'm/d/Y' ),
                    'price'          => $result->price,
                    'quoteNumber'    => $result->quoteno,
                    'quoteQuantity'  => intval( ltrim( $result->qtqty, '0' ) ),
                    'orderQuantity'  => intval( ltrim( $result->ordqty, '0' ) )
                );

            if ( ! array_key_exists( $result->cleaned_catalog_no, $unique_catalog_numbers ) )
                $unique_catalog_numbers[ $result->cleaned_catalog_no ] = true;
        }

        $data_array[ 'catalogs' ] =
            array_values(
                $this->query_packaging_information_for_catalog_numbers_exact(
                    array_keys( $unique_catalog_numbers )
                )
            );
        if ( $data_array[ 'catalogs' ] === null ) {
            $data_array[ 'catalogs' ] = array();   
        }
        $data_array[ 'quotes' ] = array_values(  $data_array[ 'quotes' ] );
        echo json_encode( $data_array );
        die();
    }

    public function quotes_id_ajax()
    {
        global $wpdb;

        // get post data
        $account_number = isset( $_POST[ 'account' ] ) ? $_POST[ 'account' ] : '';
        $catalog_number = isset( $_POST[ 'catalog' ] ) ? $_POST[ 'catalog' ] : '';
        $quote_number   = isset( $_POST[ 'quote' ] ) ? $_POST[ 'quote' ] : '';

        // sanitize
        $account_number = esc_sql( $account_number );
        $catalog_number = esc_sql( $catalog_number );
        $quote_number   = esc_sql( $quote_number );

        $account_check_results = $this->check_access_and_filter_accounts( $account_number );
        if ( ! $account_check_results[ 'has_access' ] ) {
            echo json_encode( 'Permission Denied.' );
            wp_die();
        }

        $sql =
            "SELECT
                q.*,
                pm.post_id as item_id
            FROM
                mp_quote as q
            LEFT JOIN
                wp_postmeta AS pm ON
                    pm.meta_key = 'CatalogNumber'
                    AND pm.meta_value = q.catalogno
            WHERE
                q.quoteno = '$quote_number'
                " . ( $catalog_number ? " AND q.catalogno = '$catalog_number' " : '' ) . "
            ORDER BY
                q.item";

        $results          = $wpdb->get_results( $sql );
        $data_array       = array();
        $catalogs         = array();
        $quotes_processed = array();
        foreach ( $results as $result ) {
            $quote_number = $result->quoteno;
            if ( ! in_array( $quote_number, $quotes_processed ) ) {
                $month       = substr( $result->entdate, 4, 2 );
                $day         = substr( $result->entdate, 6, 2 );
                $year        = substr( $result->entdate, 0, 4 );
                $quote_link  = home_url( "/mpservicenet/quotes/quote-detail/?quoteNo=" . $result->quoteno . "&customerNumbers=" . $result->cust );
                $enter_date  = $month . "/" . $day . "/" . $year;
                $accept_date = substr( $result->acceptby, 4, 2 ) . "/" . substr( $result->acceptby, 6, 2 ) . "/" . substr( $result->acceptby, 0, 4 );
                $expire_date = substr( $result->expireby, 4, 2 ) . "/" . substr( $result->expireby, 6, 2 ) . "/" . substr( $result->expireby, 0, 4 );

                $data_array[ $quote_number ] = array(
                    "customerNumber" => $result->cust,
                    "quoteNumber"    => $quote_number,
                    "endUser"        => rtrim( $result->enduser ),
                    "issuedDate"     => $enter_date,
                    "acceptanceDate" => $accept_date,
                    "firmPriceDate"  => $expire_date,
                    "quoteLink"      => $quote_link,
                    "month"          => $month,
                    "day"            => $day,
                    "year"           => $year
                );

                $quotes_processed[] = $quote_number;
            }

            $item_id   = NULL;
            $order_qty = ltrim( $result->ordqty, "0" ) === "" ? "0" : ltrim( $result->ordqty, "0" );
            $qt_qty    = ltrim( $result->qtqty, "0" ) === "" ? "0" : ltrim( $result->qtqty, "0" );
            $mo_qty    = ltrim( $result->moqty, "0" ) === "" ? "0" : ltrim( $result->moqty, "0" );
            $std_pak   = ltrim( $result->stdpak, "0" ) === "" ? "0" : ltrim( $result->stdpak, "0" );

            if ( array_key_exists( $result->catalogno, $catalogs ) ) {
                $item_id = $catalogs[ $result->catalogno ];
            } else {
                $item_id                        = $result->item_id;
                $catalogs[ $result->catalogno ] = $item_id;
            }

            if ( $item_id === NULL ) {
                $child = NULL;
            } else {
                $child = $this->master->products->get_the_lowest_category( $item_id );
            }

            if ( ! is_wp_error( $child ) && $child !== null && $item_id !== NULL ) {
                $term         = get_term( $child->parent, "product_cat" );
                $product_link = get_term_link( $term );
            } else {
                $product_link = '';
            }

            if ( ! is_array( $data_array[ $result->quoteno ][ "items" ] ) ) {
                $data_array[ $result->quoteno ][ "items" ] = array();
            }
            $stocknum =  rtrim( $result->stocknumber );
            $cust_part = $stocknum ? $stocknum : $result->upccode;

            $data_array[ $result->quoteno ][ "items" ][] = array(
                "itemNumber"   => rtrim( ltrim( $result->item, "0" ) ),
                "qtyQuoted"    => $qt_qty,
                "qtyPurchased" => $order_qty,
                "catalogNum"   => rtrim( $result->catalogno ),
                "custPart"     => $cust_part,
                "upcCode"      => $result->upccode,
                "description"  => rtrim( $result->description ),
                "ship"         => $this->get_plant_name( $result->l ),
                "price"        => ltrim( $result->price, "0" ),
                "extLeadTime"  => rtrim( $result->leadtime ),
                "minOrderQty"  => $mo_qty,
                "packQty"      => $std_pak,
                "productLink"  => $product_link,
                "comments"     => array(
                    "item"        => $result->item,
                    "quoteNumber" => $result->quoteno,
                )
            );
        }
        echo json_encode( $data_array );
        die();
    }

    // =================================================================================================================
    // MISC
    // =================================================================================================================

    public function check_access_and_filter_accounts( $requested_accounts_csv )
    {
        $allowed_account_post_ids = $this->post_account_ids( "user_" . wp_get_current_user()->ID, true );

        $allowed_account_numbers = array_map( function ( $e ) {
            return get_field( 'cust', $e );
        }, $allowed_account_post_ids );

        $requested_account_numbers = explode( ',', $requested_accounts_csv );

        $allowed_request_accounts = array_filter(
            $requested_account_numbers,
            function ( $e ) use ( $allowed_account_numbers ) {
                return in_array( $e, $allowed_account_numbers );
            }
        );

        return [
            'has_access'           => count( $allowed_request_accounts ) > 0,
            'allowed_accounts_csv' => implode( ',', $allowed_request_accounts )
        ];
    }

    public function can_see_inventory()
    {
        return get_field( "hide_inventory", "user_" . wp_get_current_user()->ID, true ) !== true;
    }

    public function get_sidebar_data_links( $catalogno = '' )
    {
        global $wpdb;
        $post_ids = $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM wp_postmeta WHERE meta_key = 'CatalogNumberCleaned' and meta_value='%s';", $catalogno ) );
        $meta     = "";
        if ( count( $post_ids ) > 0 ) {
            $post  = get_post( $post_ids[ 0 ] );
            $meta  = get_post_meta( $post->ID, "PartPDF", true );
            $child = $this->master->products->get_the_lowest_category( intval( $post->ID ) );
            if ( $child !== NULL ) {
                $term         = get_term( $child->parent, "product_cat" );
                $product_link = get_term_link( $term );
            } else {
                $product_link = "#";
            }
        } else {
            $product_link = "#";
        }

        $data_array = array(
            'product_link'   => $product_link,
            'catalog_number' => $catalogno
        );

        if ( strlen( $meta ) > 0 ) {
            $data_array[ 'drawing_link' ] = home_url( $meta );
        }

        return $data_array;
    }

    /**
     * Includes a wpnonce for the logout page to prevent logout confirmation.
     * Relies on the nav menu item having a label of 'Logout'.
     * @param $items
     * @return mixed
     */
    public function change_logout_menu_item( $items )
    {
        foreach ( $items as $item ) {
            if ( $item->title === 'Logout' ) {
                $item->url = $item->url . '&_wpnonce=' . wp_create_nonce( 'log-out' );
            }
        }
        return $items;
    }
}
