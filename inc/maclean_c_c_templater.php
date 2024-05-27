<?php
namespace MacleanCustomCode;

if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

class MacleanCCTemplater {
	
	public function __construct( $master ) {
		
		$this->master = $master;
		$this->templates = array(
		);
		$this->setup_actions();
		$this->setup_filters();
		$this->setup_shortcodes();
			
	}
	
	public function setup_actions() {

	}
	
	public function setup_filters() {

		if ( version_compare( floatval( get_bloginfo( 'version' ) ), '4.7', '<' ) ) {
			add_filter( 'page_attributes_dropdown_pages_args', array( $this, 'register_templates' )	);
		} else {
			add_filter(	'theme_page_templates', array( $this, 'add_new_template' ) );
		}
		
		add_filter( 'wp_insert_post_data', array( $this, 'register_templates' ) );

		add_filter( 'template_include', array( $this, 'view_template') );
	}
	
	public function setup_shortcodes() {
		
	}

	public function add_new_template( $posts_templates ) {
		$posts_templates = array_merge( $posts_templates, $this->templates );
		return $posts_templates;
	}

	public function register_templates( $atts ) {

		$cache_key = 'page_templates-' . md5( get_theme_root() . '/' . get_stylesheet() );

		$templates = wp_get_theme()->get_page_templates();
		if ( empty( $templates ) ) {
			$templates = array();
		} 

		wp_cache_delete( $cache_key , 'themes');

		$templates = array_merge( $templates, $this->templates );

		wp_cache_add( $cache_key, $templates, 'themes', 1800 );

		return $atts;

	} 

	public function view_template( $template ) {
		
		global $post;

		if ( ! $post ) {
			return $template;
		}

		if ( ! isset( $this->templates[ get_post_meta( $post->ID, '_wp_page_template', true ) ] ) ) {
			return $template;
		} 

		$file = $this->master->templatepath . get_post_meta( $post->ID, '_wp_page_template', true );

		if ( file_exists( $file ) ) {
			return $file;
		} else {
			echo $file;
		}

		return $template;

	}

} 