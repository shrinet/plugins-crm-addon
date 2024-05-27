<?php


namespace MacleanCustomCode\Locators;


class MpsLocator
{
    const PAGE_SLUG = 'contact-us';

    private $master;

    public function __construct( $master )
    {
        $this->master = $master;

        // shortcodes
        add_action( 'init', array( $this, 'register_shortcodes' ) );

        add_action( "wp_enqueue_scripts", function() {
            if ( is_page( 'contact-us' ) ) {
                $parameters = array();

                $mps_locator_countries = get_field( 'mp_locator_country_choices', 'option' );
                $mps_locator_distances = get_field( 'mp_locator_distance_choices', 'option' );
        
                if ( $mps_locator_countries )
                    $parameters[ 'countries' ] = $mps_locator_countries;
                if ( $mps_locator_distances )
                    $parameters[ 'distances' ] = $mps_locator_distances;
        
                $post_ids = get_posts( array(
                    'fields'         => 'ids',
                    'post_type'      => 'mps_location',
                    'posts_per_page' => -1
                ) );
        
                $locations = array();
        
                foreach ( $post_ids as $post_id ) {
                    $post = get_post( $post_id );
                    $meta = get_post_custom( $post_id );
        
                    array_push( $locations, array(
                        'title'     => get_the_title( $post_id ),
                        'menuOrder' => $post->menu_order,
                        'address1'  => $meta[ 'address_line_1' ][ 0 ],
                        'address2'  => $meta[ 'address_line_2' ][ 0 ],
                        'city'      => $meta[ 'city' ][ 0 ],
                        'state'     => $meta[ 'state_province' ][ 0 ],
                        'zip'       => $meta[ 'zipcode' ][ 0 ],
                        'country'   => $meta[ 'mps_location_country' ][ 0 ],
                        'phone'     => $meta[ 'phone' ][ 0 ],
                        'fax'       => $meta[ 'fax' ][ 0 ],
                        'email'     => $meta[ 'email' ][ 0 ],
                        'url'       => $meta[ 'url' ][ 0 ],
                        'lat'       => $meta[ 'lat' ][ 0 ],
                        'lng'       => $meta[ 'lng' ][ 0 ],
                        'notes'     => $meta[ 'notes' ][ 0 ],
                        'distance'  => 0
                    ) );
                }
        
                $parameters[ 'locations' ] = $locations;
        
                $parameters[ 'translations' ] = array(
                    'result'  => __( 'Result' ),
                    'results' => __( 'Results' )
                );
                wp_localize_script( 'mps_locator', 'mps_locator_parameters', $parameters );
                wp_enqueue_script( 'mps_locator' );
            }
        });

        // acf
        add_filter( 'acf/load_field/name=mps_location_country', array( $this, 'load_mp_locator_country_choices' ) );
    }

    // =================================================================================================================
    // FILTERS
    // =================================================================================================================

    public function load_mp_locator_country_choices( $field )
    {
        $field[ 'choices' ] = array();

        if ( have_rows( 'mp_locator_country_choices', 'option' ) ) {
            while ( have_rows( 'mp_locator_country_choices', 'option' ) ) {
                the_row();

                $value = get_sub_field( 'code' );
                $label = get_sub_field( 'label' );

                $field[ 'choices' ][ $value ] = $label;
            }
        }

        return $field;
    }

    // =================================================================================================================
    // HOOKS
    // =================================================================================================================

    public function register_shortcodes()
    {
        add_shortcode( 'ae_mps_locator', array( $this, 'mps_locator_shortcode' ) );
    }

    // =================================================================================================================
    // SHORTCODES
    // =================================================================================================================

    public function mps_locator_shortcode()
    {
        ob_start();
        ?>
        <div id="mps-locator" style="display: none">
            <div id="mps-locator-filters" class="filters filter-wrap">
                <form>
                    <div class="country filter">
                        <label for="mps-locator-country"><?= __( 'Select Country' ); ?></label>
                        <select id="mps-locator-country"
                                data-bind="value: userCountry,
                                           options: countries,
                                           optionsValue: 'code',
                                           optionsText: 'label'">
                        </select>
                    </div>
                    <div class="address filter">
                        <label for="mps-locator-address"><?= __( 'Enter Address/City/State or Zip Code' ); ?></label>
                        <input id="mps-locator-address" type="text"
                               placeholder="<?= __( 'Enter Address/City/State or Zip Code' ); ?>"
                               data-bind="value: userAddress">
                    </div>
                    <div class="distance filter">
                        <label for="mps-locator-distance"><?= __( 'Distance' ); ?></label>
                        <select id="mps-locator-distance"
                                data-bind="value: userDistance,
                                           options: distances,
                                           optionsValue: 'distance',
                                           optionsText: 'label'"></select>
                    </div>
                    <div class="controls filter">
                        <button type="submit" data-bind="click: submit"><?= __( 'Go' ) ?></button>
                    </div>
                </form>
            </div>
            <div class="mps-locator-content locator-content">
                <div id="mps-locator-results" class="results">
                    <div class="results-count" data-bind="text: countText">
                    </div>
                    <div class="results-listing">
                        <ul data-bind="foreach: results">
                            <li class="result" data-bind="click: $parent.locationClicked,
                                                          attr: { id: 'location-' + id },
                                                          event: {
                                                            mouseover: $parent.locationMouseOver,
                                                            mouseout: $parent.locationMouseOut
                                                          },
                                                          css: {
                                                            focused: $parent.focusedLocation() === $data,
                                                            active: $parent.focusedLocation() === $data,
                                                            hovered: $parent.hoveredLocation() === $data
                                                            }">
                                <div class="result">
                                    <div class="result-icon">
                                    </div>
                                    <div class="result-info">
                                        <div class="field title-field" data-bind="if: title">
                                            <span data-bind="text: title"></span>
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
                                        <div class="field phone-field" data-bind="if: phone">
                                            <span class="label"><?= __( 'Phone' ) ?>:</span>
                                            <span class="value" data-bind="text: phone"></span>
                                        </div>
                                        <div class="field fax-field" data-bind="if: fax">
                                            <span class="label"><?= __( 'Fax' ) ?>:</span>
                                            <span class="value" data-bind="text: fax"></span>
                                        </div>
                                        <div class="field website-field" data-bind="if: url">
                                            <a target="_blank" class="website-link" data-bind="attr: { href: url }">
                                                <?= __( 'Visit Website' ); ?>
                                            </a>
                                        </div>
                                        <div class="field distance-field" data-bind="if: distance">
                                            <span class="distance-value" data-bind="text: distance().toFixed(0)"></span>
                                            <span class="distance-unit"><?= __( 'Miles' ); ?></span>
                                        </div>
                                        <div class="field directions-field">
                                            <a target="_blank" class="directions-link"
                                               data-bind="attr: { href: 'https://www.google.com/maps/dir/Current+Location/' + lat + ',' + lng }">
                                                <?= __( 'Get Directions' ); ?>
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
                <div id="mps-locator-map" class="map"></div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}