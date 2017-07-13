<?php
if( ! defined( 'GNUPAY_NAME' ) ) exit; // 개별 페이지 접근 불가

if( wp_is_mobile() ){  //모바일이면
    include( __DIR__ ."/m_orderform.3.php");
    return;
}


?>