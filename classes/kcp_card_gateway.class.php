<?php
if( ! defined( 'ABSPATH' ) ) return;

//https://docs.woothemes.com/document/payment-gateway-api/

if ( class_exists( 'WC_Payment_Gateway' ) ) :

class kcp_card_gateway extends WC_Payment_Gateway {

	/** @var array Array of locales */
	public $locale;
    public $locale_setting = array();
    public $config = array();
    public $checkout;
    public $gnupay_kcp_card;
    public $log_enabled;

    public function get_the_id() {
        return $this->gnupay_kcp_card;
    }

    public function get_the_icon() {
        return apply_filters('gnupay_kcp_icon', '');
    }

    public function get_the_title() {
        return __( 'KCP 카드결제', GNUPAY_NAME );
    }

    public function get_the_description() {
        
        $return_html = '';

        $return_html = __( 'KCP 카드결제입니다.', GNUPAY_NAME );
        
        if( $error = $this->pay_bin_check() ){
            
        }
        
        return $return_html;
    }

	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {
        $pay_ids = gnupay_kcp_get_settings('pay_ids');
        $this->gnupay_kcp_card = $pay_ids['card'];

        $this->id                 = $this->get_the_id();
        $this->icon               = $this->get_the_icon();
        $this->has_fields         = false;
        $this->method_title       = $this->get_the_title();
		$this->method_description = $this->get_the_description();
        $this->log_enabled = true;

		// Define user set variables
		$this->title        = $this->get_option( 'title' );
		$this->description  = $this->get_option( 'description' );
		$this->instructions = $this->get_option( 'instructions', $this->description );
        $this->de_pay_complete_status   = $this->get_option( 'de_pay_complete_status' );
        $this->de_cancel_possible_status   = $this->get_option( 'de_cancel_possible_status' );

        $this->supports           = array(
            'refunds',   //환불관련
           'products', 
           'subscriptions',
           'subscription_cancellation', 
            );

        $keys = array(
                'de_iche_use',	//계좌이체 결제사용
                'de_vbank_use',	//가상계좌 결제사용
                'de_hp_use',	//휴대폰결제사용
                'de_card_use',	//신용카드결제사용
                'de_card_noint_use',	//시용카드 무이자할부사용
                'de_easy_pay_use',	//PG사 간편결제 버튼 사용
                'de_taxsave_use',	//현금 영수증 발급 사용
                'de_kcp_mid',	//KCP SITE CODE
                'de_kcp_site_key',	//KCP SITE KEY
                'de_escrow_use',	//에스크로 사용여부
                'de_card_test',		//결제테스트
                'de_tax_flag_use',	//복합과세 결제
                'de_order_after_action',    //주문
                'de_refund_after_status',   //환불 후 주문상태
            );

        foreach($keys as $key){
            $this->config[$key] = $this->get_kcp_option( $key );
        }

		// Load the settings.
		$this->init_form_fields();
        $this->init_settings();

		// Actions
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
    	add_action( 'woocommerce_thankyou_'. $this->id, array( $this, 'thankyou_page' ) );

        // 경로 /woocommerce/templates/checkout/form-checkout.php
        add_action( 'woocommerce_before_checkout_form' , array($this, 'kcp_pay_load') );
        add_action( 'woocommerce_checkout_before_order_review' , array($this, 'kcp_checkout_order') );
        // 경로 /woocommerce/templates/checkout/form-pay.php
        add_action( 'woocommerce_pay_order_before_submit' , array($this, 'kcp_pay_load') );
        add_action( 'woocommerce_pay_order_after_submit' , array($this, 'kcp_checkout_order') );


        add_action( 'template_redirect', array($this, 'kcp_pay_check' ), 20 );

		if ( ! $this->is_valid_for_use() )
			$this->enabled = 'no';
    }

	public function get_kcp_common_fields() {
		return apply_filters( 'woocommerce_settings_api_form_fields_' . $this->gnupay_kcp_card, $this->form_fields );
	}

    public function process_admin_options(){
        $result = parent::process_admin_options();
    
        if( $result ){
            if( $this->id == $this->gnupay_kcp_card ){     //카드일때만 실행;
                $options = apply_filters( 'woocommerce_settings_api_sanitized_fields_' . $this->id, $this->sanitized_fields );
                
                $pay_ids = gnupay_kcp_get_settings('pay_ids');
                $store_options = array();

                foreach( $pay_ids as $key=>$pid ){
                    $store_options[$key] = get_option( $this->plugin_id . $pid . '_settings' );
                }
                
                if( isset($options['de_iche_use']) && !empty( $store_options['bank'] ) ){   //계좌이체
                    $store_options['bank']['enabled'] = $options['de_iche_use'] ? 'yes' : 'no';
                    update_option( $this->plugin_id . $pay_ids['bank'] . '_settings', $store_options['bank'] );
                }

                if( isset($options['de_vbank_use']) && !empty( $store_options['vbank'] ) ){   //가상계좌
                    $store_options['vbank']['enabled'] = $options['de_vbank_use'] ? 'yes' : 'no';
                    update_option( $this->plugin_id . $pay_ids['vbank'] . '_settings', $store_options['vbank'] );
                }

                if( isset($options['de_hp_use']) && !empty( $store_options['phone'] ) ){   //핸드폰
                    $store_options['phone']['enabled'] = $options['de_hp_use'] ? 'yes' : 'no';
                    update_option( $this->plugin_id . $pay_ids['phone'] . '_settings', $store_options['phone'] );
                }

                if( isset($options['de_easy_pay_use']) && !empty( $store_options['easy'] ) ){   //간편결제
                    $store_options['easy']['enabled'] = $options['de_easy_pay_use'] ? 'yes' : 'no';
                    update_option( $this->plugin_id . $pay_ids['easy'] . '_settings', $store_options['easy'] );
                }

            }
        }

        return $result;
    }

