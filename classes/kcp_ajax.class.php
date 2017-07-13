<?php
if( ! defined( 'ABSPATH' ) ) return;

class KCP_ajax_register {

    public $plugin_id;

    public function __construct(){
        $this->plugin_id = 'woocommerce';

        add_action("wp_ajax_kcp_orderdatasave", array($this, "orderdatasave"));
        add_action("wp_ajax_nopriv_kcp_orderdatasave", array($this, "orderdatasave"));

        add_action("wp_ajax_gnupay_kcp_pay_for_order", array($this, "kcp_pay_for_order"));
        add_action("wp_ajax_nopriv_gnupay_kcp_pay_for_order", array($this, "kcp_pay_for_order"));
    }

    public function get_pay_options($order_id){

        return gnupay_kcp_get_config_payment($order_id);

    }

    public function kpay_ajax(){
        include_once(GNUPAY_KCP_PATH.'kcp/m_order_approval.php');
    }

    public function kcp_pay_for_order(){

        $order_id = isset($_POST['order_id']) ? (int) $_POST['order_id'] : '';

        if( ! gp_kcp_order_can_view($order_id) ){
            return false;
        }

        $config = $this->get_pay_options($order_id);

        $order = wc_get_order( $order_id );

        $res = array(
            'result'    => false,
            'payment_method'    =>  isset($_POST['payment_method']) ? sanitize_text_field($_POST['payment_method']) : '',
            );

        if ( $order->needs_payment() ) {
            $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
            $payment_method = $res['payment_method'];

            if ( ! $payment_method ) {
                $res['error_msg'] = __('유효한 payment gateway 가 아닙니다.', GNUPAY_NAME);
                die(wp_json_encode($res));
            }

            // Update meta
            update_post_meta( $order_id, '_payment_method', $payment_method );

            if ( isset( $available_gateways[ $payment_method ] ) ) {
                $payment_method_title = $available_gateways[ $payment_method ]->get_title();
            } else {
                $payment_method_title = '';
            }

            update_post_meta( $order_id, '_payment_method_title', $payment_method_title );

            $res['result'] = 'success';
            
            $res = wp_parse_args($res, gnupay_kcp_process_payment($order, $config));

        }

        echo json_encode($res);
        exit;
    }

    public function orderdatasave(){
        global $wpdb;

        $order_id = isset($_POST['ordr_idxx']) ? (int) $_POST['ordr_idxx'] : '';

        if( !$order_id ){
            return false;
        }

        if( ! gp_kcp_order_can_view($order_id) ){
            return false;
        }

        $dt_data = base64_encode(maybe_serialize($_POST));

        update_post_meta($order_id, '_order_tmp_kcp', $dt_data);   //에스크로 결제시

        exit;
    }
}   //end class

new KCP_ajax_register();
?>