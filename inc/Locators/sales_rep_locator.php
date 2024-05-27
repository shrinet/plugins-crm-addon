<?php


namespace MacleanCustomCode\Locators;


use MacleanCustomCode\MacleanCustomCode;

class SalesRepLocator
{
    // =================================================================================================================
    // INITIALIZATION
    // =================================================================================================================

    /** @var MacleanCustomCode */
    private $master;

    public function __construct( MacleanCustomCode $master )
    {
        $this->master = $master;
        $this->register_hooks();
    }

    private function register_hooks(): void
    {
        // shortcodes
        add_shortcode( 'sales_rep_locator', array( $this, 'sales_rep_locator_shortcode' ) );

        // ajax
        add_action( 'wp_ajax_sales_rep_locator_data', array( $this, 'sales_rep_locator_data' ) );
        add_action( 'wp_ajax_nopriv_sales_rep_locator_data', array( $this, 'sales_rep_locator_data' ) );

        // acf
        add_filter( 'acf/load_field/name=sales_rep_markets', array( $this, 'load_sales_rep_market_choices' ) );
        add_filter( 'acf/load_field/name=sales_rep_countries', array( $this, 'load_sales_rep_country_choices' ) );
        add_filter( 'acf/load_field/name=sales_rep_states', array( $this, 'load_sales_rep_state_choices' ) );

        add_action( "wp_enqueue_scripts", function() {
            if ( is_page( 'rep-locations' ) ) {
                $parameters = array();

                $sales_rep_markets   = get_field( 'sales_rep_market_choices', 'option' );
                $sales_rep_countries = get_field( 'sales_rep_country_choices', 'option' );
                $sales_rep_regions   = get_field( 'sales_rep_state_choices', 'option' );
        
                if ( $sales_rep_markets )
                    $parameters[ 'markets' ] = $sales_rep_markets;
                if ( $sales_rep_countries )
                    $parameters[ 'countries' ] = $sales_rep_countries;
                if ( $sales_rep_regions )
                    $parameters[ 'regions' ] = $sales_rep_regions;
                    
                wp_localize_script( 'sales_rep_locator', 'sales_rep_parameters', $parameters );
                wp_enqueue_script( 'sales_rep_locator' );
            }            
        });
    }

    public function load_sales_rep_market_choices( $field )
    {
        $field[ 'choices' ] = array();

        if ( have_rows( 'sales_rep_market_choices', 'option' ) ) {
            while ( have_rows( 'sales_rep_market_choices', 'option' ) ) {
                the_row();

                $value = get_sub_field( 'code' );
                $label = get_sub_field( 'label' );

                $field[ 'choices' ][ $value ] = $label;
            }
        }

        return $field;
    }

    public function load_sales_rep_country_choices( $field )
    {
        $field[ 'choices' ] = array();

        if ( have_rows( 'sales_rep_country_choices', 'option' ) ) {
            while ( have_rows( 'sales_rep_country_choices', 'option' ) ) {
                the_row();

                $value = get_sub_field( 'code' );
                $label = get_sub_field( 'label' );

                $field[ 'choices' ][ $value ] = $label;
            }
        }

        return $field;
    }

    public function load_sales_rep_state_choices( $field )
    {
        $field[ 'choices' ] = array();

        if ( have_rows( 'sales_rep_state_choices', 'option' ) ) {
            while ( have_rows( 'sales_rep_state_choices', 'option' ) ) {
                the_row();

                $value = get_sub_field( 'code' );
                $label = get_sub_field( 'label' );

                $field[ 'choices' ][ $value ] = $label;
            }
        }

        return $field;
    }

    // =================================================================================================================
    // ACTIONS
    // =================================================================================================================