	public function kcp_common_settings() {

		// Load form_field settings.
		$this->locale_setting = get_option( $this->plugin_id . $this->gnupay_kcp_card . '_settings', null );

		if ( ! $this->locale_setting || ! is_array( $this->locale_setting ) ) {

			$this->locale_setting = array();

			// If there are no settings defined, load defaults.
			if ( $form_fields = $this->get_form_fields() ) {

				foreach ( $form_fields as $k => $v ) {
					$this->locale_setting[ $k ] = isset( $v['default'] ) ? $v['default'] : '';
				}
			}
		}

		if ( ! empty( $this->locale_setting ) && is_array( $this->locale_setting ) ) {
			$this->locale_setting = array_map( array( $this, 'format_settings' ), $this->locale_setting );
			$this->enabled  = isset( $this->locale_setting['enabled'] ) && $this->locale_setting['enabled'] == 'yes' ? 'yes' : 'no';
		}
	}

    public function is_valid_for_use(){

        $is_vaild = true;

        if( ! in_array( get_woocommerce_currency(), array('KRW') ) ){
            return false;
        }

        $kcp_options = gp_kcp_get_card_options();

        if( !$kcp_options['de_card_test'] && trim($kcp_options['de_kcp_site_key']) == '' ){     //실결제일때 사이트키가 없으면
            return false;
        }

        return $is_vaild;
    }

	public function get_kcp_option( $key, $empty_value = null ) {

		if ( empty( $this->locale_setting ) ) {
			$this->kcp_common_settings();
		}

		// Get option default if unset.
		if ( ! isset( $this->locale_setting[ $key ] ) ) {
			$form_fields            = $this->get_kcp_common_fields();
			$this->locale_setting[ $key ] = isset( $form_fields[ $key ]['default'] ) ? $form_fields[ $key ]['default'] : '';
		}

		if ( ! is_null( $empty_value ) && empty( $this->locale_setting[ $key ] ) && '' === $this->locale_setting[ $key ] ) {
			$this->locale_setting[ $key ] = $empty_value;
		}

		return $this->locale_setting[ $key ];
	}

	public function can_refund_order( $order ) {
		return $order && $order->get_transaction_id();
	}

	/**
	 * Logging method.
	 * @param string $message
	 */
	public function log( $message ) {
		if ( $this->log_enabled ) {
			if ( empty( $this->log ) ) {
				$this->log = new WC_Logger();
			}
			$this->log->add( 'kcp', $message );
		}
	}

