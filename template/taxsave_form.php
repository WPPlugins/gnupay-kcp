<?php
if( ! defined( 'ABSPATH' ) ) return; // 개별 페이지 접근 불가
?>

<!--
    /* ============================================================================== */
    /* =   PAGE : 등록 요청 PAGE                                                    = */
    /* = -------------------------------------------------------------------------- = */
    /* =   Copyright (c)  2007   KCP Inc.   All Rights Reserverd.                   = */
    /* ============================================================================== */
//-->

<script>
    // 현금영수증 MAIN FUNC
    function  jsf__pay_cash( form )
    {
        jsf__show_progress(true);

        if ( jsf__chk_cash( form ) == false )
        {
            jsf__show_progress(false);
            return;
        }

        form.ordr_idxx.value = "<?php echo $order_id; ?>";
        form.amt_tot.value = "<?php echo $amt_tot; ?>";
        form.amt_sup.value = "<?php echo $amt_sup; ?>";
        form.amt_svc.value = "<?php echo $amt_svc; ?>";
        form.amt_tax.value = "<?php echo $amt_tax; ?>";

        form.submit();
    }

    // 진행 바
    function  jsf__show_progress( show )
    {
        if ( show == true )
        {
            window.show_pay_btn.style.display  = "none";
            window.show_progress.style.display = "inline";
        }
        else
        {
            window.show_pay_btn.style.display  = "inline";
            window.show_progress.style.display = "none";
        }
    }

    // 포맷 체크
    function  jsf__chk_cash( form )
    {
        if ( form.trad_time.value.length != 14 )
        {
            alert("원 거래 시각을 정확히 입력해 주시기 바랍니다.");
            form.trad_time.select();
            form.trad_time.focus();
            return false;
        }

        if ( form.corp_type.value == "1" )
        {
            if ( form.corp_tax_no.value.length != 10 )
            {
                alert("발행 사업자번호를 정확히 입력해 주시기 바랍니다.");
                form.corp_tax_no.select();
                form.corp_tax_no.focus();
                return false;
            }
        }

        if (  form.tr_code[0].checked )
        {
            if ( form.id_info.value.length != 10 &&
                 form.id_info.value.length != 11 &&
                 form.id_info.value.length != 13 )
            {
                alert("주민번호 또는 휴대폰번호를 정확히 입력해 주시기 바랍니다.");
                form.id_info.select();
                form.id_info.focus();
                return false;
            }
        }
        else if (  form.tr_code[1].checked )
        {
            if ( form.id_info.value.length != 10 )
            {
                alert("사업자번호를 정확히 입력해 주시기 바랍니다.");
                form.id_info.select();
                form.id_info.focus();
                return false;
            }
        }
        return true;
    }

    function  jsf__chk_tr_code( form )
    {
        var span_tr_code_0 = document.getElementById( "span_tr_code_0" );
        var span_tr_code_1 = document.getElementById( "span_tr_code_1" );

        if ( form.tr_code[0].checked )
        {
            span_tr_code_0.style.display = "block";
            span_tr_code_1.style.display = "none";
        }
        else if (form.tr_code[1].checked )
        {
            span_tr_code_0.style.display = "none";
            span_tr_code_1.style.display = "block";
        }
    }

</script>
</head>
<body>

