	// var console = { log: function() {} };
	// var TBF_BUTTON_API= 'http://localhost:5000/';
	var TBF_BUTTON_API= 'https://btn.tapify.io/';
	// var TBF_BUTTON_API= 'https://stagingbtn.tapify.io/';

	jQuery(document).ready(function($ ) {
		console.log("Tapify : cart widget js loaded :)");
		jQuery( document ).on( 'click', '.reset_variations', function(e) {
			jQuery('#button-api-iframe').attr('data-variation' , 0 ) ;
			jQuery('#button-api-iframe').attr('data-pr-qty' , 1 ) ;
			jQuery('#button-api-iframe').attr('data-price' , null ) ;
			jQuery('#button-api-iframe').attr('data-attributes' , null ) ;
			jQuery('.cart .qty').val(1);
			tapifyReloadCart();		
		});
	});


	/**
	* Desc 	:Function triggures when cart quantity changes in both widget and website
	* Logic : Syncing the widget quantity with the website and viceversa
	*
	**/
	function tapifySyncQuantity( quantity ){
		jQuery('#button-api-iframe').attr('data-pr-qty' , quantity ) ;
		jQuery('.cart .qty').val( quantity );
		tpfQuantityUpdated();
	}

	/**
	* Desc 	: Function triggures when any updations made in cart/products tab
	* Logic : recalculate the cart/product objects
	*
	**/
	function tapifyReloadPrice(){
		tapifyReloadCart();
	}
	
	function tapifyReloadCart( attributes = false , formattedAttributes = false ){
		if( window.tpf_is_woocommrerce && window.tpf_is_woocommrerce == "2" ) return true;
		var productId  = productQty = variation_id = 0; var attributes = null; var attributes = formattedAttributes = {};
		if( !jQuery('.tpf-iframe').hasClass('isCart') ){

			productId  = jQuery('#button-api-iframe').attr('data-pr-id');
			productQty = jQuery('#button-api-iframe').attr('data-pr-qty');
			variation_id = jQuery('#button-api-iframe').attr('data-variation');
			attributeStr = jQuery('#button-api-iframe').attr('data-attributes');
			if(attributeStr){
				var attrPair = attributeStr.split(',');
				for (var key in attrPair ) {
	        		 if (attrPair.hasOwnProperty(key)) {
	        		 	if(attrPair[[key]]){
		        		 	var attrPair2 = attrPair[key].split(':');
		        		 	if(attrPair2 && attrPair2[0] && attrPair2[1])
		        		 		attributes[attrPair2[0]] = attrPair2[1];
		        		 }
	        		 	
	        		 }	
	        	}
			}

			jQuery.ajax({
			    type: 'POST',
			    url: tapifyajax.ajaxurl,
			    data : { action:"create_cart_object" , productId:productId , productQty:productQty ,variation_id:variation_id ,attributes:attributes , formattedAttributes:formattedAttributes },
			    success: function(response) {
			    	if( response.product ) {
				    	var responseObj = { "cartWidget": true,"data":response };
				    	console.log( JSON.stringify(responseObj ,null,4 )) ;
				  		document.getElementById('button-api-iframe').contentWindow.postMessage( responseObj  , '*');
				  	}
			    }
			});
		}else{
			var responseObj = { "cartWidget": true,"data":{} };
			document.getElementById('button-api-iframe').contentWindow.postMessage( responseObj  , '*');
		}	

	}

	/**
	* Desc 	: Remove a product from the cart
	* Logic : remove product from catrt and recalculate the crat objects
	*
	**/
	function tapifyRemoveCart( key  ){
		if( !key ) tapifyPostMessage({"validationFailed":true, "message":  "Cart key missing!" });
		productId  = jQuery('#button-api-iframe').attr('data-pr-id');
		jQuery.ajax({
		    type: 'POST',
		    url: tapifyajax.ajaxurl,
		    data : {
		   			key:key,
		   			productId:productId,
		   			action:"tapify_remove_product_from_cart"
		   		},
		    success: function(response) {
		    	if( response.this_product ) {
		    		jQuery('#button-api-iframe').attr('data-pr-qty' , 1 ) ;
		    		jQuery('.cart .qty').val(1);
		    	}
		    	getUniqueId();
		    	tapifyReloadCart();
		    }
		});
	}


	/**
	* Desc 	: Change shipping method
	* Logic : Save shosen shipping method in cookie
	*
	**/
	function tapifyChangeShippingMethod( id , tab  ){
		console.log( id , tab)
		if( !id )  tapifyPostMessage({"validationFailed":true, "message":  "Shippingmethod id missing!" }); 
		jQuery.ajax({
		    type: 'POST',
		    url: tapifyajax.ajaxurl,
		    data : {
		   			id:id,
		   			tab:tab,
		   			action:"tapify_update_shipping_method"
		   		},
		    success: function(response) {
		    	if( response ){
		    		if(tab === 'product') tapifyReloadCart();
		    		else tapifyPostMessage( { "uniqueId" : true , "uuid": getCookie('tapify_life_long_cookie') , storeAccessKey:response.store_access_key} );
		    	}else tapifyPostMessage({"validationFailed":true, "message":  response.message })	
		    }
		    
		});
	}

	/**
	* Desc : Change shipping method ( for bloggers )
	* Logic : Save shosen shipping method in cookie based on the platform requested
	*
	**/
	function tapifyChangeShippingMethodBlogger( data , id , tab , requested ,selectedVariations ){
		var id = data.id ,tab = data.item, requested = data.requested , quantity = data.quantity;
		var selectedVariations = data.selectedVariations ? data.selectedVariations : new Object() ;

		if( !id ) tapifyPostMessage({"validationFailed":true, "message":  "Shippingmethod id missing!" }); 
		if( requested === "woocommerce")
			setCookie('tpfChosenProductShipping', id  , 1 ); 
		else if( requested === "shopify"){

			setCookie('tpfChosenProductShopifyShipping', id  , 1 ); 
		}
		else {
			tapifyPostMessage({"validationFailed":true, "message":  "Invalid platform requested!" })
			return false;
		}
		getBloggerSyncedProduct( quantity , selectedVariations);
	}

	/**
	* Desc : hooks when order complete
	* Logic : remove the appied coupon , reset cart related stuffs
	*
	**/
	function tapifyClearCart( currentTab = false ){
		if( !currentTab ) 
			tapifyPostMessage({"validationFailed":true, "message":  "Current 'tab' status missing!" })
		setCookie('tapify_applied_cp', "", -30); 
		jQuery.ajax({
		    type: 'POST',
		    url: tapifyajax.ajaxurl,
		    data : {
		   			currentTab:currentTab,
		   			action:"tpf_order_complete"
		   		},
		    success: function(response) {
		    	tapifyReloadCart();
				tapifyPostMessage( { "uniqueId" : true , "uuid": response.uuid , storeAccessKey:response.store_access_key} );
		    }
		});
	}


