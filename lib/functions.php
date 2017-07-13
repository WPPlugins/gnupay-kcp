<?php
if( ! defined( 'GNUPAY_NAME' ) ) exit; // 개별 페이지 접근 불가

if( ! function_exists('gp_get_stype_names') ){
    function gp_get_stype_names($str, $index=0, $array_return=false){
        $tmps = array_keys(gp_get_key_localize());

        if( $str ){
            if( is_array($str) ){   //배열이면
                $result = array_intersect($str, $tmps);    //배열의 교집합
                if( $array_return ){
                    return $result;
                }
                return "'".implode("', '", $result)."'";
            }

            if( in_array($str, $tmps) ){
                return $str;
            }
        }

        return $tmps[$index];
    }
}

if( ! function_exists('gp_get_key_localize') ){
    function gp_get_key_localize(){
        $keys = array(
            'shop'  => __('상점', GNUPAY_NAME),  //상점
            'checkout'  => __('결제', GNUPAY_NAME),  //결제
            'cart'  => __('장바구니', GNUPAY_NAME),  //장바구니
            'my_page'  => __('마이페이지', GNUPAY_NAME),  //마이페이지
            'shopping'  =>  __('쇼핑', GNUPAY_NAME),  //쇼핑
            'order' =>  __('주문', GNUPAY_NAME),    //주문
            'deposit'   =>  __('입금', GNUPAY_NAME),  //입금
            'prepare'   =>  __('준비', GNUPAY_NAME), //준비
            'deliver'   =>  __('배송', GNUPAY_NAME),  //배송
            'complete'  =>  __('완료', GNUPAY_NAME), //완료
            'cancel'    =>  __('취소', GNUPAY_NAME), //취소
            'return'    =>  __('반품', GNUPAY_NAME),   //반품
            'soldout'   =>  __('품절', GNUPAY_NAME),  //품절
            'delete'   =>  __('삭제', GNUPAY_NAME),  //삭제
            'allmembers'    =>  __('전체회원', GNUPAY_NAME), //전체회원
            'banktransfer'  =>  __('무통장', GNUPAY_NAME), //무통장
            'virtualaccount'   => __('가상계좌', GNUPAY_NAME), //가상계좌
            'accounttransfer'  => __('계좌이체', GNUPAY_NAME),  //계좌이체
            'phonepayment'  => __('휴대폰결제', GNUPAY_NAME),    //휴대폰결제
            'creditcard'    => __('신용카드', GNUPAY_NAME),    //신용카드
            'easypayment'   =>  __('간편결제', GNUPAY_NAME),    //간편결제
            'KAKAOPAY'  =>  __('카카오페이', GNUPAY_NAME), //카카오페이
        );

        return $keys;
    }
}

if( ! function_exists('gp_check_array_sanitize') ){
    //sanitize_text_field check array
    function gp_check_array_sanitize($msg){

        if( is_array($msg) ){
            return array_map( 'sanitize_text_field', $msg );
        }
        return sanitize_text_field($msg);
    }
}

if( ! function_exists('gp_alert') ){
    // 경고메세지를 경고창으로
    function gp_alert($msg='', $url='', $referer_check=false)
    {
        if (!$msg) $msg = __('올바른 방법으로 이용해 주십시오.', GNUPAY_NAME);

        if ( !$url && $referer_check ){
            $url = wp_get_referer();
        }

        $html = '<meta charset="utf-8">';
        $html .= '<script type="text/javascript">alert("'.$msg.'");';
        if (!$url){
            $html .= 'history.go(-1);';
        }
        $html .= "</script>";

        do_action( 'gp_alert', $msg, $url );
        $html = apply_filters( 'gp_alert', $html, $url );

        echo $html;

        if ($url){
            gp_goto_url($url, true);
        }
        exit;
    }
}

if( ! function_exists('gp_goto_url') ){
    function gp_goto_url($url, $noscript=false)
    {
        $url = str_replace("&amp;", "&", $url);

        if (!headers_sent() && !$noscript)
            header('Location: '.$url);
        else {
            echo '<script>';
            echo 'location.replace("'.$url.'");';
            echo '</script>';
            echo '<noscript>';
            echo '<meta http-equiv="refresh" content="0;url='.$url.'" />';
            echo '</noscript>';
        }
        exit;
    }
}