    public function sales_rep_locator_data()
    {
        $request_market       = sanitize_text_field( $_POST[ 'market' ] );
        $request_country_code = sanitize_text_field( $_POST[ 'countryCode' ] );
        $request_region_code  = sanitize_text_field( $_POST[ 'regionCode' ] );

        if ( ! $request_market || ! $request_country_code || ! $request_region_code )
            die( 'Invalid request.' );

        $post_ids = get_posts( array(
            'fields'     => 'ids',
            'post_type'  => 'rep_location',
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key'     => 'sales_rep_markets',
                    'value'   => $request_market,
                    'compare' => 'LIKE'
                ),
                array(
                    'key'     => 'sales_rep_countries',
                    'value'   => $request_country_code,
                    'compare' => 'LIKE'
                ),
                array(
                    'key'     => 'sales_rep_states',
                    'value'   => $request_region_code,
                    'compare' => 'LIKE'
                )
            )
        ) );

        $results = array();

        foreach ( $post_ids as $post_id ) {
            $post = get_post( $post_id );
            $meta = get_post_custom( $post_id );

            $logo_field = get_field( 'company_logo', $post_id );
            $logo_src   = array_key_exists( 'url', $logo_field ) ? $logo_field[ 'url' ] : '';
            $logo_alt   = array_key_exists( 'alt', $logo_field ) ? $logo_field[ 'alt' ] : '';

            array_push( $results, array(
                'companyName' => $meta[ 'company_name' ][ 0 ],
                'menuOrder'   => $post->menu_order,
                'address1'    => $meta[ 'address_1' ][ 0 ],
                'address2'    => $meta[ 'address_2' ][ 0 ],
                'city'    => $meta[ 'city' ][ 0 ],
                'state'    => $meta[ 'state' ][ 0 ],
                'zip'    => $meta[ 'zipcode' ][ 0 ],
                'contactName' => $meta[ 'contact_name' ][ 0 ],
                'phone'       => $meta[ 'phone' ][ 0 ],
                'fax'         => $meta[ 'fax' ][ 0 ],
                'email'       => $meta[ 'email' ][ 0 ],
                'url'         => $meta[ 'url' ][ 0 ],
                'notes'       => $meta[ 'notes' ][ 0 ],
                'logoSrc'     => $logo_src,
                'logoAlt'     => $logo_alt
            ) );
        }

        echo json_encode( $results );
        die();
    }

    // =================================================================================================================
    // SHORTCODES
    // =================================================================================================================

    public function sales_rep_locator_shortcode(): string
    {
        ob_start();
        ?>
        <div id="sales-rep-locator" data-bind="visible: true" style="display: none">
            <form>
                <div id="sales-rep-locator-filters-primary" class="filter-wrap">
                    <div class="market-field filter">
                        <label for="sales-rep-market"><?= __( 'Select Market' ); ?></label>
                        <select id="sales-rep-market"
                                data-bind="enabled: inputEnabled, value: selectedMarket, foreach: markets">
                            <option data-bind="value: code, text: label"></option>
                        </select>
                    </div>
                    <div class="desktop country-field filter">
                        <label for="sales-rep-country"><?= __( 'Select Country or Location on Map' ); ?></label>
                        <select id="sales-rep-country"
                                data-bind="enabled: inputEnabled, value: selectedCountry, foreach: countries">
                            <option data-bind="value: code, text: label"></option>
                        </select>
                    </div>
                    <div class="desktop state-field filter">
                        <label for="sales-rep-state"><?= __( 'Select State/Province or Click Map' ); ?></label>
                        <select id="sales-rep-state" data-bind="enabled: inputEnabled, value: selectedRegion">
                            <option value="" selected disabled>Select...</option>
                            <!-- ko foreach: regions -->
                            <option data-bind="value: code, text: label"></option>
                            <!-- /ko -->
                        </select>
                    </div>
                    <div class="desktop controls filter">
                        <button type="submit" data-bind="enabled: inputEnabled, click: onSubmitClick">Go</button>
                    </div>
                </div>
                <div class="sales-rep-locator-wrap">
                    <div id="sales-rep-locator-results" class="locator-wrapper desktop results-listing"
                         data-bind="visible: resultsVisible">
                        <div id="results-count" class="locator-count">
                            <p data-bind="text: resultCountText"></p>
                        </div>
                        <div id="results-list" data-bind="if: results().length > 0 ">
                            <ul data-bind="foreach: results">
                                <li>
                                    <div class="result">
                                        <div class="result-logo">
                                            <img data-bind="if: logoSrc, attr: { src: logoSrc, alt: logoAlt }">
                                        </div>
                                        <div class="location-marker"></div>
                                        <div class="result-info">
                                            <div class="field company-name-field" data-bind="if: companyName">
                                                <span data-bind="text: companyName"></span>
                                            </div>
                                            <div class="field address-upper-field" data-bind="if: address1">
                                                <span data-bind="text: address1"></span>
                                                <span data-bind="text: address1 ? ' ' + address2 : address2"></span>
                                            </div>
                                            <div class="field address-lower-field" data-bind="if: city || state || zip">
                                                <span class="value" data-bind="text: city"></span>
                                                <span class="value" data-bind="text: city ? ', ' : '' "></span>
                                                <span class="value" data-bind="text: state ? state : ''"></span>
                                                <span class="value" data-bind="text: state ? ' ' : '' "></span>
                                                <span class="value" data-bind="text: zip ? zip : ''"></span>
                                            </div>
                                            <div class="field contact-name-field" data-bind="if: contactName">
                                                <span class="label"><?= __( 'Contact Name' ) ?>:</span>
                                                <span class="value" data-bind="text: contactName"></span>
                                            </div>
                                            <div class="field phone-field" data-bind="if: phone">
                                                <span class="label"><?= __( 'Phone' ) ?>:</span>
                                                <span class="value" data-bind="text: phone"></span>
                                            </div>
                                            <div class="field fax-field" data-bind="if: fax">
                                                <span class="label"><?= __( 'Fax' ) ?>:</span>
                                                <span class="value" data-bind="text: fax"></span>
                                            </div>
                                            <div class="field email-field" data-bind="if: email">
                                                <span class="label"><?= __( 'Email' ) ?>:</span>
                                                <span class="value" data-bind="text: email"></span>
                                            </div>
                                            <div class="field website-field" data-bind="if: url">
                                                <a target="_blank" data-bind="attr: { href: url }">
                                                    <?= __( 'Visit Website' ) ?>
                                                </a>
                                            </div>
                                            <div class="field note-field" data-bind="if: notes">
                                                <p><?= __( 'Notes' ) ?>:</p>
                                                <p data-bind="text: notes"></p>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div id="sales-rep-locator-map" data-bind="class: mapClass">
                        <?= do_shortcode( '[mapsvg id="233679"]' ); ?>
                    </div>
                </div><!-- end .sales-rep-locator-wrap -->
                <div id="sales-rep-locator-filters-secondary" class="filter-wrap">
                    <div class="mobile country-field filter">
                        <label for="sales-rep-country-secondary"><?= __( 'Select Country or Location on Map' ); ?></label>
                        <select id="sales-rep-country-secondary"
                                data-bind="enabled: inputEnabled, value: selectedCountry, foreach: countries">
                            <option data-bind="value: code, text: label"></option>
                        </select>
                    </div>
                    <div class="mobile state-field filter">
                        <label for="sales-rep-state-secondary"><?= __( 'Select State/Province or Click Map' ); ?></label>
                        <select id="sales-rep-state-secondary" data-bind="enabled: inputEnabled, value: selectedRegion">
                            <option value="" selected disabled>Select...</option>
                            <!-- ko foreach: regions -->
                            <option data-bind="value: code, text: label"></option>
                            <!-- /ko -->
                        </select>
                    </div>
                    <div class="mobile controls filter">
                        <button type="submit" data-bind="enabled: inputEnabled, click: onSubmitClick">Go</button>
                    </div>
                </div>
                <div id="sales-rep-locator-results" class="locator-wrapper mobile-results results"
                     data-bind="visible: resultsVisible">
                    <div id="results-count" class="locator-count">
                        <p data-bind="text: resultCountText"></p>
                    </div>
                    <div id="results-list" data-bind="if: results().length">
                        <ul data-bind="foreach: results">
                            <li>
                                <div class="result">
                                    <div class="result-logo">
                                        <img data-bind="if: logoSrc, attr: { src: logoSrc, alt: logoAlt }">
                                    </div>
                                    <div class="result-info">
                                        <div class="field company-name-field" data-bind="if: companyName">
                                            <span data-bind="text: companyName"></span>
                                        </div>
                                        <div class="field address-upper-field" data-bind="if: address1">
                                            <span data-bind="text: address1"></span>
                                            <span data-bind="text: address1 ? ' ' + address2 : address2"></span>
                                        </div>
                                        <div class="field address-lower-field" data-bind="if: city || state || zip">
                                            <span class="value" data-bind="text: city"></span>
                                            <span class="value" data-bind="text: city ? ', ' + state : state"></span>
                                            <span class="value" data-bind="text: state ? ' ' + zip : ''"></span>
                                        </div>
                                        <div class="field contact-name-field" data-bind="if: contactName">
                                            <span class="label"><?= __( 'Contact Name' ) ?>:</span>
                                            <span class="value" data-bind="text: contactName"></span>
                                        </div>
                                        <div class="field phone-field" data-bind="if: phone">
                                            <span class="label"><?= __( 'Phone' ) ?>:</span>
                                            <span class="value" data-bind="text: phone"></span>
                                        </div>
                                        <div class="field fax-field" data-bind="if: fax">
                                            <span class="label"><?= __( 'Fax' ) ?>:</span>
                                            <span class="value" data-bind="text: fax"></span>
                                        </div>
                                        <div class="field email-field" data-bind="if: email">
                                            <span class="label"><?= __( 'Email' ) ?>:</span>
                                            <span class="value" data-bind="text: email"></span>
                                        </div>
                                        <div class="field website-field" data-bind="if: url">
                                        <span data-bind="attr: { href: url }">
                                            <?= __( 'Visit Website' ) ?>
                                        </span>
                                        </div>
                                        <div class="field note-field" data-bind="if: notes">
                                            <p><?= __( 'Notes' ) ?>:</p>
                                            <p data-bind="text: notes"></p>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
}