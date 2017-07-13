<?php
if( ! defined( 'ABSPATH' ) ) return;

class kcp_pay_gateway_Abstract extends WC_Payment_Gateway
{

    public static function get_the_id()
    {
        throw new Exception('Please implement the get_id method');
    }

    public static function get_the_icon()
    {
        return apply_filters('gnupay_kcp_icon', '');
    }

    public static function get_the_title()
    {
        throw new Exception('Please implement the get_title method');
    }

    public function __construct()
    {
        $this->id                 = $this->get_the_id();
        $this->icon               = $this->get_the_icon();
        $this->has_fields         = false;
        $this->method_title       = $this->get_the_title();
		$this->method_description = sprintf(__('Activate this module to accept %s transactions', GNUPAY_NAME), $this->get_the_title());

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables
		$this->title        = $this->get_option( 'title' );
    }

    public function init_settings(){
    }

	/**
	 * Process the payment and return the result.
	 *
	 * @param int $order_id
	 * @return array
	 */
	public function process_payment( $order_id ) {
        global $woocommerce;
        $order = new WC_Order( $order_id );

		//$order = wc_get_order( $order_id );

		// Mark as on-hold (we're awaiting the payment)
		//$order->update_status( 'on-hold', __( 'Awaiting process payment', 'woocommerce' ) );

		// Reduce stock levels
		//$order->reduce_order_stock();

		// Remove cart
		//WC()->cart->empty_cart();

		// Return thankyou redirect
		return array(
			'result'    => 'success',
            'order_id'  =>  $order->id,
            'order_key' =>  $order->order_key,
			'redirect'  => $this->get_return_url( $order )
		);

	}
}
?>