if( !function_exists('gp_iconv_euckr') ){
    function gp_iconv_euckr(){
        // CHARSET 변경 : utf-8 -> euc-kr
        return @iconv('utf-8', 'euc-kr', $str);
    }
}

if( !function_exists('gp_iconv_utf8') ){
    // CHARSET 변경 : euc-kr -> utf-8
    function gp_iconv_utf8($str)
    {
        return @iconv('euc-kr', 'utf-8', $str);
    }
}

function gnupay_kcp_get_settings($options='gateways'){

    return GNUPAY_KCP_SETTING::getInstance()->get_options($options);

}

function kcp_is_option_enable(){

}

function gnupay_kcp_order_test($site_cd, $config){

    if( $site_cd == 'T0000' || $site_cd == 'T0007' ){   //테스트가 맞다면
        return true;
    }

    return false;
}

function sir_get_upload_path($type='dir'){
    $upload_dir = array('error'=>'error');
    try{
        $upload_dir = wp_upload_dir();
    } catch (Exception $e) {
    }
    $path = '';
    if( empty($upload_dir['error']) ){
        $path = apply_filters('sir_get_upload_'.$type, $upload_dir['base'.$type].'/kcp', $upload_dir);
    }
    return $path;
}

function sir_get_order_status($status){
    $status = preg_replace("/^wc-/i", "", trim($status));

    return $status;
}

function gnupay_kcp_get_gateways(){

    $payment_gateways = array();

    if ( WC()->payment_gateways() ) {
        $payment_gateways = WC()->payment_gateways->get_available_payment_gateways();
    }

    return $payment_gateways;
}

// 로그를 파일에 쓴다
function gnupay_kcp_write_log($file, $log) {
    $fp = fopen($file, "a+");
    ob_start();
    print_r($log);
    $msg = ob_get_contents();
    ob_end_clean();
    fwrite($fp, $msg);
    fclose($fp);
}

//가상계좌 통보 url 
function gnupay_kcp_get_vbankurl($pay_ids=array()){

    if( !$pay_ids ){
        $pay_ids = gnupay_kcp_get_settings('pay_ids');
    }
    
    return str_replace( 'https:', 'http:', add_query_arg( 'wc-api', $pay_ids['vbank'], home_url( '/' ) ) );
    //return add_query_arg(array('wc-api'=>$pay_ids['vbank']), site_url());
}

function gnupay_kcp_mobile_ajaxurl(){
    return str_replace( 'https:', 'http:', add_query_arg( 'wc-api', 'sir_kpay_ajax', home_url( '/' ) ) );
}

// 모바일 PG 주문 필드 생성
if( ! function_exists('gc_make_order_field') ){
    function gc_make_order_field($data, $exclude)
    {
        $field = '';

        foreach((array) $data as $key=>$value) {
            if(empty($value)) continue;

            if(in_array($key, $exclude))
                continue;

            if(is_array($value)) {
                foreach($value as $k=>$v) {
                    $field .= '<input type="hidden" name="'.$key.'['.$k.']" value="'.$v.'">'.PHP_EOL;
                }
            } else {
                $field .= '<input type="hidden" name="'.$key.'" value="'.$value.'">'.PHP_EOL;
            }
        }

        return $field;
    }
}

// 모바일 PG 주문 필드 생성
if( ! function_exists('gc_request_key_check') ){
    function gc_request_key_check($key, $type=''){
        if( $type == 'get' ){
            if( isset($_GET[$key]) && !empty($_GET[$key]) ){
                return sanitize_text_field($_GET[$key]);
            }
        } else if( $type == 'post' ) {
            if( isset($_POST[$key]) && !empty($_POST[$key]) ){
                return gp_check_array_sanitize($_POST[$key]);
            }
        } else {
            if( isset($_REQUEST[$key]) && !empty($_REQUEST[$key]) ){
                return gp_check_array_sanitize($_REQUEST[$key]);
            }
        }
        return '';
    }
}

