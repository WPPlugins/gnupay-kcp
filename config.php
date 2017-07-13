<?php
if( ! defined( 'ABSPATH' ) ) exit;

Class GNUPAY_KCP_WOO_CONSTANTS {
    public function __construct() {
        define( 'GNUPAY_VERSION', '1.2' );
        define( 'GNUPAY_NAME', 'gnupay-kcp' );
        define( 'GNUPAY_KCP_ORDER_TMP', '_order_tmp_kcp' );
        add_action( 'init', array( $this, 'init' ), 1 );
        add_action( 'init', array( $this, 'init_after' ),21 );
    }

    public function init(){
        //중복 선언 방지
        if ( defined('GNUPAY_KCP_DIR_URL') ) return;

        // 함수 호출을 처음부터 호출하면 에러나기 때문에 적당한 때에 호출한다.
        // 경로 상수
        define('GNUPAY_KCP_URL',  plugin_dir_url ( __FILE__ ) );
        define('GNUPAY_KCP_PATH', plugin_dir_path( __FILE__ ) );
        define('GNUPAY_KCP_SERVER_TIME',    current_time( 'timestamp' ) );
        define('GNUPAY_KCP_MOBILE',    wp_is_mobile() );
        //define('GNUPAY_KCP_MOBILE',    1 );
    }

    public function init_after(){
        //중복 선언 방지
        if ( defined('GNUPAY_KCP_BILL_RECEIPT_URL') ) return;

        $card_options = gp_kcp_get_card_options();

        // 매출전표 url 설정
        if($card_options['de_card_test']) {
            //테스트인 경우
            define('GNUPAY_KCP_BILL_RECEIPT_URL', 'https://testadmin8.kcp.co.kr/assist/bill.BillActionNew.do?cmd=');
            define('GNUPAY_KCP_CASH_RECEIPT_URL', 'https://testadmin8.kcp.co.kr/Modules/Service/Cash/Cash_Bill_Common_View.jsp?term_id=PGNW');
        } else {
            define('GNUPAY_KCP_BILL_RECEIPT_URL', 'https://admin8.kcp.co.kr/assist/bill.BillActionNew.do?cmd=');
            define('GNUPAY_KCP_CASH_RECEIPT_URL', 'https://admin.kcp.co.kr/Modules/Service/Cash/Cash_Bill_Common_View.jsp?term_id=PGNW');
        }

    }
}

new GNUPAY_KCP_WOO_CONSTANTS();
?>