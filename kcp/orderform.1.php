<?php
if( ! defined( 'GNUPAY_NAME' ) ) exit; // 개별 페이지 접근 불가

if( GNUPAY_KCP_MOBILE ){  //모바일이면
    include( __DIR__ ."/m_orderform.1.php");
    return;
}

/* ============================================================================== */
/* =   Javascript source Include                                                = */
/* = -------------------------------------------------------------------------- = */
/* =   ※ 필수                                                                  = */
/* = -------------------------------------------------------------------------- = */

wc_enqueue_js("
try {
kcpTx_install();
} catch (e) {
    alert(e.message);
}
");

wp_enqueue_script( 'gp_kcp_ini_js', $g_conf_js_url, array('jquery', 'gnupay_kcp_pay_js'), GNUPAY_VERSION, true );
?>