if( ! function_exists('gnupay_kcp_process_payment') ){
    function gnupay_kcp_process_payment($order, $config){

        $res = array();
        $crr = array(
                'order_key',
                'payment_method',
                'payment_method_title',
                'billing_last_name',
                'billing_first_name',
                'billing_company',
                'billing_address_1',
                'billing_address_2',
                'billing_city',
                'billing_state',
                'billing_postcode',
                'billing_country',
                'billing_email',
                'billing_phone',
                'shipping_last_name',
                'shipping_first_name',
                'shipping_company',
                'shipping_address_1',
                'shipping_address_2',
                'shipping_city',
                'shipping_state',
                'shipping_postcode',
                'shipping_country',
                'shipping_email',
                'shipping_phone',
            );

        foreach($crr as $v){
            $res[$v] = isset($order->$v) ? $order->$v : '';
        }
        
        $res['order_id'] = $order->id;

        $goods = '';
        $goods_count = -1;
        $good_info = '';

        $comm_tax_mny = 0; // 과세금액
        $comm_vat_mny = 0; // 부가세
        $comm_free_mny = 0; // 면세금액
        $tot_tax_mny = 0;

        $send_cost  = 0;    //배송비

        $i = 0;

        foreach ( $order->get_items() as $item_key => $item ) {
            if( empty($item) ) continue;
            
            $_product        = wc_get_product( $item['product_id'] );
            $goods_count += (int) $item['qty'];
            $quantity = $item['qty'];

			$_tax_stats = $_product->get_tax_status();

            if (!$goods){
                $goods = preg_replace("/\'|\"|\||\,|\&|\;/", "", esc_attr($item['name']));
            }

            if( $quantity > 1 ){
                $goods .= ' x '.$quantity;
            }
            $line_total = $item['line_total'];

            // 에스크로 상품정보
            if(!empty($config['de_escrow_use'])) {
                if ($i>0)
                    $good_info .= chr(30);
                $good_info .= "seq=".($i+1).chr(31);
                $good_info .= "ordr_numb=#order_replace#_".sprintf("%04d", $i).chr(31);
                $good_info .= "good_name=".addslashes($goods).chr(31);
                $good_info .= "good_cntx=".$quantity.chr(31);
                $good_info .= "good_amtx=".$line_total.chr(31);
            }

            // 복합과세금액
            if( wc_tax_enabled() && !empty($config['de_tax_flag_use'])) {
				if($_tax_stats == 'none'){  //비과세이면
					$comm_free_mny += $line_total;
				} else {
					$tot_tax_mny += $line_total;
				}
            }

            $i++;
        }

        if ($goods_count) $goods = sprintf(__('%s 외 %d 건', GNUPAY_NAME), $goods, $goods_count);

        // 복합과세처리
        if( wc_tax_enabled() && !empty($config['de_tax_flag_use'])) {
            //이게 맞는지 나중에 다시다시 확인할것
            $comm_tax_mny = round($order->get_subtotal() - $comm_free_mny);
            $comm_vat_mny = $order->get_total_tax();
        }

        $res['goods'] = $goods;
        $res['tot_price'] = $order->get_total();
        $res['goods_count'] = $goods_count + 1;
        $res['good_info'] = $good_info;
        $res['comm_tax_mny'] = $comm_tax_mny;
        $res['comm_vat_mny'] = $comm_vat_mny;
        $res['comm_free_mny'] = $comm_free_mny;

        return $res;
    }
}

if( ! function_exists('gp_kcp_get_card_options') ){
    function gp_kcp_get_card_options(){

        $plugin_id='woocommerce_';
        $pay_ids = gnupay_kcp_get_settings('pay_ids');
        $gnupay_kcp_card_payname = $pay_ids['card'];
        $payment_options = get_option( $plugin_id . $gnupay_kcp_card_payname . '_settings', null );
        
        return $payment_options;
    }
}

if( ! function_exists('gnupay_kcp_get_config_payment') ){
    function gnupay_kcp_get_config_payment($order_id){

        $plugin_id='woocommerce_';

        $payment_method = get_post_meta( $order_id, '_payment_method', true );

        $pay_ids = gnupay_kcp_get_settings('pay_ids');
        $gnupay_kcp_card_payname = $pay_ids['card'];
        $payment_options = get_option( $plugin_id . $gnupay_kcp_card_payname . '_settings', null );

        $method_options = get_option( $plugin_id . $payment_method . '_settings', null );

        
        return wp_parse_args($method_options, $payment_options);
    }
}

