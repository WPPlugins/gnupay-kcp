<?php
if( ! defined( 'ABSPATH' ) ) exit;

//https://docs.woothemes.com/wc-apidocs/class-WC_Order.html

$get_items = $order->get_items();
$od_tno = get_post_meta( $order_id, '_od_tno', true );
$od_receipt_price = get_post_meta( $order_id, '_od_receipt_price', true );
?>
<h2><?php _e('결제정보', GNUPAY_NAME); ?></h2>
<table class="shop_table order_details">
<tbody>

<?php if($app_no_subj){ ?>
<tr>
    <th scope="row"><?php echo $app_no_subj; ?></th>
    <td><?php echo $app_no; ?></td>
</tr>
<?php } ?>

<tr>
    <th scope="row"><?php _e('결제금액', GNUPAY_NAME); ?></th>
    <td>
    <?php
    if( $od_receipt_price > 0 ){
        echo wc_price($od_receipt_price);
    } else {
        _e('아직 입금되지 않았거나 입금정보를 입력하지 못하였습니다.', GNUPAY_NAME);
    }
    ?>
    </td>
</tr>

<?php if($disp_bank){ ?>
<tr>
    <th scope="row"><?php _e('입금은행', GNUPAY_NAME); ?></th>
    <td><?php echo esc_attr($od_bankname); ?></td>
</tr>
<tr>
    <th scope="row"><?php _e('입금자명', GNUPAY_NAME); ?></th>
    <td><?php echo esc_attr($od_deposit_name); ?></td>
</tr>
<tr>
    <th scope="row"><?php _e('입금계좌', GNUPAY_NAME); ?></th>
    <td><?php echo esc_attr($od_bank_account); ?></td>
</tr>
<?php } ?>

<?php if( $disp_receipt ){ ?>
<tr>
    <th scope="row"><?php _e('영수증', GNUPAY_NAME); ?></th>
    <td>
    <?php
    if($payment_method == $pay_ids['phone']){   //휴대폰
    $hp_receipt_script = 'window.open(\''.GNUPAY_KCP_BILL_RECEIPT_URL.'mcash_bill&tno='.$od_tno.'&order_no='.$order_id.'&trade_mony='.$od_receipt_price.'\', \'winreceipt\', \'width=500,height=690,scrollbars=yes,resizable=yes\');';
    ?>
    <a href="#" onclick="<?php echo $hp_receipt_script; ?>"><?php _e('영수증 출력', GNUPAY_NAME); ?></a>
    <?php } ?>
    <?php
    if($payment_method == $pay_ids['card'])  //신용카드
    {
        $card_receipt_script = 'window.open(\''.GNUPAY_KCP_BILL_RECEIPT_URL.'card_bill&tno='.$od_tno.'&order_no='.$order_id.'&trade_mony='.$od_receipt_price.'\', \'winreceipt\', \'width=470,height=815,scrollbars=yes,resizable=yes\');';
    ?>
    <a href="#" onclick="<?php echo $card_receipt_script; ?>"><?php _e('영수증 출력', GNUPAY_NAME); ?></a>
    <?php
    }
    ?>
    </td>
</tr>
<?php } ?>

<?php
// 현금영수증 발급을 사용하는 경우에만
// 환불된 금액이 없고 현금일 경우에만 현금영수증을 발급 할 수 있습니다. 계좌이체, 가상계좌
if( $payoptions['de_taxsave_use'] && !$order->get_total_refunded() && ($od_receipt_price > 0) && in_array($payment_method, array('kcp_virtualaccount', 'kcp_accounttransfer')) ){

$config = $payoptions;
require_once(GNUPAY_KCP_PATH.'kcp/settle_kcp.inc.php');
$od_cash = get_post_meta( $order_id, '_od_cash', true );
$cash = get_post_meta( $order_id, '_od_cash_info', true );
?>
<tr>
    <th scope="row"><?php _e('현금영수증', GNUPAY_NAME); ?></th>
    <td>
        <?php if( $od_cash ){   //현금 영수증을 이미 발급 받았다면
        $cash_receipt_script = 'window.open(\''.GNUPAY_KCP_CASH_RECEIPT_URL.$payoptions['de_kcp_mid'].'&orderid='.$order_id.'&bill_yn=Y&authno='.$cash['receipt_no'].'\', \'taxsave_receipt\', \'width=360,height=647,scrollbars=0,menus=0\');';
        ?>
        <a href="#" onclick="<?php echo $cash_receipt_script; ?>"><?php _e('현금영수증 확인하기', GNUPAY_NAME); ?></a>
        <?php } else { ?>
        <a href="#" onclick="window.open('<?php echo add_query_arg(array('wc-api'=>'gnupay_kcp_tax', 'order_id'=>$order_id, 'tx'=>'taxsave'), home_url( '/' )); ?>', 'taxsave', 'width=550,height=600,scrollbars=1,menus=0');"><?php _e('현금영수증을 발급하시려면 클릭하십시오.', GNUPAY_NAME); ?></a>
        <?php } ?>
    </td>
</tr>
<?php } ?>
</tbody>
</table>

<?php
//관리자에게만 보입니다.( 가상계좌 통보 테스트 )
if ($payment_method == $pay_ids['vbank'] && $payoptions['de_card_test'] && $pg_process_price == 0 && current_user_can( 'administrator' ) && !empty($od_bank_account) ) {
/*
preg_match("/\s{1}([^\s]+)\s?/", $od_bank_account, $matchs);
$deposit_no = trim($matchs[1]);
*/
?>
<form method="post" action="http://devadmin.kcp.co.kr/Modules/Noti/TEST_Vcnt_Noti_Proc.jsp" target="_blank" style="border:1px solid #333;padding:1em">
<h4><?php _e('관리자가 가상계좌 테스트를 한 경우에만 보입니다.', GNUPAY_NAME); ?></h4>
<table>
<caption><?php _e('모의입금처리', GNUPAY_NAME); ?></caption>
<colgroup>
    <col class="grid_2">
    <col>
</colgroup>
<tbody>
<tr>
    <th scope="col"><label for="e_trade_no"><?php _e('KCP 거래번호', GNUPAY_NAME); ?></label></th>
    <td><input type="text" name="e_trade_no" value="<?php echo $app_no; ?>"></td>
</tr>
<tr>
    <th scope="col"><label for="deposit_no"><?php _e('입금계좌', GNUPAY_NAME); ?></label></th>
    <td><input type="text" name="deposit_no" value="<?php echo esc_attr($od_bank_account); ?>"></td>
</tr>
<tr>
    <th scope="col"><label for="req_name"><?php _e('입금자명', GNUPAY_NAME); ?></label></th>
    <td><input type="text" name="req_name" value="<?php echo esc_attr($od_deposit_name); ?>"></td>
</tr>
<tr>
    <th scope="col"><label for="noti_url"><?php _e('입금통보 URL', GNUPAY_NAME); ?></label></th>
    <td><input type="text" name="noti_url" value="<?php echo gnupay_kcp_get_vbankurl($pay_ids); ?>"></td>
</tr>
</tbody>
</table>
<div id="sod_fin_test" class="btn_confirm">
    <input type="submit" value="<?php _e('입금통보 테스트', GNUPAY_NAME); ?>" class="btn_submit">
</div>
</form>
<?php } ?>