function tapifyAddToCart( productId ,variation_id , quantity  ){
	if( !productId || !quantity ) 
		tapifyPostMessage({"validationFailed":true, "message":  "Product id or Quantity missing!" })

	var attributes = jQuery('#button-api-iframe').attr('data-attributes');
	jQuery.ajax({
	    type: 'POST',
	    url: tapifyajax.ajaxurl,
	    data : {
	   			product_id:productId,
	   			count:quantity,
	   			variation_id:variation_id,
	   			attributes:attributes,
	   			// userStatus:getCookie('tpfUserStatus'),
	   			action:"tapify_add_to_cart"
	   		},
	    success: function(response) {
	    	jQuery('#button-api-iframe').attr('data-pr-qty' , 1 ) ;
    		jQuery('.cart .qty').val(1);
	    	tpfQuantityUpdated();
	    	if(response.status){
				var tpfPriceParam = { "showPrice":true, "amount": response.amount } ;
				document.getElementById('button-api-iframe').contentWindow.postMessage( tpfPriceParam  , '*');
	    	}else{ console.log("response ==> ", response); }
	    }
	});
}


function tapifyUpdateCartQuantity( count ,key ){
	
	if( !count || !key ) tapifyPostMessage({"validationFailed":true, "message":  "Key or Count missing!" });

	jQuery.ajax({
	    type: 'POST',
	    url: tapifyajax.ajaxurl,
	    data : {
	   			count:count,
	   			key:key,
	   			// userStatus:getCookie('tpfUserStatus'),
	   			action:"tapify_update_cart_quantity"
	   		},
	    success: function(response) {
	    	if( response.uuid ){
	    		tapifyPostMessage( { "uniqueId" : true , "uuid": getCookie('tapify_life_long_cookie') , storeAccessKey:response.store_access_key} );
	    	}else tapifyPostMessage({"validationFailed":true, "message":  response.message })	
	    }
	});
}

function tapifyPostMessage( responseObj ){	
	if( responseObj.hasOwnProperty( 'validationFailed' ) ){
		if( responseObj.message && responseObj.message != 'undefined' && responseObj.message.trim() !== '' ){
			document.getElementById('button-api-iframe').contentWindow.postMessage( responseObj  , '*');
		}else console.log( "null postMessage==>" , responseObj);
	}else document.getElementById('button-api-iframe').contentWindow.postMessage( responseObj  , '*');
}
	