// 인증, 결제 모듈 실행 체크
if( !function_exists('gp_module_exec_check') ){
    function gp_module_exec_check($exe, $type)
    {
        $output = '';
        $error = '';
        $is_linux = false;

        if(strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN'){
            $is_linux = true;
        }

        // 모듈 파일 존재하는지 체크
        if(!is_file($exe)) {
            $error = $exe.' 파일이 존재하지 않습니다.';
        } else {
            // 실행권한 체크
            
            if(!is_executable($exe)){
                @chmod($exe, 0755);
            }

            if(!is_executable($exe) && is_admin() ){
                
                $page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
                $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : '';
                $section = isset($_GET['section']) ? sanitize_text_field($_GET['section']) : '';

                if($page){

                    $add_q = $tab ? "&tab=".$tab : '';

                    if( $section ){
                        $add_q .= "&section=".$section;
                    }

                    GNUPAY_KCP()->credentials_url = $url = wp_nonce_url( admin_url( 'admin.php?page=' . $page. $add_q ) );
                    
                    $creds = false;

                    ob_start();
                    $creds = request_filesystem_credentials($url);
                    ob_end_clean();

                    if ( WP_Filesystem($creds) ) {
                        global $wp_filesystem;

                        if( method_exists($wp_filesystem, 'chmod') ){
                            $wp_filesystem->chmod( $exe, 0755 );
                        }
                    } else {
                        add_action('admin_footer', 'gp_admin_request_filesystem_credentials_modal', 1);
                        wp_enqueue_script( 'updates' );
                    }
                }
            }

            if(!is_executable($exe)) {
                //권한설정
                if($is_linux)
                    $error = str_replace('\\', '/' , $exe).'\n파일의 실행권한이 없습니다.\n\n연결 정보창이 뜨면 진행해 주시거나 또는, \n수동으로 chmod 755 '.basename($exe).' 과 같이 실행권한을 부여해 주십시오.';
                else
                    $error = str_replace('\\', '/' , $exe).'\n파일의 실행권한이 없습니다.\n\n'.basename($exe).' 파일에 실행권한을 부여해 주십시오.';
            } else {
                // 바이너리 파일인지
                if($is_linux) {
                    $search = false;
                    $isbinary = true;
                    $executable = true;

                    switch($type) {
                        case 'pp_cli':
                            exec($exe.' -h 2>&1', $out, $return_var);

                            if($return_var == 139) {
                                $isbinary = false;
                                break;
                            }

                            for($i=0; $i<count($out); $i++) {
                                if(strpos($out[$i], 'CLIENT') !== false) {
                                    $search = true;
                                    break;
                                }
                            }
                            break;
                    }

                    if(!$isbinary || !$search) {
                        $error = $exe.'\n파일을 바이너리 타입으로 다시 업로드하여 주십시오.';
                    }
                }
            }
        }

        if($error) {
            wc_enqueue_js("alert(\"$error\");");
            return $error;
        }

        return '';
    }
}

if( !function_exists('gp_admin_request_filesystem_credentials_modal') ){
    function gp_admin_request_filesystem_credentials_modal(){
        if( !is_admin() ){
            return;
        }

        if( $url = GNUPAY_KCP()->credentials_url ){
            $filesystem_method = get_filesystem_method();
            ob_start();
            $filesystem_credentials_are_stored = request_filesystem_credentials($url);
            ob_end_clean();
            $request_filesystem_credentials = ( $filesystem_method != 'direct' && ! $filesystem_credentials_are_stored );
            if ( ! $request_filesystem_credentials ) {
                return;
            }
        ?>
        <div id="request-filesystem-credentials-dialog" class="notification-dialog-wrap request-filesystem-credentials-dialog gp-credentials-dialog">
            <div class="notification-dialog-background"></div>
            <div class="notification-dialog" role="dialog" aria-labelledby="request-filesystem-credentials-title" tabindex="0">
                <div class="request-filesystem-credentials-dialog-content">
                    <?php request_filesystem_credentials( $url ); ?>
                <div>
            </div>
        </div>
        <script>
        jQuery(document).ready(function($) {
            wp.updates.requestForCredentialsModalOpen();

            $(document).on("click", '.gp-credentials-dialog [data-js-action="close"]', function(e){
                e.preventDefault();
                wp.updates.requestForCredentialsModalClose();
            });
        });
        </script>
        <?php
        }   //end if
    }
}

