<?php

namespace MacleanCustomCode\Locators;

use MacleanCustomCode\MacleanCustomCode;

class DistributorLocator
{
    const PAGE_SLUG = 'distributor-locations';

    private $master;

    // =================================================================================================================
    // INITIALIZATION
    // =================================================================================================================

    public function __construct( $master )
    {
        $this->master = $master;
        $this->register_hooks();
    }

    private function register_hooks()
    {
        add_action( 'init', array( $this, 'register_shortcodes' ) );

        $class = $this;
        add_action( "wp_enqueue_scripts", function() use( $class ) {
            if ( is_page( 'distributor-locations' ) ) {
                wp_localize_script( 'distributor_locator', 'distributor_locator_params', $class->get_params() );
                wp_enqueue_script( 'distributor_locator' );
            }            
        });

        // ajax
        add_action( 'wp_ajax_distributor_locator_data', array( $this, 'distributor_locator_data' ) );
        add_action( 'wp_ajax_nopriv_distributor_locator_data', array( $this, 'distributor_locator_data' ) );

        // acf
        add_filter( 'acf/load_field/name=distributor_location_markets', array( $this, 'load_distributor_locator_market_choices' ) );
        add_filter( 'acf/load_field/name=distributor_location_countries', array( $this, 'load_distributor_locator_country_choices' ) );
    }

    // =================================================================================================================
    // HOOKS
    // =================================================================================================================

    public function register_shortcodes()
    {
        add_shortcode( 'ae_distributor_locator', array( $this, 'distributor_locator_shortcode' ) );
    }

    // =================================================================================================================
    // FILTERS
    // =================================================================================================================

    public function load_distributor_locator_market_choices( $field )
    {
        $field[ 'choices' ] = array();

        if ( have_rows( 'distributor_locator_market_choices', 'option' ) ) {
            while ( have_rows( 'distributor_locator_market_choices', 'option' ) ) {
                the_row();

                $value = get_sub_field( 'value' );
                $label = get_sub_field( 'label' );

                $field[ 'choices' ][ $value ] = $label;
            }
        }

        return $field;
    }

    public function load_distributor_locator_country_choices( $field )
    {
        $field[ 'choices' ] = array();

        if ( have_rows( 'distributor_locator_country_choices', 'option' ) ) {
            while ( have_rows( 'distributor_locator_country_choices', 'option' ) ) {
                the_row();

                $value = get_sub_field( 'value' );
                $label = get_sub_field( 'label' );

                $field[ 'choices' ][ $value ] = $label;
            }
        }

        return $field;
    }

    // =================================================================================================================
    // AJAX
    // =================================================================================================================

    public function distributor_locator_data()
    {
        $request_market  = sanitize_text_field( $_POST[ 'market' ] );
        $request_country = sanitize_text_field( $_POST[ 'country' ] );

        if ( ! $request_market || ! $request_country )
            die( 'Invalid request.' );

        $post_ids = get_posts( array(
            'fields'         => 'ids',
            'post_type'      => 'distributor_location',
            'posts_per_page' => -1,
            'meta_query'     => array(
                'relation' => 'AND',
                array(
                    'key'     => 'distributor_location_markets',
                    'value'   => $request_market,
                    'compare' => 'LIKE'
                ),
                array(
                    'key'     => 'distributor_location_countries',
                    'value'   => $request_country,
                    'compare' => 'LIKE'
                )
            )
        ) );

        $results = array();

        foreach ( $post_ids as $post_id ) {
            $post = get_post( $post_id );
            $meta = get_post_custom( $post_id );

            array_push( $results, array(
                'title'     => html_entity_decode(get_the_title( $post_id )),
                'menuOrder' => $post->menu_order,
                'address1'  => $meta[ 'address_line_1' ][ 0 ],
                'address2'  => $meta[ 'address_line_2' ][ 0 ],
                'city'      => $meta[ 'city' ][ 0 ],
                'state'     => $meta[ 'state_province' ][ 0 ],
                'zip'       => $meta[ 'zipcode' ][ 0 ],
                'phone'     => $meta[ 'phone' ][ 0 ],
                'fax'       => $meta[ 'fax' ][ 0 ],
                'email'     => $meta[ 'email' ][ 0 ],
                'url'       => $meta[ 'url' ][ 0 ],
                'lat'       => $meta[ 'latitude' ][ 0 ],
                'lng'       => $meta[ 'longitude' ][ 0 ],
                'notes'     => $meta[ 'notes' ][ 0 ]
            ) );
        }

        echo json_encode( $results );
        die();
    }

    // =================================================================================================================
    // SHORTCODES
    // =================================================================================================================

