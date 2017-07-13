<?php
if( ! defined( 'ABSPATH' ) ) return;

class kcp_phonepay extends kcp_card_gateway
{
    public function get_the_id() {
        $pay_ids = gnupay_kcp_get_settings('pay_ids');

        return $pay_ids['phone'];   //계좌이체
    }

    public function get_the_title(){
        return __('KCP 휴대폰결제', GNUPAY_NAME);
    }

    public function get_the_description() {
        
        $return_html = '';

        $return_html = __( 'KCP 휴대폰결제입니다.', GNUPAY_NAME );
        
        if( $error = $this->pay_bin_check() ){
            
        }
        
        return $return_html;
    }

    public function init_form_fields(){
        $config = $this->config;

        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'woocommerce'),
                'type' => 'checkbox',
                'label' => sprintf(__('%s 를 활성화합니다.', GNUPAY_NAME), $this->get_the_title()),
                'default' => 'no',
            ),
            'title' => array(
                'title' => __('Title', 'woocommerce'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'woocommerce'),
                'default' => $this->get_the_title(),
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => __('Description', 'woocommerce'),
                'type' => 'textarea',
                'default' => sprintf(__('%s 로 결제합니다.', GNUPAY_NAME), $this->get_the_title()),
            ),
            'instructions' => array(
                'title' => __('Instructions', 'woocommerce'),
                'type' => 'textarea',
                'description' => __('Instructions that will be added to the thank you page.', 'woocommerce'),
                'default' => '',
                'desc_tip' => true,
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

    public function process_admin_options(){
        $result = parent::process_admin_options();
    
        if( $result ){
            $options = apply_filters( 'woocommerce_settings_api_sanitized_fields_' . $this->id, $this->sanitized_fields );

            $kcp_options = get_option( $this->plugin_id . $this->gnupay_kcp_card . '_settings' );
            
            $op_enabled = $options['enabled'] == 'yes' ? 1 : 0;

            if(isset($kcp_options['de_hp_use']) && $op_enabled != $kcp_options['de_hp_use']){

                $kcp_options['de_hp_use'] = $op_enabled;

                update_option( $this->plugin_id . $this->gnupay_kcp_card . '_settings', $kcp_options );
            }
        }

        return $result;
    }
}   //end class kcp_phonepay

?>