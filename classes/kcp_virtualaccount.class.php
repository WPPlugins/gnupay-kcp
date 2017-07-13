<?php
if( ! defined( 'ABSPATH' ) ) return;

class kcp_virtualaccount extends kcp_card_gateway
{
    public function get_the_id() {
        $pay_ids = gnupay_kcp_get_settings('pay_ids');

        return $pay_ids['vbank'];   //가상계좌
    }

    public function __construct() {
        parent::__construct();

        add_action('woocommerce_api_'.__CLASS__, array($this, 'api_request') );
    }

    public function get_the_title(){
        return __('KCP 가상계좌', GNUPAY_NAME);
    }

    public function get_the_description() {

        if( $error = $this->pay_bin_check() ){
            
        }

        return __( 'KCP 가상계좌 입금통보 URL은 아래와 같습니다.', GNUPAY_NAME )."<br><br><font size=\"3em\">".gnupay_kcp_get_vbankurl()."</font><br><br>"."KCP 가상계좌 사용시 다음 주소를 <strong><a href=\"http://admin.kcp.co.kr\" target=\"_blank\">KCP 관리자</a> &gt; 상점정보관리 &gt; 정보변경 &gt; 공통URL 정보 &gt; 공통URL 변경후</strong>에 넣으셔야 상점에 자동으로 입금 통보됩니다.";
    }