    public function distributor_locator_shortcode()
    {
        ob_start();
        ?>
        <div id="distributor-locator" style="display: none">
            <div id="distributor-locator-filters" class="filters filter-wrap">
                <form>
                    <div class="market-filter filter">
                        <label for="distributor-locator-market-filter">
                            <?= __( 'Select Market' ); ?>
                        </label>
                        <select id="distributor-locator-market-filter"
                                data-bind="value: userMarket, options: markets,
                                           optionsValue: 'value', optionsText: 'label'"></select>
                    </div>
                    <div class="country-filter filter">
                        <label for="distributor-locator-country-filter">
                            <?= __( 'Select Country' ); ?>
                        </label>
                        <select id="distributor-locator-country-filter"
                                data-bind="value: userCountry, options: countries,
                                           optionsValue: 'value', optionsText: 'label'"></select>
                    </div>
                    <div class="address-filter filter">
                        <label for="distributor-locator-address-filter">
                            <?= __( 'Enter Address/City/State OR Zip Code' ); ?>
                        </label>
                        <input type="text" id="distributor-locator-address-filter" data-bind="value: userAddress">
                    </div>
                    <div class="distance-filter filter">
                        <label for="distributor-locator-distance-filter">
                            <?= __( 'Distance' ); ?>
                        </label>
                        <select id="distributor-locator-distance-filter"
                                data-bind="value: userDistance, options: distances,
                                           optionsValue: 'value', optionsText: 'label'"></select>
                    </div>
                    <div class="controls filter">
                        <button type="submit" data-bind="click: submit"><?= __( 'Go' ); ?></button>
                    </div>
                </form>
            </div>
            <div id="distributor-locator-content" class="locator-content">
                <div id="distributor-results-primary" class="distributor-results desktop">
                    <!-- ko component: {
                        name: 'distributor-results-component',
                        params: {
                            parentId: 'distributor-results-primary',
                            locations: results,
                            focusedLocation: focusedLocation,
                            locationClicked: locationClicked,
                            hoveredLocation: hoveredLocation,
                            locationMouseOver: locationMouseOver,
                            locationMouseOut: locationMouseOut
                        }
                    } -->
                    <!-- /ko -->
                </div>
                <div id="distributor-map" class="distributor-map map"></div>
                <div id="distributor-results-secondary" class="distributor-results mobile-results">
                    <!-- ko component: {
                        name: 'distributor-results-component',
                        params: {
                            parentId: 'distributor-results-secondary',
                            locations: results,
                            focusedLocation: focusedLocation,
                            locationClicked: locationClicked,
                            hoveredLocation: hoveredLocation,
                            locationMouseOver: locationMouseOver,
                            locationMouseOut: locationMouseOut
                        }
                    } -->
                    <!-- /ko -->
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    // =================================================================================================================
    // TEMPLATES
    // =================================================================================================================

    private function templates()
    {
        return array(
            'distributor_results' => $this->results_template()
        );
    }

    private function results_template(): string
    {
        ob_start();
        ?>
        <div class="distributor-results-component results" data-bind="css: { active: active }">
            <div class="results-count">
                <p data-bind="text: countText"></p>
            </div>
            <div class="results-listing">
                <ul data-bind="foreach: locations">
                    <li class="result-item" data-bind="click: $parent.locationClicked,
                                                       attr: { id: 'location-' + id },
                                                       css: {
                                                           focused: $parent.focusedLocation() === $data,
                                                           active: $parent.focusedLocation() === $data,
                                                           hovered: $parent.hoveredLocation() === $data
                                                       },
                                                       event: {
                                                            mouseover: $parent.locationMouseOver,
                                                            mouseout: $parent.locationMouseOut
                                                       }">
                        <div class="result">
                            <div class="result-icon" data-bind="click: $parent.locationClicked">
                            </div>
                            <div class="result-info">
                                <div class="field title-field" data-bind="if: title">
                                    <span data-bind="text: title"></span>
                                </div>
                                <div class="field address-field address-upper-field" data-bind="if: address1">
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
                                <div class="field phone-field" data-bind="if: phone">
                                    <span class="label">Phone:</span>
                                    <span class="value" data-bind="text: phone"></span>
                                </div>
                                <div class="field fax-field" data-bind="if: fax">
                                    <span class="label">Fax:</span>
                                    <span class="value" data-bind="text: fax"></span>
                                </div>
                                <div class="field website-field" data-bind="if: url">
                                    <a class="website-link" data-bind="attr: { href: url }" target="_blank">
                                        Visit Website
                                    </a>
                                </div>
                                <div class="field distance-field" data-bind="if: distance">
                                    <span class="distance-value" data-bind="text: distance().toFixed(0)"></span>
                                    <span class="distance-unit">Miles</span>
                                </div>
                                <div class="field directions-field">
                                    <a class="directions-link" target="_blank"
                                       data-bind="attr: { href: 'https://www.google.com/maps/dir/Current+Location/' + lat + ',' + lng }">
                                        Get Directions
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
        <?php
        return ob_get_clean();
    }

    // =================================================================================================================
    // PARAMS
    // =================================================================================================================

    public function get_params()
    {
        $parameters = array();
        $this->params_translations( $parameters );
        $this->params_templates( $parameters );
        $this->params_choices( $parameters );
        return $parameters;
    }

    private function params_translations( array &$params )
    {
        $params[ 'translations' ] = $this->translations();
    }

    private function params_templates( array &$params )
    {
        $params[ 'templates' ] = $this->templates();
    }

    private function params_choices( array &$parameters )
    {
        if ( ( $distributor_markets = get_field( 'distributor_locator_market_choices', 'option' ) ) )
            $parameters[ 'markets' ] = $distributor_markets;
        if ( ( $distributor_countries = get_field( 'distributor_locator_country_choices', 'option' ) ) )
            $parameters[ 'countries' ] = $distributor_countries;
        if ( ( $distributor_distances = get_field( 'distributor_locator_distance_choices', 'option' ) ) )
            $parameters[ 'distances' ] = $distributor_distances;
    }

    // =================================================================================================================
    // MISCELLANEOUS
    // =================================================================================================================

    private function translations()
    {
        return array(
            'result'  => __( 'Result' ),
            'results' => __( 'Results' )
        );

    }
}