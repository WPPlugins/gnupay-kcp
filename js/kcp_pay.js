/****************************************************************/
/* m_Completepayment  설명                                      */
/****************************************************************/
/* 인증완료시 재귀 함수                                         */
/* 해당 함수명은 절대 변경하면 안됩니다.                        */
/* 해당 함수의 위치는 payplus.js 보다먼저 선언되어여 합니다.    */
/* Web 방식의 경우 리턴 값이 form 으로 넘어옴                   */
/* EXE 방식의 경우 리턴 값이 json 으로 넘어옴                   */
/****************************************************************/
function m_Completepayment( FormOrJson, closeEvent ) 
{
    var frm = document.gnupay_kcp_form; 
 
    /********************************************************************/
    /* FormOrJson은 가맹점 임의 활용 금지                               */
    /* frm 값에 FormOrJson 값이 설정 됨 frm 값으로 활용 하셔야 됩니다.  */
    /* FormOrJson 값을 활용 하시려면 기술지원팀으로 문의바랍니다.       */
    /********************************************************************/
    GetField( frm, FormOrJson ); 

    
    if( frm.res_cd.value == "0000" )
    {
        /*
            가맹점 리턴값 처리 영역
        */
     
        frm.submit(); 
    }
    else
    {
        alert( "[" + frm.res_cd.value + "] " + frm.res_msg.value );
        
        closeEvent();
    }
}

