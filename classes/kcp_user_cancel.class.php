<?php
if( ! defined( 'ABSPATH' ) ) exit;

//사용자 취소 관련

class gnupay_kcp_user_cancel extends WC_Settings_API {

    public function __construct() {
        //사용자 취소 관련
        add_filter( 'woocommerce_valid_order_statuses_for_cancel', array($this, 'order_statuses_for_cancel'), 10, 2);
        add_action( 'woocommerce_cancelled_order', array($this, 'woocommerce_cancelled_order') );
    }

    //사용자 주문 취소 관련
    public function woocommerce_cancelled_order($order_id){

        $order = wc_get_order( $order_id );

        if( !in_array($order->payment_method, gnupay_kcp_get_settings('pay_ids')) ){     //그누페이 KCP 결제가 아닌경우 리턴 
            return;
        }

        $user_can_cancel  = current_user_can( 'cancel_order', $order_id );

        if( !$user_can_cancel ){    //권한이 없다면
            wc_add_notice( __( '환불요청할수 있는 권한이 없습니다.', GNUPAY_NAME), 'error' );
            return;
        }

        if( $order->get_total_refunded() && !is_admin() ){     //환불된 내역이 있다면 사용자는 취소 할수 없습니다.
            wc_add_notice( __( '환불된 내역이 있기 때문에 사용자가 취소할수 없습니다.', GNUPAY_NAME), 'error' );
            return;
        }

        $payment_gateways = $this->get_gateways();

        $payment_method = get_post_meta( $order->id, '_payment_method', true );

        $file_path = plugin_dir_path( __FILE__ ).'classes/kcp_card_gateway.class.php';

        $refund_amount = $order->get_total();   //환불 가격
        $refund_reason = __('사용자 주문 취소', GNUPAY_NAME);

        $result = $payment_gateways[ $order->payment_method ]->order_kcp_refund( $order_id, $refund_amount, $refund_reason );
        
        $current_user = wp_get_current_user();
        $user_name = $current_user->ID ? $current_user->user_login.' ( '.$current_user->ID.' ) ' : __('비회원', GNUPAY_NAME);

        if ( is_wp_error( $result ) ) {

            $error_string = $result->get_error_message();

            $order->add_order_note( sprintf(__( '%s 님이 요청한 환불 오류 메시지 : %s', GNUPAY_NAME ),
                $user_name,
                $error_string
            ) );

            wc_add_notice( sprintf(__( '환불요청오류 : %s', GNUPAY_NAME), $error_string), 'error' );

        } elseif ( ! $result ) {
            $order->add_order_note( __( '알수 없는 이유로 환불 오류가 발생했습니다.', GNUPAY_NAME ) );

            wc_add_notice( __( '알수 없는 이유로 환불 오류가 발생했습니다.', GNUPAY_NAME ), 'error' );
        }

    }

    public function get_gateways(){

        return gnupay_kcp_get_gateways();
    }

    public function order_statuses_for_cancel($status, $order=array()){    //사용자 취소 관련
        
        /*
        //취소할는 있는 기본상태는 'pending', 'failed' 입니다.
        */

        if( !$order  ){
            $order_id = isset($_GET['order_id']) ? absint( $_GET['order_id'] ) : 0;
            $order            = wc_get_order( $order_id );
        }

        if( !$order ){
            return $status;
        }

        $payment_gateways = $this->get_gateways();

        $payment_method = get_post_meta( $order->id, '_payment_method', true );

        if( !in_array($payment_method, gnupay_kcp_get_settings('pay_ids')) ){
            return $status;
        }

        if( $order->get_total_refunded() && !is_admin() ){     //환불된 내역이 있다면 사용자는 취소 할수 없습니다.
            return array();
        }

        $kcp_status=array();

        $pay_ids = gnupay_kcp_get_settings('pay_ids');
        //$gnupay_kcp_card_payname = $pay_ids['card'];

        if( $payment_options = get_option( $this->plugin_id . $payment_method . '_settings', null ) ){
            if( !empty($payment_options['de_cancel_possible_status']) ){
                foreach((array) $payment_options['de_cancel_possible_status'] as $stat){
                    if( empty($stat) ) continue;
                    $kcp_status[] = sir_get_order_status($stat);
                }
            }
        }

        return $kcp_status;
    }
}

New gnupay_kcp_user_cancel();
?>