<div id="scash" class="new_win">
    <h1 id="win_title"><?php echo $title ?></h1>

    <section>
        <h2>주문정보</h2>

        <div class="tbl_head01 tbl_wrap">
            <table>
            <colgroup>
                <col class="grid_3">
                <col>
            </colgroup>
            <tbody>
            <tr>
                <th scope="row"><?php _e('주문 번호', GNUPAY_NAME); ?></th>
                <td><?php echo $order_id; ?></td>
            </tr>
            <tr>
                <th scope="row"><?php _e('상품 정보', GNUPAY_NAME); ?></th>
                <td><?php echo $goods_name; ?></td>
            </tr>
            <tr>
                <th scope="row"><?php _e('주문자 이름', GNUPAY_NAME); ?></th>
                <td><?php echo $od_name; ?></td>
            </tr>
            <tr>
                <th scope="row"><?php _e('주문자 E-Mail', GNUPAY_NAME); ?></th>
                <td><?php echo $od_email; ?></td>
            </tr>
            <tr>
                <th scope="row"><?php _e('주문자 전화번호', GNUPAY_NAME); ?></th>
                <td><?php echo $od_tel; ?></td>
            </tr>
            </tbody>
            </table>
        </div>
    </section>

    <section>
        <h2><?php _e('현금영수증 발급 정보', GNUPAY_NAME); ?></h2>

        <form name="cash_form" action="<?php echo add_query_arg(array('wc-api'=>'gnupay_kcp_tax', 'order_id'=>$order_id, 'tx'=>'pp_cli_hub'), home_url( '/' )); ?>" method="post">
        <?php wp_nonce_field( 'kcp_taxsave', 'kcp_nonce' ); ?>
        <input type="hidden" name="tx"        value="pp_cli_hub">
        <input type="hidden" name="corp_type" value="0"> <!-- 사업자 구분 - 0:직접판매 , 1:입점몰판매 -->
        <input type="hidden" name="ordr_idxx">
        <input type="hidden" name="good_name" value="<?php echo esc_attr($goods_name); ?>">
        <input type="hidden" name="buyr_name" value="<?php echo esc_attr($od_name); ?>">
        <input type="hidden" name="buyr_mail" value="<?php echo esc_attr($od_email); ?>">
        <input type="hidden" name="buyr_tel1" value="<?php echo esc_attr($od_tel); ?>">
        <input type="hidden" name="trad_time" value="<?php echo esc_attr($trad_time); ?>">

        <input type="hidden" name="amt_tot">
        <input type="hidden" name="amt_sup">
        <input type="hidden" name="amt_svc">
        <input type="hidden" name="amt_tax">

        <div class="tbl_head01 tbl_wrap">
            <table>
            <colgroup>
                <col class="grid_3">
                <col>
            </colgroup>
            <tbody>
            <tr>
                <th scope="row"><?php _e('원 거래 시각', GNUPAY_NAME); ?></th>
                <td><?php echo $trad_time; ?></td>
            </tr>
            <tr>
                <th scope="row"><?php _e('발행 용도', GNUPAY_NAME); ?></th>
                <td>
                    <input type="radio" name="tr_code" value="0" id="tr_code1" onClick="jsf__chk_tr_code( this.form )" checked>
                    <label for="tr_code1"><?php _e('소득공제용', GNUPAY_NAME); ?></label>
                    <input type="radio" name="tr_code" value="1" id="tr_code2" onClick="jsf__chk_tr_code( this.form )">
                    <label for="tr_code2"><?php _e('지출증빙용', GNUPAY_NAME); ?></label>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="id_info">
                        <span id="span_tr_code_0" style="display:inline"><?php _e('주민(휴대폰)번호', GNUPAY_NAME); ?></span>
                        <span id="span_tr_code_1" style="display:none"><?php _e('사업자번호', GNUPAY_NAME); ?></span>
                    </label>
                </th>
                <td>
                    <input type="text" name="id_info" id="id_info" class="frm_input" size="16" maxlength="13"> <?php _e('("-" 생략)', GNUPAY_NAME); ?> 
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('거래금액 총합', GNUPAY_NAME); ?></th>
                <td><?php echo wc_price($amt_tot); ?></td>
            </tr>
            <tr>
                <th scope="row"><?php _e('공급가액', GNUPAY_NAME); ?></th>
                <td><?php echo wc_price($amt_sup); ?><!-- ((거래금액 총합 * 10) / 11) --></td>
            </tr>
            <tr>
                <th scope="row"><?php _e('봉사료', GNUPAY_NAME); ?></th>
                <td><?php echo wc_price($amt_svc); ?></td>
            </tr>
            <tr>
                <th scope="row"><?php _e('부가가치세', GNUPAY_NAME); ?></th>
                <td><?php echo wc_price($amt_tax); ?><!-- 거래금액 총합 - 공급가액 - 봉사료 --></td>
            </tr>
            </tbody>
            </table>
        </div>

        <div id="scash_apply">
            <span id="show_pay_btn">
                <button type="button" onclick="jsf__pay_cash( this.form )"><?php _e('등록요청', GNUPAY_NAME); ?></button>
            </span>
            <span id="show_progress" style="display:none">
                <b><?php _e('등록 진행중입니다. 잠시만 기다려주십시오', GNUPAY_NAME); ?></b>
            </span>
        </div>

        <!-- 요청종류 승인(pay)/변경(mod) 요청시 사용 -->
        <input type="hidden" name="req_tx" value="pay">
        </form>
    </section>

    <p id="scash_copy"><?php _e('ⓒ Copyright 2007. KCP Inc.  All Rights Reserved.', GNUPAY_NAME); ?></p>

</div>