    public function init_form_fields(){
        $config = $this->config;

        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'woocommerce'),
                'type' => 'checkbox',
                'label' => sprintf(__('%s 를 활성화합니다.', GNUPAY_NAME), $this->get_the_title()),
                'default' => 'no',
            ),
            'title' => array(
                'title' => __('Title', 'woocommerce'),
                'type' => 'text',
                'default' => $this->get_the_title(),
            ),
            'description' => array(
                'title' => __('Description', 'woocommerce'),
                'type' => 'textarea',
                'default' => sprintf(__('%s 로 결제합니다.', GNUPAY_NAME), $this->get_the_title()),
            ),
            'instructions' => array(
                'title' => __('Instructions', 'woocommerce'),
                'type' => 'textarea',
                'description' => __('Instructions that will be added to the thank you page.', 'woocommerce'),
                'default' => '',
                'desc_tip' => true,
            ),
            'de_pay_complete_status'     =>  array(
                'title' => __( '가상계좌 입금 요청전 주문상태', GNUPAY_NAME ),
                'description'   =>  __( 'KCP 가상계좌 입금 요청전 주문상태를 지정합니다.', GNUPAY_NAME ),
                'type'              => 'select',
                'class'             => 'wc-enhanced-select',
                'css'               => 'width: 450px;',
                'label' => __( '가상계좌 입금 요청전 주문상태', GNUPAY_NAME ),
                'default' => 'wc-on-hold',
                'options' => wc_get_order_statuses(),
				'desc_tip'          => true,
				'custom_attributes' => array(
					'data-placeholder' => __('선택해 주세요.', GNUPAY_NAME )
				)
            ),
            'de_deposit_after_status'     =>  array(
                'title' => __( '가상계좌 입금 후 주문상태', GNUPAY_NAME ),
                'description'   =>  __( '가상계좌 입금 후 주문상태를 지정합니다.', GNUPAY_NAME ),
                'type'              => 'select',
                'class'             => 'wc-enhanced-select',
                'css'               => 'width: 450px;',
                'label' => __( '가상계좌 입금 후 주문상태 주문상태', GNUPAY_NAME ),
                'default' => 'wc-processing',
                'options' => wc_get_order_statuses(),
				'desc_tip'          => true,
				'custom_attributes' => array(
					'data-placeholder' => __('선택해 주세요.', GNUPAY_NAME )
				)
            ),
        );
    }

    //가상계좌 입금 처리

    public function api_request(){

        if( $config = $this->config ){
            
            if(!$config['de_card_test']) {

                switch ($_SERVER['REMOTE_ADDR']) {
                    case '203.238.36.58' :
                    case '203.238.36.160' :
                    case '203.238.36.161' :
                    case '203.238.36.173' :
                    case '203.238.36.178' :
                        break;
                    default :   //허용 외 아이피이면
                        $super_admin = current_user_can( 'administrator' ) ? 'super' : '';
                        $egpcs_str = "ENV[" . serialize($_ENV) . "] "
                                   . "GET[" . serialize($_GET) . "]"
                                   . "POST[" . serialize($_POST) . "]"
                                   . "COOKIE[" . serialize($_COOKIE) . "]"
                                   . "SESSION[" . serialize($_SESSION) . "]";

                        $headers = 'From: '.__('경고', GNUPAY_NAME).' <waring>' . "\r\n";
                        $title = __('올바르지 않은 접속 보고', GNUPAY_NAME);
                        $content = sprintf(__("%s 에 %s 이 %s 에 접속을 시도하였습니다.\n\n"), $_SERVER['SCRIPT_NAME'], $_SERVER['REMOTE_ADDR'], date('Y-m-d H:i:s', current_time( 'timestamp' )));
                        $content .= $egpcs_str;
                        
                        do_action('gnupay_kcp_virtual_account_error', $content, $title);

                        return;
                }
            }   //end if

            /* ============================================================================== */
            /* =   02. 공통 통보 데이터 받기                                                = */
            /* = -------------------------------------------------------------------------- = */
            $site_cd      = isset($_POST["site_cd"]) ? sanitize_text_field( $_POST [ "site_cd"  ] ) : '';                 // 사이트 코드
            $tno          = isset($_POST["tno"]) ?  sanitize_text_field($_POST [ "tno"      ]) : '';                 // KCP 거래번호
            $order_no     = isset($_POST["order_no"]) ? sanitize_text_field($_POST [ "order_no" ]) : '';                 // 주문번호
            $tx_cd        = isset($_POST [ "tx_cd"    ]) ? sanitize_text_field($_POST [ "tx_cd" ]) : '';                  // 업무처리 구분 코드
            $tx_tm        = isset($_POST [ "tx_tm"    ]) ? sanitize_text_field($_POST [ "tx_tm" ]) : '';                  // 업무처리 완료 시간
            /* = -------------------------------------------------------------------------- = */
            $ipgm_name    = "";                                    // 주문자명
            $remitter     = "";                                    // 입금자명
            $ipgm_mnyx    = "";                                    // 입금 금액
            $bank_code    = "";                                    // 은행코드
            $account      = "";                                    // 가상계좌 입금계좌번호
            $op_cd        = "";                                    // 처리구분 코드
            $noti_id      = "";                                    // 통보 아이디
            /* = -------------------------------------------------------------------------- = */
            $refund_nm    = "";                                    // 환불계좌주명
            $refund_mny   = "";                                    // 환불금액
            $bank_code    = "";                                    // 은행코드
            /* = -------------------------------------------------------------------------- = */
            $st_cd        = "";                                    // 구매확인 코드
            $can_msg      = "";                                    // 구매취소 사유
            /* = -------------------------------------------------------------------------- = */
            $waybill_no   = "";                                    // 운송장 번호
            $waybill_corp = "";                                    // 택배 업체명

            /* = -------------------------------------------------------------------------- = */
            /* =   02-1. 가상계좌 입금 통보 데이터 받기                                     = */
            /* = -------------------------------------------------------------------------- = */
            if ( $tx_cd == "TX00" )
            {
                $ipgm_name = isset($_POST[ "ipgm_name" ]) ? sanitize_text_field($_POST[ "ipgm_name" ]) : '';                // 주문자명
                $remitter  = isset($_POST[ "remitter"  ]) ? sanitize_text_field($_POST[ "remitter" ]) : '';                // 입금자명
                $ipgm_mnyx = isset($_POST[ "ipgm_mnyx" ]) ? sanitize_text_field($_POST[ "ipgm_mnyx" ]) : '';                // 입금 금액
                $bank_code = isset($_POST[ "bank_code" ]) ? sanitize_text_field($_POST[ "bank_code" ]) : '';                // 은행코드
                $account   = isset($_POST[ "account"   ]) ? sanitize_text_field($_POST[ "account" ]) : '';                // 가상계좌 입금계좌번호
                $op_cd     = isset($_POST[ "op_cd"     ]) ? sanitize_text_field($_POST[ "op_cd" ]) : '';                // 처리구분 코드
                $noti_id   = isset($_POST[ "noti_id"   ]) ? sanitize_text_field($_POST[ "noti_id" ]) : '';                // 통보 아이디
            }
            /* = -------------------------------------------------------------------------- = */
            /* =   02-2. 가상계좌 환불 통보 데이터 받기                                     = */
            /* = -------------------------------------------------------------------------- = */
            else if ( $tx_cd == "TX01" )
            {
                $refund_nm  = isset($_POST[ "refund_nm"  ]) ? sanitize_text_field($_POST[ "refund_nm" ]) : '';              // 환불계좌주명
                $refund_mny = isset($_POST[ "refund_mny" ]) ? sanitize_text_field($_POST[ "refund_mny" ]) : '';              // 환불금액
                $bank_code  = isset($_POST[ "bank_code"  ]) ? sanitize_text_field($_POST[ "bank_code" ]) : '';              // 은행코드
            }
            /* = -------------------------------------------------------------------------- = */
            /* =   02-3. 구매확인/구매취소 통보 데이터 받기                                 = */
            /* = -------------------------------------------------------------------------- = */
            else if ( $tx_cd == "TX02" )
            {
                $st_cd = isset($_POST[ "st_cd" ]) ? sanitize_text_field($_POST[ "st_cd" ]) : '';                        // 구매확인 코드

                if ( $st_cd == "N" )                               // 구매확인 상태가 구매취소인 경우
                {
                    $can_msg = isset($_POST[ "can_msg" ]) ? sanitize_text_field($_POST[ "can_msg" ]) : '';                // 구매취소 사유
                }
            }
            /* = -------------------------------------------------------------------------- = */
            /* =   02-4. 배송시작 통보 데이터 받기                                          = */
            /* = -------------------------------------------------------------------------- = */
            else if ( $tx_cd == "TX03" )
            {
                $waybill_no   = isset($_POST[ "waybill_no"   ]) ? sanitize_text_field($_POST[ "waybill_no" ]) : '';          // 운송장 번호
                $waybill_corp = isset($_POST[ "waybill_corp" ]) ? sanitize_text_field($_POST[ "waybill_corp" ]) : '';          // 택배 업체명
            }
            /* ============================================================================== */


            /* ============================================================================== */
            /* =   03. 공통 통보 결과를 업체 자체적으로 DB 처리 작업하시는 부분입니다.      = */
            /* = -------------------------------------------------------------------------- = */
            /* =   통보 결과를 DB 작업 하는 과정에서 정상적으로 통보된 건에 대해 DB 작업을  = */
            /* =   실패하여 DB update 가 완료되지 않은 경우, 결과를 재통보 받을 수 있는     = */
            /* =   프로세스가 구성되어 있습니다. 소스에서 result 라는 Form 값을 생성 하신   = */
            /* =   후, DB 작업이 성공 한 경우, result 의 값을 "0000" 로 세팅해 주시고,      = */
            /* =   DB 작업이 실패 한 경우, result 의 값을 "0000" 이외의 값으로 세팅해 주시  = */
            /* =   기 바랍니다. result 값이 "0000" 이 아닌 경우에는 재통보를 받게 됩니다.   = */
            /* = -------------------------------------------------------------------------- = */

            /* = -------------------------------------------------------------------------- = */
            /* =   03-1. 가상계좌 입금 통보 데이터 DB 처리 작업 부분                        = */
            /* = -------------------------------------------------------------------------- = */
            if ( $tx_cd == "TX00" )
            {
                $order_id = $order_no;
                $order = wc_get_order( $order_id );
                $order_tno = get_post_meta( $order_id, '_od_tno', true );
                $pay_options = get_option( $this->plugin_id . $this->id . '_settings' );
                if( !isset($order->id) || empty($order->id) ){
                    return;
                }

                update_post_meta($order_id, '_od_receipt_price', isset($ipgm_mnyx) ? $ipgm_mnyx : '');   //입금한 금액
                update_post_meta($order_id, '_od_receipt_time', isset($tx_tm) ? $tx_tm : '');   //입금처리한 시간
                
                // 주문정보 체크 ( 업데이트 처리 )
                $order->update_status( sir_get_order_status($pay_options['de_deposit_after_status']), __( 'kcp 가상계좌에 입금 되었습니다.', GNUPAY_NAME ) );

                if( $order_tno != $tno ){
                    $order->add_order_note( __('해당 주문번호랑 틀린 잘못된 pg 요청이 들어왔습니다.', GNUPAY_NAME) );
                }
                //
            }

            /* = -------------------------------------------------------------------------- = */
            /* =   03-2. 가상계좌 환불 통보 데이터 DB 처리 작업 부분                        = */
            /* = -------------------------------------------------------------------------- = */
            else if ( $tx_cd == "TX01" )
            {
            }

            /* = -------------------------------------------------------------------------- = */
            /* =   03-3. 구매확인/구매취소 통보 데이터 DB 처리 작업 부분                    = */
            /* = -------------------------------------------------------------------------- = */
            else if ( $tx_cd == "TX02" )
            {
            }

            /* = -------------------------------------------------------------------------- = */
            /* =   03-4. 배송시작 통보 데이터 DB 처리 작업 부분                             = */
            /* = -------------------------------------------------------------------------- = */
            else if ( $tx_cd == "TX03" )
            {
            }

            /* = -------------------------------------------------------------------------- = */
            /* =   03-5. 정산보류 통보 데이터 DB 처리 작업 부분                             = */
            /* = -------------------------------------------------------------------------- = */
            else if ( $tx_cd == "TX04" )
            {
            }

            /* = -------------------------------------------------------------------------- = */
            /* =   03-6. 즉시취소 통보 데이터 DB 처리 작업 부분                             = */
            /* = -------------------------------------------------------------------------- = */
            else if ( $tx_cd == "TX05" )
            {
            }

            /* = -------------------------------------------------------------------------- = */
            /* =   03-7. 취소 통보 데이터 DB 처리 작업 부분                                 = */
            /* = -------------------------------------------------------------------------- = */
            else if ( $tx_cd == "TX06" )
            {
            }

            /* = -------------------------------------------------------------------------- = */
            /* =   03-7. 발급계좌해지 통보 데이터 DB 처리 작업 부분                         = */
            /* = -------------------------------------------------------------------------- = */
            else if ( $tx_cd == "TX07" )
            {
            }
            /* ============================================================================== */


            /* ============================================================================== */
            /* =   04. result 값 세팅 하기                                                  = */
            /* ============================================================================== */


            if( $tx_cd ){
                die('<html><body><form><input type="hidden" name="result" value="0000"></form></body></html>');
            }

            die('-1');
        }
    }

    public function process_admin_options(){

        $result = parent::process_admin_options();
    
        if( $result ){
            $options = apply_filters( 'woocommerce_settings_api_sanitized_fields_' . $this->id, $this->sanitized_fields );

            $kcp_options = get_option( $this->plugin_id . $this->gnupay_kcp_card . '_settings' );
            
            $op_enabled = $options['enabled'] == 'yes' ? 1 : 0;

            if(isset($kcp_options['de_vbank_use']) && $op_enabled != $kcp_options['de_vbank_use']){

                $kcp_options['de_vbank_use'] = $op_enabled;

                update_option( $this->plugin_id . $this->gnupay_kcp_card . '_settings', $kcp_options );
            }
        }

        return $result;

    }
}   //end class kcp_virtualaccount

?>