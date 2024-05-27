<?php

namespace MacleanCustomCode\Payez;

if ( !defined( 'ABSPATH' ) ) {
    exit( 'Direct script access denied.' );
}

class MacleanCCPayez
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
    
    }

    public function setup_filters()
    {
        add_filter( 'page_template',  array( $this,'payez_page_template') );
    }

    public function setup_menus()
    {
    }

    public function setup_shortcodes()
    {
       
    }

    public function  payez_page_template( $page_template )
    {
        if ( is_page( 'payez' ) ) {
            $page_template = dirname( __FILE__ ) . '/payez.php';
        }
        if ( is_page( 'payez-confirm' ) ) {
            $page_template = dirname( __FILE__ ) . '/payez-confirm.php';
        }
        return $page_template;
    }



}