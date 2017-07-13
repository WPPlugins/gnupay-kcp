<?php
if( ! defined( 'ABSPATH' ) ) exit;

class GNUPAY_KCP_SETTING {
    public $gateways = array();
    public $pay_ids = array();

    public static function getInstance()
    {
        static $instance = null;
        if (null === $instance) {
            $instance = new self();
        }

        return $instance;
    }

    protected function __construct() {
    }

    protected function get_gateways() {     //class 명
        $this->gateways = array(
                'kcp_card_gateway',
                'kcp_virtualaccount',
                'kcp_accounttransfer',
                'kcp_phonepay',
                'kcp_easypay',
            );
    }

    protected function get_pay_ids() {
        $this->pay_ids = array(
                'card' => 'kcp_creditcard',   //신용카드 id
                'vbank' => 'kcp_virtualaccount', //가상계좌 id
                'bank' => 'kcp_accounttransfer', //계좌이체 id
                'phone' => 'kcp_phonepay', //휴대폰 id
                'easy' => 'kcp_easypay', //간편결제 id
            );
    }

    public function get_options($options='gateways') {
        if( ! $this->gateways && $options == 'gateways' ){
            $this->get_gateways();
        } else if ( ! $this->pay_ids && $options == 'pay_ids' ){
            $this->get_pay_ids();
        }

        return $this->$options;
    }
}
?>