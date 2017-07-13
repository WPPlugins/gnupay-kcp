<?php
if( ! defined( 'ABSPATH' ) ) return; // 개별 페이지 접근 불가

$is_testmode = get_post_meta($ordr_idxx, '_is_test', true);
?>
<script>
    //현금영수증 연동 스크립트
    function receiptView(auth_no)
    {
        var receiptWin = "<?php echo GNUPAY_KCP_BILL_RECEIPT_URL.$config['de_kcp_mid'].'&orderid='.$ordr_idxx.'&bill_yn=Y&authno='; ?>"+auth_no;
        window.open(receiptWin , "" , "width=360, height=647")
    }
</script>
<div id="kcp_req_rx" class="new_win">
    <h1 id="win_title"><?php echo sprintf(__('현금영수증 %s - KCP Online Payment System', GNUPAY_NAME), $req_tx_name); ?></h1>

    <div class="tbl_head01 tbl_wrap">
        <table>
        <colgroup>
            <col class="grid_4">
            <col>
        </colgroup>
        <tbody>
<?php
    if ($req_tx == "pay")                          // 거래 구분 : 등록
    {
        if (!$bSucc == "false")                    // 업체 DB 처리 정상
        {
            if ($res_cd == "0000")                 // 정상 승인
            {
?>
        <tr>
            <th scope="row"><?php _e('결과코드', GNUPAY_NAME); ?></th>
            <td><?php echo $res_cd; ?></td>
        </tr>
        <tr>
            <th scope="row"><?php _e('결과 메세지', GNUPAY_NAME); ?></th>
            <td><?php echo $res_msg; ?></td>
        </tr>
        <tr>
            <th scope="row"><?php _e('현금영수증 거래번호', GNUPAY_NAME); ?></th>
            <td><?php echo $cash_no; ?></td>
        </tr>
        <tr>
            <th scope="row"><?php _e('현금영수증 승인번호', GNUPAY_NAME); ?></th>
            <td><?php echo $receipt_no; ?></td>
        </tr>
        <tr>
            <th scope="row"><?php _e('등록 상태 코드', GNUPAY_NAME); ?></th>
            <td><?php echo $reg_stat; ?></td>
        </tr>
        <tr>
            <th scope="row"><?php _e('등록 상태 설명', GNUPAY_NAME); ?></th>
            <td><?php echo $reg_desc; ?></td>
        </tr>
        <tr>
            <th scope="row"><?php _e('승인시간', GNUPAY_NAME); ?></th>
            <td><?php echo preg_replace("/([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})/", "\\1-\\2-\\3 \\4:\\5:\\6",$app_time); ?></td>
        </tr>
        <tr>
            <th scope="row"><?php _e('현금영수증 URL', GNUPAY_NAME); ?></th>
            <td>
                <button type="button" name="receiptView" class="btn_frmline" onClick="javascript:receiptView('<?php echo $receipt_no; ?>')"><?php _e('영수증 확인', GNUPAY_NAME); ?></button>
                <p><?php _e('영수증 확인은 실 등록의 경우에만 가능합니다', GNUPAY_NAME); ?>.</p>
            </td>
        </tr>
        <tr>
            <td colspan="2"></td>
        </tr>
<?php
            }
            else                                       // 승인 실패
            {
?>
        <tr>
            <th scope="row"><?php _e('결과코드', GNUPAY_NAME); ?></th>
            <td><?php echo $res_cd; ?></td>
        </tr>
        <tr>
            <th scope="row"><?php _e('결과 메세지', GNUPAY_NAME); ?></th>
            <td><?php echo $res_msg; ?></td>
        </tr>
<?php
            }

        }
        else                                           // 업체 DB 처리 실패
        {
?>
        <tr>
            <th scope="row"><?php _e('취소 결과코드', GNUPAY_NAME); ?></th>
            <td><?php echo $res_cd; ?></td>
        </tr>
        <tr>
            <th scope="row"><?php _e('취소 결과 메세지', GNUPAY_NAME); ?></th>
            <td><?php echo $res_msg; ?></td>
        </tr>
        <tr>
            <th scope="row"><?php _e('상세메세지', GNUPAY_NAME); ?></th>
            <td>
<?php
            if ($res_cd == "0000")
            {
                _e('결제는 정상적으로 이루어졌지만 쇼핑몰에서 결제 결과를 처리하는 중 오류가 발생하여 시스템에서 자동으로 취소 요청을 하였습니다. <br> 쇼핑몰로 전화하여 확인하시기 바랍니다.', GNUPAY_NAME);
            }
            else
            {
                _e('결제는 정상적으로 이루어졌지만 쇼핑몰에서 결제 결과를 처리하는 중 오류가 발생하여 시스템에서 자동으로 취소 요청을 하였으나, <br> <b>취소가 실패 되었습니다.</b><br> 쇼핑몰로 전화하여 확인하시기 바랍니다.', GNUPAY_NAME);
            }
?>
            </td>
        </tr>
<?php
        }

    }
    else if ($req_tx == "mod")                     // 거래 구분 : 조회/취소 요청
    {
        if ($res_cd == "0000")
        {
?>
        <tr>
            <th scope="row"><?php _e('결과코드', GNUPAY_NAME); ?></th>
            <td><?php echo $res_cd; ?></td>
        </tr>
        <tr>
            <th scope="row"><?php _e('결과 메세지', GNUPAY_NAME); ?></th>
            <td><?php echo $res_msg; ?></td>
        </tr>
        <tr>
            <th scope="row"><?php _e('현금영수증 거래번호', GNUPAY_NAME); ?></th>
            <td><?php echo $cash_no; ?></td>
        </tr>
        <tr>
            <th scope="row"><?php _e('현금영수증 승인번호', GNUPAY_NAME); ?></th>
            <td><?php echo $receipt_no; ?></td>
        </tr>
        <tr>
            <th scope="row"><?php _e('등록 상태 코드', GNUPAY_NAME); ?></th>
            <td><?php echo $reg_stat; ?></td>
        </tr>
        <tr>
            <th scope="row"><?php _e('등록 상태 설명', GNUPAY_NAME); ?></th>
            <td><?php echo $reg_desc; ?></td>
        </tr>
        <tr>
            <th scope="row"><?php _e('승인시간', GNUPAY_NAME); ?></th>
            <td><?php echo preg_replace("/([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})/", "\\1-\\2-\\3 \\4:\\5:\\6", $app_time); ?></td>
        </tr>
        <tr>
            <th scope="row"><?php _e('현금영수증 URL', GNUPAY_NAME); ?></th>
            <td>
                <input type="button" name="receiptView" value="영수증 확인" class="box" onClick="javascript:receiptView('<?php echo $receipt_no; ?>')">
                <?php if( $is_testmode ){ ?>
                    <p><?php _e('영수증 확인은 실 등록의 경우에만 가능합니다.', GNUPAY_NAME); ?></p>
                <?php } ?>
            </td>
        </tr>
<?php
        }
        else
        {
?>
        <tr>
            <th scope="row"><?php _e('결과코드', GNUPAY_NAME); ?></th>
            <td><?php echo $res_cd; ?></td>
        </tr>
        <tr>
            <th scope="row"><?php _e('결과 메세지', GNUPAY_NAME); ?></th>
            <td><?php echo $res_msg; ?></td>
        </tr>
<?php
        }
    }
?>
        </tbody>
        </table>
    </div>

</div>