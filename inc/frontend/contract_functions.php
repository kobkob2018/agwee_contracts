<?php

	//for demo version to check pdf result try: http://ilbiz.co.il/work_contract.php?demo=1&unk={{unk of website}}&contract_page={{ contract pade id in administration }}
	//example: http://ilbiz.co.il/work_contract.php?demo=1&unk=344051510855310738&contract_page=28240

defined( 'ABSPATH' ) or die( 'Hey, no entry here. ok?!' );
class AgweeContracts_frontend{
	private $wpcon;
	private $callbeck_class;
	public function init($callbeck_class){
		global $wpdb;
		$this->wpcon = $wpdb;
		$this->callbeck_class = $callbeck_class;
		$this->callbeck_class->include_required_file('inc/agweeContracts_handler_class.php');
	}	
	
	
	public function work_contract_form($contract_id = false){
		if(isset($_REQUEST['apply_contract'])){
			return $this->work_contract_apply();
		}	
		$contract_apply_id = false;
		$contract_apply_data = false;
		$contract_apply_id_str = "new";
		$contract_apply_inputs = array();
		$contract_apply_signatures = array();
		$img_httppath = WP_CONTENT_URL."/plugins/agwee/apply/img/uploads";
		$contract_apply_user = false;
		$general_page_url = "";
		$landing_id = "";
		$post_id = get_the_ID();
		if($post_id){
			$general_page_url = add_query_arg( 'apply', '1', get_permalink($post_id));
			$landing_id = $post_id;
		}

		if(isset($_GET['contract_apply'])){
			$contract_apply_user_str = 'contract_apply_'.$_GET['contract_apply'].'_user';
			if(isset($_SESSION[$contract_apply_user_str])){
				$contract_apply_user = $_SESSION[$contract_apply_user_str];
			}
			$contract_apply = $_GET['contract_apply'];
			$sql = "SELECT * FROM ag_contract_apply WHERE id = $contract_apply";
			$res = $this->wpcon->get_results($sql,ARRAY_A);
			$contract_apply_data = $res[0];
			if($contract_apply_data['pdf_path'] != "" && !isset($_REQUEST['approve']) && !isset($_GET['enter'])){
				if($contract_apply_user){
					if(!isset($_SESSION['approve_user'])){
						unset($_SESSION[$contract_apply_user_str]);
						ob_start();
						?>
						<b style="color:green;">תהליך הפקת החוזה הסתיים וקובץ החוזה נשלח לכתובות המייל של המשתתפים.
							<br/>
							<br/>
							<span style="color:red;">יש להכנס לתיבת האימייל ולאשר את אמינות החוזה דרך הלינק שנשלח אליכם</span>
						</b>
						<?php
						agweeContracts_handler::get_ob_clean();
						return;	
					}
				}
				elseif(!isset($_GET['enter'])){
					ob_start();
					?>
					<b style="color:red;">אין לך הרשאות לצפות בהסכם זה</b>
					<?php
					agweeContracts_handler::get_ob_clean();
					return;	
				}
			}
			$contract_apply_data['title'] = $contract_apply_data['title'];

			if($contract_apply_data['contract_id'] != ""){
		
				$contract_apply_id = $contract_apply_data['id'];
				$contract_id = $contract_apply_data['contract_id'];
			
				$contract_apply_id_str = $contract_apply_id;
				$sql = "SELECT * FROM ag_contract_apply_inputs WHERE contract_apply_id = $contract_apply_id AND contract_id = $contract_id";
				$res = $this->wpcon->get_results($sql,ARRAY_A);
				foreach($res as $contract_apply_input){
					$contract_apply_inputs[$contract_apply_input['input_name']] = $contract_apply_input;
				}
				$sql = "SELECT * FROM ag_contract_apply_signatures WHERE contract_apply_id = $contract_apply_id AND contract_id = $contract_id";
				$res = $this->wpcon->get_results($sql,ARRAY_A);
				foreach($res as $contract_apply_signature){
					$contract_apply_signatures[$contract_apply_signature['input_name']] = $contract_apply_signature;
				}			
			}
		}
		if(isset($_REQUEST['exit'])){
			return $this->work_contract_exit($contract_apply_id);
		}
		elseif(isset($_GET['contract_id'])){
			$contract_id = $_GET['contract_id'];
		}
		if(!$contract_id){
			echo "";
			return;
		}
		
		$contract_data = agweeContracts_handler::get_contract_data($contract_id);
		if(!$contract_data){
			return;
		}
		$form_fields = agweeContracts_handler::create_fields_arr_from_contract($contract_id);
		foreach($form_fields['general']['fields'] as $field_key=>$field_arr){
			$field_arr['value'] = $field_arr['def'];
			$input_name = "general_1_".$field_arr['identifier'];
			$field_arr['title_str'] = $field_arr['title'];
			$field_arr['input_name'] = $input_name;
			if($contract_apply_id){

				if(isset($contract_apply_inputs[$input_name])){
					$field_arr['value'] = $contract_apply_inputs[$input_name]['input_value'];
					$field_arr['input'] = $contract_apply_inputs[$input_name];
				}
			}
			$form_fields['general']['fields'][$field_key] = $field_arr;
		}
		$users_info = array();
		$enable_export = true;
		$missing_signatures_str = "";
		$missing_signatures_i = 0;
		foreach($form_fields['users_fields']['users'] as $user_key=>$user_arr){
			$users_info[$user_arr['identifier']] = array();
			foreach($user_arr['fields'] as $field_key=>$field_arr){
				$input_name = "user_".$user_arr['identifier']."_".$field_arr['identifier'];
				$field_arr['value'] = $field_arr['def'];
				$field_arr['title_str'] = $user_arr['role_name']."-".$field_arr['title'];
				$field_arr['input_name'] = $input_name;
				if($contract_apply_id){
					if(isset($contract_apply_inputs[$input_name])){
						$field_arr['value'] =  $contract_apply_inputs[$input_name]['input_value'];
						$field_arr['input'] = $contract_apply_inputs[$input_name];
					}
				}
				$users_info[$user_arr['identifier']][$field_arr['identifier']] = $field_arr['value'];
				
				$form_fields['users_fields']['users'][$user_key]['fields'][$field_key] = $field_arr;
			}
			$users_info[$user_arr['identifier']]['signature_exist'] = false;
			if(isset($contract_apply_signatures[$user_arr['identifier']])){
				$users_info[$user_arr['identifier']]['signature_exist'] = true;
				$users_info[$user_arr['identifier']]['signature'] = $contract_apply_signatures[$user_arr['identifier']]['input_value'];
			}
			else{
				$enable_export = false;
				if($missing_signatures_i!=0){
					$missing_signatures_str .= ", ";
				}
				$missing_signatures_str .= $users_info[$user_arr['identifier']]['firstname']." ".$users_info[$user_arr['identifier']]['lastname'];
				$missing_signatures_i++;
			}		
		}
		if($contract_apply_data){
			if($contract_apply_data['pdf_path'] != ""){
				$users_info = array();
				$users_sql = "SELECT * FROM ag_contract_apply_users WHERE contract_apply_id = $contract_apply_id";
				$users_res = $this->wpcon->get_results($users_sql,ARRAY_A);
				foreach($users_res as $contract_user){
					$users_info[$contract_user['contract_user_id']] = $contract_user;
				}
			}
			if($contract_apply_data['pdf_path'] != "" && !isset($_REQUEST['approve']) && !isset($_GET['enter'])){
				if($contract_apply_user){
					if(isset($_SESSION['approve_user'])){
						return $this->work_contract_approve_form($contract_apply_id,$contract_data,$contract_apply_data,$users_info);
					}
				}
			}
		}		
		$contract_design = $contract_data['content'];
		$long_nbsp = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		$enable_sign = true;
		$missing_fields_str = "";
		$missing_fields_i = 0;
		foreach($form_fields['general']['fields'] as $field_arr){
			$input_value = $field_arr['value'];
			if($input_value == ""){
				if($field_arr['required'] == '1'){
					$enable_sign = false;
					if($missing_fields_i!=0){
						$missing_fields_str .= ", ";
					}
					$missing_fields_str .= $field_arr['title'];
					$missing_fields_i++;
				}
				$input_value = $long_nbsp;
			}
			else{
				if($field_arr['type'] == "file"){
					$input_value = "<img src='$img_httppath/$input_value' />";
				}
			}		
			$contract_design = str_replace("{{".$field_arr['title']."}}",$input_value,$contract_design);
		}
		foreach($form_fields['users_fields']['users'] as $user_arr){
			$user_role_name = $user_arr['role_name'];
			foreach($user_arr['fields'] as $field_arr){
				$input_value = $field_arr['value'];
				if($input_value == ""){
					if($field_arr['required'] == '1'){
						$enable_sign = false;
						if($missing_fields_i!=0){
							$missing_fields_str .= ", ";
						}
						$missing_fields_str .= $user_arr['role_name']." - ".$field_arr['title'];
						$missing_fields_i++;
					}				
					$input_value = $long_nbsp;
				}
				else{
					if($field_arr['type'] == "file"){
						$input_value = "<img src='$img_httppath/$input_value' />";
					}
				}
				$contract_design = str_replace("{{".$field_arr['title']."(".$user_role_name.")}}",$input_value,$contract_design);
			}
		}
		if(isset($_REQUEST['enter'])){
			return $this->work_contract_enter($contract_apply_id,$contract_apply_data,$users_info);
		}
		if(isset($_REQUEST['approve'])){
			return $this->work_contract_approve($contract_apply_id,$contract_apply_data,$users_info);
		}	
		if($contract_apply_id && !$contract_apply_user){
			ob_start();
			?>
			<b style="color:red;">אין לך הרשאות לצפות בהסכם זה</b>
			<?php
			agweeContracts_handler::get_ob_clean();
			return;
		}
		if(isset($_REQUEST['export']) && $enable_export){
			return $this->work_contract_export($contract_id,$contract_apply_id,$contract_data,$contract_apply_data,$contract_design,$form_fields,$users_info,$enable_sign);
		}	
		
		if(isset($_GET['view'])){
			$contract_messeges = false;
			if(isset($_SESSION['contract_messeges'])){
				$contract_messeges = $_SESSION['contract_messeges'];
			}
			unset($_SESSION['contract_messeges']);
		}
		ob_start();
		?>
		<?php if($contract_apply_id): ?>
			<div style="float:left;">
				<?php if($contract_apply_user != "-1"): ?>
					שלום <?php echo $users_info[$contract_apply_user]['firstname']." ".$users_info[$contract_apply_user]['lastname']; ?><br/>
				<?php endif; ?>
				<a href="<?php echo $general_page_url; ?>&contract_apply=<?php echo $contract_apply_id; ?>&exit=1" style="color:red;font-size:18px;"><b>יציאה</b></a>
			</div>
		<?php endif; ?>
		<h2>מימוש חוזה: <?php echo $contract_data['title']; ?></h2>
		<?php if(isset($_GET['view']) && $contract_messeges): ?>
			<?php foreach($contract_messeges as $msg): ?>
				<div style="color:<?php echo $msg['color']; ?>;font-size:20px;">
					<b><?php echo $msg['msg']; ?></b>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>	
		<div>
		<b><a href="javascript://" onclick="show_contract(this);" rel="closed" style="font-size:18px;">הצג <?php echo $contract_data['title']; ?></a></b>
		</div>
		<div id="contract_design" style="padding: 30px;margin-bottom:40px; display: none;background: #ffffae;border: 1px solid black;">
			<?php echo $contract_design; ?>
		</div>
		<script type="text/javascript">
			function show_contract(el_id){
				jQuery(function($){
					if($(el_id).attr("rel") == "closed"){
						$(el_id).attr("rel","open").css("color","red").html("סגור תצוגת <?php echo $contract_data['title']; ?>");				
						$("#contract_design").show();
					}
					else{
						$(el_id).attr("rel","closed").css("color","blue").html("הצג <?php echo $contract_data['title']; ?>");				
						$("#contract_design").hide();					
					}
				});
			}
		</script>

		<div  id="contract_form_inner_wrap" style="display: block;margin-bottom:100px; background: #ffffae;padding: 2px 0px;border:1px solid gray;">
			<div  style=" padding:0px 30px;">
				<?php if(isset($_GET['view'])): ?>
					
					<?php if($enable_export): ?>
						<div style="border:1px solid green; padding:10px; background:#b9ffb9;font-size:20px;margin-top:10px;">
							<b>פרטי החוזה הושלמו בהצלחה</b><br/>
							<a href="<?php echo $general_page_url; ?>&contract_apply=<?php echo $contract_apply_id; ?>&export=1">לחץ כאן להפקת הקובץ</a>
						</div>
					<?php elseif($enable_sign): ?>
						<div style="border:1px solid green; padding:10px; background:#b9ffb9;margin-top:10px;">
							<b style="color:green;font-size:20px;">פרטי החוזה הושלמו בהצלחה</b><br/>				
							<b style="color:red;">לפני הפקת קובץ החוזה, נשאר רק להוסיף את <a href="#signatures_update">חתימות המשתתפים</a>: </b>
							<br/>
							<?php echo $missing_signatures_str; ?>
						</div>					
					<?php endif; ?>	
						
					<h3>פרטי החוזה</h3>
				<?php else: ?>
					<h3>אנא מלאו את הפרטים הבאים:</h3>
				<?php endif; ?>
			</div>


			<form id="contract_form" action="" method="POST" enctype="multipart/form-data" onsubmit="return checkSignature();">
				<input type="hidden" name="apply_contract" value="<?php echo $contract_id; ?>" />
				<input type="hidden" name="contract_apply_id" value="<?php echo $contract_apply_id_str; ?>" />
				<?php if(isset($_GET['view'])): ?>
					<input type="hidden" name="sign_only" value="1" />
				<?php endif; ?>
				<div  style=" padding:0px 30px;">
					<?php foreach($form_fields['users_fields']['users'] as $user_arr): ?>
					<h4>פרטי <?php echo $user_arr['role_name']; ?>:</h4>
					<div class="row-fluid">
						<?php $i=0; foreach($user_arr['fields'] as $field_arr): $i++; ?>
							<div class="span4">
								<b><?php echo $field_arr['title'];?> <br/></b>
								<?php $this->create_contract_form_input($field_arr,$users_info); ?>
							</div>
							<?php if($i==3): $i=0; ?>
								</div>
								<div class="row-fluid" style="padding-top:10px;">
							<?php endif; ?>						
						<?php endforeach; ?>
					</div>
					<?php endforeach; ?>
					<h4>פרטים כלליים:</h4>
					<div class="row-fluid">
						<?php $i=0; foreach($form_fields['general']['fields'] as $field_arr): $i++; ?>
							<div class="span4">
								<b><?php echo $field_arr['title'];?> <br/></b>
								<?php $this->create_contract_form_input($field_arr,$users_info); ?>
							</div>
							<?php if($i==3): $i=0; ?>
								</div><div class="row-fluid" style="padding-top:10px;">
							<?php endif; ?>					
						<?php endforeach; ?>
					</div>
					<hr/>
					<div id="open_signatures_msg" rel="start">
						<b style="color:red;">להוספת חתימות יש למלא תחילה את כל הפרטים החסרים: </b>
						<span id="missing_fields"></span><br/>
						<b style="color:green;">ניתן להשאיר חלק מהפרטים ריקים על מנת לאפשר למשתתפים אחרים בחוזה להשלימם.</b>
					</div>
				
				
					<?php if($enable_sign && isset($_GET['view'])): ?>
						<h4>חתימות:</h4>
						<div class="row-fluid">
						<?php $i=0; foreach($users_info as $user_info): $i++; ?>
							<div class="span4">
								<b><?php echo $user_info['firstname']; ?> <?php echo $user_info['lastname'];?><br/></b>
								<?php if($user_info['signature_exist']): ?>
									<b style="color:green;">יש</b>
								<?php else: ?>
									<b style="color:red;">אין</b>
								<?php endif; ?>
							</div>				
							<?php if($i==3): $i=0; ?>
								</div>
								<div class="row-fluid" style="padding-top:10px;">
							<?php endif; ?>				
						<?php endforeach; ?>
						</div>
					<?php endif; ?>				
					<?php if(isset($_GET['view'])): ?>
						<?php if($contract_apply_id): ?>
							<div style="float:left;padding-top: 23px;">
								<a style="color:blue; font-size:20px;font-weight:bold; text-decoration:underline;" href="<?php echo $general_page_url; ?>&contract_apply=<?php echo $contract_apply_id; ?>">עדכון פרטים</a>
								</br>
								<b style="color:red;">לאחר עדכון הפרטים יש לחדש את החתימות</b>
							</div>
							<div style="clear:both;"></div>
						<?php endif; ?>
						<?php if($enable_export): ?>
							<div style="border:1px solid green; padding:10px; background:#b9ffb9;font-size:20px;margin-top:10px;">
								<b>פרטי החוזה הושלמו בהצלחה</b><br/>
								<a href="<?php echo $general_page_url; ?>&contract_apply=<?php echo $contract_apply_id; ?>&export=1">לחץ כאן להפקת הקובץ</a>
							</div>
						<?php elseif($enable_sign): ?>
							<div id="signatures_update" style="border:1px solid green; padding:10px; background:#b9ffb9;margin-top:10px;">
								<b style="color:green;font-size:20px;">פרטי החוזה הושלמו בהצלחה</b><br/>
								<b style="color:red;">לפני הפקת קובץ החוזה, נשאר רק להוסיף את חתימות המשתתפים: </b>
								<br/>
								<?php echo $missing_signatures_str; ?>
							</div>					
						<?php endif; ?>	
					<?php endif; ?>
				</div>
				<div id="contract_signatures">
					<div  style=" padding:0px 30px;">
						<h4>עדכון חתימות:</h4>
						<b style="color:green;">באפשרותך לעדכן חתימות כעת או להשאירן ריקות על מנת לאפשר למשתתפים אחרים לחתום</b>
						<hr/>
					</div>
					<?php foreach($form_fields['users_fields']['users'] as $user_arr): ?>
						<div>
							<div  style=" padding:0px 30px;">
								<input type="checkbox" style="zoom:2;margin-top:-1px; margin-left:4px;" name="add_sign[<?php echo $user_arr['identifier']; ?>]" class="signature_door" onchange="open_signature_wrap(this,<?php echo $user_arr['identifier']; ?>);" rel="open" data-user="<?php echo $user_arr['identifier']; ?>" /><span style="font-size:18px; line-height:18px;" >הוסף חתימה של <b class="username_holder" data-user="<?php echo $user_arr['identifier']; ?>"><?php echo $user_arr['role_name']; ?></b></span>
								<?php if($users_info[$user_arr['identifier']]['signature_exist']): ?>
									<?php if(isset($_GET['view'])): ?>
										<b style="color:green;"> (קיימת חתימה)</b>
									<?php else: ?>
										<b style="color:red;"> (החתימה הקיימת תמחק לאחר העדכון)</b>
									<?php endif; ?>
								<?php endif; ?>
							</div>
							<div class="contract_signature_wrap" id="contract_signature_wrap_<?php echo $user_arr['identifier']; ?>" style="overrflow:hidden;"> 
								<span class="signature_title">חתימה של <b class="username_holder" data-user="<?php echo $user_arr['identifier']; ?>"><?php echo $user_arr['role_name']; ?></b></span>

								<br/>
								<div class="signature_wrap">
									<div class="signature_div" id="signature_<?php echo $user_arr['identifier']; ?>" style="width:100%;background:#e0d5d5;">
										&nbsp;
									</div>
									<button style="width:100%;" class="signature_clean" onclick="clear_signature('signature_<?php echo $user_arr['identifier']; ?>')" type="button">נקה</button>
									<input class="signature_input" id="signature_<?php echo $user_arr['identifier']; ?>_input" name="sign[<?php echo $user_arr['identifier']; ?>]" rel="signature_<?php echo $user_arr['identifier']; ?>" type="hidden" />
								</div>	
							</div>
							<hr/>
						</div>
					<?php endforeach; ?>
					
				</div>
				<div  style=" padding:0px 30px;">
					<div style="float:left;padding-top: 23px;">
						<?php if(!isset($_GET['view']) && $contract_apply_id): ?>
							<a style="color:#ff5353; font-size:24px;font-weight:bold;" href="<?php echo $general_page_url; ?>&contract_apply=<?php echo $contract_apply_id; ?>&view=1">ביטול</a>
						<?php endif; ?>
					</div>
					<?php if(!isset($_GET['view']) || $enable_sign): ?>
						<input style="display: block;width: 100%;font-size: 18px;" type="submit" value="שלח" /></p>		
					<?php endif; ?>
					<div style="clear:both;"></div>
				</div>
				<div style="clear:both;"></div>
			</form>
			<script type="text/javascript" src="<?php echo WP_CONTENT_URL; ?>/plugins/agwee/style/js/jSignature/jSignature.min.noconflict.js"></script>
			<script type="text/javascript" src="<?php echo WP_CONTENT_URL; ?>/plugins/agwee/style/js/jSignature/contract_form.js?v=2"></script>		
			
		</div>
		<?php		
		agweeContracts_handler::get_ob_clean();
	}	