//http://stackoverflow.com/questions/3715264/how-to-handle-user-input-of-invalid-utf-8-characters
if( !function_exists('kcp_bad_codepoint') ){
    function kcp_bad_codepoint($string)
    {
        $result = array();

        foreach ((array) $string as $char)
        {
            $codepoint = unpack('N', iconv('UTF-8', 'UCS-4BE', $char));

            if (is_array($codepoint) && array_key_exists(1, $codepoint))
            {
                $result[] = sprintf('U+%04X', $codepoint[1]);
            }
        }

        return implode('', $result);
    }
}

if( !function_exists('gp_kcp_order_can_view') ){
    function gp_kcp_order_can_view($order_id){

        $is_can_view = false;

        // Check user has permission to edit
        if ( current_user_can( 'view_order', $order_id ) ) {
            $is_can_view = true;
        }

        if( $retrive_data = WC()->session->get( 'gp_kcp_'.$order_id ) ){
            $is_can_view = true;
        }

        return $is_can_view;
    }
}

if( ! function_exists('gnupay_kcp_payment_check') ){
    function gnupay_kcp_payment_check( $order_id, $real_pay, $payment_method, $payment_title, $pay_ids ){

        $real_pay = strtolower( $real_pay );
        $real_payment = '';

        switch( $real_pay ){
            case '100000000000' :   //신용카드
                $real_payment = $pay_ids['card'];
                break;
            case '010000000000' :     //계좌이체
                $real_payment = $pay_ids['bank'];
                break;
            case '000010000000' :     //휴대폰
                $real_payment = $pay_ids['phone'];
                break;
            case '001000000000' :     //가상계좌
                $real_payment = $pay_ids['vbank'];
                break;
            case 'easy' :   // 간편결제
                $real_payment = $pay_ids['easy'];
                break;
        }

        if( $real_payment && $real_payment != $payment_method ){    //이전것과 비교해서 틀리면

            $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
            $payment_method = $real_payment;

            // Update meta
            update_post_meta( $order_id, '_payment_method', $payment_method );

            $before_pay_title = $payment_title;

            if ( isset( $available_gateways[ $payment_method ] ) ) {
                $payment_title = $available_gateways[ $payment_method ]->get_title();
            } else {
                $payment_title = '';
            }

            update_post_meta( $order_id, '_payment_method_title', $payment_title );

            $current_user = wp_get_current_user();
            $user_name = $current_user->ID ? $current_user->user_login.' ( '.$current_user->ID.' ) ' : __('비회원', GNUPAY_NAME );

            $order->add_order_note( sprintf(__( '결제 방법이 %s 에서 %s 으로 변경되었습니다. : 사용자 %s ', GNUPAY_NAME ), 
                $payment_title ? esc_html($payment_title) : esc_html( $payment_method ),
                $before_pay_title,
                $user_name
            ) );

            return array(
                'change'=>1,
                'payment_method'=>$payment_method,
                'payment_title'=>$payment_title
                );
        }

        return false;
    }
}

if( ! function_exists('kcp_new_html_header') ){
    function kcp_new_html_header($page_mode='', $title=''){
        if( !$title ){
            $title = get_bloginfo('name');
        }
    ?>
    <!DOCTYPE html>
    <!--[if IE 8]>
        <html xmlns="http://www.w3.org/1999/xhtml" class="ie8" <?php language_attributes(); ?>>
    <![endif]-->
    <!--[if !(IE 8) ]><!-->
        <html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>
    <!--<![endif]-->
    <head>
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php $title; ?></title>
    <?php do_action( 'kcp_head_new_'.$page_mode ); ?>
    <link rel='stylesheet' id='gnupay-kcp-new'  href='<?php echo GNUPAY_KCP_URL.'template/new.css'; ?>' type='text/css' media='all' />
    </head>
    <div class="kcp_new_shortcode">
    <?php
    }
}

if( ! function_exists('kcp_new_html_footer') ){
    function kcp_new_html_footer($page_mode=''){
    ?>
    </div>
    <?php do_action( 'kcp_footer_new_'.$page_mode ); ?>
    </body>
    </html>
    <?php
    }
}
?>