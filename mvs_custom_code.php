<?php
/**
 * Plugin Name: Mvs Crm Addon Code
 * Author: Akhilesh Singh
 * Version: 1.0.0
 * Author URI: https://www.asshrinet.com
 * Description: Crm connection and display Functionality
 */
 
namespace MvsCustomCode; 

if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

if(!function_exists('wp_get_current_user')) {
    include(ABSPATH . "wp-includes/pluggable.php"); 
}

if ( !function_exists( 'mvs_custom_search_activate' ) ) {
	register_activation_hook( __FILE__, 'mvs_custom_search_activate' );
	function mvs_custom_search_activate() {
		
	}
}

global $mvscustomcode;
$mvscustomcode = new MvsCustomCode();

class MvsCustomCode {
	public function __construct() {
		$this->url = plugin_dir_url( __FILE__ );
		$this->path = plugin_dir_path( __FILE__ );
		spl_autoload_register( function ( $class_name ) {
			$class_name = str_replace( "MvsCustomCode\\", "", $class_name );
			$path_parts = preg_split('/(?=\\\\)/', $class_name );
			if ( count( $path_parts ) > 0 ) {
				$path_parts = array_slice( array_filter( $path_parts, function( $e ) { return $e !== ""; } ), 0, count( $path_parts ) - 1 );
			}
			while ( strpos( $class_name, "\\") !== false ) {
				$class_name = substr( $class_name, strpos( $class_name, "\\" ) + 1 );
			}
			$file_name = implode( "/", $path_parts ) . "/" . implode( "_", array_filter( array_map( function( $e ) { return strtolower( $e ); },  preg_split('/(?=[A-Z])/', $class_name ) ), function( $e ) { return $e !== ""; } ) ) . ".php";
			$path = __DIR__ . "/inc/$file_name";
			if ( file_exists( $path ) ) {
				include_once $path;
			}
		});
		$this->plugin_dir_url = plugin_dir_url( __FILE__ );
		$this->templatepath = $this->path . 'templates/';
		$this->background_process = new BackgroundProcess\MvsCCBackgroundProcess( $this );
		$this->background_process_email = new BackgroundProcess\MvsCCBackgroundProcessEmail( $this );
		$this->templater = new MvsCCTemplater( $this );
		$this->admin = new MvsCCAdmin( $this );
		$this->frontend = new MvsCCFrontend( $this );
		$this->functions = new MvsCCFunctions( $this );
		$this->enqueuer = new MvsCCEnqueuer( $this );
		$this->accounts = new Accounts\MvsCCAccounts( $this );
		$this->products = new Products\MvsCCProducts( $this );
		$this->representatives = new Representatives\MvsCCRepresentatives( $this );
		$this->cross_reference = new CrossReference\MvsCCCrossReference( $this );
		$this->custom_content = new CustomContent\MvsCCCustomContent( $this );
		$this->imported_data = new ImportedData\MvsCCImportedData( $this );
		$this->payez = new Payez\MvsCCPayez( $this );

		// locators
		$this->sales_rep_locator = new Locators\SalesRepLocator( $this );
		$this->mps_locator = new Locators\MpsLocator( $this );
		$this->distributor_locator = new Locators\DistributorLocator( $this );
	}
}