    public function process_refund( $order_id, $amount = null, $reason = '' ) { //환불관련 ( 부분환불 ) 관리자 페이지에서만 사용가능

        if( !is_admin() ){
            return false;
        }

        if( !$reason ){
            return new WP_Error( 'error', __( '환불요청사유를 입력해 주세요.', GNUPAY_NAME ) );
        }

        $order = wc_get_order( $order_id );

        try {
            
            if ( ! $this->can_refund_order( $order ) ) {
                //$this->log( 'Refund Failed: No transaction ID' );
                //return new WP_Error( 'error', __( 'Refund Failed: No transaction ID', 'woocommerce' ) );
            }

            $config = $this->config;
            $mod_memo = $reason;    //취소사유
            $tax_mny = $amount;  //과세금액
            $free_mny = 0;     //비과세금액
            $payment_method = get_post_meta( $order_id, '_payment_method', true );
            $od_tax_flag    = get_post_meta( $order_id, '_od_tax_flag', true );   //과세 및 비과세 사용시
			$pay_ids = gnupay_kcp_get_settings('pay_ids');

            if( $od_tax_flag ){     //복합과세이면 다시 계산

                $line_item_qtys         = json_decode( sanitize_text_field( stripslashes( $_POST['line_item_qtys'] ) ), true );
                $line_item_totals       = json_decode( sanitize_text_field( stripslashes( $_POST['line_item_totals'] ) ), true );
                $line_item_tax_totals   = json_decode( sanitize_text_field( stripslashes( $_POST['line_item_tax_totals'] ) ), true );

                // Prepare line items which we are refunding
                $line_items = array();
                $item_ids   = array_unique( array_merge( array_keys( $line_item_qtys, $line_item_totals ) ) );

                foreach ( $item_ids as $item_id ) {
                    $line_items[ $item_id ] = array( 'qty' => 0, 'refund_total' => 0, 'refund_tax' => array() );
                }
                foreach ( $line_item_qtys as $item_id => $qty ) {
                    $line_items[ $item_id ]['qty'] = max( $qty, 0 );
                }
                foreach ( $line_item_totals as $item_id => $total ) {
                    $line_items[ $item_id ]['refund_total'] = wc_format_decimal( $total );
                }
                foreach ( $line_item_tax_totals as $item_id => $tax_totals ) {
                    $line_items[ $item_id ]['refund_tax'] = array_map( 'wc_format_decimal', $tax_totals );
                }

                foreach( $line_items as $key=>$v ){
                    if( !isset($v['qty']) || empty($v['qty']) || empty($v['refund_total']) )
                        continue;

                    $refund_tax = 0;

                    foreach($v['refund_tax'] as $rex){
                        if( empty($rex) ) continue;

                        $refund_tax = $refund_tax + (int) $rex;
                    }

                    if( $v['refund_total'] && empty($refund_tax) ){    //세금이 붙지 않으면 비과세
                        $free_mny = $free_mny + (int) $v['refund_total'];       //비과세 금액을 구합니다.
                    }
                }

                $tax_mny = $amount - $free_mny;  //과세금액을 다시 구합니다.

                $get_total_qty_refunded = $order->get_total_qty_refunded();

                if( !$get_total_qty_refunded ){
                    //return new WP_Error( 'error', __( '복합과세로 결제한 주문은 반드시 수량을 체크해야 합니다.', GNUPAY_NAME ) );
                }
            }

            include_once(GNUPAY_KCP_PATH.'kcp/settle_kcp.inc.php');

            // locale ko_KR.euc-kr 로 설정
            setlocale(LC_CTYPE, 'ko_KR.euc-kr');

            // 부분취소 실행
            $g_conf_site_cd   = $config['de_kcp_mid'];
            $g_conf_site_key  = $config['de_kcp_site_key'];
            $g_conf_home_dir  = GNUPAY_KCP_PATH.'/kcp';
            $g_conf_key_dir   = '';
            $g_conf_log_dir   = '';

            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
            {
                $g_conf_log_dir   = sir_get_upload_path().'/log/refund';
                $g_conf_key_dir   = GNUPAY_KCP_PATH.'kcp/bin/pub.key';
            }

            if (preg_match("/^T000/", $g_conf_site_cd) || $config['de_card_test']) {
                $g_conf_gw_url  = "testpaygw.kcp.co.kr";
            }
            else {
                $g_conf_gw_url  = "paygw.kcp.co.kr";
                if (!preg_match("/^SR/", $g_conf_site_cd)) {
                    return new WP_Error('kcp_refund_error', __("SR 로 시작하지 않는 KCP SITE CODE 는 지원하지 않습니다.", GNUPAY_NAME));
                }
            }

            include_once(GNUPAY_KCP_PATH.'kcp/pp_cli_hub_lib.php');

            $tno            = get_post_meta( $order_id, '_od_tno', true );
            $req_tx         = 'mod';
            $mod_desc       = gp_iconv_euckr($mod_memo);
            $cust_ip        = getenv('REMOTE_ADDR');
            //$rem_mny        = $order->get_remaining_refund_amount();  // 취소 가능 잔액 ( 이렇게 하면 안됨 )
            $rem_mny        = $order->get_total() - $order->get_total_refunded() + (int) $amount;  // 취소 가능 잔액
            $mod_mny        = (int)$tax_mny;
            $mod_free_mny   = (int)$free_mny;
            $mod_type       = 'RN07';

			if( $payment_method == $pay_ids['bank'] )	//계좌이체
                $mod_type   = 'STPA';

            if($od_tax_flag) {
                $mod_mny = $tax_mny + $free_mny;
            }

            $c_PayPlus  = new C_PAYPLUS_CLI;
            $c_PayPlus->mf_clear();


            if ( $req_tx == "mod" )
            {
                $tran_cd = "00200000";

                $c_PayPlus->mf_set_modx_data( "tno"          , $tno                  );  // KCP 원거래 거래번호
                $c_PayPlus->mf_set_modx_data( "mod_type"     , $mod_type			 );  // 원거래 변경 요청 종류
                $c_PayPlus->mf_set_modx_data( "mod_ip"       , $cust_ip				 );  // 변경 요청자 IP
                $c_PayPlus->mf_set_modx_data( "mod_desc"     , $mod_desc			 );  // 변경 사유
                $c_PayPlus->mf_set_modx_data( "rem_mny"      , strval($rem_mny)      );  // 취소 가능 잔액
                $c_PayPlus->mf_set_modx_data( "mod_mny"      , strval($mod_mny)      );  // 취소 요청 금액

                if($od_tax_flag)
                {
                    $mod_tax_mny = round((int)$tax_mny / 1.1);
                    $mod_vat_mny = (int)$tax_mny - $mod_tax_mny;

                    $c_PayPlus->mf_set_modx_data( "tax_flag"     , "TG03"				 );  // 복합과세 구분
                    $c_PayPlus->mf_set_modx_data( "mod_tax_mny"  , strval($mod_tax_mny)  );	 // 공급가 부분 취소 요청 금액
                    $c_PayPlus->mf_set_modx_data( "mod_vat_mny"  , strval($mod_vat_mny)	 );  // 부과세 부분 취소 요청 금액
                    $c_PayPlus->mf_set_modx_data( "mod_free_mny" , strval($mod_free_mny) );  // 비관세 부분 취소 요청 금액
                }
            }

            if ( $tran_cd != "" )
            {
            $c_PayPlus->mf_do_tx( "",                $g_conf_home_dir, $g_conf_site_cd,
                              $g_conf_site_key,  $tran_cd,         "",
                              $g_conf_gw_url,    $g_conf_gw_port,  "payplus_cli_slib",
                              isset($ordr_idxx) ? $ordr_idxx : '',        $cust_ip,         $g_conf_log_level,
                              "",                0,                $g_conf_key_dir,
                              $g_conf_log_dir );

            $res_cd  = $c_PayPlus->m_res_cd;  // 결과 코드
            $res_msg = $c_PayPlus->m_res_msg; // 결과 메시지
            /* $res_en_msg = $c_PayPlus->mf_get_res_data( "res_en_msg" );  // 결과 영문 메세지 */
            }
            else
            {
                $c_PayPlus->m_res_cd  = "9562";
                $c_PayPlus->m_res_msg = __("연동 오류|Payplus Plugin이 설치되지 않았거나 tran_cd값이 설정되지 않았습니다.", GNUPAY_NAME);
            }

            if ($res_cd != '0000')
            {
                $res_msg = iconv("euc-kr", "utf-8", $res_msg);

                return new WP_Error('kcp_refund_error', "$res_cd : $res_msg" );
            }

            /* ============================================================================== */
            /* =       취소 결과 처리                                                       = */
            /* = -------------------------------------------------------------------------- = */
            if ( $req_tx == "mod" )
            {
                if ( $res_cd == "0000" )
                {
                $tno = $c_PayPlus->mf_get_res_data( "tno" );  // KCP 거래 고유 번호
                $amount  = $c_PayPlus->mf_get_res_data( "amount"       ); // 원 거래금액
                $mod_mny = $c_PayPlus->mf_get_res_data( "panc_mod_mny" ); // 취소요청된 금액
                $rem_mny = $c_PayPlus->mf_get_res_data( "panc_rem_mny" ); // 취소요청후 잔액

                // 환불금액기록

                $payment_gateways = gnupay_kcp_get_gateways();

                $current_user = wp_get_current_user();
                $order->add_order_note( sprintf(__( '%s 님이 %s, ( %s ) 이유로 가격 %s 를 취소하셨습니다.', GNUPAY_NAME ), 
                    $current_user->user_login.' ( '.$current_user->ID.' ) ',
                    isset( $payment_gateways[ $payment_method ] ) ? esc_html( $payment_gateways[ $payment_method ]->get_title() ) : esc_html( $payment_method ),
                    $reason,
                    wc_price($mod_mny)
                ) );

                // 미수금 등의 정보 업데이트

                } // End of [res_cd = "0000"]

                /* = -------------------------------------------------------------------------- = */
                /* =       취소 실패 결과 처리                                                  = */
                /* = -------------------------------------------------------------------------- = */
                else
                {
                    return new WP_Error('kcp_refund_error', __("알수 없는 이유로 환불 할수 없습니다.", GNUPAY_NAME) );
                }
            }

            // locale 설정 초기화
            setlocale(LC_CTYPE, '');
            return true;

        } catch ( Exception $e ) {

			return new WP_Error( 'kcp_refund_error', $e->getMessage() );

        }

		return false;

    }

