jQuery('#button-api-iframe').css('display' ,'none');
// var console = { log: function() {} };
jQuery(document).ready(function($ ) {
	console.log("Tapify : custom js loadeds :)");
    var tpfLoadStatus = false;

	 var tapifyGetUrlParameter = function (sParam) {
        var sPageURL = decodeURIComponent(window.location.search.substring(1)),
            sURLVariables = sPageURL.split('&'),
            sParameterName,
            i;
    
        for (i = 0; i < sURLVariables.length; i++) {
            sParameterName = sURLVariables[i].split('=');
    
            if (sParameterName[0] === sParam) {
                return sParameterName[1] === undefined ? true : sParameterName[1];
            }
        }
    };

	if(typeof setCookie !=  'function') {
        function setCookie(cname, cvalue, exdays) {
            var d = new Date();
            d.setTime(d.getTime() + (exdays*24*60*60*1000));
            var expires = "expires="+ d.toUTCString();
            document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
        }
    }

	if (typeof tapifyGetUrlParameter === 'function') {
        var tpref = tapifyGetUrlParameter('tpref');
        if(tpref && tpref.length > 0) {
            if (typeof setCookie === 'function') {
            	setCookie('_tpref', tpref , 30 ); 
            }
        }
	} 
	
	// if(typeof tapifyGetCurrentLocation !=  'function') {
	//     var tapifyGetCurrentLocation = function() {
	//         if( !getCookie('tapify_current_location') ) {
	//             $.getJSON('https://ipapi.co/json/', function(data) {
	//                 if( data && data.region_code && data.country ){
	//                 	var currentLocation = 'country:' + data.country + ',region_code:'+data.region_code+',postal:'+data.postal;
	//                     setCookie('tapify_current_location', currentLocation , 365 ); 
	//                     tapifyReloadCart();
	//                     return getCookie('tapify_current_location');
	//                 }
	//             });
	//         }else{
	//         	return getCookie('tapify_current_location');
	//         }
	//     }
	// }

	// tapifyGetCurrentLocation()

	$('#button-api-iframe').css('display' ,'none');
	$('#button-api-iframe').load(function(){
	    console.log( "tpf_widgetStatus==>" , window.tpf_widgetStatus , window.tpf_is_synced)	
		if( window.tpf_widgetStatus && window.tpf_widgetStatus == 1 )
			$('#button-api-iframe').css('display' ,'block');
		
		// if( window.tpf_is_woocommrerce == "2"){
        //     if( window.tpf_is_synced  ){
        //         $('#button-api-iframe').css('display' ,'block');
        //         getBloggerSyncedProduct();  
        //     } else $('#button-api-iframe').css('display' ,'none');
		// }

		// tapifyReloadCart();
		document.getElementById('button-api-iframe').contentWindow.postMessage( { "isUserLogin":true  }  , '*');
        tapifyPostMessage({ "tpf_platform_status":true , "tpf_is_woocommrerce":window.tpf_is_woocommrerce });

		bindEvent(window, 'message', function (e) {
			var resData = e.data;
            console.log( "e.data---->", e.data);

            if(resData && resData.status == `tpfInitialLoad` )
                tapifyPostMessage({ tpf_platform_status:true, tpf_is_woocommrerce:window.tpf_is_woocommrerce });

            // if( !tpfLoadStatus ){
                // tapifyPostMessage({ "tpf_platform_status":true , "tpf_is_woocommrerce":window.tpf_is_woocommrerce });
                tapifyPostMessage({"getTapifyUser":true});
                tpfLoadStatus =true;
            // }
			
	    	if(resData && resData.status == "loginStatus" ){
               // setCookie('tpfUserStatus', resData.jwt , 1 ); 
               // setCookie('tpfVisitorKey', resData.visitorKey , 1 ); 
               	if( window.tpf_is_woocommrerce != "2" )
               		tapifyUpdateCartLog( resData.jwt , resData.visitorKey  );
            }

            if(resData && resData.status == "getStoreAccessKey" ){
                var postMessageObj = { 
                        postStoreAccessKey  :true, "key":window.tpf_store_access_key ,
                        pageUrl             :window.location.href, _id  :window.tpf_is_synced ,  
                        life_long_cookie    :getCookie('tapify_life_long_cookie')
                    };
                tapifyPostMessage( postMessageObj );
            }

            /*
             * Will be called after cookie updated in nodeserve
             * */
            if(resData && resData.status == "tpfCookieUpdated" ){
               if( window.tpf_is_woocommrerce == "2"){
                    if( window.tpf_is_synced  ){
                        $('#button-api-iframe').css('display' ,'block');
                    } else $('#button-api-iframe').css('display' ,'none');
                }
                tapifyReloadCart();
            }


	    	if(resData && resData.status == "clearCart" ){
               tapifyClearCart( resData.currentTab ); 
            }

            if(resData && resData.status == "tpfInitialLoad" ){
                console.log( "tpfInitialLoad" )
                if( window.tpf_is_woocommrerce == "2"){
                    if( window.tpf_is_synced  ){
                        $('#button-api-iframe').css('display' ,'block');
                        // getBloggerSyncedProduct();  
                    } else $('#button-api-iframe').css('display' ,'none');
                }
                tapifyReloadCart();
            }

            if(resData && resData.status == "getUID" ){
               	getUniqueId(); 
			}

            if(resData && resData.status == "set_thirdparty_cookie" ){
                var data = resData.data ;
                tpfSetThirdpartyCookie(data); 
            }
			
			if(resData && resData.status == "tpfApplyCouponCode" ){
				delete resData.status;
				setCookie('tpfCouponStatus', JSON.stringify(resData) , 1 ); 
				tpfApplyCouponCode( resData.action ,resData.code ,resData.product_id ,resData.type ); 
            }

            if(resData && resData.status == "setAsDefault" ){
            	if( window.tpf_is_woocommrerce == "2" ) {
    				var selectedVariations = resData.selectedVariations ? resData.selectedVariations : new Object() ;
    				tapifyBloggerSyncQuantity( resData.count , selectedVariations );
    			}else tpfSetAsDefault( resData.jwt ); 
            }

	    	if(resData && resData.status == "open" ){
	    		// document.getElementById('button-api-iframe').style['max-width'] = '700px';
                document.getElementById('button-api-iframe').style.width 	= '100%';
                document.getElementById('button-api-iframe').style.height 	= $(window).innerHeight()+"px";
                $( "body" ).addClass( "widget-open" );
                
            }else if( resData && resData.status == "close" ){
            	// document.getElementById('button-api-iframe').style['max-width'] = '425px';
                document.getElementById('button-api-iframe').style.width 	= '300px';
                document.getElementById('button-api-iframe').style.height 	= '120px';
                $( "body" ).removeClass( "widget-open" );
                tapifyReloadCart();
            }

            if(resData && resData.status == "tpfDynamicWidgetSizeOpen" ){
            	var data = resData.data ;
	    		document.getElementById('button-api-iframe').style['max-width'] = data.maxWidth;
                document.getElementById('button-api-iframe').style.width 	= data.width;
                document.getElementById('button-api-iframe').style.height 	= $(window).innerHeight()+"px";
                $( "body" ).addClass( "widget-open" );
                
            }else if( resData && resData.status == "tpfDynamicWidgetSizeClose" ){
            	document.getElementById('button-api-iframe').style['max-width'] = data.maxWidth;
                document.getElementById('button-api-iframe').style.width 	= data.width;
                document.getElementById('button-api-iframe').style.height 	= '120px';
                $( "body" ).removeClass( "widget-open" );
                tapifyReloadCart();
            }

            if(resData && resData.status == "addTocart" ){
            	
            	if( resData.data ){
            		var data = resData.data ;
            		if( data._id && data.count ){
            			tapifyAddToCart( data._id , data.variation_id ,data.count , data.attributes) ;
            		}else console.log("Error :: Data missing");
            	}else console.log("Error :: Data missing");
               
            }

            if(resData && resData.status == "quantityUpdated" ){
            	if( resData.data ){
            		var data = resData.data ;
            		if( data.count ){
            			if( window.tpf_is_woocommrerce == "2" ) {
            				var selectedVariations = data.selectedVariations ? data.selectedVariations : new Object() ;
            				tapifyBloggerSyncQuantity( data.count , selectedVariations );
            			}
            			else tapifySyncQuantity( data.count ) ;
            		}else console.log("Error :: Quantity missing");
            	}else console.log("Error :: Data missing");
            }

            if(resData && resData.status == "tpfcartQuantityUpdated" ){
            	if( resData.data ){
            		var data = resData.data ;
            		if( data.count && data.key){
            			tapifyUpdateCartQuantity( data.count , data.key ) ;
            		}else console.log("Error :: count missing");
            	}else console.log("Error :: Data missing");
               
            }

            if(resData && resData.status == "tpfLogout" ){
            	if( resData.data ){
            		// setCookie('tpfUserStatus', null , 1 ); 
            		var data = resData.data ;
            		if( data.count ){
            			if( window.tpf_is_woocommrerce == "2" ) {
            				var selectedVariations = data.selectedVariations ? data.selectedVariations : new Object() ;
            				// getBloggerSyncedProduct( data.count , selectedVariations );
            			}
            			else tapifySyncQuantity( data.count ) ;
            		}else console.log("Error :: Quantity missing");
            	}else console.log("Error :: Data missing");
            }

            if(resData && resData.status == "removeCart" ){
            	if( resData.data ){
            		var data = resData.data ;
            		if( data.key  ){
            			tapifyRemoveCart( data.key ) ;
            		}else console.log("Error :: Data missing");
            	}else console.log("Error :: Data missing");
               
            }

            if(resData && resData.status == "tpfchangeShipping" ){
                
            	if( resData.data ){
            		var data = resData.data ;
            		if( window.tpf_is_woocommrerce == "2" ) {
            			if( data.requested ) {            				
            				tapifyChangeShippingMethodBlogger( data );
            			}
            			else 
            				tapifyPostMessage({"validationFailed":true, "message":  "Requested platform missing!" })
            		}
        			else tapifyChangeShippingMethod( data.id , data.item );
            	}else console.log("Error :: Data missing");
               
            }

            if( resData && resData.status == "proceedtoPay" ){
            	if( resData.data ){
            		var data = resData.data ;

            		if( data.tab  ){
            			if( window.tpf_is_woocommrerce == "2" ) {
            				var selectedVariations = data.selectedVariations ? data.selectedVariations : new Object() ;
            				tapifyProceedToPayBloger( data.tab , data.quantity , selectedVariations );
            			}else tapifyProceedToPay( data.tab );
            		}else console.log("Error :: Data missing");
            	}else console.log("Error :: Data missing");
            	
            }else if( resData && resData.status == "paymentSuccess" ){
            	completeWcOrder( resData );
            }else if( resData && resData.status == "paymentFail" ){
            	alert("Error :: Oops!Paymnet failed,please refresh and try again!");
            }
        });

		function bindEvent(element, eventName, eventHandler) {
            if (element.addEventListener){
                element.addEventListener(eventName, eventHandler, false);
            } else if (element.attachEvent) {
                element.attachEvent('on' + eventName, eventHandler);
            }
        }

	});


	function tapifyProceedToPay( tabStatus ){
		if( !tabStatus ) return false;

		var productId  		= $('#button-api-iframe').attr('data-pr-id');
    	var productQty 		= $('#button-api-iframe').attr('data-pr-qty');
    	var variation_id 	= $('#button-api-iframe').attr('data-variation');
    	var currency   		= window.tpf_store_currency;
    	var storeAccessKey  = window.tpf_store_access_key;

    	if($('.tpf-iframe').hasClass('disabled')  ) {
    		window.focus();
    		return false;
    	} 
	
		if( $('.tpf-iframe').hasClass('isCart') ){

    		jQuery.ajax({
			    type: 'POST',
			    url: tapifyajax.ajaxurl,
			    data : {
			   			action:"tapify_in_cart_page",
			   			// userStatus:getCookie('tpfUserStatus')
			   		},
			    success: function(response) {
			    	$('.tpf-iframe').removeClass('disabled');
			    	if(response.status){
			    		var resposeData = response.data;
			    		var stripeParams = { "returnStatus":true, "uuid": resposeData.uuid  ,"key" : resposeData.key ,"storeAccessKey" : storeAccessKey , "currency" : currency ,"tabStatus":'cart'};
						console.log( "stripeParams==>1" , stripeParams );
						document.getElementById('button-api-iframe').contentWindow.postMessage( stripeParams , '*');

			    	}else{
			    		document.getElementById('button-api-iframe').contentWindow.postMessage( {"validationFailed":true , "message" : response.message }  , '*');
			    		console.log("response ==> ", response )
			    	}
			    }
			});

    	}else if(!$('.single_add_to_cart_button').hasClass('disabled') || tabStatus == 'cart' ){
    		if(  productQty && productQty!= null ) {

    			$('.tpf-iframe').addClass('disabled');
		    	jQuery.ajax({
				    type: 'POST',
				    url: tapifyajax.ajaxurl,
				    data : {
				   			tabStatus:tabStatus,
				   			product_id:productId,
				   			variation_id:variation_id,
				   			count:productQty,
				   			// userStatus:getCookie('tpfUserStatus'),
				   			action:"tapify_add_product_to_cart"
				   		},
				    success: function(response) {
				    	$('.tpf-iframe').removeClass('disabled');
				    	if(response.status){
				    		var resposeData = response.data;


				    		var stripeParams = { "returnStatus":true, "uuid": resposeData.uuid  ,"key" : resposeData.key ,"storeAccessKey" : storeAccessKey , "currency" : currency , "tabStatus":tabStatus} ;
							console.log( "stripeParams==>" , stripeParams );
							document.getElementById('button-api-iframe').contentWindow.postMessage( stripeParams , '*');
				    	}else{
				    		document.getElementById('button-api-iframe').contentWindow.postMessage( {"validationFailed":true , "message" : response.data }  , '*');
				    		console.log( "response ==> ", response )
				    	}
				    }
				});
			}else{
				document.getElementById('button-api-iframe').contentWindow.postMessage( {"validationFailed":true ,"message":"Product quantity missing!" }  , '*');
			}
		}else{
			document.getElementById('button-api-iframe').contentWindow.postMessage( {"validationFailed":true , "message" : "Please select some product option before adding this product to cart !" }  , '*');
		}
	
	}

	function tapifyProceedToPayBloger( tabStatus , quantity  , selectedVariations =  new Object() ){
		if( !tabStatus ) return false;

		// var productId  		= $('#button-api-iframe').attr('data-pr-id');
        // var productQty 		= $('#button-api-iframe').attr('data-pr-qty');
        // var variation_id 	= $('#button-api-iframe').attr('data-variation');
    	// var currency   		= "USD";
    	// var storeAccessKey  = window.tpf_store_access_key;
    	if($('.tpf-iframe').hasClass('disabled')  ) {
    		window.focus();
    		return false;
    	} 
	    console.log("#############", tabStatus , quantity  , selectedVariations  )
		jQuery.ajax({
		    type: 'POST',
		    url: tapifyajax.ajaxurl,
		    data : {
		   			tabStatus:tabStatus,
		   			postId:window.tpf_post_id,
		   			_id:window.tpf_is_synced,
		   			selectedVariations:selectedVariations,
		   			// variation_id:variation_id,
		   			count:quantity,
		   			// userStatus:getCookie('tpfUserStatus'),
		   			action:"tapify_add_product_to_cart_blogger"
		   		},
		    success: function(response) {
		    	$('.tpf-iframe').removeClass('disabled');
		    	if(response.status){
		    		var resposeData = response.data;
		    		var stripeParams = { 
		    			"returnStatus":true, "uuid": resposeData.uuid ,
		    			"key" : resposeData.key ,"storeAccessKey" : resposeData.storeAccessKey ,
		    			"tabStatus":tabStatus , "orderPlacedFrom":resposeData.orderPlacedFrom 
		    		} ;
					console.log( "stripeParams==>" , stripeParams );
					document.getElementById('button-api-iframe').contentWindow.postMessage( stripeParams , '*');
		    	}else{
		    		document.getElementById('button-api-iframe').contentWindow.postMessage( {"validationFailed":true , "message" : response.message }  , '*');
		    		console.log( "response ==> ", response )
		    	}
		    }
		});
	
	}

	function paymentFailled( message ){

	}
	function completeWcOrder( message ){

		var tpfStoreAccessKey   = window.tpf_store_access_key;
		if( message.storeAccessKey && message.storeAccessKey == tpfStoreAccessKey ){
    		var productId  = $('#button-api-iframe').attr('data-pr-id');
			var productQty = $('#button-api-iframe').attr('data-pr-qty');
			var variationId = jQuery('#button-api-iframe').attr('data-variation');

    		jQuery.ajax({
			    type: 'POST',
			    url: tapifyajax.ajaxurl,
			    dataType: "json",
			    data : {
			   			product_id:productId,
			   			variation_id:variationId,
			   			count:productQty,
			   			jwtToken:message.jwtToken,
			   			storeAccessKey:message.storeAccessKey,
			   			shippingAddress:message.addressInfo,
			   			action:"quickpay"
			   		},
			    success: function(response) {
			    	$('.tpf-iframe').removeClass('disabled');
			    	if(response.status){
			    		$('#button-api-iframe').remove();
			    		window.location.href = response.redirectrUrl;
			    	}else{
					alert(response.message);
					$('#button-api-iframe').remove();
			    		console.log("response ==> ", response )
			    	}
			    }
			});
	    }else{
	    	console.log("Message ==> ", message);
	    }
	}



    $( '.variations_form' ).each( function() {

        // when variation is found, do something
        $(this).on( 'found_variation', function( event, variation ) {

        	var attributes = {} , attributesString = '';
        	for (var key in variation.attributes ) {
        		 if (variation.attributes.hasOwnProperty(key)) {
        		 	attributes[key] = $('select[name="'+key+'"]').val();
        		 	attributesString += key + ':' + $('select[name="'+key+'"]').val()+' ,';
        		 }	
        	}

        	$('#button-api-iframe').attr('data-attributes' , attributesString ) ;
        	$('#button-api-iframe').attr('data-variation' , variation.variation_id ) ;
        	var productQty = $('#button-api-iframe').attr('data-pr-qty');
            jQuery.ajax({
			    type: 'POST',
			    url: tapifyajax.ajaxurl,
			    dataType: "json",
			    data : {
			   			cost:variation.display_price,
			   			productQty:productQty,
			   			attributes:attributes,
			   			action:"show_variable_price"
			   		},
			    success: function(response) {
			    	if(response.status){
			    		var tpfPriceParam = { "showPrice":true, "amount": response.cost , "attributes": attributes , "formatted_attributes" : response.formatted_attributes } ;		
						document.getElementById('button-api-iframe').contentWindow.postMessage( tpfPriceParam  , '*');
			    		tapifyReloadCart( attributes , response.formatted_attributes );
			    	}
			    }
			});
        });

    });
});




