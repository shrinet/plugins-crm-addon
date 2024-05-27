<?php
namespace MacleanCustomCode;

if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

class MacleanCCEnqueuer {

	public function __construct( $master ) {
		$this->master = $master;
		$this->setup_actions();
		$this->setup_filters();
		$this->setup_menus();
		$this->setup_shortcodes();
	}
	
	public function setup_actions() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public' ) );
		add_action( 'wp_print_scripts', array( $this, 'dequeue_script' ), 100 );
	}
	
	public function dequeue_script() {
		wp_dequeue_script( 'maps-geocoder' );
	}
	
	public function setup_filters() {

	}
	
	public function setup_menus() {
	
	}
	
	public function setup_shortcodes() {
		
	}
	
	public function enqueue_public() {
		wp_register_script( 'maclean_custom_code_slick_script',  $this->master->url . 'assets/vendor/slick/slick.min.js', array('jquery'));
		wp_enqueue_script( 'maclean_custom_code_slick_script' );
		wp_register_style( 'maclean_custom_code_default_style', $this->master->url . 'assets/public/css/public.css');
		wp_enqueue_style( 'maclean_custom_code_default_style' );
		wp_register_style( 'maclean_custom_code_jquery_ui_style', '//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
		wp_enqueue_style( 'maclean_custom_code_jquery_ui_style' );
		wp_register_style( 'maclean_custom_code_slick_style', $this->master->url . 'assets/vendor/slick/slick.css');
		wp_enqueue_style( 'maclean_custom_code_slick_style' );
		wp_register_style( 'maclean_custom_code_slick_theme_style', $this->master->url . 'assets/vendor/slick/slick-theme.css');
		wp_enqueue_style( 'maclean_custom_code_slick_theme_style' );
		wp_register_script( 'maclean_custom_code_jquery_ui', 'https://code.jquery.com/ui/1.12.1/jquery-ui.js', array('jquery'));
		wp_enqueue_script('maclean_custom_code_jquery_ui');
		wp_register_script( 'maclean_custom_code_LoadingOverlay', 'https://cdn.jsdelivr.net/jquery.loadingoverlay/latest/loadingoverlay.min.js', array('jquery'));
		wp_enqueue_script('maclean_custom_code_LoadingOverlay');
		wp_register_script( 'maclean_custom_code_LoadingOverlayProgress', 'https://cdn.jsdelivr.net/jquery.loadingoverlay/latest/loadingoverlay_progress.min.js', array('LoadingOverlay'));
		wp_enqueue_script('maclean_custom_code_LoadingOverlayProgress');

		wp_register_script( 'maclean_custom_code_recaptcha', 'https://www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit', array() );
		wp_enqueue_script('maclean_custom_code_recaptcha');

		wp_enqueue_script( "maclean-functions-script", $this->master->url  . 'assets/shared/js/maclean-functions.js', array( 'jquery' ) );
        wp_register_script( 'maclean_custom_code_script_initialize',  $this->master->url  . 'assets/admin/js/jquery.initialize.min.js', array('jquery'));
		wp_enqueue_script( 'maclean_custom_code_script_initialize' );
		
		wp_register_script( 'maclean_custom_code_table_functions', $this->master->url . 'assets/shared/js/table-functions.js', array( 'jquery', 'datatables' ) );
		wp_enqueue_script('maclean_custom_code_table_functions');

        // MPServiceNet
        wp_register_script( 'knockout', $this->master->url . 'assets/public/js/knockout-3.5.0.js' );
        wp_register_script( 'navigo', $this->master->url . 'assets/public/js/navigo.min.js' );
		wp_register_script( 'eev', $this->master->url . 'assets/public/js/eev.min.js' );
		wp_register_script( 'datatables', $this->master->url . 'assets/vendor/datatables/datatables.min.js', array( 'jquery' ) );
		wp_enqueue_script( 'maclean_custom_code_datatables_button_js', $this->master->url . 'assets/vendor/datatables/Buttons-2.0.1/js/dataTables.buttons.min.js');
		wp_enqueue_script( 'maclean_custom_code_datatables_jszip', $this->master->url . 'assets/vendor/datatables/JSZip-2.5.0/jszip.min.js');
		wp_enqueue_script( 'maclean_custom_code_datatables_pdfmake_min', $this->master->url . 'assets/vendor/datatables/pdfmake-0.1.36/pdfmake.min.js');
		wp_enqueue_script( 'maclean_custom_code_datatables_pdfmake_font', $this->master->url . 'assets/vendor/datatables/pdfmake-0.1.36/vfs_fonts.js');
		wp_enqueue_script( 'maclean_custom_code_datatables_button_print', $this->master->url . 'assets/vendor/datatables/Buttons-2.0.1/js/buttons.print.min.js');
		wp_enqueue_script( 'maclean_custom_code_datatables_button_html5', $this->master->url . 'assets/vendor/datatables/Buttons-2.0.1/js/buttons.html5.min.js');


		wp_register_script( 'mpservicenet', $this->master->url . 'assets/shared/js/mpservicenet.js', array( 'jquery', 'knockout', 'navigo', 'eev', 'datatables' ), '6.0.1' );

        // Locators
		wp_register_script( 'ae_map', $this->master->url . 'assets/shared/js/locators/map.js', array( 'jquery', 'knockout' ) );
        wp_register_script( 'mps_locator', $this->master->url . 'assets/shared/js/locators/mps-locator.js', array( 'jquery', 'knockout', 'ae_map' ) );
        wp_register_script( 'sales_rep_locator', $this->master->url . 'assets/shared/js/locators/sales-rep-locator.js', array( 'jquery', 'knockout') );
        wp_register_script( 'distributor_locator', $this->master->url . 'assets/shared/js/locators/distributor-locator.js', array( 'jquery', 'knockout', 'ae_map' ) );

		wp_enqueue_script( "maclean_custom_code_lightslider", $this->master->url  . 'assets/vendor/lightslider/js/lightslider.min.js', array( 'jquery' ) );
		wp_enqueue_style( 'maclean_custom_code_lightslider_style', $this->master->url . 'assets/vendor/lightslider/css/lightslider.min.css');

		wp_enqueue_style( 'maclean_custom_code_datatables_style', $this->master->url . 'assets/vendor/datatables/datatables.min.css');
		wp_enqueue_style( 'maclean_custom_code_datatables_button_style', $this->master->url . 'assets/vendor/datatables/Buttons-2.0.1/css/buttons.dataTables.min.css');



        wp_enqueue_script( 'google-maps', 'https://maps.googleapis.com/maps/api/js?key=xxxx&libraries=geometry', array('jquery'), false, true);
	}
	
	public function enqueue_admin() {
		wp_register_script( 'maclean_custom_code_script_initialize',  $this->master->url  . 'assets/admin/js/jquery.initialize.min.js', array('jquery'));
		wp_enqueue_script( 'maclean_custom_code_script_initialize' );
		wp_register_script( 'maclean_custom_code_default_script',  $this->master->url . 'assets/admin/js/admin.js', array('jquery'));
		wp_enqueue_script( 'maclean_custom_code_default_script' );
		wp_register_style( 'maclean_custom_code_default_style', $this->master->url . 'assets/admin/css/admin.css');
		wp_enqueue_style( 'maclean_custom_code_default_style' );
		wp_register_script( 'maclean_custom_code_LoadingOverlay', 'https://cdn.jsdelivr.net/jquery.loadingoverlay/latest/loadingoverlay.min.js', array('jquery'));
		wp_enqueue_script( 'maclean_custom_code_LoadingOverlay');
		wp_register_script( 'maclean_custom_code_LoadingOverlayProgress', 'https://cdn.jsdelivr.net/jquery.loadingoverlay/latest/loadingoverlay_progress.min.js', array('LoadingOverlay'));
		wp_enqueue_script( 'maclean_custom_code_LoadingOverlayProgress');
	}
}