    public function order_kcp_refund( $order_id, $amount = null, $reason = '' ) { //환불관련 ( 주문건을 환불처리 합니다. )

        $config = $this->config;
        $order = wc_get_order( $order_id );

        try {
            include_once(GNUPAY_KCP_PATH.'kcp/settle_kcp.inc.php');
            require_once(GNUPAY_KCP_PATH.'kcp/pp_ax_hub_lib.php');

            // locale ko_KR.euc-kr 로 설정
            setlocale(LC_CTYPE, 'ko_KR.euc-kr');

            $c_PayPlus = new C_PP_CLI;

            $c_PayPlus->mf_clear();

            $tno = get_post_meta( $order_id, '_od_tno', true );

            if( ! $tno ){
                return new WP_Error('kcp_refund_error', __('pg 거래번호가 저장되지 않았거나 없습니다.', GNUPAY_NAME));
            }

            $tran_cd = '00200000';
            $g_conf_log_dir = '';
            $g_conf_home_dir  = GNUPAY_KCP_PATH.'/kcp';
            $g_conf_key_dir   = '';

            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
            {
                $g_conf_log_dir   = sir_get_upload_path().'/log/refund';
                $g_conf_key_dir   = GNUPAY_KCP_PATH.'kcp/bin/pub.key';
            }

            $g_conf_site_cd  = $config['de_kcp_mid'];

            if (preg_match("/^T000/", $g_conf_site_cd) || $config['de_card_test']) {
                $g_conf_gw_url  = "testpaygw.kcp.co.kr";
            } else {
                $g_conf_gw_url  = "paygw.kcp.co.kr";
            }

            if( !$reason ){     //변경 사유가 없다면
                $reason = is_admin() ? __('쇼핑몰 운영자 승인 취소', GNUPAY_NAME) : __('사용자 취소', GNUPAY_NAME);
            }

            $cancel_msg = gp_iconv_euckr($reason);

            $cust_ip = $_SERVER['REMOTE_ADDR'];
            $bSucc_mod_type = "STSC";

            $c_PayPlus->mf_set_modx_data( "tno",      $tno                         );  // KCP 원거래 거래번호
            $c_PayPlus->mf_set_modx_data( "mod_type", $bSucc_mod_type              );  // 원거래 변경 요청 종류
            $c_PayPlus->mf_set_modx_data( "mod_ip",   $cust_ip                     );  // 변경 요청자 IP
            $c_PayPlus->mf_set_modx_data( "mod_desc", $cancel_msg );  // 변경 사유

            $c_PayPlus->mf_do_tx( $tno,  $g_conf_home_dir, $g_conf_site_cd,
                                  $g_conf_site_key,  $tran_cd,    "",
                                  $g_conf_gw_url,  $g_conf_gw_port,  "payplus_cli_slib",
                                  isset($order_id) ? $order_id : '', $cust_ip, "3" ,
                                  0, 0, $g_conf_key_dir, $g_conf_log_dir);

            $res_cd  = $c_PayPlus->m_res_cd;
            $res_msg = $c_PayPlus->m_res_msg;

            if($res_cd != '0000') {
                $pg_res_cd = $res_cd;
                $pg_res_msg = gp_iconv_utf8($res_msg);

                return new WP_Error( 'kcp_refund_error', $res_cd.' : '.$pg_res_msg );
            }

            $current_user = wp_get_current_user();
            $order->add_order_note( sprintf(__( '%s 님의 요청으로 인해 %s 이 환불되었습니다.', GNUPAY_NAME ), 
                $current_user->user_login.' ( '.$current_user->ID.' ) ',
                wc_price($amount)
            ) );

            // locale 설정 초기화
            setlocale(LC_CTYPE, '');

            return true;

        } catch ( Exception $e ) {

			return new WP_Error( 'kcp_refund_error', $e->getMessage() );

        }

		return false;

    }


//includes/class-wc-form-handler.php    를 볼것

