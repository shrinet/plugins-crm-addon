<?php
namespace MacleanCustomCode;

if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

class MacleanCCFrontend {
	
	public function __construct( $master ) {
		$this->master = $master;
		$this->setup_actions();
		$this->setup_filters();
		$this->setup_menus();
		$this->setup_shortcodes();
	}
	
	public function setup_actions() {
		add_action( 'wp_head', array( $this, 'frontend_js_globals' ) );
	}
	
	public function setup_filters() {

	}	
	
	public function setup_menus() {
	}
	
	public function setup_shortcodes() {

	}
	
	public function frontend_js_globals() {
		?>
			<script>
				var maclean_ajax_url = "<?php echo admin_url( "admin-ajax.php" ); ?>";
				var maclean_base_url = "<?php echo home_url( "/" ); ?>";
				var geocoder = {};
				jQuery( document ).ready( function() {
					geocoder = new google.maps.Geocoder();
				});
			</script>
		<?php
	}	
}