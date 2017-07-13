<?php
if( ! defined( 'GNUPAY_NAME' ) ) exit; // 개별 페이지 접근 불가

// kcp 전자결제를 사용할 때만 실행
/*
if(!($config['de_iche_use'] || $config['de_vbank_use'] || $config['de_hp_use'] || $config['de_card_use'])) {
    return;
}
*/

if( isset($info) && is_array($info) ){

    // 결제등록 요청시 사용할 입금마감일
    $info['ipgm_date'] = date("Ymd", (GNUPAY_KCP_SERVER_TIME + 86400 * 5));
    $info['param_opt_1'] = isset($param_opt_1) ? $param_opt_1 : '';
    $info['param_opt_2'] = isset($param_opt_2) ? $param_opt_2 : '';
    $info['param_opt_3'] = isset($param_opt_3) ? $param_opt_3 : '';
    $info['tablet_size'] = "1.0"; // 화면 사이즈 조정 - 기기화면에 맞게 수정(갤럭시탭,아이패드 - 1.85, 스마트폰 - 1.0)

}

//거래등록 하는 kcp 서버와 통신을 위한 스크립트
wp_enqueue_script( 'kcp_mobile_ini_js', GNUPAY_KCP_URL.'kcp/approval_key.js', array('jquery', 'gnupay_kcp_pay_js'), GNUPAY_VERSION , true );
?>