/* global wc_checkout_params */
jQuery( function( $ ) {

	// wc_checkout_params is required to continue, ensure the object exists
	if ( typeof wc_checkout_params === 'undefined' ) {
		return false;
	}
    
    var sir_checkout_form = {
        $checkout_form: $( 'form.checkout' ),
        $order_review: $( '#order_review' ),
        $kcp_pay_form: $( '#gnupay_kcp_form' ),
		submit_error: function( error_message ) {

            var $form = this.$checkout_form;

			$( '.woocommerce-error, .woocommerce-message' ).remove();
			$form.prepend( error_message );
			$form.removeClass( 'processing' ).unblock();
			$form.find( '.input-text, select' ).blur();

			$( 'html, body' ).animate({
				scrollTop: ( $form.offset().top - 100 )
			}, 1000 );
			$( document.body ).trigger( 'checkout_error' );

		},
        form_set : function(f, json){

            if(json.shipping_last_name == ''){
                json.shipping_last_name = json.billing_last_name;
            }
            if(json.shipping_first_name == ''){
                json.shipping_first_name = json.billing_first_name;
            }
            if(json.shipping_company == ''){
                json.shipping_company = json.billing_company;
            }
            if(json.shipping_address_1 == ''){
                json.shipping_address_1 = json.billing_address_1;
            }
            if(json.shipping_address_2 == ''){
                json.shipping_address_2 = json.billing_address_2;
            }
            if(json.shipping_city == ''){
                json.shipping_city = json.billing_city;
            }
            if(json.shipping_state == ''){
                json.shipping_state = json.billing_state;
            }
            if(json.shipping_postcode == ''){
                json.shipping_postcode = json.billing_postcode;
            }
            if(json.shipping_country == ''){
                json.shipping_country = json.billing_country;
            }
            if(json.shipping_email == ''){
                json.shipping_email = json.billing_email;
            }
            if(json.shipping_phone == ''){
                json.shipping_phone = json.billing_phone;
            }

            f.ordr_idxx.value = json.order_id;
            f.good_name.value = json.goods;
            f.good_mny.value = json.tot_price;
            f.bask_cntx.value = json.goods_count;
            f.good_info.value = json.good_info;

            if(f.comm_tax_mny !== undefined) f.comm_tax_mny.value = json.comm_tax_mny;
            if(f.comm_vat_mny !== undefined) f.comm_vat_mny.value = json.comm_vat_mny;
            if(f.comm_free_mny !== undefined) f.comm_free_mny.value = json.comm_free_mny;

            f.buyr_name.value = json.billing_last_name + json.billing_first_name;
            f.buyr_mail.value = json.billing_email;
            f.buyr_tel1.value = json.billing_phone;
            f.buyr_tel2.value = json.billing_phone;

            f.rcvr_name.value = json.shipping_last_name + json.shipping_first_name
            f.rcvr_tel1.value = json.shipping_phone;
            f.rcvr_tel2.value = json.shipping_phone;
            f.rcvr_mail.value = json.shipping_email;
            f.rcvr_zipx.value = json.shipping_postcode;
            f.rcvr_add1.value = ' ' + json.shipping_city + ' ' + json.shipping_address_1;
            f.rcvr_add2.value = json.shipping_address_2;

        },
        kcp_mobile_submit : function(f, json){

            var othis = this,
                $woo_form = othis.$checkout_form,
                data_array = $woo_form.serializeArray(),
                result = {};

            $.each( data_array, function() {
                result[this.name] = this.value;
            });

            var settle_method = result.payment_method;

            f.ordr_idxx.value = json.order_id;
            f.order_key.value = json.order_key;
            f.payco_direct.value = "";
            f.settle_method.value = settle_method;

            if( settle_method == 'kcp_easypay' ){

                if( f.is_test.value ){  //테스트이면
                    f.site_cd.value      = "S6729";
                }  
                //f.pay_method.value   = "100000000000";
                f.payco_direct.value = "Y";

            }

            /*
            switch(settle_method)
            {
                case "kcp_accounttransfer":    // 계좌이체
                    f.pay_method.value   = "010000000000";
                    break;
                case "kcp_virtualaccount":    //가상계좌
                    f.pay_method.value   = "001000000000";
                    break;
                case "kcp_phonepay":  //휴대폰
                    f.pay_method.value   = "000010000000";
                    break;
                case "kcp_creditcard":    //신용카드
                    f.pay_method.value   = "100000000000";
                    break;
                case "kcp_easypay":     //간편결제
                default:
                    if( f.is_test.value ){  //테스트이면
                        f.site_cd.value      = "S6729";
                    }  
                    f.pay_method.value   = "100000000000";
                    f.payco_direct.value = "Y";
            }
            */

            sir_checkout_form.form_set(f, json);

            // 주문 정보 임시저장
            var order_data = jQuery(f).serialize();
            
            order_data += "&action="+encodeURIComponent('kcp_orderdatasave');

            var save_result = "";

            jQuery.ajax({
                type: "POST",
                data: order_data,
                url: gnupay_kcp_object.ajaxurl,
                cache: false,
                async: false,
                success: function(data) {
                    save_result = data;
                }
            });

            $woo_form.removeClass( 'processing' ).unblock();

            if(save_result) {
                alert(save_result);
                return false;
            }

            f.submit();

        },
        kcp_pay_submit: function(f, json){

            // 금액체크
            
            var othis = this,
                $woo_form = othis.$checkout_form,
                data_array = $woo_form.serializeArray(),
                result = {};

            $.each( data_array, function() {
                result[this.name] = this.value;
            });

            var settle_method = json.payment_method ? json.payment_method : result.payment_method;

            f.ordr_idxx.value = json.order_id;
            f.order_key.value = json.order_key;
            f.site_cd.value = f.def_site_cd.value;
            f.payco_direct.value = "";

            switch(settle_method)
            {
                case "kcp_accounttransfer":    // 계좌이체
                    f.pay_method.value   = "010000000000";
                    break;
                case "kcp_virtualaccount":    //가상계좌
                    f.pay_method.value   = "001000000000";
                    break;
                case "kcp_phonepay":  //휴대폰
                    f.pay_method.value   = "000010000000";
                    break;
                case "kcp_creditcard":    //신용카드
                    f.pay_method.value   = "100000000000";
                    break;
                case "kcp_easypay":     //간편결제
                default:

                    if( parseInt(f.is_test.value) > 0 ){  //테스트이면
                        f.site_cd.value      = "S6729";
                    }

                    f.pay_method.value   = "100000000000";
                    f.payco_direct.value = "Y";
            }

            sir_checkout_form.form_set(f, json);

            $woo_form.removeClass( 'processing' ).unblock();

            return othis.jsf__pay( f );

        },
        //Payplus Plug-in 실행/
        jsf__pay : function( form )
        {

			try
			{
	            KCP_Pay_Execute( form ); 
			}
			catch (e)
			{
				/* IE 에서 결제 정상종료시 throw로 스크립트 종료 */ 
			}

            /*
            var RetVal = false;

            //Payplus Plugin 실행
            if ( MakePayMessage( form ) == true )
            {
                RetVal = true ;
            }
            else
            {
                //res_cd와 res_msg변수에 해당 오류코드와 오류메시지가 설정됩니다.
                //ex) 고객이 Payplus Plugin에서 취소 버튼 클릭시 res_cd=3001, res_msg=사용자 취소
                //값이 설정됩니다.
                res_cd  = form.res_cd.value;
                res_msg = form.res_msg.value;

                alert( res_cd + ' : ' + res_msg );
            }

            return RetVal ;
            */
        }
    }

    // Trigger a handler to let gateways manipulate the checkout if needed
    // kcp 결제를 선택했을 경우에만 실행
    sir_checkout_form.$checkout_form.on("checkout_place_order_gnupay_kcp checkout_place_order_kcp_creditcard checkout_place_order_kcp_virtualaccount checkout_place_order_kcp_accounttransfer checkout_place_order_kcp_phonepay checkout_place_order_kcp_easypay", gnupay_kcp_checkout_submit);

    sir_checkout_form.$order_review.on('submit', function(e){

        var payment_method = sir_checkout_form.$order_review.find( 'input[name="payment_method"]:checked' ).val();
        var methods = ['kcp_creditcard', 'kcp_virtualaccount', 'kcp_accounttransfer', 'kcp_phonepay', 'kcp_easypay'];

        if ( $.inArray(payment_method, methods) !== -1 ){
            e.preventDefault();

            var $form = $( this ),
            formdata = $form.serialize();

            formdata = formdata+'&action=gnupay_kcp_pay_for_order&order_id='+gnupay_kcp_object.order_id;

            $.ajax({
                type:		'POST',
                url:		gnupay_kcp_object.ajaxurl,
                data:		formdata,
                dataType:   'json',
                success:	function( result ) {

                    if( result.result === 'success' ){
                        if( gnupay_kcp_object.is_mobile ){   //모바일결제

                            sir_checkout_form.kcp_mobile_submit( sir_checkout_form.$kcp_pay_form[0], result );

                        } else {    //pc 결제

                            sir_checkout_form.kcp_pay_submit( sir_checkout_form.$kcp_pay_form[0], result );

                        }
                    } else {
                        if( result.error_msg !== undefined ){
                            alert( result.error_msg );
                        } else {
                            alert( 'error' );
                        }
                    }
                },
                error:	function( jqXHR, textStatus, errorThrown ) {
                    alert( errorThrown );
                }
            });

            return false;
        }

    });

    function gnupay_kcp_checkout_submit(){
        var $form = $( this );

        if ( $form.is( '.processing' ) ) {
            return false;
        }

        $form.addClass( 'processing' );

        var form_data = $form.data();

        if ( 1 !== form_data['blockUI.isBlocked'] ) {
            $form.block({
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                }
            });
        }

        // ajaxSetup is global, but we use it to ensure JSON is valid once returned.
        $.ajaxSetup( {
            dataFilter: function( raw_response, dataType ) {
                // We only want to work with JSON
                if ( 'json' !== dataType ) {
                    return raw_response;
                }

                try {
                    // check for valid JSON
                    var data = $.parseJSON( raw_response );

                    if ( data && 'object' === typeof data ) {

                        // Valid - return it so it can be parsed by Ajax handler
                        return raw_response;
                    }

                } catch ( e ) {

                    // attempt to fix the malformed JSON
                    var valid_json = raw_response.match( /{"result.*"}/ );

                    if ( null === valid_json ) {
                        //console.log( 'Unable to fix malformed JSON' );
                    } else {
                        //console.log( 'Fixed malformed JSON. Original:' );
                        //console.log( raw_response );
                        raw_response = valid_json[0];
                    }
                }

                return raw_response;
            }
        } );

        $.ajax({
            type:		'POST',
            url:		wc_checkout_params.checkout_url,
            data:		$form.serialize(),
            dataType:   'json',
            success:	function( result ) {
                
                try {
                    if ( result.result === 'success' ) {

                        if( gnupay_kcp_object.is_mobile ){   //모바일결제

                            sir_checkout_form.kcp_mobile_submit( sir_checkout_form.$kcp_pay_form[0], result );

                        } else {    //pc 결제

                            sir_checkout_form.kcp_pay_submit( sir_checkout_form.$kcp_pay_form[0], result );

                        }

                        /*
                        if ( -1 === result.redirect.indexOf( 'https://' ) || -1 === result.redirect.indexOf( 'http://' ) ) {
                            console.log( result );
                            //kcp_pay_submit( sir_checkout_form.$kcp_pay_form[0] );
                            //window.location = result.redirect;
                        } else {
                            console.log( result );
                            //kcp_pay_submit( sir_checkout_form.$kcp_pay_form[0] );
                            //window.location = decodeURI( result.redirect );
                        }
                        */

                    } else if ( result.result === 'failure' ) {
                        throw 'Result failure';
                    } else {
                        throw 'Invalid response';
                    }
                } catch( err ) {
                    // Reload page
                    if ( result.reload === 'true' ) {
                        window.location.reload();
                        return;
                    }

                    // Trigger update in case we need a fresh nonce
                    if ( result.refresh === 'true' ) {
                        $( document.body ).trigger( 'update_checkout' );
                    }

                    if ( result.messages ) {
                        sir_checkout_form.submit_error( result.messages );
                    }
                    /*
                    if( err != 'EXE 예외처리로 인한 업무로직 강제 중단' ){
                        console.log( err );

                        // Add new errors
                        if ( result.messages ) {
                            sir_checkout_form.submit_error( result.messages );
                        } else {
                            sir_checkout_form.submit_error( '<div class="woocommerce-error">' + wc_checkout_params.i18n_checkout_error + '</div>' );
                        }
                    }
                    */
                }
            },
            error:	function( jqXHR, textStatus, errorThrown ) {
                sir_checkout_form.submit_error( '<div class="woocommerce-error">' + errorThrown + '</div>' );
            }
        });

        return false;
    }
});