<?php
if( ! defined( 'ABSPATH' ) ) return;

Class sir_kpay_ajax extends WC_Settings_API{
    public function __construct() {
        add_action('woocommerce_api_'.__CLASS__, array($this, 'api_request') );
    }

    public function get_pay_options($order_id){
        
        $payment_method = get_post_meta( $order_id, '_payment_method', true );

        $pay_ids = gnupay_kcp_get_settings('pay_ids');
        $gnupay_kcp_card_payname = $pay_ids['card'];
        $payment_options = get_option( $this->plugin_id . $gnupay_kcp_card_payname . '_settings', null );

        $method_options = get_option( $this->plugin_id . $gnupay_kcp_card_payname . '_settings', null );

        
        return wp_parse_args($method_options, $payment_options);

    }

    public function api_request(){
        $order_id = isset($_GET['ordr_idxx']) ? (int) $_GET['ordr_idxx'] : 0;
        if( !$order_id )
            return;

        $config = $this->get_pay_options($order_id);

        include_once(GNUPAY_KCP_PATH.'kcp/m_order_approval.php');
    }
}

new sir_kpay_ajax();
?>