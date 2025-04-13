<?php
/**
 * @package Daan\EDD\NonReqStateField
 * @author  Daan van den Bergh
 * @license MIT
 */

namespace Daan\EDD\NonReqStateField;

use EDD_Customer;

class Plugin {
	/**
	 * Contains all country codes for countries with a predefined list of states.
	 *
	 * @see   edd_get_shop_states()
	 * @since v1.3.0
	 * @var array $required_countries
	 */
	private $required_countries = [
		'US',
		'CA',
		'AU',
		'CN',
		'BR',
		'MX',
		'MY',
		'NZ',
	];

	/**
	 * FFWP_NonRequiredStateField_Set constructor.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Filter & Action hooks.
	 *
	 * @return void
	 */
	private function init() {
		add_filter( 'edd_purchase_form_required_fields', [ $this, 'remove_state_from_required_fields' ] );
		add_action( 'wp_footer', [ $this, 'insert_script' ] );
	}

	/**
	 * @param $fields
	 *
	 * @return array
	 */
	public function remove_state_from_required_fields( $fields ) {
		$country = sanitize_text_field( $_POST[ 'billing_country' ] ?? '' );

		// User just entered checkout. If logged in, let's check if a primary address is set and use that country.
		if ( empty( $country ) ) {
			if ( is_user_logged_in() ) {
				$customer = new EDD_Customer( get_current_user_id(), true );
				$address  = $customer->get_address();

				if ( $address ) {
					$country = $address->country;
				}
			}
		}

		/**
		 * If at this point, $country is still empty. Let's just use the fallback option, because that'll be what the checkout is set to by default.
		 *
		 * @TODO If I ever want to use EDD Pro's geolocation option, I'll have to add another fix for that in here.
		 */
		if ( empty( $country ) ) {
			$country = edd_get_option( 'base_country', 'US' );
		}

		if ( in_array( $country, $this->required_countries ) ) {
			return $fields;
		}

		if ( array_key_exists( 'card_state', $fields ) ) {
			unset( $fields[ 'card_state' ] );
		}

		return $fields;
	}

	/**
	 *
	 */
	public function insert_script() {
		if ( ! edd_is_checkout() ) {
			return;
		}
		?>
        <script>
            jQuery(document).ready(function ($) {
                let daan_edd_nrs = {
                    required_countries: <?= json_encode( $this->required_countries ); ?>,

                    /**
                     * ALL SYSTEMS GO!
                     */
                    init: function () {
                        $(document.body).on('change', '#billing_country', this.set_loader);
                        $(document.body).on('edd_cart_billing_address_updated', this.is_required);
                        this.is_required();
                    },

                    /**
                     * Set a loader to indicate that the state input is about to change.
                     */
                    set_loader: function () {
                        let $card_state_wrap = $('#edd-card-state-wrap');

                        $card_state_wrap.append('<span class="daan-loader edd-loading-ajax edd-loading"></span>');
                        $card_state_wrap.css({
                            opacity: 0.5
                        });
                    },

                    /**
                     * Set or remove asterisk and remove loader.
                     */
                    is_required: function () {
                        let $billing_country = $('#billing_country').val();
                        let $required_indicator = $('#edd-card-state-wrap label .edd-required-indicator');
                        let $card_state_wrap = $('#edd-card-state-wrap');
                        let $card_state_label = $('#edd-card-state-wrap label');


                        if (daan_edd_nrs.required_countries.includes($billing_country)) {
                            if ($required_indicator.length === 0) {
                                $card_state_label.append('<span class="edd-required-indicator">*</span>');
                            }
                        } else {
                            $required_indicator.remove();
                        }

                        $('#edd-card-state-wrap .daan-loader').remove();
                        $card_state_wrap.css({
                            opacity: 1
                        });
                    }
                };

                daan_edd_nrs.init();
            });
        </script>
        <style>
            #edd-card-state-wrap {
                position: relative;
            }

            #edd-card-state-wrap .daan-loader {
                position: absolute;
                top: 50%;
                left: 0;
                right: 0;
                margin-left: auto;
                margin-right: auto;
            }
        </style>
		<?php
	}
}
