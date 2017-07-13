<?php
if( ! defined( 'ABSPATH' ) ) return;

class gnupay_kcp_metabox{

	/**
	 * Constructor.
	 */
	public function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 31, 2 );
    }

	/**
	 * Add WC Meta boxes.
	 */
	public function add_meta_boxes($post_type, $post) {

        if( empty($post->ID) ) return;

        if( $payment_method = get_post_meta( $post->ID, '_payment_method', true ) ){
            if( in_array($payment_method, gnupay_kcp_get_settings('pay_ids')) ){
                foreach ( wc_get_order_types( 'order-meta-boxes' ) as $type ) {
                    $order_type_object = get_post_type_object( $type );

                    add_meta_box( 'woocommerce-kcp-order-log', __( 'PG 결재', GNUPAY_NAME ), array($this, 'output'), $type, 'side', 'default' );
                }
            }
        }
    }

    public function output($post){
        global $post;
        
        $order = wc_get_order( $post->ID );
        
		if ( WC()->payment_gateways() ) {
			$payment_gateways = WC()->payment_gateways->payment_gateways();
		} else {
			$payment_gateways = array();
		}

        $pay_ids = gnupay_kcp_get_settings('pay_ids');

        if( !empty($order->payment_method) && in_array($order->payment_method, $pay_ids) ){
            $config = gnupay_kcp_get_config_payment( $order->id );

            $payment_method = $order->payment_method;
            $pg_url  = 'http://admin8.kcp.co.kr';
            $pg_test = 'KCP';

            $od_pg = get_post_meta($order->id, '_od_pg', true);   //결제 pg사를 저장
            $od_pay_method = get_post_meta($order->id, '_od_pay_method', true);   //결제 pg사를 저장
            $od_tno = get_post_meta($order->id, '_od_tno', true);   //결제 pg사를 주문번호
            $od_app_no = get_post_meta($order->id, '_od_app_no', true);   //결제 승인 번호
            $od_receipt_price = get_post_meta($order->id, '_od_receipt_price', true);   //결제 금액
			$od_test = get_post_meta($order->id, '_od_test', true);   //테스트체크
			$od_escrow = get_post_meta($order->id, '_od_escrow', true);   //에스크로
        ?>
        <div>
            <table>
                <tr>
                    <th><?php _e('결제승인', GNUPAY_NAME); ?></th>
                    <td>
                        <?php echo isset( $payment_gateways[ $payment_method ] ) ? esc_html( $payment_gateways[ $payment_method ]->get_title() ) : esc_html( $payment_method ); ?>
						<br>
						( <?php echo $od_test ? __('테스트결제', GNUPAY_NAME) : __('실결제', GNUPAY_NAME); ?> )
						<?php if( $od_escrow ) {
						echo "<br><span style='color:green'>( ".__('에스크로', GNUPAY_NAME)." )</span>";
						} ?>
                    </td>
                </tr>
                <tr>
                    <th><?php echo __('결제대행사 링크', GNUPAY_NAME); ?></th>
                    <td><?php echo "<a href=\"{$pg_url}\" target=\"_blank\">{$pg_test}바로가기</a><br>"; ?></td>
                </tr>
                <?php
                if( $od_receipt_price && !$order->get_total_refunded() && ($order->get_total() > 0) && in_array($payment_method, array($pay_ids['vbank'], $pay_ids['bank'])) ){     //가상계좌, 계좌이체
                
                $od_cash_info = get_post_meta($order->id, '_od_cash_info', true);
                $od_cash = get_post_meta($order->id, '_od_cash', true);
                ?>
                    <tr>
                        <th><?php _e('현금영수증', GNUPAY_NAME); ?></th>
                        <td>
                        <?php
                        if( $od_cash ){
                            $default_cash = array('receipt_no'=>'');
                            $cash = wp_parse_args($od_cash_info, $default_cash);
                            require GNUPAY_KCP_PATH.'kcp/settle_kcp.inc.php';

                            $cash_receipt_script = 'window.open(\''.GNUPAY_KCP_CASH_RECEIPT_URL.$config['de_kcp_mid'].'&orderid='.$order->id.'&bill_yn=Y&authno='.$cash['receipt_no'].'\', \'taxsave_receipt\', \'width=360,height=647,scrollbars=0,menus=0\');';

                            ?>
                            <a href="#" onclick="<?php echo $cash_receipt_script; ?>"><?php _e('현금영수증 확인', GNUPAY_NAME); ?></a>
                            <?php } else { ?>
                            <a href="#" onclick="window.open('<?php echo add_query_arg(array('wc-api'=>'gnupay_kcp_tax', 'order_id'=>$order->id, 'tx'=>'taxsave'), home_url( '/' )); ?>', 'taxsave', 'width=550,height=600,scrollbars=1,menus=0');"><?php _e('현금영수증 발급', GNUPAY_NAME); ?></a>
                            <?php
                            }   //end if $od_cash
                        }   //end if
                        ?>
                        </td>
                    </tr>
            </table>
        </div>
        <?php
        }   //end if
    }
    
}   //end class gnupay_kcp_metabox

new gnupay_kcp_metabox();
?>