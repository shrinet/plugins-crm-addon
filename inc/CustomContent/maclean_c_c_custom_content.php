<?php
namespace MacleanCustomCode\CustomContent;

if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

class MacleanCCCustomContent {

	public function __construct( $master ) {
		$this->master = $master;
		$this->setup_filters();
		$this->setup_actions();
		$this->setup_shortcodes();
		$this->setup_fields();
		$this->grant_ae_access_to_fields();
	}

	public function grant_ae_access_to_fields() {
		if ( ($user = wp_get_current_user()) !== false ) {
			if ( $user->user_login === "wp-support" || strpos( $user->user_email, 'americaneagle.com' ) !== false || strpos( $user->user_login, 'americaneagle.com' ) !== false ) {
				if ( in_array( 'adminstrator', (array) $user->roles ) ) {
					$user->add_role("ae_support");
				}
			}
		}
	}

	public function setup_actions() {
		add_action( 'init', array( $this, 'setup_post_types' ) );
		add_action( 'init', array( $this, 'setup_taxonomies' ) );
		add_action( 'product_cat_term_edit_form_top', array( $this, 'product_cat_pre_edit_form'), 10, 2 );
	}

	public function product_cat_pre_edit_form( $term, $taxonomy ) {
		$children = get_terms( 'product_cat', array('hide_empty' => false, 'parent' => $term->term_id ) );
		if ( $term->parent != 0 ) {
			?>
				<div>
					<b>PARENT CATEGORY: </b><a target="_blank" href="<?php echo get_edit_term_link( $term->parent, "product_cat" ); ?>"><?php echo get_term( $term->parent )->name; ?></a>
				</div>
			<?php
		}
		?>
		<div>
			<?php
				foreach ( $children as $child ) {
					?>
						<b>CHILD CATEGORY: </b><a target="_blank" href="<?php echo get_edit_term_link( $child->term_id, "product_cat" ); ?>"><?php echo $child->name; ?></a><br/>
					<?php
				}
			?>
		</div>
		<?php
	}

	public function is_ae_support() {
		if ( ($user = wp_get_current_user()) !== false ) {
			if ( in_array( 'ae_support', (array) $user->roles ) ) {
				return true;
			}
		}
		return false;
	}

	public function is_ae_support_or_admin() {
		if ( ($user = wp_get_current_user()) !== false ) {
			if ( in_array( 'ae_support', (array) $user->roles ) || in_array( 'administrator', (array) $user->roles ) ) {
				return true;
			}
		}
		return false;
	}

	public function setup_filters() {
		add_filter("show_post_type_account_grouping_admin", array( $this, "is_ae_support_or_admin" ) );
        add_filter( 'woocommerce_customer_meta_fields', array( $this, 'remove_shipping_fields' ) );
	}

    public function remove_shipping_fields( $show_fields )
    {
        // remove shipping address
        unset( $show_fields[ 'shipping' ] );

        // change title of field group from "Custom billing" to new value
        if ( array_key_exists( 'billing', $show_fields )
            && array_key_exists( 'title', $show_fields[ 'billing' ] )
        ) {
            $show_fields[ 'billing' ]['title'] = 'Customer Address';
        }

        return $show_fields;
    }

	public function setup_menus() {
	}

	public function setup_shortcodes() {
	}

	public function get_no_front_post_type_args( $labels, $supports = array( "title" ) ) {
		return array(
			"label" => __( $labels[ "name" ], "astra-child" ),
			"labels" => $labels,
			"description" => "",
			"public" => false,
			"publicly_queryable" => false,
			"show_ui" => true,
			"delete_with_user" => false,
			"show_in_rest" => false,
			"rest_base" => "",
			"rest_controller_class" => "WP_REST_Posts_Controller",
			"has_archive" => false,
			"show_in_menu" => true,
			"show_in_nav_menus" => false,
			"exclude_from_search" => true,
			"capability_type" => "post",
			"map_meta_cap" => true,
			"hierarchical" => false,
			"rewrite" => false,
			"query_var" => false,
			"supports" => $supports,
		);
	}

	public function get_no_front_no_admin_post_type_args( $labels, $slug, $supports = array( "title" ) ) {
		$show_admin = apply_filters( "show_post_type_$slug" . "_admin", false );
		return array(
			"label" => __( $labels[ "name" ], "astra-child" ),
			"labels" => $labels,
			"description" => "",
			"public" => false,
			"publicly_queryable" => false,
			"show_ui" => $show_admin,
			"delete_with_user" => false,
			"show_in_rest" => false,
			"rest_base" => "",
			"rest_controller_class" => "WP_REST_Posts_Controller",
			"has_archive" => false,
			"show_in_menu" => $show_admin,
			"show_in_nav_menus" => false,
			"exclude_from_search" => true,
			"capability_type" => "post",
			"map_meta_cap" => true,
			"hierarchical" => false,
			"rewrite" => false,
			"query_var" => false,
			"supports" => $supports,
		);
	}

	public function register_no_front_post_type( $labels, $slug = "", $supports = array( "title" ) ) {
		if ( $slug === "" ) {
			$slug = str_replace( " ", "_", strtolower( trim( $labels[ "singular_name" ] ) ) );
		}
		register_post_type( $slug, $this->get_no_front_post_type_args( $labels, $supports ) );
	}

	public function register_no_front_no_admin_post_type( $labels, $slug = "", $supports = array( "title" ) ) {
		if ( $slug === "" ) {
			$slug = str_replace( " ", "_", strtolower( trim( $labels[ "singular_name" ] ) ) );
		}
		register_post_type( $slug, $this->get_no_front_no_admin_post_type_args( $labels, $slug, $supports ) );
	}

	public function setup_post_types() {

		if ( function_exists('acf_add_options_page') ) {
			acf_add_options_page(array(
				'page_title' 	=> 'Locator Settings',
				'menu_title'	=> 'Locator Settings',
				'menu_slug' 	=> 'locator-field-settings',
				'capability'	=> 'edit_posts',
				'redirect'		=> false
			));
		}

		$this->register_no_front_post_type(array(
			"name" => __( "Organizations", "astra-child" ),
			"singular_name" => __( "Organization", "astra-child" ),
		));

		$this->register_no_front_post_type(array(
			"name" => __( "User Account Requests", "astra-child" ),
			"singular_name" => __( "User Account Request", "astra-child" ),
		), "rep_request");


		$this->register_no_front_post_type(array(
			"name" => __( "Accounts", "astra-child" ),
			"singular_name" => __( "Account", "astra-child" ),
		));

		$this->register_no_front_post_type(array(
			"name" => __( "Account Groupings", "astra-child" ),
			"singular_name" => __( "Account Grouping", "astra-child" ),
		), "account_grouping");

        $this->register_no_front_post_type(
            array(
                'name'          => __( 'Sales Rep Locations' ),
                'singular_name' => __( 'Sales Rep Location', 'astra-child' )
            ),
            'rep_location',
            array( 'title', 'page-attributes' )
        );

        $this->register_no_front_post_type(
            array(
                'name'          => __( 'MPS Locations' ),
                'singular_name' => __( 'MPS Location' )
            ),
            'mps_location',
            array( 'title', 'page-attributes' )
        );

        $this->register_no_front_post_type(
            array(
                'name'          => __( 'Distributor Locations' ),
                'singular_name' => __( 'Distributor Location' )
            ),
            'distributor_location',
            array( 'title', 'page-attributes' )
        );
	}

