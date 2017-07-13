<?php
if( ! defined( 'ABSPATH' ) ) exit;

include_once(GNUPAY_KCP_PATH.'kcp/settle_kcp.inc.php');
require_once(GNUPAY_KCP_PATH.'kcp/KCPComLibrary.php');              // library [수정불가]

    // 쇼핑몰 페이지에 맞는 문자셋을 지정해 주세요.
    $charSetType      = 'utf-8';             // UTF-8인 경우 "utf-8"로 설정

    $siteCode         = isset($_GET[ 'site_cd'     ]) ? sanitize_text_field($_GET['site_cd']) : '';
    $orderID          = isset($_GET[ 'ordr_idxx'   ]) ? sanitize_text_field($_GET['ordr_idxx']) : '';
    $paymentMethod    = isset($_GET[ 'pay_method'  ]) ? sanitize_text_field($_GET['pay_method']) : '';
    $escrow           = gc_request_key_check('escw_used', 'get') == 'Y' ? true : false;
    $productName      = isset($_GET[ 'good_name'   ]) ? sanitize_text_field($_GET['good_name']) : '';

    // 아래 두값은 POST된 값을 사용하지 않고 서버에 SESSION에 저장된 값을 사용하여야 함.
    $paymentAmount    = isset($_GET[ 'good_mny'    ]) ? sanitize_text_field($_GET['good_mny']) : ''; // 결제 금액
    $returnUrl        = isset($_GET[ 'Ret_URL'     ]) ? sanitize_text_field($_GET['Ret_URL']) : '';

    // Access Credential 설정
    $accessLicense    = '';
    $signature        = '';
    $timestamp        = '';

    // Base Request Type 설정
    $detailLevel      = '0';
    $requestApp       = 'WEB';
    $requestID        = $orderID;
    $userAgent        = $_SERVER['HTTP_USER_AGENT'];
    $version          = '0.1';

    try
    {
        $payService = new PayService( $g_wsdl );

        $payService->setCharSet( $charSetType );

        $payService->setAccessCredentialType( $accessLicense, $signature, $timestamp );
        $payService->setBaseRequestType( $detailLevel, $requestApp, $requestID, $userAgent, $version );
        $payService->setApproveReq( $escrow, $orderID, $paymentAmount, $paymentMethod, $productName, $returnUrl, $siteCode );

        $approveRes = $payService->approve();

        printf( "%s,%s,%s,%s", $payService->resCD,  $approveRes->approvalKey,
                               $approveRes->payUrl, $payService->resMsg );

    }
    catch (SoapFault $ex )
    {
        printf( "%s,%s,%s,%s", "95XX", "", "", "연동 오류 (PHP SOAP 모듈 설치 필요)" );
    }

    exit;
?>