    public function kcp_pay_check(){

        //체크아웃일때만 작동
        if( !is_checkout() )
            return;

        //요청이 있을때 동작합니다.
        if( isset($_REQUEST['checkout_nonce']) && wp_verify_nonce( $_REQUEST['checkout_nonce'], 'kcp_form_nonce' ) ){


            $pay_ids = gnupay_kcp_get_settings('pay_ids');
            $config = $this->config;
            $order_id = isset($_POST['ordr_idxx']) ? absint($_POST['ordr_idxx']) : '';
            $good_mny = isset($_POST['good_mny']) ? absint($_POST['good_mny']) : '';
            $pid = $payment_method = get_post_meta( $order_id, '_payment_method', true );
            $payment_title = get_post_meta( $order_id, '_payment_method_title', true );
            $site_cd = isset($_POST['site_cd']) ? sanitize_text_field($_POST['site_cd']) : '';

            // Prevent timeout
            @set_time_limit(0);

            $params = array();
            $param_array = array('pay_method', 'ret_pay_method', 'use_pay_method', 'escw_yn', 'payco_direct');

            foreach( $param_array as $v ){
                $params[$v] = isset($_POST[$v]) ? sanitize_text_field($_POST[$v]) : '';
            }

            extract( $params );

            if( !$order_id || !$pid ) return;    //order_id 가 존재하지 않으면 실행하지 않습니다.
            
            $real_paymethod = !empty( $payco_direct ) ? 'easy' : $pay_method;

            if( $real_paymethod == 'easy' ){
                $_POST['od_settle_case'] = 'easypayment';
            }

            if( $real_paymethod && $method_check = gnupay_kcp_payment_check( $order_id, $real_paymethod, $payment_method, $payment_title, $pay_ids ) ){      //payment_method 가 변했는지 체크
                
                if( $method_check['change'] ){
                    $pid = $payment_method = $method_check['payment_method'];
                    $payment_title = $method_check['payment_title'];
                }

            }

            if( ! in_array($pid, $pay_ids) ) return;     //pid가 해당 사당이 없으면

            $pay_options = get_option( $this->plugin_id . $pid . '_settings' );

            $order = wc_get_order( $order_id );

            //주문한 금액의 가격을 구합니다.

            $total_price = $order->get_total();

            if( $total_price != $good_mny ){    //결제한 금액이 틀리다면
                wp_die(__('금액이 틀립니다.', GNUPAY_NAME));
            }

            if( GNUPAY_KCP_MOBILE && 'return' == $_REQUEST['kcppay'] ){    //모바일일 경우
                include_once(GNUPAY_KCP_PATH.'kcp/m_order_approval_form.php');
            }

            include_once(GNUPAY_KCP_PATH.'kcp/pp_ax_hub.php');

            if( $payment_method == $pay_ids['bank'] ){ //계좌이체
                $save_data = array(
                        'od_receipt_price'  =>  $amount,
                        'od_receipt_time'   =>  preg_replace("/([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})/", "\\1-\\2-\\3 \\4:\\5:\\6", $app_time),
                        'od_deposit_name'   =>  $od_name,
                        'od_bank_account'   =>  $bank_name,
                        'pg_price'  =>  $amount,
                    );
            } else if ( $payment_method == $pay_ids['vbank'] ) {    //가상계좌
                $save_data = array(
                        'od_receipt_price'  =>  0,
                        'od_receipt_time'   =>  '',
                        'od_bank_account'   =>  iconv("cp949", "utf-8", $bankname).' '.$account,
                        'od_deposit_name'   =>  iconv("cp949", "utf-8", $depositor),
                        'pg_price'  =>  $amount,
                    );
            } else if ( $payment_method == $pay_ids['phone'] ){ //휴대폰
                $save_data = array(
                        'od_receipt_price'  =>  $amount,
                        'od_receipt_time'   =>  preg_replace("/([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})/", "\\1-\\2-\\3 \\4:\\5:\\6", $app_time),
                        'od_bank_account'   =>  $commid . ($commid ? ' ' : '').$mobile_no,
                        'od_deposit_name'   =>  '',
                        'pg_price'  =>  $amount,
                    );
            } else if ( $payment_method == $pay_ids['card'] ){ //신용카드
                $save_data = array(
                        'od_app_no'  =>  $app_no,
                        'od_receipt_price'  =>  $amount,
                        'od_receipt_time'   =>  preg_replace("/([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})/", "\\1-\\2-\\3 \\4:\\5:\\6", $app_time),
                        'od_bank_account'   =>  $card_name,
                        'od_deposit_name'   =>  '',
                        'pg_price'  =>  $amount,
                    );
            } else if ( $payment_method == $pay_ids['easy'] ){  //간편결제
                $save_data = array(
                        'od_app_no'  =>  $app_no,
                        'od_receipt_price'  =>  $amount,
                        'od_receipt_time'   =>  preg_replace("/([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})/", "\\1-\\2-\\3 \\4:\\5:\\6", $app_time),
                        'od_bank_account'   =>  $card_name,
                        'od_deposit_name'   =>  '',
                        'pg_price'  =>  $amount,
                    );
            }

            // 주문금액과 결제금액이 일치하는지 체크
            if(isset($tno) && !empty($tno)) {
                if((int)$total_price !== (int)$amount) {

                    $cancel_msg = __('결제금액 불일치', GNUPAY_NAME);

                    //include GNUPAY_KCP_PATH.'kcp/pp_ax_hub_cancel.php';

                    wp_die("Receipt Amount Error");

                }
            }

            if( empty($pay_options['de_pay_complete_status']) ){    //지정한 값이 없다면 default 값으로 넣어준다.
                $pay_options['de_pay_complete_status'] = 'wc-processing';

                if( $pay_ids['vbank'] == $pid ){    //가상계좌이면
                    $pay_options['de_pay_complete_status'] = 'wc-pending';
                }
            }

            $msg = __(sprintf('%s 로 처리되었습니다.', $pay_options['title'] ), GNUPAY_NAME);

            // Mark as on-hold (we're awaiting the payment)
            $order->update_status( sir_get_order_status($pay_options['de_pay_complete_status']), __( 'Awaiting process payment', 'woocommerce' ) );

            $current_user = wp_get_current_user();
            $user_name = $current_user->ID ? $current_user->user_login.' ( '.$current_user->ID.' ) ' : __('비회원', GNUPAY_NAME);

            if( $pay_ids['vbank'] == $pid ){    //가상계좌이면

                $order->add_order_note( sprintf(__( '%s 님이 %s로 주문하였습니다.', GNUPAY_NAME ), 
                    $user_name,
                    $payment_title ? esc_html($payment_title) : esc_html( $payment_method ),
                    $good_mny
                ) );

            } else {    //그 외에는 (신용카드, 계좌이체, 휴대폰, 간편결제)

                $order->add_order_note( sprintf(__( '%s 님이 %s 가격 %s 결제 하였습니다.', GNUPAY_NAME ), 
                    $user_name,
                    $payment_title ? esc_html($payment_title) : esc_html( $payment_method ),
                    wc_price($good_mny)
                ) );

            }
            
            // 복합과세 금액
            $od_tax_mny = round($amount / 1.1);
            $od_vat_mny = $amount - $od_tax_mny;
            $od_free_mny = 0;

            if( wc_tax_enabled() && $config['de_tax_flag_use'] ) {

                if( isset($_POST['comm_tax_mny']) )
                    $od_tax_mny = (int)$_POST['comm_tax_mny'];
                if( isset($_POST['comm_vat_mny']) )
                    $od_vat_mny = (int)$_POST['comm_vat_mny'];
                if( isset($_POST['comm_free_mny']) )
                    $od_free_mny = (int)$_POST['comm_free_mny'];

                update_post_meta($order_id, '_od_tax_flag', 1);   //복합과세로설정
            }

            update_post_meta($order_id, '_od_pg', 'kcp');   //결제 pg사를 저장
            update_post_meta($order_id, '_od_pay_method', $pay_method);   //결제 pg사를 저장
            update_post_meta($order_id, '_od_tno', isset($tno) ? $tno : '');   //결제 pg사를 주문번호
            update_post_meta($order_id, '_od_app_no', isset($app_no) ? $app_no : '');   //결제 승인 번호

            if ( $payment_method == $pay_ids['vbank'] ){   //가상계좌이면
                update_post_meta($order_id, '_od_receipt_price', 0);
            } else {
                update_post_meta($order_id, '_od_receipt_price', isset($amount) ? $amount : 0);   //결제금액
            }

            update_post_meta($order_id, '_od_tax_mny', $od_tax_mny);   //
            update_post_meta($order_id, '_od_vat_mny', $od_vat_mny);   //
            update_post_meta($order_id, '_od_free_mny', $od_free_mny);   //

            if( gnupay_kcp_order_test($site_cd, $config) ){
                update_post_meta($order_id, '_od_test', 1);   //test 결제이면
            }

            if( $escw_yn == 'Y' ){
                update_post_meta($order_id, '_od_escrow', 1);   //에스크로 결제시
            }

            if ( $payment_method == $pay_ids['vbank'] ){   //가상계좌이면
                update_post_meta($order_id, '_od_bankname', iconv("cp949", "utf-8", $bankname));   // 입금할 은행 이름
                update_post_meta($order_id, '_od_depositor', iconv("cp949", "utf-8", $depositor));   // 입금할 계좌 예금주
                update_post_meta($order_id, '_od_account', $account);   // 입금할 계좌 번호
                update_post_meta($order_id, '_od_va_date', $va_date);   // 가상계좌 입금마감시간
            }

            WC()->session->set( 'gp_kcp_'.$order_id , true );

            //주문이 끝나면
            //$order->payment_complete();

            // Reduce stock levels
            $order->reduce_order_stock();       //재고 처리

            // Remove cart
            WC()->cart->empty_cart();   //장바구니 삭제

            $return_url = $this->get_return_url( $order );

            gp_goto_url($return_url);
            exit;
        }

    }

