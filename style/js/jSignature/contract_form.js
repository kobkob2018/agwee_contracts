jQuery(document).ready(function() {
	jQuery(".signature_div").jSignature({color:"#00f",lineWidth:1});
	jQuery("#contract_form").validate();
	jQuery("#contract_form").change(function(){
		show_hide_signatures();
	});				
});	
setTimeout(function(){
	jQuery(".contract_signature_wrap").css("height","0px");
	jQuery(".contract_signature_wrap").css("overflow","hidden");
	jQuery(function($){
		$(".contract_input").each(function(){
			$(this).change(function(){
				$(".signature_clean").each(function(){$(this).click();});
				$(".signature_door").each(function(){if($(this).is(":checked")){$(this).click()};});
			});
		});
	});
},2000); 
function open_signature_wrap(el_id,uid){
	jQuery(function($){
		if($(el_id).is(':checked')){
			$("#contract_signature_wrap_"+uid).css("height","260px");
		}
		else{
			$("#contract_signature_wrap_"+uid).css("height","0px");
			$("#contract_signature_wrap_"+uid).css("overflow","hidden");
		}
	});
}
setTimeout(function(){show_hide_signatures();},2000);
function show_hide_signatures(){
	jQuery(function($){
		var enable_sign = true;
		var missing_fields = "";
		var missing_i = 0;
		$(".sign_required").each(function(){
			if($(this).val().length === 0){
				if(missing_i!=0){
					missing_fields += ", ";
				}
				enable_sign = false;
				missing_fields += $(this).attr("data-title");
				missing_i++;
			}
		});
		$(".sign_email").each(function(){
			if($(this).val().length !== 0 && !$(this).valid()){
				if(missing_i!=0){
					missing_fields += ", ";
				}
				enable_sign = false;
				missing_fields += $(this).attr("data-title")+"("+$(this).attr("data-msg-email")+")";
				missing_i++;
			}
		});
		$("#missing_fields").html(missing_fields);
		if(enable_sign){
			if($("#open_signatures_msg").attr("rel") == "open" || $("#open_signatures_msg").attr("rel") == "start"){
				$("#contract_signatures").show();
				$("#open_signatures_msg").hide();
				$("#open_signatures_msg").attr("rel","closed");
			}
			$(".username_holder").each(function(){
				var uid = $(this).attr("data-user");
				var first_name = $("#input_user_"+uid+"_firstname").val();
				var last_name = $("#input_user_"+uid+"_lastname").val();
				$(this).html(first_name+" "+last_name);
				
			});
		}
		else{
			if($("#open_signatures_msg").attr("rel") != "open" || $("#open_signatures_msg").attr("rel") == "start"){
				$("#contract_signatures").hide();
				$("#open_signatures_msg").show();
				$("#open_signatures_msg").attr("rel","open");
			}
		}
	});
}
function checkSignature(){
	var retval = true;
	jQuery(function($){
		
		$(".signature_input").each(function(){
			var sigdiv_id = $(this).attr("rel");
			var sigdata_30_arr = $("#"+sigdiv_id).jSignature("getData","base30");
			var sigdata_30 = sigdata_30_arr[1];
			
			if(sigdata_30 == ""){
				$(this).val("");
			}
			else{
				var sigdata = $("#"+sigdiv_id).jSignature("getData");
				$(this).val(sigdata);
				$("#"+sigdiv_id+"_error").html("").hide();
			}
		});
	});
	return retval;	
}	
function clear_signature(signature_id){
	jQuery("#"+signature_id).jSignature("clear");
}