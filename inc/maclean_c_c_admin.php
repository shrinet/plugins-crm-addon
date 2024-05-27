<?php
namespace MacleanCustomCode;

if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

class MacleanCCAdmin {
	
	public function __construct( $master ) {
		$this->master = $master;
		$this->setup_actions();
		$this->setup_filters();
		$this->setup_shortcodes();

		if( function_exists('acf_add_options_page') ) {	
			acf_add_options_page(array(
				'page_title' 	=> 'Maclean General Settings',
				'menu_title'	=> 'Maclean General Settings',
				'menu_slug' 	=> 'maclean-general-settings',
				'capability'	=> 'edit_posts',
				'redirect'		=> false
			));
		}
	}
	
	public function setup_actions() {
		add_action('admin_head', array( $this, 'admin_js_globals' ) );
	}
	
	public function setup_filters() {
		
	}	
	
	public function setup_menus() {

	}
	
	public function setup_shortcodes() {
		
	}
	
	public function admin_js_globals() {
		?>
		<script>
			var maclean_ajax_url = '<?php echo admin_url( "admin-ajax.php" ); ?>';
		</script>
		<?php
	}	
}