	public function create_contract_form_input($field_arr,$users_info){	
		$field_value = $field_arr['value'];
		$input_name = $field_arr['input_name'];
		$edit_by = false;
		if(isset($field_arr['input'])){
			if(isset($field_arr['input']['edit_by'])){
				$edit_by_temp = $field_arr['input']['edit_by'];
				if($edit_by_temp!="" && $edit_by_temp!="-1"){
					$edit_by = $edit_by_temp;
				}
			}
		}
		
		$readonly = "";
		if($field_arr['allowedit'] != '1'){
			$readonly = "readonly";
		}
		$classname = "contract_input";
		if($field_arr['type'] == "email"){
			$classname .= " email";
		}
		if(!is_numeric($field_arr['identifier'])){
			$classname .= " required";
		}
		if($field_arr['required'] == '1'){
			if($field_arr['type'] != "file" || $field_value == ""){
				$classname .= " sign_required";
			}
		}
		if(isset($_GET['view'])){
			if($field_value == ""){
				echo "----";
			}
			if($field_arr['type'] == "text" || $field_arr['type'] == "email"){
				echo $field_value;
			}
			if($field_arr['type'] == "textarea"){
				echo nl2br($field_value);
			}
			if($field_arr['type'] != "file"){ 
			?>
			<input id="input_<?php echo $input_name; ?>" class="sign_<?php echo $field_arr['type']; ?> <?php echo $classname; ?>" data-msg-required="יש למלא <?php echo $field_arr['title']; ?>" data-msg-email="כתובת המייל אינה תקינה" type="hidden" name="contract[<?php echo $input_name; ?>]" value="<?php echo $field_value; ?>" <?php echo $readonly; ?> data-title="<?php echo $field_arr['title_str']; ?>"/>
			<?php
			}
			else{
			?>
			<input id="input_<?php echo $input_name; ?>" class="sign_<?php echo $field_arr['type']; ?> <?php echo $classname; ?>" data-msg-required="יש למלא <?php echo $field_arr['title']; ?>" type="hidden" name="contract_files[<?php echo $input_name; ?>]" value="<?php echo $field_value; ?>" <?php echo $readonly; ?> data-title="<?php echo $field_arr['title_str']; ?>"/>	
			<?php
			}
		}
		else{
					
			if($field_arr['type'] == "text" || $field_arr['type'] == "email" ||  $field_arr['type'] == "file"){
				?>
					<input style="max-width:95%;margin-bottom:0px;" id="input_<?php echo $input_name; ?>" class="sign_<?php echo $field_arr['type']; ?> <?php echo $classname; ?>" data-msg-required="יש למלא <?php echo $field_arr['title']; ?>" data-msg-email="כתובת המייל אינה תקינה" type="<?php echo $field_arr['type']; ?>" name="contract[<?php echo $input_name; ?>]" value="<?php echo $field_value; ?>" <?php echo $readonly; ?> data-title="<?php echo $field_arr['title_str']; ?>"/>
				<?php
			}
			if($field_arr['type'] == "textarea"){
				?>
					<textarea style="max-width:95%;margin-bottom:0px;" class="<?php echo $classname; ?>" name="contract[<?php echo $input_name; ?>]" <?php echo $readonly; ?>  data-title="<?php echo $field_arr['title_str']; ?>"><?php echo $field_value; ?></textarea>
				<?php
			}

		}
		if($field_arr['type'] == "file" && $field_value!=""){
			$time_str = time();
			$img_dirurl = WP_CONTENT_URL."/plugins/agwee/apply/img/uploads";
			?>
				<br/><img src="<?php echo $img_dirurl; ?>/<?php echo $field_value; ?>?t=<?php echo $time_str; ?>" style="width:100%;" />
			<?php
		}
		if($edit_by){
			if($field_arr['input']['edit_by'] != "" && $field_arr['input']['edit_by'] != '-1'){
				echo "<div style=''> עודכן על ידי ".$users_info[$edit_by]['firstname']." ".$users_info[$edit_by]['lastname']."</div>"; 
			}
		}
	}	
	 