    public function kcp_config(){
        $args = wp_parser_args( $this->config, array(

            ));
        return $args;
    }

    public function kcp_pay_load( $checkout ){
        global $wp, $woocommerce;
        
        //체크아웃일때만 작동
        if( !is_checkout() )
            return;

        if( GNUPAY_KCP()->is_kcp_pay_load ){   //한번만 실행되게 한다.
            return;
        }

        GNUPAY_KCP()->is_kcp_pay_load = true;

        $config = $this->config;
        $this->checkout = $checkout;

        $goods = '';
        $goods_count = -1;
        $good_info = '';

        $comm_tax_mny = 0; // 과세금액
        $comm_vat_mny = 0; // 부가세
        $comm_free_mny = 0; // 면세금액
        $tot_tax_mny = 0;

        $send_cost  = 0;    //배송비

        $i = 0;

        foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
            if( empty($cart_item) ) continue;

            $_product        = wc_get_product( $cart_item['product_id'] );
            $product_title = $_product->get_title();
            $quantity = $cart_item['quantity'];
            $line_total = $cart_item['line_total'];
			$_tax_stats = $_product->get_tax_status();

            if( $quantity > 1 ){
                $product_title .= ' x '.$quantity;
            }

            if (!$goods){
                $goods = preg_replace("/\'|\"|\||\,|\&|\;/", "", $product_title);
            }

            $goods_count++;

            // 에스크로 상품정보
            if($config['de_escrow_use']) {
                if ($i>0)
                    $good_info .= chr(30);
                $good_info .= "seq=".($i+1).chr(31);
                $good_info .= "ordr_numb=#order_replace#_".sprintf("%04d", $i).chr(31);
                $good_info .= "good_name=".addslashes($product_title).chr(31);
                $good_info .= "good_cntx=".$quantity.chr(31);
                $good_info .= "good_amtx=".$line_total.chr(31);
            }

            // 복합과세금액
            if( wc_tax_enabled() && $config['de_tax_flag_use']) {
				if($_tax_stats == 'none'){
					$comm_free_mny += $line_total;;
				} else {
					$tot_tax_mny += $line_total;
				}
            }

            $i++;
        }

        if ($goods_count) $goods = sprintf(__('%s 외 %d 건', GNUPAY_NAME), $goods, $goods_count);

        // 복합과세처리
        if( wc_tax_enabled() && $config['de_tax_flag_use']) {
            $comm_tax_mny = round( WC()->cart->cart_contents_total + WC()->cart->shipping_total - $comm_free_mny ); // 과세금액 ( 카트 )
            $comm_vat_mny = round(WC()->cart->get_taxes_total(false, false));    //부가세( 카트 )
        }

        $info = array(
                'od_id' =>  '',
                'goods' =>  $goods,
                'tot_price' =>  WC()->cart->total,
                'goods_count'   =>  ($goods_count + 1),
                'good_info'     =>  $good_info,
                'comm_tax_mny'      =>  $comm_tax_mny,
                'comm_vat_mny'      =>  $comm_vat_mny,
                'comm_free_mny'  =>  $comm_free_mny,
            );

        GNUPAY_KCP()->goodsinfo=$info;

        require_once(GNUPAY_KCP_PATH.'kcp/settle_kcp.inc.php');
        require_once(GNUPAY_KCP_PATH.'kcp/orderform.1.php');
        
        // 결제대행사별 코드 include (결제대행사 정보 필드)
        require_once(GNUPAY_KCP_PATH.'kcp/orderform.2.php');

        wp_enqueue_script('jquery');
        wp_register_script('gnupay_kcp_pay_js', GNUPAY_KCP_URL.'js/kcp_pay.js', array('jquery'), GNUPAY_VERSION , true);