if(typeof getCookie !=  'function') {
    function getCookie(name) {
        var re = new RegExp(name + "=([^;]+)");
        var value = re.exec(document.cookie);
        return (value != null) ? unescape(value[1]) : null;
    }
}

if(typeof setCookie !=  'function') {
	function setCookie(cname, cvalue, exdays) {
		var d = new Date();
		d.setTime(d.getTime() + (exdays*24*60*60*1000));
		var expires = "expires="+ d.toUTCString();
		document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
	}
}

function tpfApplyCouponCode( action = false , coupon =false , product_id = false , type = false , ){
	if( !coupon || !type || !action || !product_id )
		tapifyPostMessage({"validationFailed":true, "message":  "coupon or type missing!" });
	if( action == "remove" ){
		setCookie('tapify_applied_cp', "", -30);
		// getUniqueId();
	}

	jQuery.ajax({
		type: 'POST',
		url: tapifyajax.ajaxurl,
		beforeSend: function ( xhr ) {
	        xhr.setRequestHeader( 'X-WP-Nonce', tapifyajax.security );
	    },
		data : { action:"tpf_apply_coupon_code" , type:type ,product_id:product_id ,coupon:coupon , event:action },
		success: function(response) {
			console.log( "response==>" , response)
			if(response.status){
				if( response.cp ) setCookie('tapify_applied_cp', JSON.stringify(response.cp) , 1 ); 
				tapifyReloadCart();
				document.getElementById('button-api-iframe').contentWindow.postMessage( { "uniqueId" : true , "uuid": getCookie('tapify_life_long_cookie') , storeAccessKey:response.tapify_store_access_key}  , '*');
			}else{ 
				var postMessageObj = { "validationFailed": true, "message": ( response.message )?response.message:"Sorry, Unable to load the coupon!" };
                tapifyPostMessage(postMessageObj); 
				console.log("response ==> ", response);
			}
		}
	});	
}

function getUniqueId(){
	jQuery.ajax({
	    type: 'POST',
	    url: tapifyajax.ajaxurl,
	    data : {
	   			action:"get_unique_id"
	   		},
	    success: function(response) {
	    	jQuery('.tpf-iframe').removeClass('disabled');
		   if(response.status){
					tapifyReloadCart();
					console.log("####################" ,response);
					document.getElementById('button-api-iframe').contentWindow.postMessage( { "uniqueId" : true , "uuid": getCookie('tapify_life_long_cookie') , storeAccessKey:response.store_access_key}  , '*');
		    	}else{ console.log("response ==> ", response); }
		    }
	});
}

function getBloggerSyncedProduct( $quantity = 1 , selectedVariations =  new Object() ){
	if( window.tpf_is_synced && window.tpf_is_synced != "false"){
		jQuery.ajax({
		    type: 'GET',
		    url: tapifyajax.ajaxurl,
		    data : {
		   			action:"get_blogger_synced_product",
		   			postId:window.tpf_post_id,
		   			_id:window.tpf_is_synced,
		   			quantity:$quantity,
		   			selectedVariations:selectedVariations,
		   			// jwtToken:getCookie('tpfUserStatus')
		   		},
		    success: function(response) {
				document.getElementById('button-api-iframe').contentWindow.postMessage( { "cartWidget": true, data:{ } }  , '*');
		    	jQuery('.tpf-iframe').removeClass('disabled');
		    	console.log( JSON.stringify("response====>", response ,null,4 )) ;
			   	if( response.product ) {
								    	
					var responseObj = { "cartWidget": true,"data":response };
			    	console.log( JSON.stringify(responseObj ,null,4 )) ;
			  		document.getElementById('button-api-iframe').contentWindow.postMessage( responseObj  , '*');
		    	}else{ 
		    		console.log("response ==> ", response);
		    		var responseObj = { "cartWidget": true,"data":{} };
					document.getElementById('button-api-iframe').contentWindow.postMessage( responseObj  , '*'); 
				}
		    }
		});
	}
}

function tapifyUpdateCartLog( token , visitorKey ){
	if( window.tpf_is_woocommrerce && window.tpf_is_woocommrerce == "2"  ) return true;
	jQuery.ajax({
	    type: 'POST',
	    url: tapifyajax.ajaxurl,
	    data : {
	   			action:"tapify_update_cart_collection",
	   			tapify_life_long_cookie:getCookie('tapify_life_long_cookie'),
	   			tpfUserStatus:token,
	   			visitorKey:visitorKey
	   		},
	    success: function(response) {
	    	
	    }
	});
}