	public function work_contract_apply(){
		//error_reporting(E_ALL);
		//ini_set('display_errors', 1);
		//echo "<pre>";
		//print_r($_POST);
		//echo "</pre>";
		//return;
		$general_page_url = "";
		$landing_id = "";
		$landing_id_sql = "NULL";
		$post_id = get_the_ID();
		if($post_id){
			$general_page_url = add_query_arg( 'apply', '1', get_permalink($post_id));
			$landing_id = $post_id;
			$landing_id_sql = "'$landing_id'";
		}	
		
		//set the directories first
		$dirpath = WP_CONTENT_DIR."/plugins/agwee/apply/pdf";
		$img_dirpath = WP_CONTENT_DIR."/plugins/agwee/apply/img/uploads";
		if(!is_dir($dirpath)){
			$mask=umask(0);
			mkdir($dirpath, 0777);
			umask($mask);
		}
		if(!is_dir($img_dirpath)){
			$mask=umask(0);
			mkdir($img_dirpath, 0777);
			umask($mask);
		}	
		
		$apply_new_contract = false;
		$contract_apply_user = false;
		if($_REQUEST['contract_apply_id'] == "new"){
			$apply_new_contract = true;
			$contract_apply_user = "-1";
		}
		else{
			$contract_apply_user_str = 'contract_apply_'.$_REQUEST['contract_apply_id'].'_user';
			if(isset($_SESSION[$contract_apply_user_str])){
				$contract_apply_user = $_SESSION[$contract_apply_user_str];
			}		
		}
		if(!$contract_apply_user){
			?>
			<script type="text/javascript">
				window.location = "<?php echo $general_page_url; ?>&contract_apply=<?php echo $_REQUEST['contract_apply_id']; ?>";
			</script>
			<?php	
			return;
		}
		$contract_id = $_REQUEST['apply_contract'];
		$contract_data = agweeContracts_handler::get_contract_data($contract_id);
		if(!$contract_data){
			return;
		}
		$form_fields = agweeContracts_handler::create_fields_arr_from_contract($contract_id);
		$edit_ip = $_SERVER['REMOTE_ADDR'];
		$apply_info = $_REQUEST['contract'];
		if(isset($_REQUEST['contract_files'])){
			$apply_info_files_hidden = $_REQUEST['contract_files'];
		}
		$contract_values = array();	
		$old_contract_values = array();
		$edit_by_def = $contract_apply_user;
		$sign_only = false;
		if(isset($_REQUEST['sign_only'])){
			$sign_only = true;
		}
		$time_str = time();
		if(!$apply_new_contract){
			$contract_apply_sql = "SELECT * FROM ag_contract_apply WHERE id = '".$_REQUEST['contract_apply_id']."'";
			$contract_apply_res = $this->wpcon->get_results($contract_apply_sql,ARRAY_A);
			$contract_apply_data = $contract_apply_res[0];
			$contract_apply_id = $contract_apply_data['id'];
			$sql = "SELECT * FROM ag_contract_apply_inputs WHERE contract_apply_id = '".$contract_apply_id."'";
			$res = $this->wpcon->get_results($sql,ARRAY_A);
			foreach($res as $input_data){
				$old_contract_values[$input_data['input_name']] = $input_data;
			}
		}
		else{
			$sql_title = $contract_data['title'];
			$insert_sql = "INSERT INTO ag_contract_apply(contract_id,landing_id,title,ip,sign_time)
			
			VALUES('$contract_id',$landing_id_sql,'$sql_title','$edit_ip',now())";
			
			$insert_res = $this->wpcon->get_results($insert_sql,ARRAY_A);
			$insert_id =  $this->wpcon->insert_id;	
			$contract_apply_id = $insert_id;		
		}
		$enable_sign = true;
		foreach($form_fields['general']['fields'] as $key=>$input_arr){
			
			$input_name = "general_1_".$input_arr['identifier'];
			if($input_arr['type'] != "file"){
				$input_value = stripslashes(str_replace("'","''",$apply_info[$input_name]));
				if($input_arr['required'] == '1' && $input_value == ""){
					$enable_sign = false;
				}
			}
			else{
				$old_input_value = "";
				if(isset($old_contract_values[$input_name])){
					$old_input_value = $old_contract_values[$input_name]['input_value'];
				}
				if($_FILES['contract']['name'][$input_name] != ""){
					$temp_file_name = $_FILES['contract']['name'][$input_name];
					$file_name_arr = explode(".",$temp_file_name);
					$ext_str = $file_name_arr[(count($file_name_arr) - 1)];
					$ext_str = strtolower($ext_str);
					if($ext_str!="png" && $ext_str!="jpg" && $ext_str!="gif"){
						$session_messeges[] = array("color"=>"red","msg"=>"שגיאה בהעלאת תמונה");
						$input_value = $old_input_value;
					}
					else{
						$input_value = $contract_apply_id."_general_1_".$input_arr['identifier'].".".$ext_str;
						$old_contract_values[$input_name]['input_value'] = $old_contract_values[$input_name]['input_value']."_old";
						if(file_exists($img_dirpath."/".$input_value) && !is_dir($img_dirpath."/".$input_value)){
							unlink($img_dirpath."/".$input_value);
						}
						$up = move_uploaded_file($_FILES['contract']['tmp_name'][$input_name],$img_dirpath."/".$input_value);
					}
				}
				else{
					$input_value = $old_input_value;
					if($input_arr['required'] == '1' && $input_value == ""){
						$enable_sign = false;
					}				
				}
			}
			$contract_values[$input_name] = array(
				"contract_id"=>$contract_data['id'],
				"contract_apply_id"=>$contract_apply_id,
				"input_name"=>$input_name,
				"input_value"=>$input_value,
				"edit_by"=>$edit_by_def,
				"edit_ip"=>$edit_ip,
			);
		}
		$ClientMails = array();
		$contract_email_values = array();
		$users_info = array();
		foreach($form_fields['users_fields']['users'] as $user_arr){
			$user_info = array();
			foreach($user_arr['fields'] as $key=>$input_arr){
				$input_name = "user_".$user_arr['identifier']."_".$input_arr['identifier'];
				if($input_arr['type'] != "file"){
					$input_value = stripslashes(str_replace("'","''",$apply_info[$input_name]));
					if($input_arr['required'] == '1' && $input_value == ""){
						$enable_sign = false;
					}				
				}
				else{
					$old_input_value = "";
					if(isset($old_contract_values[$input_name])){
						$old_input_value = $old_contract_values[$input_name]['input_value'];
					}
					if(isset($_FILES['contract']) && $_FILES['contract']['name'][$input_name] != ""){
						$temp_file_name = $_FILES['contract']['name'][$input_name];
						$file_name_arr = explode(".",$temp_file_name);
						$ext_str = $file_name_arr[(count($file_name_arr) - 1)];
						$ext_str = strtolower($ext_str);
						if($ext_str!="png" && $ext_str!="jpg" && $ext_str!="gif"){
							$session_messeges[] = array("color"=>"red","msg"=>"שגיאה בהעלאת תמונה");
							$input_value = $old_input_value;
						}
						else{
							$input_value = $contract_apply_id."_user_".$user_arr['identifier']."_".$input_arr['identifier'].".".$ext_str;
							$old_contract_values[$input_name]['input_value'] = $old_contract_values[$input_name]['input_value']."_old";
							if(file_exists($img_dirpath."/".$input_value) && !is_dir($img_dirpath."/".$input_value)){
								unlink($img_dirpath."/".$input_value);
							}
							$up = move_uploaded_file($_FILES['contract']['tmp_name'][$input_name],$img_dirpath."/".$input_value);
							
						}
					}
					else{				
						$input_value = $old_input_value;
						if($input_arr['required'] == '1' && $input_value == ""){
							$enable_sign = false;
						}					
					}				
				}
				$contract_values[$input_name] = array(
					"contract_id"=>$contract_data['id'],
					"contract_apply_id"=>$contract_apply_id,
					"input_name"=>$input_name,
					"input_value"=>$input_value,
					"edit_by"=>$edit_by_def,
					"edit_ip"=>$edit_ip,
				);
				$user_info[$input_arr['identifier']] = $input_value;
				if($input_arr['identifier'] == "email"){
					$ClientMails[] = stripslashes(str_replace("'","''",$apply_info[$input_name]));
					$contract_email_values[$input_name] = $contract_values[$input_name];
				}
			}
			$users_info[$user_arr['identifier']] = $user_info;
		}
		
		$contract_apply_emails = "";
		$contract_apply_emails_approved = "";
		if(!$apply_new_contract){
			$contract_apply_emails = $contract_apply_data['emails'];
			$contract_apply_emails_approved = $contract_apply_data['emails_approved'];	
		}
		if(!$sign_only){
			if($apply_new_contract){
					$ClientMails_sql = "";
					$emails_appreoved_sql = "";
					$emails_appreoved_arr = array();
					$ClientMails_i = 0;
					foreach($ClientMails as $ClientMail){
						$ClientMail = $ClientMail; 
						if($ClientMails_i != 0){
							$ClientMails_sql .= ",";
							$emails_appreoved_sql.=";";
						}
						$approve_key = md5($time_str.$ClientMail);
						$emails_appreoved_sql .= $ClientMail.":".$approve_key;
						$emails_appreoved_arr[$ClientMail] = $approve_key;
						$ClientMails_sql .= $ClientMail;
						$ClientMails_i++;
					}
					$contract_apply_emails = $ClientMails_sql;
					$contract_apply_emails_approved = $emails_appreoved_sql;
					$sql_ip = $_SERVER['REMOTE_ADDR'];
					$sql_title = $contract_data['title'];
					$insert_sql = "UPDATE ag_contract_apply SET emails = '$ClientMails_sql',emails_approved = '$emails_appreoved_sql' 
					WHERE id = '$contract_apply_id'";
					$insert_res = $this->wpcon->get_results($insert_sql,ARRAY_A);	
					$contract_apply_id = $this->wpcon->insert_id;
			}
			else{
				foreach($old_contract_values as $input_name=>$old_input_arr){
					if(isset($contract_values[$input_name])){
						$new_input_arr = $contract_values[$input_name];
						if($old_input_arr['input_value'] == $new_input_arr['input_value']){
							$new_input_arr['edit_by'] = $old_input_arr['edit_by'];
							$new_input_arr['edit_ip'] = $old_input_arr['edit_ip'];
							$contract_values[$input_name] = $new_input_arr;
						}
					}
				}
				$email_change = array();
				foreach($contract_email_values as $input_name=>$value_arr){
					if($old_contract_values[$input_name]['input_value'] != $value_arr['input_value']){
						$email_change[$old_contract_values[$input_name]['input_value']] = $value_arr['input_value'];
					}
				}
				if(!empty($email_change)){
					foreach($email_change as $search=>$replace){
						$contract_apply_emails = str_replace($search,$replace,$contract_apply_emails);
						$contract_apply_emails_approved = str_replace($search,$replace,$contract_apply_emails_approved);
						$email_change_sql = "UPDATE ag_contract_apply_users SET email = '".$replace."'  WHERE 1 AND contract_apply_id = ".$contract_apply_id." AND email = '".$search."'";
						$email_change_res = $this->wpcon->get_results($email_change_sql,ARRAY_A);
					}
					$sql = "UPDATE ag_contract_apply SET emails = '".$contract_apply_emails."', emails_approved = '".$contract_apply_emails_approved."' WHERE id = '".$contract_apply_id."'";
					$res = $this->wpcon->get_results($sql,ARRAY_A);
				}
			}

			$del_sql = "DELETE FROM ag_contract_apply_inputs WHERE contract_apply_id = '$contract_apply_id'";
			$del_res = $this->wpcon->get_results($del_sql,ARRAY_A);
			foreach($contract_values as $contract_value){
				$keys = array();
				$vals = array();
				foreach($contract_value as $key=>$val){
					$keys[] = $key;
					$vals[] = "'".$val."'";
				}
				$keys_sql = implode(",",$keys);
				$vals_sql = implode(",",$vals);
				$add_sql = "INSERT INTO ag_contract_apply_inputs($keys_sql) VALUES($vals_sql)";
				$add_res = $this->wpcon->get_results($add_sql,ARRAY_A);
			}
			
			$del_sql = "DELETE FROM ag_contract_apply_users WHERE contract_apply_id = '$contract_apply_id'";
			$del_res = $this->wpcon->get_results($del_sql,ARRAY_A);
			foreach($users_info as $user_key=>$user_info){
				$insert_info = array(
					"contract_apply_id"=>$contract_apply_id,
					"contract_user_id"=>$user_key,
					"firstname"=>$user_info['firstname'],
					"lastname"=>$user_info['lastname'],
					"email"=>$user_info['email']
				);
				$keys = array();
				$vals = array();
				foreach($insert_info as $key=>$val){
					$keys[] = $key;
					$vals[] = "'".$val."'";
				}
				$keys_sql = implode(",",$keys);
				$vals_sql = implode(",",$vals);
				$add_sql = "INSERT INTO ag_contract_apply_users($keys_sql) VALUES($vals_sql)";
				$add_res = $this->wpcon->get_results($add_sql,ARRAY_A);
			}
			
			//after update, delete old signatures and apply new ones if exist
			$delsign_sql = "DELETE FROM ag_contract_apply_signatures WHERE contract_apply_id = '$contract_apply_id'";
			$delsign_res = $this->wpcon->get_results($delsign_sql,ARRAY_A);
		}
		if($enable_sign){
			$signatures_update_users = array();
			if(isset($_POST['add_sign'])){
				$signatures_update_users = $_POST['add_sign'];
			}
			$signatures_values = $_POST['sign'];
			foreach($form_fields['users_fields']['users'] as $user_arr){
				if(isset($signatures_update_users[$user_arr['identifier']])){
					if(isset($signatures_values[$user_arr['identifier']])){
						if($sign_only){
							$delsign_sql = "DELETE FROM ag_contract_apply_signatures WHERE contract_apply_id = '$contract_apply_id' AND input_name='".$user_arr['identifier']."'";
							$delsign_res = $this->wpcon->get_results($delsign_sql,ARRAY_A);
						}
						$signature_value = array(
							"contract_id"=>$contract_data['id'],
							"contract_apply_id"=>$contract_apply_id,
							"input_name"=>$user_arr['identifier'],
							"input_value"=>$signatures_values[$user_arr['identifier']],
							"edit_by"=>$edit_by_def,
							"edit_ip"=>$edit_ip,
						);
						$keys = array();
						$vals = array();
						foreach($signature_value as $key=>$val){
							$keys[] = $key;
							$vals[] = "'".$val."'";
						}
						$keys_sql = implode(",",$keys);
						$vals_sql = implode(",",$vals);
						$add_sql = "INSERT INTO ag_contract_apply_signatures($keys_sql) VALUES($vals_sql)";
						$add_res = $this->wpcon->get_results($add_sql,ARRAY_A);
					}
				}
			}
		}
		$users_contract_values = $contract_values;
		if($sign_only){
			$users_contract_values = $old_contract_values;
		}
		
		$users_info = array();
		foreach($form_fields['users_fields']['users'] as $user_arr){
			$users_info[$user_arr['identifier']] = array();
			foreach($user_arr['fields'] as $key=>$input_arr){
				$input_name = "user_".$user_arr['identifier']."_".$input_arr['identifier'];
				$users_info[$user_arr['identifier']][$input_arr['identifier']] = $users_contract_values[$input_name]['input_value'];
			}
		}
		$last_alert_sql = "UPDATE ag_contract_apply SET last_alert = NOW() WHERE id = $contract_apply_id";
		$last_alert_res = $this->wpcon->get_results($last_alert_sql,ARRAY_A);
		$contract_apply_user_str = 'contract_apply_'.$contract_apply_id.'_user';
		
		$_SESSION[$contract_apply_user_str] = $contract_apply_user;		
			//send notification to site owner about contract approval			
			$user_details = agweeContracts_handler::get_user_details();
			$user_email = $user_details['email'];
			$emails_appreoved_arr = array();
			$emails_appreoved_str = $contract_apply_emails_approved;
			$emails_appreoved_arr_1 = explode(";",$emails_appreoved_str);
			foreach($emails_appreoved_arr_1 as $email_appreoved_str){
				$email_appreoved_arr = explode(":",$email_appreoved_str);
				$emails_appreoved_arr[$email_appreoved_arr[0]] = $email_appreoved_arr[1];
			}
			$usernames = "";
			foreach($users_info as $user_info){
				$usernames .= $user_info['firstname']." ".$user_info['lastname'].",";
			}
			foreach($users_info as $user_info){
				$username = $user_info['firstname']." ".$user_info['lastname'];
				$content = "
				שלום ".$username.",<br>
				<br>
				עודכן הסכם עבודה עבור ".$usernames." <br>
				
				<br/><br/>
				<a href='{{approve_str}}' class='text_link' target='_blank'>לחץ כאן לצפייה ועדכון פרטים</a>
				<br>
				<br>
				בברכה,<br>
				".stripslashes($user_details['name'])."<br>
					".$_SERVER['HTTP_HOST']."
				";
				$header_send_to_Client= "הסכם עבודה בשיתופך עודכן עבור ".$usernames;
				$content_send_to_Client = "
					<html dir=rtl>
				
					<head>
							<title></title>
							<style type='text/css'>
								.textt{font-family: arial; font-size:12px; color: #000000}
								.text_link{font-family: arial; font-size:12px; color: navy}
							</style>
					</head>
					
					<body>
						<p class='textt' dir=rtl align=right>".$content."</p>
					</body>
				</html>";			
				$ClientMail = $user_info['email'];
				$content_send_to_Client_final = str_replace("{{username}}",$username,$content_send_to_Client);
				$header_send_to_Client_final =  str_replace("{{username}}",$username,$header_send_to_Client);

				$host = $_SERVER['HTTP_HOST'];
				$approve_key = $emails_appreoved_arr[$ClientMail];
				$appreove_str = $general_page_url."&enter=$approve_key&contract_apply=$contract_apply_id";
				$content_send_to_Client_final = str_replace("{{approve_str}}",$appreove_str,$content_send_to_Client);
				agweeContracts_handler::send_emails($this->callbeck_class, $header_send_to_Client_final, $content_send_to_Client_final, $ClientMail);
				
				
			}
				
			$content = "
			שלום,<br>
			<br>
			עודכן הסכם עבודה עבור: ".$usernames."
			בברכה,<br>
			".stripslashes($user_details['name'])."<br>
				".$_SERVER['HTTP_HOST']."
			";
			$content = $content;
			$header_send_to_Client= "הסכם עבודה עבור ".$usernames;
			$content_send_to_Client = "
				<html dir=rtl>
			
				<head>
						<title></title>
						<style type='text/css'>
							.textt{font-family: arial; font-size:12px; color: #000000}
							.text_link{font-family: arial; font-size:12px; color: navy}
						</style>
				</head>
				
				<body>
					<p class='textt' dir=rtl align=right>".$content."</p>
				</body>
			</html>";			
			$ClientMail = $user_details['email'];
			$content_send_to_Client_final = str_replace("{{username}}",$username,$content_send_to_Client);
			$header_send_to_Client_final =  str_replace("{{username}}",$username,$header_send_to_Client);
			agweeContracts_handler::send_emails($this->callbeck_class, $header_send_to_Client_final, $content_send_to_Client_final, $ClientMail);
		
		
		
		
		
		$session_messeges[] = array("color"=>"green","msg"=>"פרטי החוזה עודכנו בהצלחה");
		$_SESSION['contract_messeges'] = $session_messeges;	
		?>
		
		<script type="text/javascript">
			window.location = "<?php echo $general_page_url; ?>&contract_apply=<?php echo $contract_apply_id; ?>&view=1";
		</script>
		<?php
	}
	public function work_contract_export($contract_id,$contract_apply_id,$contract_data,$contract_apply_data,$contract_design,$form_fields,$users_info,$enable_sign){
		$general_page_url = "";
		$landing_id = "";
		$post_id = get_the_ID();
		if($post_id){
			$general_page_url = add_query_arg( 'apply', '1', get_permalink($post_id));
			$landing_id = $post_id;
		}
		if(!$enable_sign){
			$session_messeges[] = array("color"=>"red","msg"=>"לא ניתן להשלים את יצירת החוזה. חסרים פרטים");
		}
		elseif(!$enable_sign){
			$session_messeges[] = array("color"=>"red","msg"=>"לא ניתן להשלים את יצירת החוזה. חסרות חתימות");
		}
		else{
			$file_name = "contract_".$contract_id."_".$contract_apply_id.".pdf";
			$dirpath = WP_CONTENT_DIR."/plugins/agwee/apply/pdf";
			$file_path = $dirpath."/".$file_name;
			$dirhttp = WP_CONTENT_URL."/plugins/agwee/apply/pdf";
			$file_http = $dirhttp."/".$file_name;		
			$header_html = "";
			$asset_img_httppath = WP_CONTENT_URL."/plugins/agwee/assets/img/uploads";
			if($contract_data['header_img'] != ""){
				$header_html = "<img src='".$asset_img_httppath."/".$contract_data['header_img']."' />";
			}
			$footer_html = "";
			if($contract_data['footer_img'] != ""){
				$footer_html = "<img src='".$asset_img_httppath."/".$contract_data['footer_img']."' />";
			}		
			$signatures_footer_html = "";
			foreach($users_info as $user_info){
				$signatures_footer_html.= $user_info['firstname']." ".$user_info['lastname'].":"."<img style='height:50px;' src='".$user_info['signature']."' />";
			}
			$signatures_footer_html = "<div style='padding:5px; border:1px solid gray;'>".$signatures_footer_html."</div>";
			$contract_data = agweeContracts_handler::get_contract_data($contract_id);
			$contract_content = $contract_data['content'];
			$img_httppath = WP_CONTENT_URL."/plugins/agwee/apply/img/uploads";
			$img_dirpath = WP_CONTENT_DIR."/plugins/agwee/apply/img/uploads";
			$img_files = array();
			foreach($form_fields['general']['fields'] as $field_key=>$field_arr){
				$input_value = $field_arr['value'];					
				if($field_arr['type'] == "file" && $input_value != ""){
					$img_files[] = "$img_dirpath/$input_value";
					$input_value = "<img src='$img_httppath/$input_value' />";
				}
				$contract_content = str_replace("{{".$field_arr['title']."}}",$input_value,$contract_content);
			}
			foreach($form_fields['users_fields']['users'] as $user_arr){		
				foreach($user_arr['fields'] as $field_key=>$field_arr){
					$input_value = $field_arr['value'];					
					if($field_arr['type'] == "file" && $input_value != ""){
						$img_files[] = "$img_dirpath/$input_value";
						$input_value = "<img src='$img_httppath/$input_value' />";
					}
					$contract_content = str_replace("{{".$field_arr['title']."(".$user_arr['role_name'].")}}",$input_value,$contract_content);
				}
				$user_signature = "<img src='".$users_info[$user_arr['identifier']]['signature']."' style='width:250px;border-bottom:1px solid;' />";
				$contract_content = str_replace("{{חתימה(".$user_arr['role_name'].")}}",$user_signature,$contract_content);
			}
			
			$html = "
				<style>
					body { font-family: 'DejaVu Sans Condensed'; font-size: 20px;  }
					div.mpdf_index_main {font-family: xbriyaz;}
					div.mpdf_index_entry {font-family: xbriyaz;}
					div.mpdf_index_letter {font-family: xbriyaz;}
				</style>
				<body dir='rtl'>
					$contract_content
				</body>
			";
			
			
			
			$html_arr = explode("{{נספח}}",$html);
			$html = $html_arr[0];		
			
			$this->callbeck_class->include_required_file('vendor/autoload.php');
			//$mpdf=new mPDF('','A4','','',10,10,$contract_data['head_px'],$contract_data['foot_px'],0); 
			$mpdf=new \Mpdf\Mpdf([
				'mode' => 'utf-8', 
				'format' => 'A4-L',
				'margin_top' => $contract_data['head_px'],
				'margin_bottom' => $contract_data['foot_px'],
				]); 			
			$mpdf->SetDirectionality('rtl');
			$mpdf->SetHTMLHeader($header_html);
			$mpdf->SetHTMLFooter($signatures_footer_html.$footer_html);
			$mpdf->WriteHTML($html);
			$mpdf->SetHTMLFooter($footer_html);
			if(isset($html_arr[1])){
				$mpdf->AddPage();
				$mpdf->WriteHTML($html_arr[1]);
			}		
			$mpdf->Output($file_path,'F');
					$file_path = $dirpath."/".$file_name;
			$file_http = "https://".$_SERVER['HTTP_HOST']."/work_contracts/".$file_name;
			
			$sql = "UPDATE ag_contract_apply SET pdf_name = '$file_name', pdf_path='$file_path',pdf_http = 'file_http',sign_time = NOW() WHERE id = $contract_apply_id";
			$res = $this->wpcon->get_results($sql,ARRAY_A);
			//after creating pdf - delete unused data and images
			foreach($img_files as $img_file){
				if(file_exists($img_file) && !is_dir($img_file)){
					unlink($img_file);
				}
			}
			$sql = "DELETE FROM ag_contract_apply_inputs WHERE contract_apply_id = $contract_apply_id";
			$res = $this->wpcon->get_results($sql,ARRAY_A);
			$sql = "DELETE FROM ag_contract_apply_signatures WHERE contract_apply_id = $contract_apply_id";
			$res = $this->wpcon->get_results($sql,ARRAY_A);		
			$user_details = agweeContracts_handler::get_user_details();
			$user_email = $user_details['email'];
			$emails_appreoved_arr = array();
			$emails_appreoved_str = $contract_apply_data['emails_approved'];
			$emails_appreoved_arr_1 = explode(";",$emails_appreoved_str);
			foreach($emails_appreoved_arr_1 as $email_appreoved_str){
				$email_appreoved_arr = explode(":",$email_appreoved_str);
				$emails_appreoved_arr[$email_appreoved_arr[0]] = $email_appreoved_arr[1];
			}
			$usernames = "";
			foreach($users_info as $user_info){
				$username = $user_info['firstname']." ".$user_info['lastname'];
				$content = "
				שלום ".$user_info['firstname']." ".$user_info['lastname'].",<br>
				<br>
				קובץ הסכם העבודה נוצר בהצלחה <br>
				
				<br/><br/><span style='color:red;'>* בלחיצה על הלינק הבא אני מאשר שקראתי את החוזה ואני מסכים לאמת את אמינותו</span><br/>
				<a href='{{approve_str}}' class='text_link' target='_blank'>לחץ כאן על מנת לאשר את אמינות החוזה</a>
				<br>
				<br>
				בברכה,<br>
				".stripslashes($user_details['name'])."<br>
					".$_SERVER['HTTP_HOST']."
				";
				$content = $content;
				$header_send_to_Client= "הסכם עבודה עבור {{username}}";
				$content_send_to_Client = "
					<html dir=rtl>
				
					<head>
							<title></title>
							<style type='text/css'>
								.textt{font-family: arial; font-size:12px; color: #000000}
								.text_link{font-family: arial; font-size:12px; color: navy}
							</style>
					</head>
					
					<body>
						<p class='textt' dir=rtl align=right>".$content."</p>
					</body>
				</html>";			
				$ClientMail = $user_info['email'];
				$content_send_to_Client_final = str_replace("{{username}}",$username,$content_send_to_Client);
				$header_send_to_Client_final =  str_replace("{{username}}",$username,$header_send_to_Client);

				$host = $_SERVER['HTTP_HOST'];
				$approve_key = $emails_appreoved_arr[$ClientMail];
				$appreove_str = $general_page_url."&approve=$approve_key&contract_apply=$contract_apply_id";
				$content_send_to_Client_final = str_replace("{{approve_str}}",$appreove_str,$content_send_to_Client);
				agweeContracts_handler::send_emails($this->callbeck_class, $header_send_to_Client_final, $content_send_to_Client_final, $ClientMail, $file_path);
				$usernames .= $user_info['firstname']." ".$user_info['lastname'].",";
				
			}
				
			$content = "
			שלום,<br>
			<br>
			קובץ הסכם העבודה נוצר בהצלחה <br>
			עבור : ".$usernames."
			בברכה,<br>
			".stripslashes($user_details['name'])."<br>
				".$_SERVER['HTTP_HOST']."
			";
			$content = $content;
			$header_send_to_Client= "הסכם עבודה עבור ".$usernames;
			$content_send_to_Client = "
				<html dir=rtl>
			
				<head>
						<title></title>
						<style type='text/css'>
							.textt{font-family: arial; font-size:12px; color: #000000}
							.text_link{font-family: arial; font-size:12px; color: navy}
						</style>
				</head>
				
				<body>
					<p class='textt' dir=rtl align=right>".$content."</p>
				</body>
			</html>";			
			$ClientMail = $user_details['email'];
			$content_send_to_Client_final = str_replace("{{username}}",$username,$content_send_to_Client);
			$header_send_to_Client_final =  str_replace("{{username}}",$username,$header_send_to_Client);
			agweeContracts_handler::send_emails($this->callbeck_class, $header_send_to_Client_final, $content_send_to_Client_final, $ClientMail, $file_path);
				

			$session_messeges[] = array("green"=>"red","msg"=>"קובץ החוזה הופק בהצלחה ונשלח אל כתובות המייל של המשתתפים בחוזה");
			$last_alert_sql = "UPDATE ag_contract_apply SET last_alert = NOW() WHERE id = $contract_apply_id";
			$last_alert_res = $this->wpcon->get_results($last_alert_sql,ARRAY_A);
		}
		$_SESSION['contract_messeges'] = $session_messeges;
		?>
		<script type="text/javascript">
			window.location = "<?php echo $general_page_url; ?>&contract_apply=<?php echo $contract_apply_id; ?>&view=1";
		</script>
		<?php	
	}
	public function work_contract_exit($contract_apply_id){
		$contract_apply_user_str = 'contract_apply_'.$contract_apply_id.'_user';
		unset($_SESSION[$contract_apply_user_str]);
		?>
		<script type="text/javascript">
			window.location = "<?php echo HTTP_S; ?>://<?php echo $_SERVER['HTTP_HOST']; ?>";
		</script>
		<?php	
	}
	public function work_contract_enter($contract_apply_id,$contract_apply_data,$users_info){
		$general_page_url = "";
		$landing_id = "";
		$post_id = get_the_ID();
		if($post_id){
			$general_page_url = add_query_arg( 'apply', '1', get_permalink($post_id));
			$landing_id = $post_id;
		}	
		$user_keys_by_email = array();
		foreach($users_info as $user_key=>$user_info){
			$user_keys_by_email[$user_info['email']] = $user_key;
		}
		$emails_approved_str = $contract_apply_data['emails_approved'];

		$emails_approved_arr = explode(";",$emails_approved_str);
		foreach($emails_approved_arr as $email_approve_str){
			$email_approve_arr = explode(":",$email_approve_str);			
			$eprove_key_str = trim($email_approve_arr[1]);
			$eprove_key_check = trim($_REQUEST['enter']);
			if($eprove_key_str == $eprove_key_check){
				$contract_apply_user_str = 'contract_apply_'.$contract_apply_id.'_user';
				$_SESSION[$contract_apply_user_str] = $user_keys_by_email[$email_approve_arr[0]];
			}
		}
		?>
		<script type="text/javascript">
			window.location = "<?php echo $general_page_url; ?>&contract_apply=<?php echo $contract_apply_id; ?>&view=1";
		</script>
		<?php
	}

	public function work_contract_approve($contract_apply_id,$contract_apply_data,$users_info){
		$general_page_url = "";
		$landing_id = "";
		$post_id = get_the_ID();
		if($post_id){
			$general_page_url = add_query_arg( 'apply', '1', get_permalink($post_id));
			$landing_id = $post_id;
		}	
		$user_keys_by_email = array();
		foreach($users_info as $user_key=>$user_info){
			$user_keys_by_email[$user_info['email']] = $user_key;
		}
		$emails_approved_str = $contract_apply_data['emails_approved'];
		$emails_approved_arr = explode(";",$emails_approved_str);
		foreach($emails_approved_arr as $email_approve_str){
			$email_approve_arr = explode(":",$email_approve_str);			
			$eprove_key_str = trim($email_approve_arr[1]);
			$eprove_key_check = trim($_REQUEST['approve']);
			if($eprove_key_str == $eprove_key_check){
				$contract_apply_user_str = 'contract_apply_'.$contract_apply_id.'_user';
				$_SESSION[$contract_apply_user_str] = $user_keys_by_email[$email_approve_arr[0]];
				$_SESSION['approve_user'] = $eprove_key_check;
			}
		}
		?>
		<script type="text/javascript">
			window.location = "<?php echo $general_page_url; ?>&contract_apply=<?php echo $contract_apply_id; ?>&view=1";
		</script>
		<?php
	}

	public function work_contract_approve_form($contract_apply_id,$contract_data,$contract_apply_data,$users_info){
		$general_page_url = "";
		$landing_id = "";
		$post_id = get_the_ID();
		if($post_id){
			$general_page_url = add_query_arg( 'apply', '1', get_permalink($post_id));
			$landing_id = $post_id;
		}	
		if(isset($_REQUEST['final_approve'])){
			return $this->work_contract_approve_final($contract_apply_id,$contract_apply_data,$users_info);
		}
		$contract_apply_user = $_SESSION['contract_apply_'.$contract_apply_id.'_user'];
		ob_start();
		?>
			<div style="float:left;">
				<?php if($contract_apply_user != "-1"): ?>
					שלום <?php echo $users_info[$contract_apply_user]['firstname']." ".$users_info[$contract_apply_user]['lastname']; ?><br/>
				<?php endif; ?>
				<a href="<?php echo $general_page_url; ?>&contract_apply=<?php echo $contract_apply_id; ?>&exit=1" style="color:red;font-size:18px;"><b>יציאה</b></a>
			</div>	
		<h2>אישור סופי לחוזה: <?php echo $contract_data['title']; ?></h2>
		<p style="font-size:18px;">
		שנחתם בין המשתתפים: 
		<?php foreach($users_info as $user_info): ?>
			<?php echo $user_info['firstname']." ".$user_info['lastname'].","; ?>
		<?php endforeach; ?>
		</p>
		<div  style="display: block;margin-bottom:100px; font-size:18px; background: #ffffae;padding: 15px;border:1px solid gray;">
			<h3>
				אנא בחר האם לאשר את אמינות החוזה:
			</h3>
			<form id="contract_form" action="" method="POST" onsubmit="return check_approve_note();">
				<div>
					<input  type="radio" id="approve_radio_1" class="approve_radio" name="final_approve" value="1" style="zoom:2;margin-top:-1px; margin-left:4px;" checked />
					<span>
						אני מאשר את החוזה
					</span>
					<br/>
				</div>
				<div style="margin-top:15px;">
					<input type="radio" id="approve_radio_0" class="approve_radio" name="final_approve" style="zoom:2;margin-top:-1px; margin-left:4px;" value="0" />
					<span>
					אינני מאשר את החוזה
					</span>
				</div>
				<div style="display:none;margin-top:15px; margin-right:30px;" id="approve_note_wrap">
					<b>מדוע אינני מאשר את החוזה?</b><br/>
					<textarea name="approve_note" id="approve_note"></textarea>
				</div>
				<input style="font-size:30px;font-weight:bold;display:block; height:87px;width:200px;border-radius:20px;float:right;margin-top:20px;background:#eae4e4;color:#8e7d7d;cursor:pointer;" type="submit" value="שלח" /></p>
			</form>
			<div style="clear:both;"></div>
		</div>
		<script type="text/javascript">
			jQuery(function($){
				$(".approve_radio").change(function () {
					if ($("#approve_radio_1").is(":checked")) {
						$("#approve_note_wrap").hide();
					}
					else {
						$("#approve_note_wrap").show();
					}
				});
			});
			
			function check_approve_note(){
				var ret = true;
				jQuery(function($){
					if ($("#approve_radio_1").is(":checked")) {
						ret = true;
					}
					else {
						if($("#approve_note").val() == ""){
							alert("אנא רשום מדוע אינך מאשר את אמינות החוזה");
							ret = false;
						}
					}
					
				});	
				return ret;
			}
		</script>
		<?php
		agweeContracts_handler::get_ob_clean();
	}

	public function work_contract_approve_final($contract_apply_id,$contract_apply_data,$users_info){
			$appreove_code = $_SESSION['approve_user'];
			$emails_approved_str = $contract_apply_data['emails_approved'];
			$emails_approved_arr = explode(";",$emails_approved_str);
			$emails_approved_update = "";
			$emails_approved_i = 0;
			$approve_key_found = false;
			$fully_approved = "1";
			$user_found = false;
			$users_by_mails = array();
			foreach($users_info as $user_info){
				$users_by_mails[$user_info['email']] = $user_info;
			}

			foreach($emails_approved_arr as $email_approve_str){
				if($emails_approved_i != 0){
					$emails_approved_update .= ";";
				}
				$email_approve_arr = explode(":",$email_approve_str);
				$email_str = trim($email_approve_arr[0]);
				if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
					$approve_ip = $_SERVER['HTTP_CLIENT_IP'];
				} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
					$approve_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
				} else {
					$approve_ip = $_SERVER['REMOTE_ADDR'];
				}			
				$eprove_key_str = trim($email_approve_arr[1]);
				$eprove_key_check = trim($appreove_code);
				//echo $approve_code;
				
				if($eprove_key_str == $eprove_key_check){
					$eprove_key_str = '1';
					$email_str_notification = $email_str;
					$ip_str_notification = $approve_ip;
					$user_found = $users_by_mails[$email_str];		
					$approve_key_found = true;
					if($_REQUEST['final_approve'] == '1'){
						$sql = "UPDATE ag_contract_apply_users SET approve_ip = '".$_SERVER['REMOTE_ADDR']."'  WHERE 1 AND contract_apply_id = ".$contract_apply_id." AND email = '".$email_str."'";
					}
					else{
						$approve_note = $_REQUEST['approve_note'];
						$sql = "UPDATE ag_contract_apply_users SET approve_note = '".$approve_note."'  WHERE 1 AND contract_apply_id = ".$contract_apply_id." AND email = '".$email_str."'";
					}
					$res = $this->wpcon->get_results($sql,ARRAY_A);		
				}
				if($eprove_key_str != "1"){
					$fully_approved = "0";
				}
				$emails_approved_update .= "$email_str:$eprove_key_str";
				$emails_approved_i++;
			}
			if($approve_key_found){
				if($_REQUEST['final_approve'] == '1'){
					$sql = "UPDATE ag_contract_apply SET emails_approved = '$emails_approved_update',fully_approved = '$fully_approved'  WHERE 1 AND id = ".$contract_apply_id."";
					$res = $this->wpcon->get_results($sql,ARRAY_A);	
				}
				//send notification to site owner about contract approval
				$user_details = agweeContracts_handler::get_user_details();
				$user_email = $user_details['email'];
				if($_REQUEST['final_approve'] == '1'){
					$header_send_to_Client= "אישור אמינות של הסכם עבודה";
					$approve_state_str = "<b style='color:green'>". "אושר בהצלחה". "</b>";
					$approve_note = "";
				}
				else{
					$header_send_to_Client= "דחיית אמינות של הסכם עבודה";
					$approve_state_str = "<b style='color:red'>". "נדחה". "</b>";
					$approve_note = "<b>סיבת הדחייה: </br>".$_REQUEST['approve_note']."</b>";
				}
				$content = "
				שלום ,<br>
				<br>
				הסכם עבודה על שם : ".$user_found['firstname']." ".$user_found['lastname']."<br>
				<br>
				".$approve_state_str." מכתובת המייל: ".$email_str_notification." <br/>IP: ".$ip_str_notification."
				<br>".$approve_note."
				<br>
				בברכה,<br>
				".stripslashes($user_details['name'])."<br>
					".$_SERVER['HTTP_HOST']."
				";
				$content = $content;
				
				$content_send_to_Client = "
					<html dir=rtl>
				
					<head>
							<title></title>
							<style type='text/css'>
								.textt{font-family: arial; font-size:12px; color: #000000}
								.text_link{font-family: arial; font-size:12px; color: navy}
							</style>
					</head>
					
					<body>
						<p class='textt' dir=rtl align=right>".$content."</p>
					</body>
					</html>";
									
				
				agweeContracts_handler::send_emails($this->callbeck_class, $header_send_to_Client, $content_send_to_Client, $user_email);
				$last_alert_sql = "UPDATE ag_contract_apply SET last_alert = NOW() WHERE id = $contract_apply_id";
				$last_alert_res = $this->wpcon->get_results($last_alert_sql,ARRAY_A);
				ob_start();
				unset($_SESSION['contract_apply_'.$contract_apply_id.'_user']);
				unset($_SESSION['approve_user']);
				if($_REQUEST['final_approve'] == '1'){
					echo "<b style='color:green;font-size:18px;'>החוזה אושר בהצלחה</b>";
				}
				else{
					echo "<b style='color:green;font-size:18px;'>הערתך התקבלה ותועבר להנהלת האתר. תודה.</b>";
				}
				agweeContracts_handler::get_ob_clean();
			}
			else{
				ob_start();
				echo "<b style='color:red;font-size:18px;'>החוזה כבר אושר מכתובת המייל שלך</b>";
				agweeContracts_handler::get_ob_clean();
			}
			
	}

	public function work_contract_find(){
		ob_start();

		$email_find = "";
		$form_msg = "";
		if(isset($_REQUEST['for_email'])){
			$email_find = $_REQUEST['for_email'];
			if($email_find == ""){
				$form_msg = "כתובת המייל שרשמת אינה תקינה";
			}
		}
		if($email_find == ""){
			?>
			
			<h3>מציאת חוזה לפי כתובת מייל</h3>
			<?php if($form_msg != ""): ?>
				<b style="color:red;"><?php echo $form_msg; ?></b>
			<?php endif; ?>
			<p>
			כאן ניתן לאתר חוזים חתומים שמשוייכים לכתובת המייל שלך
			</p>
			
			<form action="?m=work_contract_find" method="POST">
				<label>רשום כאן את כתובת המייל שלך</label>
				<br/>
				<input type="email" class="email" name="for_email" />
				<input type="submit" value="אתר את החוזים שלי"/>
			</form>
			<?php
		}
		else{
			$send_contracts_msg = "";
			if(isset($_REQUEST['send_contracts'])){
				if(empty($_REQUEST['send_contract'])){
					$send_contracts_msg = "לא סומנו חוזים לשליחה";
				}
				else{
					$contracts = array();
					$host = $_SERVER['HTTP_HOST'];
					//send notification to site owner about contract approval
					$user_details = agweeContracts_handler::get_user_details();
					$user_email = $user_details['email'];
					foreach($_REQUEST['send_contract'] as $contract_apply_id){
						$sql = "SELECT * FROM ag_contract_apply_users WHERE 1 AND contract_apply_id = $contract_apply_id";
						$res = $this->wpcon->get_results($sql,ARRAY_A);
						$contract_users = array();
						$email_user = false;
						foreach($res as $user_data){
							if($user_data['email'] == $email_find){
								$email_user = $user_data;
							}
							$contract_users[$user_data['contract_user_id']] = $user_data;
						}
						if(!$email_user){
							continue;
						}
						
						$sql = "SELECT * FROM ag_contract_apply WHERE 1 AND id = $contract_apply_id";
						$res = $this->wpcon->get_results($sql,ARRAY_A);
						$contract = $res[0];
						$contract['users'] = $contract_users;
						$general_page_url = "";
						$landing_id = "";
						if($contract['landing_id'] != ""){
							$general_page_url = add_query_arg( 'apply', '1', get_permalink($contract['landing_id']));
							$landing_id = $contract['landing_id'];
						}
						$users_title_arr = array();
						foreach($contract_users as $contract_user){
							$users_title_arr[] = $contract_user['firstname']." ".$contract_user['lastname'];
						}
						$emails_approved_arr = explode(";",$contract['emails_approved']);
						$approve_key = false;
						foreach($emails_approved_arr as $email_approved_str){
							$email_approved_arr = explode(":",$email_approved_str);
							if($email_approved_arr[0] == $email_find){
									$approve_key = $email_approved_arr[1];
							}
						}
						$email_title = "שליחה חוזרת של חוזה: ";
						$email_title.= $contract['title'];
						$email_title.= " בין: ";
						$email_title.= implode(",",$users_title_arr);
						$email_content = "שלום ";
						$email_content.= $email_user['firstname']." ".$email_user['lastname'].".<br/>";
						$contract_file_email = null;
						if($contract['pdf_path'] == ""){						
							$enter_url = $general_page_url."&enter=$approve_key&contract_apply=$contract_apply_id";				
							$email_content.= "לצפייה ועדכון פרטי החוזה: ";
							$email_content.= $contract['title'];
							$email_content.= " שנחתם בין: ";
							$email_content.= implode(",",$users_title_arr);	
							$email_content.= "<br/>";
							$email_content.= " לחץ על הלינק הבא:  <br/>";
							$email_content.= "<a href = '$enter_url'>לחץ כאן לצפייה ועדכון החוזה</a><br/>";
						}
						else{
							$contract_file_email = array($contract['pdf_path']);
							$email_content.= "מצורף קובץ החוזה - ";
							$email_content.= $contract['title'];
							$email_content.= " שנחתם בין: ";
							$email_content.= implode(",",$users_title_arr);	
							$email_content.= "<br/>";
							if($approve_key && $approve_key != '1'){
								$approve_url = $general_page_url."&approve=$approve_key&contract_apply=$contract_apply_id";
								$email_content.= "<a href = '$approve_url'>לחץ כאן על מנת לאשר את אמינות החוזה</a><br/>";
							}
						}
						$email_content.= "בברכה,<br/>";
						$email_content.= stripslashes($user_details['name']);
						$email_content.= "<br>";
						$email_content.= $_SERVER['HTTP_HOST'];
						$header_send_to_Client= $email_title;
						$content_send_to_Client = "<html dir=rtl><head><title></title>
													<style type='text/css'>
														.textt{font-family: arial; font-size:12px; color: #000000}
														.text_link{font-family: arial; font-size:12px; color: navy}
													</style></head><body><p class='textt' dir=rtl align=right>". $email_content."</p></body>
													</html>";
						$ClientMail = $email_find;	
						agweeContracts_handler::send_emails($this->callbeck_class, $header_send_to_Client, $content_send_to_Client, $ClientMail,$contract_file_email);
						$send_contracts_msg = "החוזים שביקשת נשלחו אל כתובת המייל: ".$email_find;		
						$contracts[] = $contract;
					}
					if(empty($contracts)){
						$send_contracts_msg = "לא סומנו חוזים לשליחה";
					}
				}
			}
			$email_find = trim($email_find);
			$contracts_found = array();
			$find_sql = "SELECT contract_apply_id FROM ag_contract_apply_users WHERE 1 AND email = '$email_find'";
			$find_res = $this->wpcon->get_results($find_sql,ARRAY_A);
			$contract_apply_id_arr = array();
			foreach($find_res as $contract_apply_id_data){
				$contract_apply_id_arr[$contract_apply_id_data['contract_apply_id']] = $contract_apply_id_data['contract_apply_id'];
			}
			$contract_apply_id_in = implode(",",$contract_apply_id_arr);
			$find_sql = "SELECT * FROM ag_contract_apply WHERE 1 AND id IN ($contract_apply_id_in)";
			$find_res = $this->wpcon->get_results($find_sql,ARRAY_A);		
			foreach($find_res as $contract){
				if($contract['title'] == ""){
					$contract['title'] = "ללא כותרת";
				}
				else{
					$contract['title'] = $contract['title'];
				}
				$contract_apply_id = $contract['id'];
				$users_info_sql = "SELECT * FROM ag_contract_apply_users WHERE 1 AND contract_apply_id = $contract_apply_id";
				$users_info_res = $this->wpcon->get_results($users_info_sql,ARRAY_A);
				$usernames = array();
				$useremails = array();
				foreach($users_info_res as $user_info){
					$usernames[] = $user_info['firstname']." ".$user_info['lastname'];
					$useremails[] =  $user_info['email'];
				}
				$contract['usernames'] = implode(",<br/>",$usernames);
				$contract['useremails'] = implode("<br/>",$useremails);
				$contracts_found[] = $contract;
			}
			
			?>
			<h3>מציאת חוזה לפי כתובת מייל <?php echo $email_find; ?></h3>
			<a href="?m=work_contract_find"><<<חזור</a><br/>
			<?php if(empty($contracts_found)): ?>
				<p><b style="color:red;">לא נמצאו חוזים לכתובת מייל זו</b></p>
			<?php else: ?>
				<?php if($send_contracts_msg != ""): ?>
					<p><b style="color:green;"><?php echo $send_contracts_msg; ?></b></p>
				<?php endif; ?>
				<b>נמצאו <?php echo count($contracts_found); ?> חוזים: </b>
				<p>סמן את החוזים שברצונך שיילחו אליך למייל</p>
				
				<form action="?m=work_contract_find" method="POST">
					<input type="hidden" name="for_email" value="<?php echo $email_find; ?>" />
					<input type="hidden" name="send_contracts" value="1" />
					<table border="1" cellpadding="10px;" style="border-collapse:collapse;">
						<tr>
							<th></th>
							<th>כותרת</th>
							<th>שמות</th>
							<th>אימיילים</th>
						</tr>
						<?php foreach($contracts_found as $contract): ?>
							<tr>
								<td><input type="checkbox" name="send_contract[]" value="<?php echo $contract['id']; ?>" /></td>
								<td><?php echo $contract['title']; ?></td>
								<td><?php echo $contract['usernames']; ?></td>
								<td><?php echo $contract['useremails']; ?></td>
							</tr>
						<?php endforeach; ?>					
					</table>
					<br/>
					<br/>
					<input type="submit" value="שלח את החוזים המסומנים אל כתובת המייל שלי"/>
				</form>			
			<?php endif; ?>
			<a href="?m=work_contract_find" style="font-size:18px;">חזרה לאיתור חוזה</a>
			<?php
		}
		$page_content = ob_get_clean();
		echo $page_content;
	}
	public function work_contract_export_demo($contract_id){		
		$contract_data = agweeContracts_handler::get_contract_data($contract_id);
			$asset_img_httppath = WP_CONTENT_URL."/plugins/agwee/assets/img/uploads";
			if($contract_data['header_img'] != ""){
				$header_html = "<img src='".$asset_img_httppath."/".$contract_data['header_img']."' />";
			}
			$footer_html = "";
			if($contract_data['footer_img'] != ""){
				$footer_html = "<img src='".$asset_img_httppath."/".$contract_data['footer_img']."' />";
			}		
			$signatures_footer_html = "";

			$signatures_footer_html = "<div style='padding:5px; border:1px solid gray;'>חתימות בעמוד</div>";
			
			$contract_content = $contract_data['content'];

			$contract_content =  preg_replace("/\r\n|\r|\n/",'<br/>',$contract_content); 
			$html = "
				<style>
					body { font-family: 'DejaVu Sans Condensed'; font-size: 20px;  }
					div.mpdf_index_main {font-family: xbriyaz;}
					div.mpdf_index_entry {font-family: xbriyaz;}
					div.mpdf_index_letter {font-family: xbriyaz;}
				</style>
				<body dir='rtl'>
					$contract_content
				</body>
			";
			
			
			//exit($html);
			$html_arr = explode("{{נספח}}",$html);
			$html = $html_arr[0];	
			
			$this->callbeck_class->include_required_file('vendor/autoload.php');
//$mpdf = new \Mpdf\Mpdf('','A4','','',10,10,$contract_data['head_px'],$contract_data['foot_px'],0); 
		
			$mpdf=new \Mpdf\Mpdf([
				'mode' => 'utf-8', 
				'format' => 'A4-L',
				'margin_top' => $contract_data['head_px'],
				'margin_bottom' => $contract_data['foot_px'],
				]); 
			$mpdf->SetDirectionality('rtl');
			$mpdf->SetHTMLHeader($header_html);
			$mpdf->SetHTMLFooter($signatures_footer_html.$footer_html);
			$mpdf->WriteHTML($html);
			//$mpdf->SetHTMLFooter($footer_html);
			if(isset($html_arr[1])){
				$mpdf->AddPage();
				$mpdf->WriteHTML($html_arr[1]);
			}		
			$mpdf->Output();
	}

	public function work_contracts_auto_alerts(){
		$alert_days = array(1,3,7,15,30);
		$alert_contracts = array();
		
		foreach($alert_days as $alert_day){
			$alert_day_before = $alert_day - 1;
			$site_owners = array();
			
			$sql = "SELECT * FROM ag_contract_apply WHERE fully_approved = 0 AND last_alert >= DATE_ADD(CURDATE(), INTERVAL -$alert_day DAY) AND last_alert <= DATE_ADD(CURDATE(), INTERVAL -$alert_day_before DAY) AND canceled = 0";
			
			$res = $this->wpcon->get_results($sql,ARRAY_A);
			foreach($res as $contract){
				$contract_apply_id = $contract['id'];
				$contract['alert_day'] = $alert_day;
				$approve_keys = array();
				$emails_approve_arr = explode(";",$contract['emails_approved']);
				foreach($emails_approve_arr as $email_approve_str){
					$email_approve_arr = explode(":",$email_approve_str);
					$approve_keys[$email_approve_arr[0]] = $email_approve_arr[1];
				}
				
				if($contract['pdf_http'] != ""){
					$contract['alert_type'] = 'approve';
				}
				else{
					$contract['alert_type'] = 'update';
				}
				$users_sql = "SELECT * FROM ag_contract_apply_users WHERE contract_apply_id = $contract_apply_id";
				$users_res = $this->wpcon->get_results($users_sql,ARRAY_A);
				$users_arr = array();
				$users_send_to = array();
				foreach($users_res as $user_data){
					if($user_data['approve_note'] == ""){
						$user_data['approve_key'] = $approve_keys[$user_data['email']];
						if($user_data['approve_ip'] == "" || $contract['alert_type'] == 'update'){
							$users_send_to[] = $user_data['contract_user_id'];
						}
						$users_arr[$user_data['contract_user_id']] = $user_data;
					}
				}
				$contract['users'] = $users_arr;
				$contract['users_send_to'] = $users_send_to;
				$alert_contracts[$contract['id']] = $contract;
			}

		}
	
		foreach($alert_contracts as $contract){
			$user_details = agweeContracts_handler::get_user_details();
			$host = $user_details['domain'];		
			$general_page_url = "";
			$landing_id = "";
			if($contract['landing_id'] != ""){
				$general_page_url = add_query_arg( 'apply', '1', get_permalink($contract['landing_id']));
				$landing_id = $contract['landing_id'];
			}
			$users_title_arr = array();
			foreach($contract['users'] as $contract_user){
				$users_title_arr[] = $contract_user['firstname']." ".$contract_user['lastname'];
			}
			$contract_ids = array();		
			foreach($contract['users_send_to'] as $user_send_to){
				$contract_ids[] = $contract['id'];
				$email_user = $contract['users'][$user_send_to];
				$contract_apply_id = $contract['id'];
				$email_find = $email_user['email']; 
				$approve_key = $email_user['approve_key']; 
				$user_email = $user_details['email'];					

				$email_content = "שלום ";
				$email_content.= $email_user['firstname']." ".$email_user['lastname'].".<br/>";
				$contract_file_email = null;
				if($contract['pdf_path'] == ""){
					$email_title = "בקשה לעדכון חוזה: ";
					$enter_url = $general_page_url."&enter=$approve_key&contract_apply=$contract_apply_id";									
					$email_content.= "נמצא חוזה על שמך, הממתין לעדכון וחתימה: ";
					$email_content.= $contract['title'];
					$email_content.= " שנחתם בין: ";
					$email_content.= implode(",",$users_title_arr);	
					$email_content.= "<br/>";
					$email_content.= " ונוצר ב ".$contract['sign_time'];
					$email_content.= "<br/>";
					$email_content.= "לצפייה ועדכון פרטי החוזה, ";
					$email_content.= " לחץ על הלינק הבא:  <br/>";
					$email_content.= "<a href = '$enter_url'>לחץ כאן לצפייה ועדכון החוזה</a><br/>";
				}
				else{
					$email_title = "בקשה לאישור אמינות חוזה: ";
					$contract_file_email = array($contract['pdf_path']);
					$email_content.= "מצורף קובץ החוזה - ";
					$email_content.= $contract['title'];
					$email_content.= " שנחתם בין: ";
					$email_content.= implode(",",$users_title_arr);	
					$email_content.= "<br/>";
					$email_content.= "הסכם זה ממתין שתאשר את אמינותו.";
					$email_content.= "<br/>";
					if($approve_key && $approve_key != '1'){
						$approve_url = $general_page_url."&approve=$approve_key&contract_apply=$contract_apply_id";
						$email_content.= "<a href = '$approve_url'>לחץ כאן על מנת לאשר את אמינות החוזה</a><br/>";
					}
				}
				$email_title.= $contract['title'];
				$email_title.= " בין: ";
				$email_title.= implode(",",$users_title_arr);
				
				$email_content.= "בברכה,<br/>";
				$email_content.= stripslashes($user_details['name']);
				$email_content.= "<br>";
				$email_content.= $host;
				$header_send_to_Client= $email_title;
				$content_send_to_Client = "<html dir=rtl><head><title></title>
											<style type='text/css'>
												.textt{font-family: arial; font-size:12px; color: #000000}
												.text_link{font-family: arial; font-size:12px; color: navy}
											</style></head><body><p class='textt' dir=rtl align=right>". $email_content."</p></body>
											</html>";
				$ClientMail = $email_find;		
				agweeContracts_handler::send_emails($this->callbeck_class, $header_send_to_Client, $content_send_to_Client, $ClientMail, $contract_file_email);
				
			}
			$contract_ids_in = implode(",",$contract_ids);
			//$sql = "UPDATE ag_contract_apply SET last_alert = NOW() WHERE id IN($contract_ids_in)";
			//$res = $this->wpcon->get_results($sql,ARRAY_A);
		}	
	}
}