        // Localize the script with new data
        $translation_array = array(
            'is_mobile'=>GNUPAY_KCP_MOBILE,
            'ajaxurl'=>admin_url('admin-ajax.php'),
            'kcp_approval_url'=>add_query_arg( array('action'=>'kpay_ajax'), get_permalink() ),
        );

        if ( ! empty( $wp->query_vars['order-pay'] ) ) {
            $translation_array['order_id'] = $wp->query_vars['order-pay'];
        }

        wp_localize_script( 'gnupay_kcp_pay_js', 'gnupay_kcp_object', $translation_array );

        wp_enqueue_script('gnupay_kcp_pay_js');
    }

    public function kcp_checkout_order(){
        global $wp, $woocommerce;
        
        $config = $this->config;
    }

    public function thankyou_page(){
        echo apply_filters('gnupay_kcp_thankyou_msg', __('결제해 주셔서 감사합니다.', GNUPAY_NAME), $this->id );
    }

    public function init_form_fields(){
        $this->form_fields = array(
            'enabled' => array(
                'title' => __( 'Enable/Disable', 'woocommerce' ),
                'type' => 'checkbox',
                'label' => __( 'KCP 카드결제를 활성화합니다.', GNUPAY_NAME ),
                'default' => ''
            ),
            'title' => array(
                'title' => __( 'Title', 'woocommerce' ),
                'type' => 'text',
                'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
                'default' => __( 'KCP 카드결제', GNUPAY_NAME ),
                'desc_tip'      => true,
            ),
            'description' => array(
                'title' => __( 'Description', 'woocommerce' ),
                'type' => 'textarea',
                'default' => ''
            ),
            'instructions' => array(
                'title' => __('Instructions', 'woocommerce'),
                'type' => 'textarea',
                'description' => __('Instructions that will be added to the thank you page.', 'woocommerce'),
                'default' => '',
                'desc_tip' => true,
            ),
            'de_card_noint_use' => array(
                'title' => __( '신용카드 무이자할부사용', GNUPAY_NAME ),
                'type' => 'select',
                'options'	=> array(
                    '0'			=> __( '사용안함', GNUPAY_NAME ),
                    '1'			=> __( '사용', GNUPAY_NAME ),
                ),
                'description'   =>  __( '주문시 신용카드 무이자할부를 가능하게 할것인지를 설정합니다.<br>사용으로 설정하시면 PG사 가맹점 관리자 페이지에서 설정하신 무이자할부 설정이 적용됩니다.<br>사용안함으로 설정하시면 PG사 무이자 이벤트 카드를 제외한 모든 카드의 무이자 설정이 적용되지 않습니다.', GNUPAY_NAME ),
            ),
            /*
            'de_iche_use' => array(
                'title' => __( '계좌이체 결제사용', GNUPAY_NAME ),
                'type' => 'select',
                'options'	=> array(
                    '0'			=> __( '사용안함', GNUPAY_NAME ),
                    '1'			=> __( '사용', GNUPAY_NAME ),
                ),
            ),
            'de_vbank_use' => array(
                'title' => __( '가상계좌 결제사용', GNUPAY_NAME ),
                'type' => 'select',
                'options'	=> array(
                    '0'			=> __( '사용안함', GNUPAY_NAME ),
                    '1'			=> __( '사용', GNUPAY_NAME ),
                ),
            ),
            'de_hp_use' => array(
                'title' => __( '휴대폰 결제사용', GNUPAY_NAME ),
                'type' => 'select',
                'options'	=> array(
                    '0'			=> __( '사용안함', GNUPAY_NAME ),
                    '1'			=> __( '사용', GNUPAY_NAME ),
                ),
            ),
            'de_card_use' => array(
                'title' => __( '신용카드 결제사용', GNUPAY_NAME ),
                'type' => 'select',
                'options'	=> array(
                    '0'			=> __( '사용안함', GNUPAY_NAME ),
                    '1'			=> __( '사용', GNUPAY_NAME ),
                ),
            ),
            'de_easy_pay_use' => array(
                'title' => __( 'PG사 간편결제 버튼 사용', GNUPAY_NAME ),
                'type' => 'select',
                'options'	=> array(
                    '0'			=> __( '노출안함', GNUPAY_NAME ),
                    '1'			=> __( '노출함', GNUPAY_NAME ),
                ),
            ),
            */
            'de_taxsave_use'    =>  array(
                'title' => __( '현금영수증 발급사용', GNUPAY_NAME ),
                'type' => 'select',
                'options'	=> array(
                    '0'			=> __( '사용안함', GNUPAY_NAME ),
                    '1'			=> __( '사용', GNUPAY_NAME ),
                ),
                'description'   =>  __( '관리자는 설정에 관계없이 주문 보기에서 발급이 가능합니다.<br>현금영수증 발급 취소는 PG사에서 지원하는 현금영수증 취소 기능을 사용하시기 바랍니다.', GNUPAY_NAME ),
            ),
            'de_kcp_mid'     =>  array(
                'title' => __( 'KCP SITE CODE', GNUPAY_NAME ),
                'type' => 'input',
                'description'   =>  __( 'KCP 에서 받은 SR 로 시작하는 영대문자, 숫자 혼용 총 5자리 중 SR 을 제외한 나머지 3자리 SITE CODE 를 입력하세요.<br>만약, 사이트코드가 SR로 시작하지 않는다면 KCP에 사이트코드 변경 요청을 하십시오. 예) SR9A3', GNUPAY_NAME ),
				'custom_attributes' => array(
					'data-placeholder' => __('SR 을 제외한 나머지 3자리 SITE CODE 를 입력하세요.', GNUPAY_NAME )
				)
            ),
            'de_kcp_site_key'     =>  array(
                'title' => __( 'KCP SITE KEY', GNUPAY_NAME ),
                'type' => 'input',
                'description'   =>  __( '25자리 영대소문자와 숫자 - 그리고 _ 로 이루어 집니다. SITE KEY 발급 KCP 전화: 1544-8660<br>예) 1Q9YRV83gz6TukH8PjH0xFf__', GNUPAY_NAME ),
				'custom_attributes' => array(
					'data-placeholder' => __('SITE KEY 를 입력해주세요.', GNUPAY_NAME )
				)
            ),
            'de_escrow_use'     =>  array(
                'title' => __( '에스크로 사용', GNUPAY_NAME ),
                'type' => 'select',
                'options'	=> array(
                    '0'			=> __( '일반결제 사용', GNUPAY_NAME ),
                    '1'			=> __( '에스크로결제 사용', GNUPAY_NAME ),
                ),
                'description'   =>  __( '에스크로 결제를 사용하시려면, 반드시 결제대행사 상점 관리자 페이지에서 에스크로 서비스를 신청하신 후 사용하셔야 합니다.<br>에스크로 사용시 배송과의 연동은 되지 않으며 에스크로 결제만 지원됩니다.', GNUPAY_NAME ),
            ),
            'de_card_test'     =>  array(
                'title' => __( '결제테스트', GNUPAY_NAME ),
                'type' => 'select',
                'options'	=> array(
                    '0'			=> __( '실결제', GNUPAY_NAME ),
                    '1'			=> __( '테스트 결제', GNUPAY_NAME ),
                ),
                'default' => '1',
            ),
            'de_tax_flag_use'     =>  array(
                'title' => __( '복합과세 결제', GNUPAY_NAME ),
                'type' => 'checkbox',
                'label' => __( '복합결제 사용', GNUPAY_NAME ),
                'default' => 'no',
                'description'   =>  __( '복합과세(과세, 비과세) 결제를 사용하려면 체크하십시오.<br >( 우커머스 -> 설정 -> 세금 옵션 -> 세금 활성화 도 같이 체크되어야 합니다. )<br>복합과세 결제를 사용하기 전 PG사에 별도로 결제 신청을 해주셔야 합니다. 사용시 PG사로 문의하여 주시기 바랍니다.', GNUPAY_NAME ),
            ),
            'de_refund_after_status'     =>  array(
                'title' => __( '환불 후 주문상태', GNUPAY_NAME ),
                'description'   =>  __( '환불 후 주문상태를 지정합니다.', GNUPAY_NAME ),
                'type'              => 'select',
                'class'             => 'wc-enhanced-select',
                'css'               => 'width: 450px;',
                'label' => __( '환불 후 주문상태', GNUPAY_NAME ),
                'default' => 'wc-cancelled',
                'options' => wc_get_order_statuses(),
				'desc_tip'          => true,
				'custom_attributes' => array(
					'data-placeholder' => __('환불 후 주문상태를 선택해 주세요.', GNUPAY_NAME )
				)
            ),
            'de_pay_complete_status'     =>  array(
                'title' => __( '사용자 결제 후 주문상태', GNUPAY_NAME ),
                'description'   =>  __( 'KCP 결제시 사용자의 주문상태를 지정합니다.', GNUPAY_NAME ),
                'type'              => 'select',
                'class'             => 'wc-enhanced-select',
                'css'               => 'width: 450px;',
                'label' => __( '사용자 결제 후 주문상태', GNUPAY_NAME ),
                'default' => 'wc-processing',
                'options' => wc_get_order_statuses(),
				'desc_tip'          => true,
				'custom_attributes' => array(
					'data-placeholder' => __('선택해 주세요.', GNUPAY_NAME )
				)
            ),
            'de_cancel_possible_status'     =>  array(
                'title' => __( '주문취소 가능한 상태 ( 사용자 )', GNUPAY_NAME ),
                'description'   =>  __( 'KCP 결제시 사용자가 주문취소 할 수 있는 상태를 지정합니다.', GNUPAY_NAME ),
                'type'              => 'multiselect',
                'class'             => 'wc-enhanced-select',
                'css'               => 'width: 450px;',
                'label' => __( '주문취소 가능한 상태 ( 사용자 )', GNUPAY_NAME ),
                'default' => '',
                'options' => wc_get_order_statuses(),
				'desc_tip'          => true,
				'custom_attributes' => array(
					'data-placeholder' => __('주문취소 가능한 상태를 선택해 주세요.', GNUPAY_NAME )
				)
            ),
        );
    }

    public function pay_bin_check(){

        if( ! is_admin() ){
            return;
        }

        if( GNUPAY_KCP()->credentials_check ){      //중복방지
            return;
        }
    
        GNUPAY_KCP()->credentials_check = true;     //중복방지

        if(!extension_loaded('openssl')) {
            wc_enqueue_js('alert("'.__("PHP openssl 확장모듈이 설치되어 있지 않습니다.\\n모바일 쇼핑몰 결제 때 사용되오니 openssl 확장 모듈을 설치하여 주십시오.", GNUPAY_NAME).'");');
        }

        if(!extension_loaded('soap') || !class_exists('SOAPClient')) {
            wc_enqueue_js('alert("'.__("PHP SOAP 확장모듈이 설치되어 있지 않습니다.\\n모바일 쇼핑몰 결제 때 사용되오니 SOAP 확장 모듈을 설치하여 주십시오.", GNUPAY_NAME).'");');
        }

        $is_linux = true;
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
            $is_linux = false;

        $exe = 'kcp/bin/';

        if($is_linux) {
            if(PHP_INT_MAX == 2147483647) // 32-bit
                $exe .= 'pp_cli';
            else
                $exe .= 'pp_cli_x64';
        } else {
            $exe .= 'pp_cli_exe.exe';
        }

        if( $error = gp_module_exec_check(GNUPAY_KCP_PATH.$exe, 'pp_cli') ){
            
            GNUPAY_KCP()->errs[] = str_replace(array("\r\n","\r","\n","\\r","\\n","\\r\\n"), "<br/>", $error);

        }

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

        $res = wp_parse_args(array(
			'result'    => 'success',
            'order_id'  =>  $order->id,
            'order_key' =>  $order->order_key,
			'redirect'  => $this->get_return_url( $order )
		), gnupay_kcp_process_payment($order, $this->config));

		return $res;

	}

}   //end class

endif;  //Class exists WC_Payment_Gateway end if
?>