function tpfSetAsDefault( token ){
	if( window.tpf_is_woocommrerce && window.tpf_is_woocommrerce == "2" ) return true;
	jQuery.ajax({
	    type: 'POST',
	    url: tapifyajax.ajaxurl,
	    data : {
	   			action:"tapify_sync_address",
	   			token:token
	   		},
	    success: function(response) {
	    	jQuery('.tpf-iframe').removeClass('disabled');
		   	if(response.status){
	   			tpfQuantityUpdated();
	    		tapifyReloadCart();
				document.getElementById('button-api-iframe').contentWindow.postMessage( { "uniqueId" : true , "uuid": response.uuid , storeAccessKey:response.store_access_key}  , '*');
	    	}else{ 

	    		tapifyPostMessage({"validationFailed":true, "message":  response.message })
	    	}
	    }
	});
}


function tpfSetAsDefaultForBloggers( token ){

	jQuery.ajax({
	    type: 'POST',
	    url: tapifyajax.ajaxurl,
	    data : {
	   			action:"tapify_sync_address_bloggers",
	   			token:token
	   		},
	    success: function(response) {
	    	jQuery('.tpf-iframe').removeClass('disabled');
		  //  	if(response.status){
	   // 			tpfQuantityUpdated();
	   //  		tapifyReloadCart();
				// document.getElementById('button-api-iframe').contentWindow.postMessage( { "uniqueId" : true , "uuid": response.uuid , storeAccessKey:response.store_access_key}  , '*');
	   //  	}else{ 

	   //  		tapifyPostMessage({"validationFailed":true, "message":  response.message })
	   //  	}
	    }
	});
}

function tpfQuantityUpdated(){

	document.getElementById('button-api-iframe').contentWindow.postMessage( { "isUserLogin":true  }  , '*');
	var productId  		= jQuery('#button-api-iframe').attr('data-pr-id');
	var variation_id 	= jQuery('#button-api-iframe').attr('data-variation');
	var productQty 		= jQuery('#button-api-iframe').attr('data-pr-qty');

	if( !jQuery('.tpf-iframe').hasClass('isCart') ){
		if(!jQuery('.single_add_to_cart_button').hasClass('disabled')  ){
			jQuery('.tpf-iframe').addClass('disabled');
	    	jQuery.ajax({
			    type: 'POST',
			    url: tapifyajax.ajaxurl,
			    data : {
			   			product_id:productId,
			   			count:productQty,
			   			variation_id:variation_id,
			   			// userStatus:getCookie('tpfUserStatus'),
			   			action:"tapify_calculate_product_total"
			   		},
			    success: function(response) {
			    	jQuery('.tpf-iframe').removeClass('disabled');
				   if(response.total){
				    		tapifyReloadCart();
							var tpfPriceParam = { "showPrice":true, "amount": response.total } ;
							document.getElementById('button-api-iframe').contentWindow.postMessage( tpfPriceParam  , '*');
				    	}else{ console.log("response ==> ", response); }
				    }
			});
	    }else{
	    	if( variation_id && variation_id != 0){
	    		tapifyReloadCart();
	    	}
	    }
	}
}

function tapifyBloggerSyncQuantity( quantity , selectedVariations =  new Object() ){
	document.getElementById('button-api-iframe').contentWindow.postMessage( { "isUserLogin":true  }  , '*');
	console.log("quantity" , quantity);
	if( window.tpf_is_synced && window.tpf_is_synced != "false"){
		jQuery.ajax({
		    type: 'GET',
		    url: tapifyajax.ajaxurl,
		    data : {
		   			postId:window.tpf_post_id,
		   			_id:window.tpf_is_synced,
		   			quantity:quantity,
		   			action:"get_blogger_synced_product",
		   			selectedVariations:selectedVariations,
		   			// jwtToken:getCookie('tpfUserStatus')
		   		},
		    success: function(response) {
		    	jQuery('.tpf-iframe').removeClass('disabled');
				if( response.product ) {
					if( Object.keys( selectedVariations ).length ){
						var variableProduct = response.product;
						if( variableProduct.variation_id && variableProduct.variation_id === "0" ){
							tapifyPostMessage({"validationFailed":true, "message":  "sorry, no products matched your selection. please choose a different combination.!"})
						}
					}

					var responseObj = { "cartWidget": true,"data":response };
			    	console.log( JSON.stringify(responseObj ,null,4 )) ;
			  		document.getElementById('button-api-iframe').contentWindow.postMessage( responseObj  , '*');
		    	}else{ 
					console.log("response ==> ", response);				
		    		tapifyPostMessage({"validationFailed":true, "message":  response.message })
				}
		    }
		});
	}
}

/**
* Desc 	:Function to set cookie in safari
* Logic :Open the button api in new tab and set the token and redirect to current tab
*
**/
function tpfSetThirdpartyCookie( cookies ){
	window.open( TBF_BUTTON_API + 'set-cookie?t=' + JSON.stringify( cookies ) , '_blank');
}
// tpfSetThirdpartyCookie()
