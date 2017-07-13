<?php
if( ! defined( 'ABSPATH' ) ) exit;

require_once(GNUPAY_KCP_PATH.'kcp/settle_kcp.inc.php');

    /* ============================================================================== */
    /* =   PAGE : 결과 처리 PAGE                                                    = */
    /* = -------------------------------------------------------------------------- = */
    /* =   Copyright (c)  2007   KCP Inc.   All Rights Reserverd.                   = */
    /* ============================================================================== */

    /* ============================================================================== */
    /* =   01. KCP 지불 서버 정보 설정                                              = */
    /* = -------------------------------------------------------------------------- = */
    if ($config['de_card_test']) {
        $g_conf_pa_url    = "testpaygw.kcp.co.kr"; // ※ 테스트: testpaygw.kcp.co.kr, 리얼: paygw.kcp.co.kr
        $g_conf_pa_port   = "8090";                // ※ 테스트: 8090,                리얼: 8090
    }
    else {
        $g_conf_pa_url    = "paygw.kcp.co.kr";
        $g_conf_pa_port   = "8090";
    }

    $g_conf_tx_mode   = 0;
    /* ============================================================================== */


    /* ============================================================================== */
    /* =   지불 결과                                                                = */
    /* = -------------------------------------------------------------------------- = */
    $req_tx     = isset($_POST[ "req_tx"     ]) ? sanitize_text_field($_POST["req_tx"]) : '';                             // 요청 종류
    $bSucc      = isset($_POST[ "bSucc"      ]) ? sanitize_text_field($_POST["bSucc"]) : '';                             // DB처리 여부
    $trad_time  = isset($_POST[ "trad_time"  ]) ? sanitize_text_field($_POST["trad_time"]) : '';                             // 원거래 시각
    /* = -------------------------------------------------------------------------- = */
    $ordr_idxx  = isset($_POST[ "ordr_idxx"  ]) ? sanitize_text_field($_POST["ordr_idxx"]) : '';                             // 주문번호
    $buyr_name  = isset($_POST[ "buyr_name"  ]) ? sanitize_text_field($_POST["buyr_name"]) : '';                             // 주문자 이름
    $buyr_tel1  = isset($_POST[ "buyr_tel1"  ]) ? sanitize_text_field($_POST["buyr_tel1"]) : '';                             // 주문자 전화번호
    $buyr_mail  = isset($_POST[ "buyr_mail"  ]) ? sanitize_text_field($_POST["buyr_mail"]) : '';                             // 주문자 메일
    $good_name  = isset($_POST[ "good_name"  ]) ? sanitize_text_field($_POST["good_name"]) : '';                             // 주문상품명
    $comment    = isset($_POST[ "comment"    ]) ? sanitize_text_field($_POST["comment"]) : '';                             // 비고
    /* = -------------------------------------------------------------------------- = */
    $corp_type     = isset($_POST[ "corp_type"      ]) ? sanitize_text_field($_POST["corp_type"]) : '';                      // 사업장 구분
    $corp_tax_type = isset($_POST[ "corp_tax_type"  ]) ? sanitize_text_field($_POST["corp_tax_type"]) : '';                      // 과세/면세 구분
    $corp_tax_no   = isset($_POST[ "corp_tax_no"    ]) ? sanitize_text_field($_POST["corp_tax_no"]) : '';                      // 발행 사업자 번호
    $corp_nm       = isset($_POST[ "corp_nm"        ]) ? sanitize_text_field($_POST["corp_nm"]) : '';                      // 상호
    $corp_owner_nm = isset($_POST[ "corp_owner_nm"  ]) ? sanitize_text_field($_POST["corp_owner_nm"]) : '';                      // 대표자명
    $corp_addr     = isset($_POST[ "corp_addr"      ]) ? sanitize_text_field($_POST["corp_addr"]) : '';                      // 사업장 주소
    $corp_telno    = isset($_POST[ "corp_telno"     ]) ? sanitize_text_field($_POST["corp_telno"]) : '';                      // 사업장 대표 연락처
    /* = -------------------------------------------------------------------------- = */
    $tr_code    = isset($_POST[ "tr_code"    ]) ? sanitize_text_field($_POST["tr_code"]) : '';                             // 발행용도
    $id_info    = isset($_POST[ "id_info"    ]) ? sanitize_text_field($_POST["id_info"]) : '';                             // 신분확인 ID
    $amt_tot    = isset($_POST[ "amt_tot"    ]) ? sanitize_text_field($_POST["amt_tot"]) : '';                             // 거래금액 총 합
    $amt_sup    = isset($_POST[ "amt_sup"    ]) ? sanitize_text_field($_POST["amt_sup"]) : '';                             // 공급가액
    $amt_svc    = isset($_POST[ "amt_svc"    ]) ? sanitize_text_field($_POST["amt_svc"]) : '';                             // 봉사료
    $amt_tax    = isset($_POST[ "amt_tax"    ]) ? sanitize_text_field($_POST["amt_tax"]) : '';                             // 부가가치세
    /* = -------------------------------------------------------------------------- = */
    $pay_type      = isset($_POST[ "pay_type"       ]) ? sanitize_text_field($_POST["pay_type"]) : '';                      // 결제 서비스 구분
    $pay_trade_no  = isset($_POST[ "pay_trade_no"   ]) ? sanitize_text_field($_POST["pay_trade_no"]) : '';                      // 결제 거래번호
    /* = -------------------------------------------------------------------------- = */
    $mod_type   = isset($_POST[ "mod_type"   ]) ? sanitize_text_field($_POST["mod_type"]) : '';                             // 변경 타입
    $mod_value  = isset($_POST[ "mod_value"  ]) ? sanitize_text_field($_POST["mod_value"]) : '';                             // 변경 요청 거래번호
    $mod_gubn   = isset($_POST[ "mod_gubn"   ]) ? sanitize_text_field($_POST["mod_gubn"]) : '';                             // 변경 요청 거래번호 구분
    $mod_mny    = isset($_POST[ "mod_mny"    ]) ? sanitize_text_field($_POST["mod_mny"]) : '';                             // 변경 요청 금액
    $rem_mny    = isset($_POST[ "rem_mny"    ]) ? sanitize_text_field($_POST["rem_mny"]) : '';                             // 변경처리 이전 금액
    /* = -------------------------------------------------------------------------- = */
    $res_cd     = isset($_POST[ "res_cd"     ]) ? sanitize_text_field($_POST["res_cd"]) : '';                             // 응답코드
    $res_msg    = isset($_POST[ "res_msg"    ]) ? sanitize_text_field($_POST["res_msg"]) : '';                             // 응답메시지
    $cash_no    = isset($_POST[ "cash_no"    ]) ? sanitize_text_field($_POST["cash_no"]) : '';                             // 현금영수증 거래번호
    $receipt_no = isset($_POST[ "receipt_no" ]) ? sanitize_text_field($_POST["receipt_no"]) : '';                             // 현금영수증 승인번호
    $app_time   = isset($_POST[ "app_time"   ]) ? sanitize_text_field($_POST["app_time"]) : '';                             // 승인시간(YYYYMMDDhhmmss)
    $reg_stat   = isset($_POST[ "reg_stat"   ]) ? sanitize_text_field($_POST["reg_stat"]) : '';                             // 등록 상태 코드
    $reg_desc   = isset($_POST[ "reg_desc"   ]) ? sanitize_text_field($_POST["reg_desc"]) : '';                             // 등록 상태 설명
    /* ============================================================================== */

    $req_tx_name = "";

    if( $req_tx == "pay" )
    {
        $req_tx_name = __('등록', GNUPAY_NAME);
    }
    else if( $req_tx == "mod" )
    {
        $req_tx_name = __('변경/조회', GNUPAY_NAME);
    }

$title = sprintf(__('현금영수증발급 %s | %s'), $req_tx_name, get_bloginfo());

kcp_new_html_header('', $title);
include_once(GNUPAY_KCP_PATH.'template/tax_result.php');
kcp_new_html_footer();

exit;
?>