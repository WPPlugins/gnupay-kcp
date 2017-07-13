<?php
if( ! defined( 'ABSPATH' ) ) exit;

//사용자 주문 보여주기 ( 영수증 및 환불 버튼 표시 )

class gnupay_kcp_user_detail extends WC_Settings_API
{
    public function __construct() {
        //사용자 취소 관련
        add_filter( 'woocommerce_order_details_after_order_table', array($this, 'order_table'));
    }

    public function order_table($order){

        $order_id = $order->id;

        if( ! gp_kcp_order_can_view($order_id) ){   //볼수 있는 권한이 없으면
            return;
        }

        $payment_method = get_post_meta( $order_id, '_payment_method', true );
        $payment_title = get_post_meta( $order_id, '_payment_method_title', true );

        if( !in_array($payment_method, gnupay_kcp_get_settings('pay_ids')) ){
            return;
        }

        $payoptions = $this->get_method_options();
        $pay_ids = gnupay_kcp_get_settings('pay_ids');

        if(!$payoptions) return;

        $v = array(
            'app_no_subj'=>'',
            'app_no'=>'',
            'disp_bank'=>true,
            'disp_receipt'=>false,
            'easy_pay_name'=>'',
            'od_deposit_name'=>'',
            'od_bank_account'=>'',
            'pg_process_price'=>0
        );

        if($payment_method == $pay_ids['card']){   //신용카드

            $v['app_no_subj'] = __('승인번호', GNUPAY_NAME);
            $v['app_no'] = get_post_meta( $order_id, '_od_app_no', true );
            $v['disp_bank'] = false;
            $v['disp_receipt'] = true;

        } else if($payment_method == $pay_ids['easy']) {    //간편결제

            $v['app_no_subj'] = __('승인번호', GNUPAY_NAME);
            $v['app_no'] = get_post_meta( $order_id, '_od_app_no', true );
            $v['disp_bank'] = false;
            $v['easy_pay_name'] = 'PAYCO';

        } else if($payment_method == $pay_ids['phone']) {  //휴대폰

            $v['app_no_subj'] = __('휴대폰번호', GNUPAY_NAME);
            $v['app_no'] = get_post_meta( $order_id, '_od_bank_account', true );
            $v['disp_bank'] = false;
            $v['disp_receipt'] = true;

        } else if($payment_method == $pay_ids['vbank'] || $payment_method == $pay_ids['bank']){ //가상계좌, 계좌이체

            $v['app_no_subj'] = __('거래번호', GNUPAY_NAME);
            $v['app_no'] = get_post_meta( $order_id, '_od_tno', true );
            $v['od_bankname']   = get_post_meta( $order_id, '_od_bankname', true );     //입금할 은행 이름
            $v['od_deposit_name'] = get_post_meta( $order_id, '_od_depositor', true );  //입금할 계좌 예금주

            $v['od_bank_account'] = get_post_meta( $order_id, '_od_account', true );   //계좌번호
            $v['va_date'] = get_post_meta( $order_id, 'od_va_date', true );   //가상계좌 입금마감시간
            
            $pg_process_price  = get_post_meta( $order_id, 'pg_process_price', true );   //가상계좌 입금마감시간
            $v['pg_process_price'] = $pg_process_price ? $pg_process_price : 0;    //가상계좌시 입금된 금액
        }

        extract($v);

        ob_start();
        include(GNUPAY_KCP_PATH.'template/order_detail.php');
        $html = ob_get_clean();

        echo apply_filters('gnupay_kcp_order_detail', $html, $order, $payment_method, $payoptions);
    }

    public function get_method_options(){
        $pay_ids = gnupay_kcp_get_settings('pay_ids');
        $gnupay_kcp_card_payname = $pay_ids['card'];

        if( $payment_options = get_option( $this->plugin_id . $gnupay_kcp_card_payname . '_settings', null ) ){
            
            return $payment_options;

        }

        return array();
    }
}

New gnupay_kcp_user_detail();
?>