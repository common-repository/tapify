jQuery(document).ready(function($ ) {
	console.log("Tapify : common js loaded :)");
	var node_api_url 				= "https://api.tapify.io/" ; 
	var login_end_point 			= "v1/login";
	var get_access_key_end_point 	= "v1/connect/store";
	var tpf_auto_connect_loaded 	= false;
	$('#tapify-connect-store').load(function(){
		// tpfgetHomeurl();
		$('#button-api-iframe').css('display' ,'block');
		console.log("loaded iframe===================",);


		document.getElementById('tapify-connect-store').contentWindow.postMessage( { "tpf_auto_connect":true } , '*');
		bindEvent(window, 'message', function (e) {
			// if(e.origin == "")
			
			var resData = e.data;
			console.log( "resData.status " , resData );
			tpfgetHomeurl();

			if(resData && resData.status == "StoreConnectStatus" ){
               tpfAddStoreAccessKey( resData.data , resData.token ); 
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
	function tpfGetUrlParam(parameter, defaultvalue){
	    var urlparameter = defaultvalue;
	    if(window.location.href.indexOf(parameter) > -1){
	        urlparameter = tpfGetUrlVars()[parameter];
	        }
	    return urlparameter;
	}

	function tpfGetUrlVars() {
	    var vars = {};
	    var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m,key,value) {
	        vars[key] = value;
	    });
	    return vars;
	}

	function tpfgetHomeurl( ){
		var postMessageParam = { "tpfHomeURl":true, "storeUrl": tapifyajaxAdmin.home_url };
		document.getElementById('tapify-connect-store').contentWindow.postMessage(  postMessageParam   , '*');
		var auto_connect = tpfGetUrlParam('auto',false) ;
		if( tpf_auto_connect_loaded ) auto_connect = false;
		console.log( "auto_connect==>" , auto_connect)
		document.getElementById('tapify-connect-store').contentWindow.postMessage(  { "tpf_loader_status": auto_connect }   , '*');
		if( tpfGetUrlParam('auto',false) && !tpf_auto_connect_loaded ){
			tpf_auto_connect_loaded = true;
			document.getElementById('tapify-connect-store').contentWindow.postMessage(  { "tpf_auto_connect":true }   , '*');
		}
	}


	function tpfAddStoreAccessKey( storeAccessKey , token=false ){
		if( !window.tpf_is_woocommrerce ){
			if( !token ) { alert( "missing token"); return false }
		}		
		jQuery.ajax({
		    type: 'POST',
		    url: tapifyajaxAdmin.ajaxurl,
		    data : {
		   			storeAccessKey:storeAccessKey,
		   			is_ecommerce:window.tpf_is_woocommrerce,
		   			jwtToken:token,
		   			action:"tapif_add_store_access_key",
		   			redirectUrl:window.location.href + '&wc=api' 
		   		},
		    success: function(response) {
			jQuery('#tpf_settings_submit').removeClass('disabled-class');

		    	$('.tpf-iframe').removeClass('disabled');
		    	if(response.status){
		    		if( !window.tpf_is_woocommrerce ) location.reload();
		    		else window.location = response.url;
		    	}else{
		    		alert(response.message);
		    	}
		    }
		});
	}


	jQuery('.reset-tapify-settings').click(function (e) {	
		e.preventDefault();
		jQuery.ajax({
		    type: 'POST',
		    url: tapifyajaxAdmin.ajaxurl,
		    data : {
		   			action:"reset_store_connection"
		   		},
		    success: function(response) {
		    	console.log("response====>>>>/" , response, window.location.href.split("&")[0] );
		    	window.location.href =  window.location.href.split("&")[0];
				// window.location.reload(true);
			}
		});
	});

	jQuery('#save_language').click(function (e) {	
		e.preventDefault();
		var tpfLanguage = jQuery( "#tapify_lang_switch_language option:selected" ).val();
		jQuery.ajax({
		    type: 'POST',
		    url: tapifyajaxAdmin.ajaxurl,
		    data : {
		   			tpfLanguage:tpfLanguage,
		   			action:"tapify_save_language",
		   		},
		    success: function(response) {
			jQuery('#tpf_settings_submit').removeClass('disabled-class');

		    	$('.tpf-iframe').removeClass('disabled');
		    	if(response.status){
		    		 alert(response.message);
		    	}else{
		    		alert(response.message);
		    	}
		    }
		});

	});
	jQuery('#tpf_settings_submit').click(function (e) {	
		e.preventDefault();
		jQuery("input[name=tapify_store_access_key]").css('border-color', '');
		var storeAccessKey = jQuery("input[name=tapify_store_access_key]").val();
		if(!storeAccessKey){
			jQuery("input[name=tapify_store_access_key]").css('border-color', 'red');
			return false;
		}
		jQuery('#tpf_settings_submit').addClass('disabled-class');

		jQuery.ajax({
		    type: 'POST',
		    url: tapifyajaxAdmin.ajaxurl,
		    data : {
		   			storeAccessKey:storeAccessKey,
		   			action:"tapif_add_store_access_key",
		   			redirectUrl:window.location.href 
		   		},
		    success: function(response) {
			jQuery('#tpf_settings_submit').removeClass('disabled-class');

		    	$('.tpf-iframe').removeClass('disabled');
		    	if(response.status){
		    		window.location = response.url;
		    	}else{
		    		alert(response.message);
		    	}
		    }
		});
	});



	jQuery('#tpf_connect_store').click(function (e) {	
		e.preventDefault();
		jQuery("input[name=tpf_email]").css('border-color', '');
		jQuery("input[name=tpf_password]").css('border-color', '');
		var email = jQuery("input[name=tpf_email]").val();
		var password = jQuery("input[name=tpf_password]").val();
		if(!email || !password){
			if(!email) jQuery("input[name=tpf_email]").css('border-color', 'red');
			if(!password) jQuery("input[name=tpf_password]").css('border-color', 'red');
			return false;
		}
		jQuery('#tpf_connect_store').addClass('disabled-class');

		jQuery.ajax({
		    type: 'POST',
		    url: node_api_url +login_end_point,
		    data : {
		   			email:email,
		   			password:password,
		   		},
		    success: function(response) {
				jQuery('#tpf_connect_store').removeClass('disabled-class');
		    	$('.tpf-iframe').removeClass('disabled');
		    	if(response.user){
		    		tpfGetStoreAccesskey( response.user._id , response.token )
		    	}else{ console.log("response ==> ", response); }
		    }
		    ,error:function(err){
		    	jQuery('#tpf_connect_store').removeClass('disabled-class');
		    	if(err && err.responseText ){
		    		var res = JSON.parse(err.responseText);
		    		alert(res.message);
		    	}else alert(JSON.stringify(err));
		    }
		});
	});

	function tpfGetStoreAccesskey( user_id , token ){

		jQuery.ajax({
		    type: 'GET',
		    url: node_api_url + get_access_key_end_point,
		    headers: {"Authorization": token },
		    data : {},
		    success: function(response) {
				jQuery('#tpf_connect_store').removeClass('disabled-class');
		    	$('.tpf-iframe').removeClass('disabled');

		    	if(response.status){
		    		tpfAddStoreAccessKey(response.data);
		    	}else{ console.log("response ==> ", response); }
		    }
		    ,error:function(err){
		    	jQuery('#tpf_connect_store').removeClass('disabled-class');
		    	if(err && err.responseText ){
		    		var res = JSON.parse(err.responseText);
		    		alert(res.message);
		    	}else alert(JSON.stringify(err));
		    }
		});

	}
	


});