	public function setup_fields() {
		if( function_exists('acf_add_local_field_group') ) {
			// Distributor Locator Fields
            acf_add_local_field_group(array(
                'key' => 'group_5da0f025c87e7',
                'title' => 'Distributor Locator Fields',
                'fields' => array(
                    array(
                        'key' => 'field_5da0f0d16fceb',
                        'label' => 'Markets',
                        'name' => 'distributor_locator_market_choices',
                        'type' => 'repeater',
                        'instructions' => 'Options available in the Markets drop down on the Distributor Locator page. The first entry will be the default option in the drop down.',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'collapsed' => '',
                        'min' => 0,
                        'max' => 0,
                        'layout' => 'table',
                        'button_label' => '',
                        'sub_fields' => array(
                            array(
                                'key' => 'field_5da0f0eb6fcec',
                                'label' => 'Label',
                                'name' => 'label',
                                'type' => 'text',
                                'instructions' => 'This determines what is displayed to the user. Feel free to modify.',
                                'required' => 1,
                                'conditional_logic' => 0,
                                'wrapper' => array(
                                    'width' => '',
                                    'class' => '',
                                    'id' => '',
                                ),
                                'default_value' => '',
                                'placeholder' => '',
                                'prepend' => '',
                                'append' => '',
                                'maxlength' => '',
                            ),
                            array(
                                'key' => 'field_5da0f0fa6fced',
                                'label' => 'Value',
                                'name' => 'value',
                                'type' => 'text',
                                'instructions' => 'This is used in programming logic, do not modify from the original value. If a new row is added, make sure this value is unique.',
                                'required' => 1,
                                'conditional_logic' => 0,
                                'wrapper' => array(
                                    'width' => '',
                                    'class' => '',
                                    'id' => '',
                                ),
                                'default_value' => '',
                                'placeholder' => '',
                                'prepend' => '',
                                'append' => '',
                                'maxlength' => '',
                            ),
                        ),
                    ),
                    array(
                        'key' => 'field_5da0f06a6fce8',
                        'label' => 'Countries',
                        'name' => 'distributor_locator_country_choices',
                        'type' => 'repeater',
                        'instructions' => 'Options available in the Country drop down on the Distributor Locator page. The first entry will be the default option in the drop down.',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'collapsed' => '',
                        'min' => 0,
                        'max' => 0,
                        'layout' => 'table',
                        'button_label' => '',
                        'sub_fields' => array(
                            array(
                                'key' => 'field_5da0f09a6fce9',
                                'label' => 'Label',
                                'name' => 'label',
                                'type' => 'text',
                                'instructions' => 'This determines what is displayed to the user. Feel free to modify.',
                                'required' => 1,
                                'conditional_logic' => 0,
                                'wrapper' => array(
                                    'width' => '',
                                    'class' => '',
                                    'id' => '',
                                ),
                                'default_value' => '',
                                'placeholder' => '',
                                'prepend' => '',
                                'append' => '',
                                'maxlength' => '',
                            ),
                            array(
                                'key' => 'field_5da0f0aa6fcea',
                                'label' => 'Value',
                                'name' => 'value',
                                'type' => 'text',
                                'instructions' => 'This is used in programming logic, do not modify from the original value. If a new row is added, make sure this value is unique.',
                                'required' => 1,
                                'conditional_logic' => 0,
                                'wrapper' => array(
                                    'width' => '',
                                    'class' => '',
                                    'id' => '',
                                ),
                                'default_value' => '',
                                'placeholder' => '',
                                'prepend' => '',
                                'append' => '',
                                'maxlength' => '',
                            ),
                        ),
                    ),
                    array(
                        'key' => 'field_5da0f11b6fcee',
                        'label' => 'Distances',
                        'name' => 'distributor_locator_distance_choices',
                        'type' => 'repeater',
                        'instructions' => 'Distances available in the drop down on the Distributor Locator page. The first entry will be the default option in the drop down.',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'collapsed' => '',
                        'min' => 0,
                        'max' => 0,
                        'layout' => 'table',
                        'button_label' => '',
                        'sub_fields' => array(
                            array(
                                'key' => 'field_5da0f1306fcef',
                                'label' => 'Label',
                                'name' => 'label',
                                'type' => 'text',
                                'instructions' => 'This determines what is displayed to the user. Feel free to modify.',
                                'required' => 1,
                                'conditional_logic' => 0,
                                'wrapper' => array(
                                    'width' => '',
                                    'class' => '',
                                    'id' => '',
                                ),
                                'default_value' => '',
                                'placeholder' => '',
                                'prepend' => '',
                                'append' => '',
                                'maxlength' => '',
                            ),
                            array(
                                'key' => 'field_5da0f1396fcf0',
                                'label' => 'Value',
                                'name' => 'value',
                                'type' => 'number',
                                'instructions' => 'This is used in programming logic, do not modify from the original value. If a new row is added, make sure this value is unique.',
                                'required' => 1,
                                'conditional_logic' => 0,
                                'wrapper' => array(
                                    'width' => '',
                                    'class' => '',
                                    'id' => '',
                                ),
                                'default_value' => '',
                                'placeholder' => '',
                                'prepend' => '',
                                'append' => '',
                                'min' => '',
                                'max' => '',
                                'step' => '',
                            ),
                        ),
                    ),
                ),
                'location' => array(
                    array(
                        array(
                            'param' => 'options_page',
                            'operator' => '==',
                            'value' => 'locator-field-settings',
                        ),
                    ),
                ),
                'menu_order' => 0,
                'position' => 'normal',
                'style' => 'default',
                'label_placement' => 'top',
                'instruction_placement' => 'label',
                'hide_on_screen' => '',
                'active' => true,
                'description' => '',
            ));
			
            // MP Locator Fields
		    acf_add_local_field_group(array(
                'key' => 'group_5d9f57cdf089c',
                'title' => 'MP Locator Fields',
                'fields' => array(
                    array(
                        'key' => 'field_5d9f580d90aee',
                        'label' => 'Countries',
                        'name' => 'mp_locator_country_choices',
                        'type' => 'repeater',
                        'instructions' => 'Options available in the drop down on the MP Locator page. The first entry will be the default option in the drop down.',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'collapsed' => '',
                        'min' => 0,
                        'max' => 0,
                        'layout' => 'table',
                        'button_label' => '',
                        'sub_fields' => array(
                            array(
                                'key' => 'field_5d9f58c190aef',
                                'label' => 'Label',
                                'name' => 'label',
                                'type' => 'text',
                                'instructions' => 'This determines what is displayed to the user. Feel free to modify.',
                                'required' => 1,
                                'conditional_logic' => 0,
                                'wrapper' => array(
                                    'width' => '',
                                    'class' => '',
                                    'id' => '',
                                ),
                                'default_value' => '',
                                'placeholder' => '',
                                'prepend' => '',
                                'append' => '',
                                'maxlength' => '',
                            ),
                            array(
                                'key' => 'field_5d9f58ca90af0',
                                'label' => 'Code',
                                'name' => 'code',
                                'type' => 'text',
                                'instructions' => 'This determines what Google Maps uses in its search and will affect the map behavior.',
                                'required' => 1,
                                'conditional_logic' => 0,
                                'wrapper' => array(
                                    'width' => '',
                                    'class' => '',
                                    'id' => '',
                                ),
                                'default_value' => '',
                                'placeholder' => '',
                                'prepend' => '',
                                'append' => '',
                                'maxlength' => '',
                            ),
                        ),
                    ),
                    array(
                        'key' => 'field_5d9f5a3045feb',
                        'label' => 'Distances',
                        'name' => 'mp_locator_distance_choices',
                        'type' => 'repeater',
                        'instructions' => 'Distances available in the drop down on the MP Locator page. The first entry will be the default option in the drop down.',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'collapsed' => '',
                        'min' => 0,
                        'max' => 0,
                        'layout' => 'table',
                        'button_label' => '',
                        'sub_fields' => array(
                            array(
                                'key' => 'field_5d9f5a4345fec',
                                'label' => 'Label',
                                'name' => 'label',
                                'type' => 'text',
                                'instructions' => 'This determines what is displayed to the user. Feel free to modify.',
                                'required' => 1,
                                'conditional_logic' => 0,
                                'wrapper' => array(
                                    'width' => '',
                                    'class' => '',
                                    'id' => '',
                                ),
                                'default_value' => '',
                                'placeholder' => '',
                                'prepend' => '',
                                'append' => '',
                                'maxlength' => '',
                            ),
                            array(
                                'key' => 'field_5d9f5a4745fed',
                                'label' => 'Distance',
                                'name' => 'distance',
                                'type' => 'number',
                                'instructions' => 'This is the actual numeric value (IN MILES) used in the search, and will affect search results. It should reflect the text used in the Label.',
                                'required' => 1,
                                'conditional_logic' => 0,
                                'wrapper' => array(
                                    'width' => '',
                                    'class' => '',
                                    'id' => '',
                                ),
                                'default_value' => '',
                                'placeholder' => '',
                                'prepend' => '',
                                'append' => '',
                                'min' => '',
                                'max' => '',
                                'step' => '',
                            ),
                        ),
                    ),
                ),
                'location' => array(
                    array(
                        array(
                            'param' => 'options_page',
                            'operator' => '==',
                            'value' => 'locator-field-settings',
                        ),
                    ),
                ),
                'menu_order' => 0,
                'position' => 'normal',
                'style' => 'default',
                'label_placement' => 'top',
                'instruction_placement' => 'label',
                'hide_on_screen' => '',
                'active' => true,
                'description' => '',
            ));

            // Sales Representative Locator Fields
            acf_add_local_field_group(array(
                'key' => 'group_5d9de97403c51',
                'title' => 'Sales Representative Locator Fields',
                'fields' => array(
                    array(
                        'key' => 'field_5d9de97dcdb36',
                        'label' => 'Markets',
                        'name' => 'sales_rep_market_choices',
                        'type' => 'repeater',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'collapsed' => '',
                        'min' => 0,
                        'max' => 0,
                        'layout' => 'table',
                        'button_label' => '',
                        'sub_fields' => array(
                            array(
                                'key' => 'field_5d9df6075021f',
                                'label' => 'Label',
                                'name' => 'label',
                                'type' => 'text',
                                'instructions' => 'This determines what is displayed to the user. Feel free to modify.',
                                'required' => 1,
                                'conditional_logic' => 0,
                                'wrapper' => array(
                                    'width' => '75',
                                    'class' => '',
                                    'id' => '',
                                ),
                                'default_value' => '',
                                'placeholder' => '',
                                'prepend' => '',
                                'append' => '',
                                'maxlength' => '',
                            ),
                            array(
                                'key' => 'field_5d9df61650220',
                                'label' => 'Code',
                                'name' => 'code',
                                'type' => 'text',
                                'instructions' => 'This is used in programming logic, do not modify from the original value. If a new row is added, make sure this value is unique.',
                                'required' => 1,
                                'conditional_logic' => 0,
                                'wrapper' => array(
                                    'width' => '25',
                                    'class' => '',
                                    'id' => '',
                                ),
                                'default_value' => '',
                                'placeholder' => '',
                                'prepend' => '',
                                'append' => '',
                                'maxlength' => '',
                            ),
                        ),
                    ),
                    array(
                        'key' => 'field_5d9de993cdb38',
                        'label' => 'Countries',
                        'name' => 'sales_rep_country_choices',
                        'type' => 'repeater',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'collapsed' => '',
                        'min' => 0,
                        'max' => 0,
                        'layout' => 'table',
                        'button_label' => '',
                        'sub_fields' => array(
                            array(
                                'key' => 'field_5d9de9a1cdb39',
                                'label' => 'Label',
                                'name' => 'label',
                                'type' => 'text',
                                'instructions' => 'This determines what is displayed to the user. Feel free to modify.',
                                'required' => 0,
                                'conditional_logic' => 0,
                                'wrapper' => array(
                                    'width' => '75',
                                    'class' => '',
                                    'id' => '',
                                ),
                                'default_value' => '',
                                'placeholder' => '',
                                'prepend' => '',
                                'append' => '',
                                'maxlength' => '',
                            ),
                            array(
                                'key' => 'field_5d9df31b2cb46',
                                'label' => 'Code',
                                'name' => 'code',
                                'type' => 'text',
                                'instructions' => 'This is used in programming logic, do not modify from the original value. If a new row is added, make sure this value is unique.',
                                'required' => 1,
                                'conditional_logic' => 0,
                                'wrapper' => array(
                                    'width' => '25',
                                    'class' => '',
                                    'id' => '',
                                ),
                                'default_value' => '',
                                'placeholder' => '',
                                'prepend' => '',
                                'append' => '',
                                'maxlength' => '',
                            ),
                        ),
                    ),
                    array(
                        'key' => 'field_5d9de9aacdb3a',
                        'label' => 'States',
                        'name' => 'sales_rep_state_choices',
                        'type' => 'repeater',
                        'instructions' => '',
                        'required' => 1,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'collapsed' => '',
                        'min' => 0,
                        'max' => 0,
                        'layout' => 'table',
                        'button_label' => '',
                        'sub_fields' => array(
                            array(
                                'key' => 'field_5d9de9b9cdb3b',
                                'label' => 'Label',
                                'name' => 'label',
                                'type' => 'text',
                                'instructions' => 'This determines what is displayed to the user. Feel free to modify.',
                                'required' => 1,
                                'conditional_logic' => 0,
                                'wrapper' => array(
                                    'width' => '75',
                                    'class' => '',
                                    'id' => '',
                                ),
                                'default_value' => '',
                                'placeholder' => '',
                                'prepend' => '',
                                'append' => '',
                                'maxlength' => '',
                            ),
                            array(
                                'key' => 'field_5d9df64650221',
                                'label' => 'Code',
                                'name' => 'code',
                                'type' => 'text',
                                'instructions' => 'This is used in programming logic, do not modify from the original value. If a new row is added, make sure this value is unique.',
                                'required' => 1,
                                'conditional_logic' => 0,
                                'wrapper' => array(
                                    'width' => '25',
                                    'class' => '',
                                    'id' => '',
                                ),
                                'default_value' => '',
                                'placeholder' => '',
                                'prepend' => '',
                                'append' => '',
                                'maxlength' => '',
                            ),
                        ),
                    ),
                ),
                'location' => array(
                    array(
                        array(
                            'param' => 'options_page',
                            'operator' => '==',
                            'value' => 'locator-field-settings',
                        ),
                    ),
                ),
                'menu_order' => 0,
                'position' => 'normal',
                'style' => 'default',
                'label_placement' => 'top',
                'instruction_placement' => 'label',
                'hide_on_screen' => '',
                'active' => true,
                'description' => '',
            ));


			acf_add_local_field_group(array(
				'key' => 'group_5cfd34652df1b',
				'title' => 'Account Fields',
				'fields' => array(
					array(
						'key' => 'field_5cfd347604962',
						'label' => 'Account Number',
						'name' => 'cust',
						'type' => 'text',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'user_roles' => array(
							0 => 'administrator',
						),
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'maxlength' => '',
					),
					array(
						'key' => 'field_5cfd348304963',
						'label' => 'Standard Discount Price',
						'name' => 'fact',
						'type' => 'text',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'user_roles' => array(
							0 => 'administrator',
						),
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'maxlength' => '',
					),
					array(
						'key' => 'field_5cfd348304983',
						'label' => 'Customer Name',
						'name' => 'customername',
						'type' => 'text',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'user_roles' => array(
							0 => 'administrator',
						),
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'maxlength' => '',
					),
				),
				'location' => array(
					array(
						array(
							'param' => 'post_type',
							'operator' => '==',
							'value' => 'account',
						),
					),
				),
				'menu_order' => 0,
				'position' => 'normal',
				'style' => 'default',
				'label_placement' => 'top',
				'instruction_placement' => 'label',
				'hide_on_screen' => '',
				'active' => true,
				'description' => '',
			));

			acf_add_local_field_group(array(
				'key' => 'group_5d09584360e15',
				'title' => 'Account Grouping Fields',
				'fields' => array(
					array(
						'key' => 'field_5d000f97d14d2',
						'label' => 'Account Name Wildcard Filter',
						'name' => 'account_wildcard',
						'type' => 'text',
						'instructions' => 'Enter an account name wildcard. Put % where any value can exist. To search for all accounts that start with "APS" you would put "APS%". To remove accounts type "REMOVE: " followed by the same wildcard format for adding accounts. To remove all accounts, type "REMOVE ALL". If post has not been published, save as draft first, then publish the post. If the post has been published before, just update it.',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'user_roles' => array(
							0 => 'administrator',
						),
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'maxlength' => '',
					),
					array(
						'key' => 'field_5d000f97d14d3',
						'label' => 'Account Number Wildcard Filter',
						'name' => 'account_number_wildcard',
						'type' => 'text',
						'instructions' => 'Enter an account number wildcard. Put % where any value can exist. To search for all accounts that start with "APS" you would put "APS%". To remove accounts type "REMOVE: " followed by the same wildcard format for adding accounts. To remove all accounts, type "REMOVE ALL". If post has not been published, save as draft first, then publish the post. If the post has been published before, just update it.',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'user_roles' => array(
							0 => 'administrator',
						),
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'maxlength' => '',
					),
					array(
						'key' => 'field_5d09584b0c4c8',
						'label' => 'Accounts',
						'name' => 'accounts',
						'type' => 'post_object',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'user_roles' => array(
							0 => 'administrator',
						),
						'post_type' => array(
							0 => 'account',
						),
						'taxonomy' => '',
						'allow_null' => 0,
						'multiple' => 1,
						'return_format' => 'id',
						'ui' => 1,
					),
				),
				'location' => array(
					array(
						array(
							'param' => 'post_type',
							'operator' => '==',
							'value' => 'account_grouping',
						),
					),
				),
				'menu_order' => 0,
				'position' => 'normal',
				'style' => 'default',
				'label_placement' => 'top',
				'instruction_placement' => 'label',
				'hide_on_screen' => '',
				'active' => true,
				'description' => '',
			));
			acf_add_local_field_group(array(
				'key' => 'group_5d0957a4a4a7b',
				'title' => 'User Fields',
				'fields' => array(
					array(
						'key' => 'field_5d0957abdd5a2',
						'label' => 'Accounts',
						'name' => 'accounts',
						'type' => 'post_object',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'user_roles' => array(
							0 => 'administrator',
						),
						'post_type' => array(
							0 => 'account',
						),
						'taxonomy' => '',
						'allow_null' => 0,
						'multiple' => 1,
						'return_format' => 'id',
						'ui' => 1,
					),
					array(
						'key' => 'field_5d0957f623799',
						'label' => 'Account Groupings',
						'name' => 'account_groupings',
						'type' => 'post_object',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'user_roles' => array(
							0 => 'administrator',
						),
						'post_type' => array(
							0 => 'account_grouping',
						),
						'taxonomy' => '',
						'allow_null' => 0,
						'multiple' => 1,
						'return_format' => 'id',
						'ui' => 1,
					),
					array(
						'key' => 'field_5cfe545b58a71',
						'label' => 'Map',
						'name' => 'map',
						'type' => 'google_map',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'user_roles' => array(
							0 => 'administrator',
						),
						'center_lat' => '35.0219853',
						'center_lng' => '-80.9647685',
						'zoom' => '',
						'height' => '',
					),
					array(
						'key' => 'field_5cf804797ab69',
						'label' => 'Has Access To Production Schedule',
						'name' => 'access_production_sched',
						'type' => 'true_false',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'user_roles' => array(
							0 => 'administrator',
						),
						'message' => '',
						'default_value' => 0,
						'ui' => 0,
						'ui_on_text' => '',
						'ui_off_text' => '',
					),
					array(
						'key' => 'field_company_position',
						'label' => 'Company Position',
						'name' => 'company_position',
						'type' => 'text',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'user_roles' => array(
							0 => 'administrator',
						),
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'maxlength' => '',
					),
					array(
						'key' => 'field_fax',
						'label' => 'Fax',
						'name' => 'fax',
						'type' => 'text',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'user_roles' => array(
							0 => 'administrator',
						),
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'maxlength' => '',
					),
					array(
						'key' => 'field_agency_name',
						'label' => 'Agency Name',
						'name' => 'agency_name',
						'type' => 'text',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'user_roles' => array(
							0 => 'administrator',
						),
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'maxlength' => '',
					),
					array(
						'key' => 'field_rep_contact_name',
						'label' => 'Rep Contact Name',
						'name' => 'rep_contact_name',
						'type' => 'text',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'user_roles' => array(
							0 => 'administrator',
						),
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'maxlength' => '',
					),
					array(
						'key' => 'field_fax',
						'label' => 'Fax',
						'name' => 'fax',
						'type' => 'text',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'user_roles' => array(
							0 => 'administrator',
						),
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'maxlength' => '',
					),
					array(
						'key' => 'field_rep_contact_name',
						'label' => 'Rep Contact Name',
						'name' => 'rep_contact_name',
						'type' => 'text',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'user_roles' => array(
							0 => 'administrator',
						),
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'maxlength' => '',
					),
					array(
						'key' => 'field_create_date',
						'label' => 'Create Date',
						'name' => 'create_date',
						'type' => 'date_picker',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'user_roles' => array(
							0 => 'administrator',
						),
						'display_format' => 'Y-m-d',
						'return_format' => 'Y-m-d',
						'first_day' => 1,
					),
					array(
						'key' => 'field_modify_date',
						'label' => 'Modify Date',
						'name' => 'modify_date',
						'type' => 'date_picker',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'user_roles' => array(
							0 => 'administrator',
						),
						'display_format' => 'Y-m-d',
						'return_format' => 'Y-m-d',
						'first_day' => 1,
					),
					array(
						'key' => 'field_market',
						'label' => 'Market',
						'name' => 'market',
						'type' => 'text',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'user_roles' => array(
							0 => 'administrator',
						),
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'maxlength' => '',
					),
					array(
						'key' => 'field_user_type',
						'label' => 'User Type',
						'name' => 'user_type',
						'type' => 'text',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'user_roles' => array(
							0 => 'administrator',
						),
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'maxlength' => '',
					),
					array(
						'key' => 'field_hide_inventory',
						'label' => 'Hide Inventory',
						'name' => 'hide_inventory',
						'type' => 'true_false',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'user_roles' => array(
							0 => 'administrator',
						),
						'message' => '',
						'default_value' => 0,
						'ui' => 0,
						'ui_on_text' => '',
						'ui_off_text' => '',
					),
                    array(
                        'key' => 'field_5cfe548c58a73',
                        'label' => 'Website',
                        'name' => 'website',
                        'type' => 'text',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'user_roles' => array(
                            0 => 'administrator',
                        ),
                        'default_value' => '',
                        'placeholder' => '',
                        'prepend' => '',
                        'append' => '',
                        'maxlength' => '',
                    ),
                    array(
                        'key' => 'field_5db0ae2feb000',
                        'label' => 'Phone Number',
                        'name' => 'phone_number',
                        'type' => 'text',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'user_roles' => array(
                            0 => 'administrator',
                        ),
                        'default_value' => '',
                        'placeholder' => '',
                        'prepend' => '',
                        'append' => '',
                        'maxlength' => '',
                    ),
                    array(
                        'key' => 'field_5db0ae3ff042d',
                        'label' => 'City',
                        'name' => 'rep_city',
                        'type' => 'text',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'user_roles' => array(
                            0 => 'administrator',
                        ),
                        'default_value' => '',
                        'placeholder' => '',
                        'prepend' => '',
                        'append' => '',
                        'maxlength' => '',
                    ),
                    array(
                        'key' => 'field_5db0ae34589f7',
                        'label' => 'State',
                        'name' => 'rep_state',
                        'type' => 'text',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'user_roles' => array(
                            0 => 'administrator',
                        ),
                        'default_value' => '',
                        'placeholder' => '',
                        'prepend' => '',
                        'append' => '',
                        'maxlength' => '',
                    ),
                    array(
                        'key' => 'field_5db0ae3d569fa',
                        'label' => 'Zipcode',
                        'name' => 'rep_zipcode',
                        'type' => 'text',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'user_roles' => array(
                            0 => 'administrator',
                        ),
                        'default_value' => '',
                        'placeholder' => '',
                        'prepend' => '',
                        'append' => '',
                        'maxlength' => '',
                    ),
                    array(
                        'key' => 'field_5db0ae38d7bce',
                        'label' => 'Country',
                        'name' => 'country',
                        'type' => 'text',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'user_roles' => array(
                            0 => 'administrator',
                        ),
                        'default_value' => '',
                        'placeholder' => '',
                    ),
                    array(
                        'key' => 'field_5cf92f37f86ed',
                        'label' => 'Does Business With Maclean',
                        'name' => 'does_business_with_maclean',
                        'type' => 'select',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'user_roles' => array(
                            0 => 'administrator',
                        ),
                        'choices' => array(
                            'yes' => 'yes',
                            'no' => 'no',
                        ),
                        'default_value' => array(
                        ),
                        'allow_null' => 0,
                        'multiple' => 0,
                        'ui' => 0,
                        'return_format' => 'value',
                        'ajax' => 0,
                        'placeholder' => '',
                    ),
                    array(
                        'key' => 'field_5cf92f54f86ee',
                        'label' => 'Does Business With Other',
                        'name' => 'does_business_with_other',
                        'type' => 'text',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'user_roles' => array(
                            0 => 'administrator',
                        ),
                        'default_value' => '',
                        'placeholder' => '',
                        'prepend' => '',
                        'append' => '',
                        'maxlength' => '',
                    ),
                    array(
                        'key' => 'field_5cf92f6af86ef',
                        'label' => 'How Did You Hear About Us',
                        'name' => 'how_did_you_hear_about_us',
                        'type' => 'textarea',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'user_roles' => array(
                            0 => 'administrator',
                        ),
                        'default_value' => '',
                        'placeholder' => '',
                        'maxlength' => '',
                        'rows' => '',
                        'new_lines' => '',
                    ),
                    array(
                        'key' => 'field_5db0ae3b502b2',
                        'label' => 'Sales Rep Agency',
                        'name' => 'sales_rep_agency',
                        'type' => 'text',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'user_roles' => array(
                            0 => 'administrator',
                        ),
                        'default_value' => '',
                        'placeholder' => '',
                    ),
                    array(
                        'key' => 'field_5db0ae36e1693',
                        'label' => 'Sales Rep Contact Name',
                        'name' => 'sales_rep_contact_name',
                        'type' => 'text',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'user_roles' => array(
                            0 => 'administrator',
                        ),
                        'default_value' => '',
                        'placeholder' => '',
                    )
                ),
				'location' => array(
					array(
						array(
							'param' => 'user_form',
							'operator' => '==',
							'value' => 'all',
						),
					),
				),
				'menu_order' => 0,
				'position' => 'normal',
				'style' => 'default',
				'label_placement' => 'top',
				'instruction_placement' => 'label',
				'hide_on_screen' => '',
				'active' => true,
				'description' => '',
			));

			acf_add_local_field_group(array(
				'key' => 'group_5cfe511686e42',
				'title' => 'Organization Fields',
				'fields' => array(
					array(
						'key' => 'field_5cfe5195a33bf',
						'label' => 'Address 1',
						'name' => 'address_1',
						'type' => 'text',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'user_roles' => array(
							0 => 'administrator',
						),
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'maxlength' => '',
					),
					array(
						'key' => 'field_5cfe51a0a33c0',
						'label' => 'Address 2',
						'name' => 'address_2',
						'type' => 'text',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'user_roles' => array(
							0 => 'administrator',
						),
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'maxlength' => '',
					),
					array(
						'key' => 'field_5cfe51a1a33c1',
						'label' => 'City',
						'name' => 'city',
						'type' => 'text',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'user_roles' => array(
							0 => 'administrator',
						),
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'maxlength' => '',
					),
					array(
						'key' => 'field_5cfe51a2a33c2',
						'label' => 'State',
						'name' => 'state',
						'type' => 'text',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'user_roles' => array(
							0 => 'administrator',
						),
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'maxlength' => '',
					),
					array(
						'key' => 'field_5cfe51b9a33c3',
						'label' => 'Zipcode',
						'name' => 'zipcode',
						'type' => 'text',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'user_roles' => array(
							0 => 'administrator',
						),
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'maxlength' => '',
					),
					array(
						'key' => 'field_5cfe51c5a33c4',
						'label' => 'Map',
						'name' => 'map',
						'type' => 'google_map',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'user_roles' => array(
							0 => 'administrator',
						),
						'center_lat' => '35.0219853',
						'center_lng' => '-80.9647685',
						'zoom' => '',
						'height' => '',
					),
					array(
						'key' => 'field_5cfe523fa33c6',
						'label' => 'Phone Number',
						'name' => 'phone_number',
						'type' => 'text',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'user_roles' => array(
							0 => 'administrator',
						),
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'maxlength' => '',
					),
					array(
						'key' => 'field_5cfe522ea33c5',
						'label' => 'Website',
						'name' => 'website',
						'type' => 'url',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'user_roles' => array(
							0 => 'administrator',
						),
						'default_value' => '',
						'placeholder' => '',
					),
				),
				'location' => array(
					array(
						array(
							'param' => 'post_type',
							'operator' => '==',
							'value' => 'organization',
						),
					),
				),
				'menu_order' => 0,
				'position' => 'normal',
				'style' => 'default',
				'label_placement' => 'top',
				'instruction_placement' => 'label',
				'hide_on_screen' => '',
				'active' => true,
				'description' => '',
			));

			acf_add_local_field_group( array(
                'key' => 'group_admin_ftp_settings',
                'title' => 'FTP Import Settings',
                'fields' => array(
                    array(
                        'key' => 'field_ftp_email_recipients',
                        'label' => 'FTP Email Log Recipients',
                        'name' => 'ftp_email_recipients',
                        'type' => 'text',
                        'instructions' => 'Enter a list of email addresses, separated by commas.',
                        'prepend' => '',
                        'append' => '',
					),
					array(
                        'key' => 'field_member_email_recipients',
                        'label' => 'Member Request Email Recipients',
                        'name' => 'member_email_recipients',
                        'type' => 'text',
                        'instructions' => 'Enter a list of email addresses, separated by commas.',
                        'prepend' => '',
                        'append' => '',
					),
					array(
                        'key' => 'field_import_log_days_to_keep',
                        'label' => 'Number Of Days To Persist Import Log',
                        'name' => 'import_log_days_to_keep',
                        'type' => 'text',
                        'instructions' => 'Enter a number of days.',
                        'prepend' => '',
                        'append' => '',
					),
					
                ),
                'location' => array(
                    array(
                        array(
                            'param' => 'options_page',
                            'operator' => '==',
                            'value' => 'maclean-general-settings'
                        ),
                    ),
                )
            ) );

			acf_add_local_field_group(array(
				'key' => 'group_5cf805e58fa97',
				'title' => 'User Settings',
				'fields' => array(
					array(
                        'key' => 'field_5cc80fkjdd7e4x',
                        'label' => 'Suspended Message Text',
                        'name' => 'suspended_message_text',
                        'type' => 'text',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'user_roles' => array(
                            0 => 'administrator',
                        ),
                        'default_value' => '',
                        'placeholder' => '',
                        'prepend' => '',
                        'append' => '',
                        'maxlength' => '',
                    ),
                    array(
                        'key' => 'field_5cc80fasdf7e4x',
                        'label' => 'Application Submited Message Text',
                        'name' => 'application_submitted_text',
                        'type' => 'text',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'user_roles' => array(
                            0 => 'administrator',
                        ),
                        'default_value' => '',
                        'placeholder' => '',
                        'prepend' => '',
                        'append' => '',
                        'maxlength' => '',
                    ),
					array(
						'key' => 'field_5cf90d35786cc',
						'label' => 'Reactivation Request Error Message - User Already Exists',
						'name' => 'req_error_user_exists',
						'type' => 'wysiwyg',
						'instructions' => '{{contact_support_link_url}}, {{login_link_url}}',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'user_roles' => array(
							0 => 'administrator',
						),
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'maxlength' => '',
					),
					array(
						'key' => 'field_5cf90d35786cz',
						'label' => 'Reactivation Request Error Message - Server Error',
						'name' => 'req_error_server_error',
						'type' => 'wysiwyg',
						'instructions' => '{{contact_support_link_url}}, {{login_link_url}}',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'user_roles' => array(
							0 => 'administrator',
						),
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'maxlength' => '',
					),
					array(
						'key' => 'field_5cf90d35786cf',
						'label' => 'Reactivation Request Error Message - Request Already Submitted',
						'name' => 'req_error_already_submitted',
						'type' => 'wysiwyg',
						'instructions' => '{{contact_support_link_url}}, {{login_link_url}}',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'user_roles' => array(
							0 => 'administrator',
						),
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'maxlength' => '',
					),
					array(
						'key' => 'field_5cd806c158e6q',
						'label' => 'Reactivate Member Request Admin Email Body',
						'name' => 'reac_member_email_body',
						'type' => 'wysiwyg',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'user_roles' => array(
							0 => 'administrator',
						),
						'default_value' => '',
						'tabs' => 'administrator',
						'toolbar' => 'full',
						'media_upload' => 1,
						'delay' => 0,
					),
					array(
						'key' => 'field_5cc80fddd7e4x',
						'label' => 'Reactivate Member Request Admin Email Subject',
						'name' => 'reac_member_email_subject',
						'type' => 'text',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'user_roles' => array(
							0 => 'administrator',
						),
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'maxlength' => '',
					),
					array(
						'key' => 'field_5cf806c158e6q',
						'label' => 'New Member Request Admin Email Body',
						'name' => 'new_member_email_body',
						'type' => 'wysiwyg',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'user_roles' => array(
							0 => 'administrator',
						),
						'default_value' => '',
						'tabs' => 'administrator',
						'toolbar' => 'full',
						'media_upload' => 1,
						'delay' => 0,
					),
					array(
						'key' => 'field_5cf80fddd7e4x',
						'label' => 'New Member Request Admin Email Subject',
						'name' => 'new_member_email_subject',
						'type' => 'text',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'user_roles' => array(
							0 => 'administrator',
						),
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'maxlength' => '',
					),					
					array(
						'key' => 'field_5cd806c158e6z',
						'label' => 'Reactivate Member Request Email Body',
						'name' => 'reactivate_notice_body',
						'type' => 'wysiwyg',
						'instructions' => '{{first_name}} will replace with user\'s first name
						{{last_name}} will replace with user\'s last name
						{{username}} will replace with user\'s username/login
						{{email}} will replace with user\'s first name
						{{site_name}} will replace with the name of the site
						{{login_link}} will replace with the link for the user to login in with',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'user_roles' => array(
							0 => 'administrator',
						),
						'default_value' => '',
						'tabs' => 'administrator',
						'toolbar' => 'full',
						'media_upload' => 1,
						'delay' => 0,
					),
					array(
						'key' => 'field_5cc80fddd7e4y',
						'label' => 'Reactivate Member Request Email Subject',
						'name' => 'reactivate_notice_subject',
						'type' => 'text',
						'instructions' => '{{first_name}} will replace with user\'s first name
						{{last_name}} will replace with user\'s last name
						{{username}} will replace with user\'s username/login
						{{email}} will replace with user\'s first name
						{{site_name}} will replace with the name of the site',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'user_roles' => array(
							0 => 'administrator',
						),
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'maxlength' => '',
					),
					array(
						'key' => 'field_5cf806c158e62',
						'label' => 'Password Reset Notice Email Body',
						'name' => 'password_notice_body',
						'type' => 'wysiwyg',
						'instructions' => '{{first_name}} will replace with user\'s first name
			{{last_name}} will replace with user\'s last name
			{{username}} will replace with user\'s username/login
			{{email}} will replace with user\'s email
			{{password_reset_link}} will replace with the password reset link "Reset Your Password"
			{{site_name}} will replace with the name of the site',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'user_roles' => array(
							0 => 'administrator',
						),
						'default_value' => '',
						'tabs' => 'administrator',
						'toolbar' => 'full',
						'media_upload' => 1,
						'delay' => 0,
					),
					array(
						'key' => 'field_5cf81fddd7e42',
						'label' => 'Password Reset Notice Email Subject',
						'name' => 'password_notice_subject',
						'type' => 'text',
						'instructions' => '{{first_name}} will replace with user\'s first name
			{{last_name}} will replace with user\'s last name
			{{username}} will replace with user\'s username/login
			{{site_name}} will replace with the name of the site',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'user_roles' => array(
							0 => 'administrator',
						),
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'maxlength' => '',
					),
					// 30 DAY
					array(
						'key' => 'field_5cf807dd58e71',
						'label' => 'Account Will Expire Notice Email Body - 30 Day',
						'name' => '30_day_notice_body',
						'type' => 'wysiwyg',
						'instructions' => '{{first_name}} will replace with user\'s first name
			{{last_name}} will replace with user\'s last name
			{{username}} will replace with user\'s username/login
			{{email}} will replace with user\'s email
			{{date}} will replace with the date the account expired
			{{site_name}} will replace with the name of the site
			{{reactivation_request_link}} will replace with the link to request reactivation',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'user_roles' => array(
							0 => 'administrator',
						),
						'default_value' => '',
						'tabs' => 'administrator',
						'toolbar' => 'full',
						'media_upload' => 1,
						'delay' => 0,
					),
					array(
						'key' => 'field_5cf8108377e45',
						'label' => 'Account Will Expire Notice Email Subject - 30 Day',
						'name' => '30_day_notice_subject',
						'type' => 'text',
						'instructions' => '{{first_name}} will replace with user\'s first name
			{{last_name}} will replace with user\'s last name
			{{username}} will replace with user\'s username/login
			{{date}} will replace with the date the account expired
			{{site_name}} will replace with the name of the site',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'user_roles' => array(
							0 => 'administrator',
						),
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'maxlength' => '',
					),

					// 7 DAY
					array(
						'key' => 'field_5cf837dd58e70',
						'label' => 'Account Will Expire Notice Email Body - 7 Day',
						'name' => '7_day_notice_body',
						'type' => 'wysiwyg',
						'instructions' => '{{first_name}} will replace with user\'s first name
			{{last_name}} will replace with user\'s last name
			{{username}} will replace with user\'s username/login
			{{email}} will replace with user\'s email
			{{date}} will replace with the date the account expired
			{{site_name}} will replace with the name of the site
			{{reactivation_request_link}} will replace with the link to request reactivation',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'user_roles' => array(
							0 => 'administrator',
						),
						'default_value' => '',
						'tabs' => 'administrator',
						'toolbar' => 'full',
						'media_upload' => 1,
						'delay' => 0,
					),
					array(
						'key' => 'field_5cf81083d7g45',
						'label' => 'Account Will Expire Notice Email Subject - 7 Day',
						'name' => '7_day_notice_subject',
						'type' => 'text',
						'instructions' => '{{first_name}} will replace with user\'s first name
			{{last_name}} will replace with user\'s last name
			{{username}} will replace with user\'s username/login
			{{date}} will replace with the date the account expired
			{{site_name}} will replace with the name of the site',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'user_roles' => array(
							0 => 'administrator',
						),
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'maxlength' => '',
					),

					// 1 DAY
					array(
						'key' => 'field_5cf807dd58z70',
						'label' => 'Account Will Expire Notice Email Body - 1 Day',
						'name' => '1_day_notice_body',
						'type' => 'wysiwyg',
						'instructions' => '{{first_name}} will replace with user\'s first name
			{{last_name}} will replace with user\'s last name
			{{username}} will replace with user\'s username/login
			{{email}} will replace with user\'s email
			{{date}} will replace with the date the account expired
			{{site_name}} will replace with the name of the site
			{{reactivation_request_link}} will replace with the link to request reactivation',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'user_roles' => array(
							0 => 'administrator',
						),
						'default_value' => '',
						'tabs' => 'administrator',
						'toolbar' => 'full',
						'media_upload' => 1,
						'delay' => 0,
					),
					array(
						'key' => 'field_5cf81083d7e45',
						'label' => 'Account Will Expire Notice Email Subject - 1 Day',
						'name' => '1_day_notice_subject',
						'type' => 'text',
						'instructions' => '{{first_name}} will replace with user\'s first name
			{{last_name}} will replace with user\'s last name
			{{username}} will replace with user\'s username/login
			{{date}} will replace with the date the account expired
			{{site_name}} will replace with the name of the site',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'user_roles' => array(
							0 => 'administrator',
						),
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'maxlength' => '',
					),
					array(
						'key' => 'field_5cf9085858e71',
						'label' => 'Confirm Account Information Notice Email Body',
						'name' => 'confirmation_notice_body',
						'type' => 'wysiwyg',
						'instructions' => '{{first_name}} will replace with user\'s first name
			{{last_name}} will replace with user\'s last name
			{{username}} will replace with user\'s username/login
			{{email}} will replace with user\'s email
			{{account_information_confirmation_link}} will replace with the link to confirm the user\'s info
			{{site_name}} will replace with the name of the site',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'user_roles' => array(
							0 => 'administrator',
						),
						'default_value' => '',
						'tabs' => 'administrator',
						'toolbar' => 'full',
						'media_upload' => 1,
						'delay' => 0,
					),
					array(
						'key' => 'field_5cf8109ad7e48',
						'label' => 'Confirm Account Information Notice Email Subject',
						'name' => 'confirmation_notice_subject',
						'type' => 'text',
						'instructions' => '{{first_name}} will replace with user\'s first name
			{{last_name}} will replace with user\'s last name
			{{username}} will replace with user\'s username/login
			{{site_name}} will replace with the name of the site',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'user_roles' => array(
							0 => 'administrator',
						),
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'maxlength' => '',
					),
					array(
						'key' => 'field_5cf8185bb0570',
						'label' => 'Account Creation Notice Email Body',
						'name' => 'activation_notice_body',
						'type' => 'wysiwyg',
						'instructions' => '{{first_name}} will replace with user\'s first name
			{{last_name}} will replace with user\'s last name
			{{username}} will replace with user\'s username/login
			{{organization}} will replace with the user\'s organization name
			{{site_name}} will replace with the name of the site
			{{login_link}} will replace with the login link
			{{member_confirmation_link}} will replace with the member confrimation link',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'user_roles' => array(
							0 => 'administrator',
						),
						'default_value' => '',
						'tabs' => 'administrator',
						'toolbar' => 'full',
						'media_upload' => 1,
						'delay' => 0,
					),
					array(
						'key' => 'field_5cf814e1c0572',
						'label' => 'Account Creation Notice Email Subject',
						'name' => 'activation_notice_subject',
						'type' => 'text',
						'instructions' => '{{first_name}} will replace with user\'s first name
			{{last_name}} will replace with user\'s last name
			{{username}} will replace with user\'s username/login
			{{organization}} will replace with the user\'s organization name
			{{site_name}} will replace with the name of the site',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'user_roles' => array(
							0 => 'administrator',
						),
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'maxlength' => '',
					),
					array(
						'key' => 'field_5cf8fb27219c9',
						'label' => 'Account Has Expired / Been Suspended Notice Email Body',
						'name' => 'suspension_notice_body',
						'type' => 'wysiwyg',
						'instructions' => '{{first_name}} will replace with user\'s first name
			{{last_name}} will replace with user\'s last name
			{{username}} will replace with user\'s username/login
			{{email}} will replace with user\'s email
			{{reactivation_request_link}} will replace with the link to login
			{{expiration_reason}} will replace with the reason the account expired
			{{site_name}} will replace with the name of the site',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'user_roles' => array(
							0 => 'administrator',
						),
						'default_value' => '',
						'tabs' => 'administrator',
						'toolbar' => 'full',
						'media_upload' => 1,
						'delay' => 0,
					),
					array(
						'key' => 'field_5cf8fb67219ca',
						'label' => 'Account Has Expired / Been Suspended Notice Email Subject',
						'name' => 'suspension_notice_subject',
						'type' => 'text',
						'instructions' => '{{first_name}} will replace with user\'s first name
			{{last_name}} will replace with user\'s last name
			{{username}} will replace with user\'s username/login
			{{site_name}} will replace with the name of the site',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'user_roles' => array(
							0 => 'administrator',
						),
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'maxlength' => '',
					),
				),
				'location' => array(
					array(
						array(
							'param' => 'options_page',
							'operator' => '==',
							'value' => 'maclean-general-settings',
						),
					),
				),
				'menu_order' => 0,
				'position' => 'normal',
				'style' => 'default',
				'label_placement' => 'top',
				'instruction_placement' => 'label',
				'hide_on_screen' => '',
				'active' => true,
				'description' => '',
			));

			acf_add_local_field_group(array(
				'key' => 'group_5cf80426eb0eb',
				'title' => 'User Settings',
				'fields' => array(
					array(
						'key' => 'field_5cf8045f7ab48',
						'label' => 'Last Login Date',
						'name' => 'last_login_date',
						'type' => 'date_picker',
						'instructions' => 'This is the last time the user has logged into the site.',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'user_roles' => array(
							0 => 'administrator',
						),
						'display_format' => 'Y-m-d',
						'return_format' => 'Y-m-d',
						'first_day' => 1,
					),
					array (
						'key' => 'field_expiration_date',
						'label' => 'Expiration Date',
						'name' => 'expiration_date',
						'type' => 'date_picker',
						'user_roles' => array(
							0 => 'administrator',
						),
						'display_format' => 'Y-m-d',
						'return_format' => 'Y-m-d',
					),
					array(
						'key' => 'field_5cf804797ab49',
						'label' => 'Is Suspended',
						'name' => 'is_suspended',
						'type' => 'true_false',
						'instructions' => 'This, when checked, prevents the user from logging in. The user will get an error message with the content of the "Account Suspended Reason" field.',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'user_roles' => array(
							0 => 'administrator',
						),
						'message' => '',
						'default_value' => 0,
						'ui' => 0,
						'ui_on_text' => '',
						'ui_off_text' => '',
					),
					array(
						'key' => 'field_is_verified',
						'label' => 'Is Verified',
						'name' => 'is_verified',
						'type' => 'true_false',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'user_roles' => array(
							0 => 'administrator',
						),
						'message' => '',
						'default_value' => 0,
						'ui' => 0,
						'ui_on_text' => '',
						'ui_off_text' => '',
					),
					array(
						'key' => 'field_is_active',
						'label' => 'Is Active',
						'name' => 'is_active',
						'type' => 'true_false',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'user_roles' => array(
							0 => 'administrator',
						),
						'message' => '',
						'default_value' => 0,
						'ui' => 0,
						'ui_on_text' => '',
						'ui_off_text' => '',
					),
					array(
						'key' => 'field_5cf8049c7ab4a',
						'label' => 'Is Employee',
						'name' => 'is_employee',
						'type' => 'true_false',
						'instructions' => 'Is this user a Maclean employee?',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'user_roles' => array(
							0 => 'administrator',
						),
						'message' => '',
						'default_value' => 0,
						'ui' => 0,
						'ui_on_text' => '',
						'ui_off_text' => '',
					),
					array(
						'key' => 'field_5cf90d35786ce',
						'label' => 'Account Reactivation Hash',
						'name' => 'account_reactivation_hash',
						'type' => 'text',
						'instructions' => 'Do not modify or use this field, it is for AE ONLY (the automated system uses it to validate requests as being authentic).',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'user_roles' => array(
							0 => 'administrator',
						),
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'maxlength' => '',
					),
					array(
						'key' => 'field_5cf90d35786de',
						'label' => 'Account Confirmation Hash',
						'name' => 'account_confirmation_hash',
						'type' => 'text',
						'instructions' => 'Do not modify or use this field, it is for AE ONLY (the automated system uses it to validate requests as being authentic).',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'user_roles' => array(
							0 => 'administrator',
						),
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'maxlength' => '',
					),
					array(
						'key' => 'field_5cf81ddd3b058',
						'label' => 'Account Suspended Reason',
						'name' => 'account_suspended_reason',
						'type' => 'text',
						'instructions' => 'The reason the account was suspended. Will be displayed to the user as an error message if and when they attempt to log in to the site.',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'user_roles' => array(
							0 => 'administrator',
						),
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'maxlength' => '',
					),
					array(
						'key' => 'field_5cf80b4db9fda',
						'label' => 'Password Reset Link',
						'name' => 'password_reset_link',
						'type' => 'url',
						'instructions' => 'Do not change. The link sent to the user to prompt the user to change their password so that their account remains active.',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'user_roles' => array(
							0 => 'administrator',
						),
						'default_value' => '',
						'placeholder' => '',
					),
					array(
						'key' => 'field_5cf80b73b9fdc',
						'label' => 'Account Information Confirmation Link',
						'name' => 'account_information_confirmation_link',
						'type' => 'url',
						'instructions' => 'Do not change. The link sent to the user to prompt the user to confirm their account information so that their account remains active.',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'user_roles' => array(
							0 => 'administrator',
						),
						'default_value' => '',
						'placeholder' => '',
					),
					array(
						'key' => 'field_5cf9130f036a7',
						'label' => 'Reactivation Request Link',
						'name' => 'reactivation_request_link',
						'type' => 'url',
						'instructions' => 'Do not change. The link sent to the user to prompt the user to request account reactivation so that their account is reactivated.',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'user_roles' => array(
							0 => 'administrator',
						),
						'default_value' => '',
						'placeholder' => '',
					),
					array(
						'key' => 'field_5cf8fb8a5dfd3',
						'label' => 'Send Account Has Expired Notice',
						'name' => 'send_account_has_expired_notice',
						'type' => 'true_false',
						'instructions' => 'Check this to send the user an email letting the user know that their account has expired.',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'user_roles' => array(
							0 => 'administrator',
						),
						'message' => '',
						'default_value' => 0,
						'ui' => 0,
						'ui_on_text' => '',
						'ui_off_text' => '',
					),
					array(
						'key' => 'field_5cf8fbc15dfd6',
						'label' => 'Send Password Reset Notice',
						'name' => 'send_password_reset_notice',
						'type' => 'true_false',
						'instructions' => 'For Automated System Only',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'user_roles' => array(
							0 => 'administrator',
						),
						'message' => '',
						'default_value' => 0,
						'ui' => 0,
						'ui_on_text' => '',
						'ui_off_text' => '',
					),
					array(
						'key' => 'field_5cf8fbc15dfd5',
						'label' => 'Send Account Information Confirmation Notice',
						'name' => 'send_account_information_confirmation_notice',
						'type' => 'true_false',
						'instructions' => 'Check this to send an email to the user prompting the user to confirm their account information.',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'user_roles' => array(
							0 => 'administrator',
						),
						'message' => '',
						'default_value' => 0,
						'ui' => 0,
						'ui_on_text' => '',
						'ui_off_text' => '',
					),
					array(
						'key' => 'field_5cfa797e7fb4c',
						'label' => 'Send Account Reactivation Confirmation Notice',
						'name' => 'send_account_reactivation_confirmation_notice',
						'type' => 'true_false',
						'instructions' => 'For Automated System Only.',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'user_roles' => array(
							0 => 'administrator',
						),
						'message' => '',
						'default_value' => 0,
						'ui' => 0,
						'ui_on_text' => '',
						'ui_off_text' => '',
					),
				),
				'location' => array(
					array(
						array(
							'param' => 'user_form',
							'operator' => '==',
							'value' => 'all',
						),
					),
				),
				'menu_order' => 0,
				'position' => 'normal',
				'style' => 'default',
				'label_placement' => 'top',
				'instruction_placement' => 'label',
				'hide_on_screen' => '',
				'active' => true,
				'description' => '',
			));

			acf_add_local_field_group(array(
				'key' => 'group_5cf689d251ff5',
				'title' => 'User Account Request Fields',
				'fields' => array(
                    // START: AUTOMATION DO NOT REMOVE
                    // request_type
                    array(
                        // automation, do not remove
                        'key'               => 'field_5cf808ce3430f',
                        'label'             => 'Request Type',
                        'name'              => 'request_type',
                        'type'              => 'select',
                        'instructions'      => 'The type of request.',
                        'required'          => 0,
                        'conditional_logic' => 0,
                        'wrapper'           => array(
                            'width' => '',
                            'class' => '',
                            'id'    => '',
                        ),
                        'user_roles'        => array(
                            0 => 'administrator',
                        ),
                        'choices'           => array(
                            'Create New User Account'          => 'Create New User Account',
                            'Reactivate Existing User Account' => 'Reactivate Existing User Account'
                        ),
                        'default_value'     => array(
                            0 => 'Create New User Account',
                        ),
                        'allow_null'        => 0,
                        'multiple'          => 0,
                        'ui'                => 0,
                        'return_format'     => 'value',
                        'ajax'              => 0,
                        'placeholder'       => '',
                    ),
                    array(
                        // automation, do not remove
                        'key'               => 'field_5d012c19dbe37',
                        'label'             => 'User Account To Reactivate',
                        'name'              => 'representatives_to_reactivate',
                        'type'              => 'user',
                        'instructions'      => '',
                        'required'          => 0,
                        'conditional_logic' => array(
                            array(
                                array(
                                    'field'    => 'field_5cf808ce3430f',
                                    'operator' => '==',
                                    'value'    => 'Reactivate Existing User Account',
                                ),
                            ),
                        ),
                        'wrapper'           => array(
                            'width' => '',
                            'class' => '',
                            'id'    => '',
                        ),
                        'user_roles'        => array(
                            0 => 'administrator',
                        ),
                        'role'              => array(
                            0 => 'representative'
                        ),
                        'allow_null'        => 0,
                        'multiple'          => 0,
                        'return_format'     => 'id',
                    ),
                    array(
                        'key'               => 'field_5df7e10731c22',
                        'label'             => 'First Name',
                        'name'              => 'first_name',
                        'type'              => 'text',
                        'instructions'      => '',
                        'required'          => 0,
                        'conditional_logic' => array(
                            array(
                                array(
                                    'field'    => 'field_5cf808ce3430f',
                                    'operator' => '==',
                                    'value'    => 'Create New User Account',
                                )
                            )
                        ),
                        'wrapper'           => array(
                            'width' => '',
                            'class' => '',
                            'id'    => '',
                        ),
                        'user_roles'        => array(
                            0 => 'administrator',
                        ),
                        'default_value'     => '',
                        'placeholder'       => '',
                    ),
                    // last name
                    array(
                        'key'               => 'field_5df7e13cd3849',
                        'label'             => 'Last Name',
                        'name'              => 'last_name',
                        'type'              => 'text',
                        'instructions'      => '',
                        'required'          => 0,
                        'conditional_logic' => array(
                            array(
                                array(
                                    'field'    => 'field_5cf808ce3430f',
                                    'operator' => '==',
                                    'value'    => 'Create New User Account',
                                )
                            )
                        ),
                        'wrapper'           => array(
                            'width' => '',
                            'class' => '',
                            'id'    => '',
                        ),
                        'user_roles'        => array(
                            0 => 'administrator',
                        ),
                        'default_value'     => '',
                        'placeholder'       => '',
                    ),
                    // email
                    array(
                        'key'               => 'field_5db1a9ee8a3fe',
                        'label'             => 'Email',
                        'name'              => 'email',
                        'type'              => 'email',
                        'instructions'      => '',
                        'required'          => 0,
                        'conditional_logic' => array(
                            array(
                                array(
                                    'field'    => 'field_5cf808ce3430f',
                                    'operator' => '==',
                                    'value'    => 'Create New User Account',
                                )
                            )
                        ),
                        'wrapper'           => array(
                            'width' => '',
                            'class' => '',
                            'id'    => '',
                        ),
                        'user_roles'        => array(
                            0 => 'administrator',
                        ),
                        'default_value'     => '',
                        'placeholder'       => '',
                    ),
                    // website
                    array(
                        'key'               => 'field_5cf68a5a3dd6c',
                        'label'             => 'Website',
                        'name'              => 'website',
                        'type'              => 'url',
                        'instructions'      => '',
                        'required'          => 0,
                        'conditional_logic' => array(
                            array(
                                array(
                                    'field'    => 'field_5cf808ce3430f',
                                    'operator' => '==',
                                    'value'    => 'Create New User Account',
                                )
                            ),
                        ),
                        'wrapper'           => array(
                            'width' => '',
                            'class' => '',
                            'id'    => '',
                        ),
                        'user_roles'        => array(
                            0 => 'administrator',
                        ),
                        'default_value'     => '',
                        'placeholder'       => '',
                    ),
                    // phone number
                    array(
                        'key'               => 'field_5cf68a283dd6a',
                        'label'             => 'Phone Number',
                        'name'              => 'phone_number',
                        'type'              => 'text',
                        'instructions'      => '',
                        'required'          => 0,
                        'conditional_logic' => array(
                            array(
                                array(
                                    'field'    => 'field_5cf808ce3430f',
                                    'operator' => '==',
                                    'value'    => 'Create New User Account',
                                )
                            )
                        ),
                        'wrapper'           => array(
                            'width' => '',
                            'class' => '',
                            'id'    => '',
                        ),
                        'user_roles'        => array(
                            0 => 'administrator',
                        ),
                        'default_value'     => '',
                        'placeholder'       => '',
                        'prepend'           => '',
                        'append'            => '',
                        'maxlength'         => '',
                    ),
                    // city
                    array(
                        'key'               => 'field_5d0a23d7ff842',
                        'label'             => 'City',
                        'name'              => 'rep_city',
                        'type'              => 'text',
                        'instructions'      => '',
                        'required'          => 0,
                        'conditional_logic' => array(
                            array(
                                array(
                                    'field'    => 'field_5cf808ce3430f',
                                    'operator' => '==',
                                    'value'    => 'Create New User Account',
                                )
                            )
                        ),
                        'wrapper'           => array(
                            'width' => '',
                            'class' => '',
                            'id'    => '',
                        ),
                        'user_roles'        => array(
                            0 => 'administrator',
                        ),
                        'default_value'     => '',
                        'placeholder'       => '',
                        'prepend'           => '',
                        'append'            => '',
                        'maxlength'         => '',
                    ),
                    // state
                    array(
                        'key'               => 'field_5d0a23e3ff843',
                        'label'             => 'State',
                        'name'              => 'rep_state',
                        'type'              => 'text',
                        'instructions'      => '',
                        'required'          => 0,
                        'conditional_logic' => array(
                            array(
                                array(
                                    'field'    => 'field_5cf808ce3430f',
                                    'operator' => '==',
                                    'value'    => 'Create New User Account',
                                )
                            )
                        ),
                        'wrapper'           => array(
                            'width' => '',
                            'class' => '',
                            'id'    => '',
                        ),
                        'user_roles'        => array(
                            0 => 'administrator',
                        ),
                        'default_value'     => '',
                        'placeholder'       => '',
                        'prepend'           => '',
                        'append'            => '',
                        'maxlength'         => '',
                    ),
                    // zip
                    array(
                        'key'               => 'field_5d0a23ecff844',
                        'label'             => 'Zipcode',
                        'name'              => 'rep_zipcode',
                        'type'              => 'text',
                        'instructions'      => '',
                        'required'          => 0,
                        'conditional_logic' => array(
                            array(
                                array(
                                    'field'    => 'field_5cf808ce3430f',
                                    'operator' => '==',
                                    'value'    => 'Create New User Account',
                                )
                            )
                        ),
                        'wrapper'           => array(
                            'width' => '',
                            'class' => '',
                            'id'    => '',
                        ),
                        'user_roles'        => array(
                            0 => 'administrator',
                        ),
                        'default_value'     => '',
                        'placeholder'       => '',
                        'prepend'           => '',
                        'append'            => '',
                        'maxlength'         => '',
                    ),
                    // country
                    array(
                        'key'               => 'field_5db0abd7eff57',
                        'label'             => 'Country',
                        'name'              => 'country',
                        'type'              => 'text',
                        'instructions'      => '',
                        'required'          => 0,
                        'conditional_logic' => array(
                            array(
                                array(
                                    'field'    => 'field_5cf808ce3430f',
                                    'operator' => '==',
                                    'value'    => 'Create New User Account',
                                )
                            ),
                        ),
                        'wrapper'           => array(
                            'width' => '',
                            'class' => '',
                            'id'    => '',
                        ),
                        'user_roles'        => array(
                            0 => 'administrator',
                        ),
                        'default_value'     => '',
                        'placeholder'       => '',
                    ),
                    array(
                        'key'               => 'field_5cf92f1a33760',
                        'label'             => 'How Did You Hear About Us',
                        'name'              => 'how_did_you_hear_about_us',
                        'type'              => 'textarea',
                        'instructions'      => '',
                        'required'          => 0,
                        'conditional_logic' => array(
                            array(
                                array(
                                    'field'    => 'field_5cf808ce3430f',
                                    'operator' => '==',
                                    'value'    => 'Create New User Account',
                                )
                            )
                        ),
                        'wrapper'           => array(
                            'width' => '',
                            'class' => '',
                            'id'    => '',
                        ),
                        'user_roles'        => array(
                            0 => 'administrator',
                        ),
                        'default_value'     => '',
                        'placeholder'       => '',
                        'maxlength'         => '',
                        'rows'              => '',
                        'new_lines'         => '',
                    ),
                    // sales rep agency
                    array(
                        'key'               => 'field_5db0aace80008',
                        'label'             => 'Sales Rep Agency',
                        'name'              => 'sales_rep_agency',
                        'type'              => 'text',
                        'instructions'      => '',
                        'required'          => 0,
                        'conditional_logic' => array(
                            array(
                                array(
                                    'field'    => 'field_5cf808ce3430f',
                                    'operator' => '==',
                                    'value'    => 'Create New User Account',
                                )
                            ),
                        ),
                        'wrapper'           => array(
                            'width' => '',
                            'class' => '',
                            'id'    => '',
                        ),
                        'user_roles'        => array(
                            0 => 'administrator',
                        ),
                        'default_value'     => '',
                        'placeholder'       => '',
                    ),
                    // sales rep contact name
                    array(
                        'key'               => 'field_5cf68a5a3dd6c',
                        'label'             => 'Sales Rep Contact Name',
                        'name'              => 'sales_rep_contact_name',
                        'type'              => 'text',
                        'instructions'      => '',
                        'required'          => 0,
                        'conditional_logic' => array(
                            array(
                                array(
                                    'field'    => 'field_5cf808ce3430f',
                                    'operator' => '==',
                                    'value'    => 'Create New User Account',
                                )
                            )
                        ),
                        'wrapper'           => array(
                            'width' => '',
                            'class' => '',
                            'id'    => '',
                        ),
                        'user_roles'        => array(
                            0 => 'administrator',
                        ),
                        'default_value'     => '',
                        'placeholder'       => '',
                    )
                    // END: FIELDS ON FRONTEND FORM
				),
				'location' => array(
					array(
						array(
							'param' => 'post_type',
							'operator' => '==',
							'value' => 'rep_request',
						),
					),
				),
				'menu_order' => 0,
				'position' => 'normal',
				'style' => 'default',
				'label_placement' => 'top',
				'instruction_placement' => 'label',
				'hide_on_screen' => '',
				'active' => true,
				'description' => '',
			));

			// Sales Rep Fields
            acf_add_local_field_group( array(
                'key'      => 'group_rep_locators',
                'title'    => 'Sales Rep Fields',
                'fields'   => array(
                    array(
                        'key' => 'field_sales_rep_markets',
                        'label' => 'Represented Markets',
                        'name' => 'sales_rep_markets',
                        'type' => 'select',
                        'required' => 1,
                        'multiple' => 1,
                        'ui' => 1,
                        'ajax' => 1
                    ),
                    array(
                        'key' => 'field_sales_rep_countries',
                        'label' => 'Represented Countries',
                        'name' => 'sales_rep_countries',
                        'type' => 'select',
                        'required' => 1,
                        'multiple' => 1,
                        'ui' => 1,
                        'ajax' => 1
                    ),
                    array(
                        'key' => 'field_sales_rep_states',
                        'label' => 'Represented States',
                        'name' => 'sales_rep_states',
                        'type' => 'select',
                        'required' => 1,
                        'multiple' => 1,
                        'ui' => 1,
                        'ajax' => 1
                    ),
                    array(
                        'key'   => 'field_company_name',
                        'label' => 'Company Name',
                        'name'  => 'company_name',
                        'type'  => 'text'
                    ),
                    array(
                        'key'   => 'field_contact_name',
                        'label' => 'Contact Name',
                        'name'  => 'contact_name',
                        'type'  => 'text'
                    ),
                    array(
                        'key'   => 'field_company_logo',
                        'label' => 'Company Logo',
                        'name'  => 'company_logo',
                        'type'  => 'image'
                    ),
                    array(
                        'key'   => 'field_address_1',
                        'label' => 'Address Line 1',
                        'name'  => 'address_1',
                        'type'  => 'text'
                    ),
                    array(
                        'key'   => 'field_address_2',
                        'label' => 'Address Line 2',
                        'name'  => 'address_2',
                        'type'  => 'text'
					),
					array(
                        'key'   => 'field_city',
                        'label' => 'City',
                        'name'  => 'city',
                        'type'  => 'text'
					),
					array(
                        'key'   => 'field_state',
                        'label' => 'State',
                        'name'  => 'state',
                        'type'  => 'text'
					),
					array(
                        'key'   => 'field_zipcode',
                        'label' => 'Zipcode',
                        'name'  => 'zipcode',
                        'type'  => 'text'
					),
					array(
                        'key'   => 'field_country',
                        'label' => 'Country',
                        'name'  => 'country',
                        'type'  => 'text'
                    ),
                    array(
                        'key'   => 'field_phone',
                        'label' => 'Phone',
                        'name'  => 'phone',
                        'type'  => 'text'
                    ),
                    array(
                        'key'   => 'field_fax',
                        'label' => 'Fax',
                        'name'  => 'fax',
                        'type'  => 'text'
                    ),
                    array(
                        'key'   => 'field_email',
                        'label' => 'Email',
                        'name'  => 'email',
                        'type'  => 'email'
                    ),
                    array(
                        'key'   => 'field_url',
                        'label' => 'URL',
                        'name'  => 'url',
                        'type'  => 'url'
                    ),
                    array(
                        'key'   => 'field_notes',
                        'label' => 'Notes',
                        'name'  => 'notes',
                        'type'  => 'text'
                    )
                ),
                'location' => array(
                    array(
                        array(
                            'param'    => 'post_type',
                            'operator' => '==',
                            'value'    => 'rep_location'
                        )
                    )
                )
			) );

			// Distributor Location Fields
			acf_add_local_field_group(array(
				'key' => 'group_5da0f6b9e8fbd',
				'title' => 'Distributor Location Fields',
				'fields' => array(
					array(
						'key' => 'field_5da0f954c052b',
						'label' => 'Represented Markets',
						'name' => 'distributor_location_markets',
						'type' => 'select',
						'instructions' => '',
						'required' => 1,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'allow_null' => 0,
						'multiple' => 1,
						'ui' => 1,
						'ajax' => 1,
						'return_format' => 'value',
						'placeholder' => '',
					),
					array(
						'key' => 'field_5da0f6fdd58e0',
						'label' => 'Represented Countries',
						'name' => 'distributor_location_countries',
						'type' => 'select',
						'instructions' => '',
						'required' => 1,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'allow_null' => 0,
						'multiple' => 1,
						'ui' => 1,
						'ajax' => 1,
						'return_format' => 'value',
						'placeholder' => '',
					),
					array (
                        'key' => 'field_contact_name',
                        'label' => 'Contact Name',
                        'name' => 'contact_name',
                        'type' => 'text',
                    ),
					array(
						'key' => 'field_5da0f6d3d58db',
						'label' => 'Address Line 1',
						'name' => 'address_line_1',
						'type' => 'text',
						'instructions' => '',
						'required' => 1,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'maxlength' => '',
					),
					array(
						'key' => 'field_5da0f6dfd58dc',
						'label' => 'Address Line 2',
						'name' => 'address_line_2',
						'type' => 'text',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'maxlength' => '',
					),
					array(
						'key' => 'field_5da0f6e7d58dd',
						'label' => 'City',
						'name' => 'city',
						'type' => 'text',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'maxlength' => '',
					),
					array(
						'key' => 'field_5da0f6edd58de',
						'label' => 'State / Province',
						'name' => 'state_province',
						'type' => 'text',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'maxlength' => '',
					),
					array(
						'key' => 'field_5da0f6f8d58df',
						'label' => 'Zipcode',
						'name' => 'zipcode',
						'type' => 'text',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'maxlength' => '',
					),
					array(
						'key' => 'field_5daa1abb18740',
						'label' => 'Country',
						'name' => 'country',
						'type' => 'text',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'maxlength' => '',
					),
					array(
						'key' => 'field_5da0f732d58e1',
						'label' => 'Phone',
						'name' => 'phone',
						'type' => 'text',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'maxlength' => '',
					),
					array(
						'key' => 'field_5da0f73bd58e2',
						'label' => 'Fax',
						'name' => 'fax',
						'type' => 'text',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'maxlength' => '',
					),
					array(
						'key' => 'field_5da0f73ed58e3',
						'label' => 'Email',
						'name' => 'email',
						'type' => 'email',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
					),
					array(
						'key' => 'field_5da0f74bd58e4',
						'label' => 'URL',
						'name' => 'url',
						'type' => 'url',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'default_value' => '',
						'placeholder' => '',
					),
					array(
						'key' => 'field_5daa1ae718741',
						'label' => 'Notes',
						'name' => 'notes',
						'type' => 'text',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'maxlength' => '',
					),
					array(
						'key' => 'field_5daa1bb26915d',
						'label' => 'Logo',
						'name' => 'logo',
						'type' => 'image',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'return_format' => 'array',
						'preview_size' => 'medium',
						'library' => 'all',
						'min_width' => '',
						'min_height' => '',
						'min_size' => '',
						'max_width' => '',
						'max_height' => '',
						'max_size' => '',
						'mime_types' => '',
					),
					array(
						'key' => 'field_5da4adebd4394',
						'label' => 'Latitude',
						'name' => 'latitude',
						'type' => 'number',
						'instructions' => '',
						'required' => 1,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'min' => '',
						'max' => '',
						'step' => '',
					),
					array(
						'key' => 'field_5da4adf5d4395',
						'label' => 'Longitude',
						'name' => 'longitude',
						'type' => 'number',
						'instructions' => '',
						'required' => 1,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'min' => '',
						'max' => '',
						'step' => '',
					),
				),
				'location' => array(
					array(
						array(
							'param' => 'post_type',
							'operator' => '==',
							'value' => 'distributor_location',
						),
					),
				),
				'menu_order' => 0,
				'position' => 'normal',
				'style' => 'default',
				'label_placement' => 'top',
				'instruction_placement' => 'label',
				'hide_on_screen' => '',
				'active' => true,
				'description' => '',
			));

            // MPS Location Fields
            acf_add_local_field_group(array(
                'key' => 'group_5d9f6be6a489b',
                'title' => 'MPS Location Fields',
                'fields' => array(
                    array(
                        'key' => 'field_5d9f6bfe474c8',
                        'label' => 'Address Line 1',
                        'name' => 'address_line_1',
                        'type' => 'text',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '',
                        'placeholder' => '',
                        'prepend' => '',
                        'append' => '',
                        'maxlength' => '',
                    ),
                    array(
                        'key' => 'field_5d9f6c10474c9',
                        'label' => 'Address Line 2',
                        'name' => 'address_line_2',
                        'type' => 'text',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '',
                        'placeholder' => '',
                        'prepend' => '',
                        'append' => '',
                        'maxlength' => '',
                    ),
                    array(
                        'key' => 'field_5d9f6c16474ca',
                        'label' => 'City',
                        'name' => 'city',
                        'type' => 'text',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '',
                        'placeholder' => '',
                        'prepend' => '',
                        'append' => '',
                        'maxlength' => '',
                    ),
                    array(
                        'key' => 'field_5d9f6c1e474cb',
                        'label' => 'State / Province',
                        'name' => 'state_province',
                        'type' => 'text',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '',
                        'placeholder' => '',
                        'prepend' => '',
                        'append' => '',
                        'maxlength' => '',
                    ),
                    array(
                        'key' => 'field_5d9f6c2a474cc',
                        'label' => 'Zipcode',
                        'name' => 'zipcode',
                        'type' => 'text',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '',
                        'placeholder' => '',
                        'prepend' => '',
                        'append' => '',
                        'maxlength' => '',
                    ),
                    array(
                        'key' => 'field_5d9f6c38474cd',
                        'label' => 'Country',
                        'name' => 'mps_location_country',
                        'type' => 'select',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'choices' => array(
                        ),
                        'default_value' => array(
                        ),
                        'allow_null' => 0,
                        'multiple' => 0,
                        'ui' => 1,
                        'ajax' => 1,
                        'return_format' => 'value',
                        'placeholder' => '',
                    ),
                    array(
                        'key' => 'field_5d9f6c53474ce',
                        'label' => 'Phone',
                        'name' => 'phone',
                        'type' => 'text',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '',
                        'placeholder' => '',
                        'prepend' => '',
                        'append' => '',
                        'maxlength' => '',
                    ),
                    array(
                        'key' => 'field_5d9f6c62474cf',
                        'label' => 'Fax',
                        'name' => 'fax',
                        'type' => 'text',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '',
                        'placeholder' => '',
                        'prepend' => '',
                        'append' => '',
                        'maxlength' => '',
                    ),
                    array(
                        'key' => 'field_5d9f6c66474d0',
                        'label' => 'Email',
                        'name' => 'email',
                        'type' => 'email',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '',
                        'placeholder' => '',
                        'prepend' => '',
                        'append' => '',
                    ),
                    array(
                        'key' => 'field_5d9f6c71474d1',
                        'label' => 'URL',
                        'name' => 'url',
                        'type' => 'url',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '',
                        'placeholder' => '',
                    ),
                    array(
                        'key' => 'field_5d9f8a83d8dda',
                        'label' => 'Latitude',
                        'name' => 'lat',
                        'type' => 'number',
                        'instructions' => '',
                        'required' => 1,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '',
                        'placeholder' => '',
                        'prepend' => '',
                        'append' => '',
                        'min' => '',
                        'max' => '',
                        'step' => '',
                    ),
                    array(
                        'key' => 'field_5d9f8a83d8ddd',
                        'label' => 'Longitude',
                        'name' => 'lng',
                        'type' => 'number',
                        'instructions' => '',
                        'required' => 1,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '',
                        'placeholder' => '',
                        'prepend' => '',
                        'append' => '',
                        'min' => '',
                        'max' => '',
                        'step' => '',
                    )
                ),
                'location' => array(
                    array(
                        array(
                            'param' => 'post_type',
                            'operator' => '==',
                            'value' => 'mps_location',
                        ),
                    ),
                ),
                'menu_order' => 0,
                'position' => 'normal',
                'style' => 'default',
                'label_placement' => 'top',
                'instruction_placement' => 'label',
                'hide_on_screen' => '',
                'active' => true,
                'description' => '',
            ));
		}
	}

	public function setup_taxonomies() {
	}
}