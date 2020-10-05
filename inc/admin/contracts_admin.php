<?php

defined( 'ABSPATH' ) or die( 'Hey, no entry here. ok?!' );
class AgweeContracts_admin{
	private $wpcon;
	private $callbeck_class;
	public function init($callbeck_class){
		global $wpdb;
		$this->wpcon = $wpdb;
		$this->callbeck_class = $callbeck_class;
		//todo: check permissions!!!
		$this->callbeck_class->include_required_file('inc/agweeContracts_handler_class.php');
		if(isset($_GET['editor'])){
			if(!isset($_GET['contract_id']) && $_GET['editor'] != "list"){
				//$url = admin_url("admin.php?page=agweeContracts_plugin&editor=list");
				//$settings_link = '<a href="'.$url.'">Back to contract list</a>';
				$this->redirect_to_page_js("?page=agweeContracts_plugin&editor=list");
				return;
			}
			if($_GET['editor'] != "list"){
				$this->editor_header();
			}
			$func_name = $_GET['editor']."_editor";
			$this->$func_name();
		}
		else{
			$this->find();
		}
	}	

	private function redirect_to_page_js($url_to){
		?>
		<script type="text/javascript">
			window.location.href = "<?php echo $url_to; ?>";
		</script>
		<?php
	}

	public function find(){

		?>
		
		<h3><?php echo __("Submitted contracts list", 'agwee-contracts-text'); ?></h3>
		<h3><?php echo __("Search", 'agwee-contracts-text'); ?></h3>
		<form action="" method="POST">
			<br/>
			<table>
				<tr>
					<td>
						<?php echo __("Email", 'agwee-contracts-text'); ?> <br/><input type="text" name="for_email" value="<?php echo $this->getRequestVal('for_email'); ?>"/> 
					</td>

					<td>
						<?php echo __("First name", 'agwee-contracts-text'); ?> <br/><input type="text" name="for_firstname"  value="<?php echo $this->getRequestVal('for_firstname'); ?>"/> 
					</td>
					
					<td>
						<?php echo __("Last name", 'agwee-contracts-text'); ?> <br/><input type="text" name="for_lastname"  value="<?php echo $this->getRequestVal('for_lastname'); ?>"/> 
					</td>
				</tr>
				<tr>
					<td>
						<?php echo __("Contract title", 'agwee-contracts-text'); ?> <br/><input type="text" name="title" value="<?php echo $this->getRequestVal('title'); ?>"/> 
					</td>			
					<td>
						<?php echo __("From date", 'agwee-contracts-text'); ?> <br/><input type="text" name="date_from" value="<?php echo $this->getRequestVal('date_from'); ?>"/> 
					</td>
					
					<td>
						<?php echo __("To date", 'agwee-contracts-text'); ?> <br/><input type="text" name="date_to" value="<?php echo $this->getRequestVal('date_to'); ?>"/> 
					</td>
				</tr>
				<tr>				
					<td colspan='2'>	
						<?php 
							$order_by_aproved_checked = "";
							if(isset($_REQUEST['order_by_approved'])){
								$order_by_aproved_checked = "checked";
							}
						?>
				
					
					
							
					
						<input type="checkbox" name="order_by_approved" value="1" <?php echo $order_by_aproved_checked; ?> /><?php echo __("Begin list from none approved", 'agwee-contracts-text'); ?> 
					</td>
				</tr>
				<tr>
					
					<td>
						<input type="submit" value="<?php echo __("Search", 'agwee-contracts-text'); ?>" />
					</td>
				</tr>
			</table>
		</form>
		<?php

		$send_contracts_msg = "";
		
		if(isset($_REQUEST['send_contracts'])){
			
			if(!isset($_REQUEST['send_contract']) || empty($_REQUEST['send_contract'])){
				
				$send_contracts_msg = __("No contracts was selected for the task", 'agwee-contracts-text');
			}
			else{
				if($_REQUEST['sent_to'] == "cancel"){
					foreach($_REQUEST['send_contract'] as $contract_id){
						$sql = "UPDATE ag_contract_apply SET canceled= '1' WHERE AND id = $contract_id";
						$res = $this->wpcon->get_results($sql,ARRAY_A);				
					}
					$send_contracts_msg = __("The contracts got cancelled", 'agwee-contracts-text');
				}
				else{
					$user_details = agweeContracts_handler::get_user_details();
					$contracts = array();
					
					foreach($_REQUEST['send_contract'] as $contract_id){
						$sql = "SELECT * FROM ag_contract_apply WHERE 1 AND id = $contract_id";
						$contract = $this->wpcon->get_results($sql,ARRAY_A);
						if($contract['emails'] != ""){
							$contract['emails_send_to'] = array();
							if($_REQUEST['sent_to'] == "onlyme"){
								$contract['emails_send_to'][] = $user_details['email'];
							}
							else{
								$emails_arr = explode(",",$contract['emails']);
								foreach($emails_arr as $send_to){
									$contract['emails_send_to'][] = $send_to;
								}
							}
							$contracts[] = $contract;
						}
					}
					
					if(empty($contracts)){
						$send_contracts_msg = __("No contracts was selected to send", 'agwee-contracts-text');
					}
					else{
						

						$contracts = array();
						//send notification to site owner about contract approval
						$user_details = agweeContracts_handler::get_user_details();
						$user_email = $user_details['email'];
						$host = $user_details['domain'];
						foreach($_REQUEST['send_contract'] as $contract_apply_id){
							$sql = "SELECT * FROM ag_contract_apply_users WHERE 1 AND contract_apply_id = $contract_apply_id";
							$users_data = $this->wpcon->get_results($sql,ARRAY_A);
							$emails_approved_arr = explode(";",$contract['emails_approved']);
							$emails_approved = array();
							foreach($emails_approved_arr as $email_approved_str){
								$email_approved_arr = explode(":",$email_approved_str);
								$emails_approved[$email_approved_arr[0]] = $email_approved_arr[1];
							}
							$contract_users = array();						
							foreach($users_data as $user_data){
								$approve_key = false;
								if(isset($emails_approved[$user_data['email']])){
									$approve_key = $emails_approved[$user_data['email']];
								}
								$user_data['approve_key'] = $approve_key;
								$contract_users[$user_data['contract_user_id']] = $user_data;
							}
							
							$sql = "SELECT * FROM ag_contract_apply WHERE 1 AND id = $contract_apply_id";
							$contract = $this->wpcon->get_results($sql,ARRAY_A);	
							$contract['users'] = $contract_users;
							$general_page_url = "";
							$landing_id = "";
							if($contract['landing_id'] != ""){
								$general_page_url =  add_query_arg( 'apply', '1', get_permalink($contract['landing_id']));
								$landing_id = $contract['landing_id'];
							}						
							$users_title_arr = array();
							foreach($contract_users as $contract_user){
								$users_title_arr[] = $contract_user['firstname']." ".$contract_user['lastname'];
							}
							if($_REQUEST['sent_to'] == "all"){
								foreach($contract_users as $email_user){
									$email_find = $email_user['email']; 
									$approve_key = $email_user['approve_key']; 
									$email_title = "".__("Resend contract", 'agwee-contracts-text').": ";
									$email_title.= $contract['title'];
									$email_title.= " ".__("Between", 'agwee-contracts-text').": ";
									$email_title.= implode(",",$users_title_arr);
									$email_content = __("Hello", 'agwee-contracts-text')." ";
									$email_content.= $email_user['firstname']." ".$email_user['lastname'].".<br/>";
									$contract_file_email = null;
									
									if($contract['pdf_path'] == ""){						
										$enter_url = $general_page_url."&enter=$approve_key&contract_apply=$contract_apply_id";									
										$email_content.= __("To watch and update the contract", 'agwee-contracts-text').": ";
										$email_content.= $contract['title'];
										$email_content.= " ".__("Signed between", 'agwee-contracts-text').": ";
										$email_content.= implode(",",$users_title_arr);	
										$email_content.= "<br/>";
										$email_content.= " ".__("Click the next link", 'agwee-contracts-text').":  <br/>";
										$email_content.= "<a href = '$enter_url'>".__("לחץ כאן לצפייה ועדכון החוזה", 'agwee-contracts-text')."</a><br/>";
									}
									else{
										$contract_file_email = array($contract['pdf_path']);
										$email_content.= __("Attached, the pdf of the contract", 'agwee-contracts-text')." - ";
										$email_content.= $contract['title'];
										$email_content.= " ".__("Signed between", 'agwee-contracts-text').": ";
										$email_content.= implode(",",$users_title_arr);	
										$email_content.= "<br/>";
										if($approve_key && $approve_key != '1'){
											$approve_url = $general_page_url."&approve=$approve_key&contract_apply=$contract_apply_id";
											$email_content.= "<a href = '$approve_url'>".__("Click here to approve the reliability of the contract", 'agwee-contracts-text')."</a><br/>";
										}
									}
									$email_content.= __("Greetings", 'agwee-contracts-text').",<br/>";
									$email_content.= $user_details['name'];
									$email_content.= "<br>";
									$email_content.=  $host;
									$header_send_to_Client= $email_title;
									$content_send_to_Client = "<html dir=rtl><head><title></title>
																<style type='text/css'>
																	.textt{font-family: arial; font-size:12px; color: #000000}
																	.text_link{font-family: arial; font-size:12px; color: navy}
																</style></head><body><p class='textt' dir=rtl align=right>". $email_content."</p></body>
																</html>";
									$ClientMail = $email_find;	
									agweeContracts_handler::send_emails($this->callbeck_class, $header_send_to_Client,  $content_send_to_Client, $ClientMail,$contract_file_email);
									
								}
							}
							if($contract['pdf_path'] != ""){
								$email_find = $user_details['email']; 
								$email_title = __("Resend contract to site admin", 'agwee-contracts-text').": ";
								$email_title.= $contract['title'];
								$email_title.= " ".__("Between", 'agwee-contracts-text').": ";
								$email_title.= implode(",",$users_title_arr);
								$email_content = __("Hello", 'agwee-contracts-text')." ";
								$email_content.=  $user_details['name'].".<br/>";

								$contract_file_email = array($contract['pdf_path']);
								$email_content.= __("Attached, the pdf of the contract", 'agwee-contracts-text')." - ";
								$email_content.= $contract['title'];
								$email_content.= " ".__("Signed between", 'agwee-contracts-text').": ";
								$email_content.= implode(",",$users_title_arr);	
								$email_content.= "<br/>";
								
								$email_content.= __("Greetings", 'agwee-contracts-text').",<br/>";
								$email_content.= $user_details['name'];
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
							$send_contracts_msg = __("The selected contracts Sent successfully", 'agwee-contracts-text');		
							$contracts[] = $contract;
						}
						if(empty($contracts)){
							$send_contracts_msg = __("No contracts was selected to send", 'agwee-contracts-text');
						}
					}
				}
			}
		}	
		$email_find = "";
		if(isset($_REQUEST['for_email'])){
			$email_find = trim($_REQUEST['for_email']);
		}
		$regular_contracts_found = array();
		$canceled_contracts_found = array();
		$emails_sql = "";
		$firstname_sql = "";
		$lastname_sql = "";
		if($email_find != ""){
			$emails_sql = " AND email LIKE(\"%".$email_find."%\") ";
		}
		$firstname_find = "";
		if(isset($_REQUEST['for_firstname'])){
			$firstname_find = trim($_REQUEST['for_firstname']);
		}
		if($firstname_find != ""){
			$firstname_sql = " AND firstname LIKE(\"%".$firstname_find."%\") ";
		}
		$lastname_find = "";
		if(isset($_REQUEST['for_lastname'])){
			$lastname_find = trim($_REQUEST['for_lastname']);
		}
		if($lastname_find != ""){
			$lastname_sql = " AND lastname LIKE(\"%".$lastname_find."%\") ";
		}
		$user_find_where_sql = $emails_sql.$firstname_sql.$lastname_sql;
		$id_in_sql = "";
		if($user_find_where_sql != ""){
			$apply_users_sql = "SELECT distinct(contract_apply_id) FROM ag_contract_apply_users WHERE 1 ".$user_find_where_sql;
			$apply_users_res = $this->wpcon->get_results($apply_users_sql,ARRAY_A);
			$id_in_arr = array();
			foreach($apply_users_res as $apply_users_data){
				$id_in_arr[] = $apply_users_data['contract_apply_id'];
			}
			$id_in_str = implode(",",$id_in_arr);
			$id_in_sql = " AND id IN(".$id_in_str.") ";
		}
		
		$date_from_sql = "";
		if(isset($_REQUEST['date_from']) && $_REQUEST['date_from']!=""){
			$date_from_str = trim($_REQUEST['date_from']);
			$date_from_arr = explode("-",$date_from_str);
			$date_from_str = $date_from_arr[2]."-".$date_from_arr[1]."-".$date_from_arr[0];
			$date_from_sql = " AND sign_time > '$date_from_str' ";
		}
		$date_to_sql = "";
		if(isset($_REQUEST['date_to']) && $_REQUEST['date_to']!=""){
			$date_to_str = trim($_REQUEST['date_to']);
			$date_to_arr = explode("-",$date_to_str);
			$date_to_str = $date_to_arr[2]."-".$date_to_arr[1]."-".$date_to_arr[0];
			$date_to_sql = " AND sign_time <= '$date_to_str' ";
		}
		$title_find = "";
		if(isset($_REQUEST['title'])){
			$title_find = trim($_REQUEST['title']);
		}
		$title_sql = "";
		if($title_find != ""){
			$title_sql = " AND title LIKE(\"%".$title_find."%\") ";
		}
		$order_by_fully_approved = "";
		if(isset($_GET['order_by_approved'])){
			$order_by_fully_approved = "fully_approved , ";
		}
		$find_sql = "SELECT * FROM ag_contract_apply WHERE 1 $id_in_sql $date_from_sql $date_to_sql $title_sql ORDER BY $order_by_fully_approved id desc";
		//echo $find_sql;
		$find_res = $this->wpcon->get_results($find_sql,ARRAY_A);
		foreach($find_res as $contract){
			$contract['status_str'] = __("Update details", 'agwee-contracts-text');
			if($contract['pdf_path'] != ""){
				$contract['status_str'] = __("A pdf created", 'agwee-contracts-text');
			}
			$users_sql = "SELECT * FROM ag_contract_apply_users WHERE contract_apply_id = ".$contract['id'];
			$users_res = $this->wpcon->get_results($users_sql,ARRAY_A);
			$contract_users = array();
			$users_by_mails = array();
			foreach($users_res as $user_data){
				$contract_users[$user_data['contract_user_id']] = $user_data;
				$users_by_mails[$user_data['email']] = $user_data;
			}
			$usernames_arr = array();
			foreach($contract_users as $contract_user){
				$usernames_arr[] = $contract_user['firstname']." ".$contract_user['lastname'];
			}
			$usernames_str = implode("<br/>",$usernames_arr);
			$contract['usernames'] = $usernames_str;
			$datetime_arr = explode(" ",$contract['sign_time']);
			$date_str_1 = $datetime_arr[0];
			$date_arr = explode("-",$date_str_1);
			$contract['date_str'] = $date_arr[2]."-".$date_arr[1]."-".$date_arr[0];
			if($contract['title'] == ""){
				$contract['title'] = __("No title", 'agwee-contracts-text');
			}
			else{
				$contract['title'] = $contract['title'];
			}
			$contract['emails_approved_list_str'] = "";
			$contract['emails_approved_wait_list_str'] = "";
			$contract['emails_approved_list_arr'] = array();
			$contract['emails_approved_wait_list_arr'] = array();
			if($contract['emails_approved'] != ""){			
				$emails_approved_arr = explode(";",$contract['emails_approved']);
				foreach($emails_approved_arr as $email_approve_str){
					$has_unapproved_mail = false;
					$email_approve_arr = explode(":",$email_approve_str);
					
					if(isset($email_approve_arr[1]) && $email_approve_arr[1] != '1' && $email_approve_arr[0]!=""){
						$has_unapproved_mail = true;
						$contract['emails_approved_wait_list_str'].=$email_approve_arr[0]."<br/>";
						$contract['emails_approved_wait_list_arr'][$email_approve_arr[0]] = $users_by_mails[$email_approve_arr[0]];
					}
					else{
						$contract['emails_approved_list_str'].=$email_approve_arr[0]."<br/>";
						$contract['emails_approved_list_arr'][$email_approve_arr[0]] = $users_by_mails[$email_approve_arr[0]];
					}
				}
			}
			$utf8_vals = array('emails','usernames','emails_approved_list_str','emails_approved_wait_list_str');
			foreach($utf8_vals as $utf8_val){
				$contract[$utf8_val] = $contract[$utf8_val];
			}
			if($contract['canceled'] == '1'){
				$canceled_contracts_found[] = $contract;
			}
			else{
				$regular_contracts_found[] = $contract;
			}
		}
		$contracts_found = array();
		foreach($regular_contracts_found as $contract){
			$contracts_found[] = $contract;
		}
		foreach($canceled_contracts_found as $contract){
			$contracts_found[] = $contract;
		}	
		?>
		
		<?php if(empty($contracts_found)): ?>
			<p><b style="color:red;"><?php echo __("No contracts found", 'agwee-contracts-text'); ?></b></p>
		<?php else: ?>
			<?php if($send_contracts_msg != ""): ?>
				<p><b style="color:green;"><?php echo $send_contracts_msg; ?></b></p>
			<?php endif; ?>
			<b><?php echo sprintf( __("%s contracts found", 'agwee-contracts-text'),count($contracts_found)); ?>: </b>
			<p><?php echo __("Please select the contracts to send to your mailbox", 'agwee-contracts-text'); ?></p>
			<form action="?m=work_contracts" method="GET">

				<br/>
				<input type="hidden" name="for_email" value="<?php echo $email_find; ?>" />
				<?php if(isset($_GET['order_by_approved'])): ?>
					<input type="hidden" name="order_by_approved" value="1" />
				<?php endif; ?>
				<input type="hidden" name="send_contracts" value="1" />
				<table border="1" cellspacing="0" cellpadding="12" class="maintext">
					<tr>
						<th><?php echo __("Select", 'agwee-contracts-text'); ?></th>
						<th><?php echo __("Date", 'agwee-contracts-text'); ?></th>
						<th><?php echo __("IP", 'agwee-contracts-text'); ?></th>
						<th><?php echo __("Emails", 'agwee-contracts-text'); ?></th>
						<th><?php echo __("Names", 'agwee-contracts-text'); ?></th>
						<th><?php echo __("Progress", 'agwee-contracts-text'); ?></th>
					</tr>
					
					<?php foreach($contracts_found as $contract): ?>
					<tr>
						<th colspan = 20><?php echo $contract['title']; ?></th>
					</tr>
					<tr>
						<td>
						<?php if($contract['canceled'] == '1'): ?>
							<br/><b style="color:red;"><?php echo __("cancelled", 'agwee-contracts-text'); ?></b>
						<?php else: ?>
							<input type="checkbox" name="send_contract[]" value="<?php echo $contract['id']; ?>" />
						<?php endif; ?>
						</td>
						
						<td style="white-space: nowrap;"><?php echo $contract['date_str']?></td>
						<td><?php echo $contract['ip']; ?></td>
						<td>
							<table border="1" cellspacing="0" >
								<tr>
									<th><?php echo __("Email", 'agwee-contracts-text'); ?></th>
									<th><?php echo __("Approve IP or Decline reason", 'agwee-contracts-text'); ?></th>
								</tr>
								<tr>
									<th colspan="2"><?php echo __("Approved", 'agwee-contracts-text'); ?></th>
								</tr>
								
								<?php foreach($contract['emails_approved_list_arr'] as $email_user): ?>
									<tr>
										<td style="text-align:left;direction:ltr;"><?php echo $email_user['email']; ?></td>
										<td><?php echo $email_user['approve_ip']; ?></td>
									</tr>
								<?php endforeach; ?>
								
								<tr>
									<th colspan="2"><?php echo __("Waiting for approve", 'agwee-contracts-text'); ?></th>
								</tr>
								<?php foreach($contract['emails_approved_wait_list_arr'] as $email_user): ?>
									<tr>
										<td style="text-align:left;direction:ltr;"><?php echo $email_user['email']; ?></td>
										<td><?php echo $email_user['approve_note']; ?></td>
									</tr>
								<?php endforeach; ?>
							</table>
						</td>
						<td style="min-width:100px;"><?php echo $contract['usernames']; ?></td>
						<td><?php echo $contract['status_str']; ?></td>
					</tr>
						
					<?php endforeach; ?>
				</table>
				<br/>
				<b><?php echo __("Send selected", 'agwee-contracts-text'); ?></b>: 
				<select name="sent_to">
					<option value="onlyme"><?php echo __("Send only to me", 'agwee-contracts-text'); ?></option>
					<option value="all"><?php echo __("Send to all participants", 'agwee-contracts-text'); ?></option>
					<option value="cancel"><?php echo __("Cancel the contract", 'agwee-contracts-text'); ?></option>
				</select>
				<input type="hidden" name="main" value="work_contracts" />
				<input type="hidden" name="for_name" value="<?php echo $_GET['for_name']; ?>" />
				<input type="hidden" name="for_email" value="<?php echo $_GET['for_email']; ?>" />
				<input type="submit" value="שלח"/>
				<br/>
				<b style="color:red"><?php echo __("Only done cotracts with PDF will be sent to admin", 'agwee-contracts-text'); ?></b>
			</form>			
		<?php endif; ?>
		
		<?php
	}

	public function getRequestVal($att){
		return isset($_REQUEST[$att])?$_REQUEST[$att]:"";
	}

	public function editor_header(){

		$site_url = get_site_url();
		$contract_id = $_GET['contract_id']; 

		$contract_data = agweeContracts_handler::get_contract_data($_GET['contract_id']);
		
		
		?>
			<div class="ag_editor_beck_button">
				<a class="editor_list_menu_item" href="?page=agweeContracts_plugin&editor=list"><< <?php echo __("Back to Contract list", 'agwee-contracts-text'); ?></a>
			</div>
			<a class="contract-demo-link" style="display: block;color: #1a1aa0;float: left;margin: 10px;font-size: 21px;" target="_BLANK" href="<?php echo $this->callbeck_class->get_contract_demo_url($contract_id); ?>"><?php echo __("Contract PDF", 'agwee-contracts-text'); ?></a>
			<h2 class="ag_editor_h2"><?php echo __("Edit Contract", 'agwee-contracts-text'); ?>: <?php echo $contract_data['title']; ?></h2>
			<table border="0" cellpadding="5">
				<tr class="ag_editor_menu_items">
					
					<th><a class="editor_general_menu_item" href="?page=agweeContracts_plugin&editor=general&contract_id=<?php echo $contract_id; ?>"><?php echo __("Main settings", 'agwee-contracts-text'); ?></a></th>
					<th><a class="editor_fields_menu_item" href="?page=agweeContracts_plugin&editor=fields&contract_id=<?php echo $contract_id; ?>"><?php echo __("Contract fields", 'agwee-contracts-text'); ?></a></th>
					<th><a class="editor_content_menu_item" href="?page=agweeContracts_plugin&editor=content&contract_id=<?php echo $contract_id; ?>"><?php echo __("Content", 'agwee-contracts-text'); ?></a></th>
					<th><a onclick= "return confirm('<?php echo __("Do you want to delete this contract?", 'agwee-contracts-text'); ?>')" class="editor_del_menu_item" style="color:red;" href="?page=agweeContracts_plugin&editor=general&delete_contract=1&contract_id=<?php echo $contract_id; ?>"><?php echo __("Delete", 'agwee-contracts-text'); ?></a></th>
					<th colspan="3"></th>
				</tr>
				<tr class="ag_editor_submenu_items">
					<th colspan="5" style="text-align:right;"><?php echo __("Shortcode to add the contract form to content", 'agwee-contracts-text'); ?>: 
						<input type="text" editonly="1" value='[ag_contract_form id="<?php echo $contract_id; ?>"]' />
					</th>
					
					
				</tr>			
				
			</table>
			
			<style type="text/css">
				.ag_editor_menu_items th{padding:10px 2px;}
				.ag_editor_menu_items a{
background: #a4b3ff;
    font-size: 18px;
    margin: 0px;
    padding: 9px;
    text-decoration: none;
    color: #14483e;
    border-radius: 4px;
    border: 2px solid;					
				}
				.ag_editor_menu_items a:hover{background:#14483e;color:#a4b3ff;}
				.ag_editor_menu_items a.editor_<?php echo $_GET['editor']; ?>_menu_item{background:#eaeeff;}
				.ag_editor_beck_button a{
					padding: 10px 0px 0px;
					font-size: 19px;
					font-weight: bold;
					display:block;
					margin-top:15px;
				}
				.ag_editor_h2{font-size: 22px;}
				.ag_editor_submenu_items th{padding-top:15px;}
				.ag_editor_submenu_items a{font-size:18px;}
			</style>
		<?php
	}
	public function list_editor(){
		
		if(isset($_REQUEST['create_new_contract'])){
			$contract_title = $_REQUEST['contract_title'];
			$sql = "INSERT INTO ag_contract_design (title) VALUES('".$contract_title."')";
			$res = $this->wpcon->get_results($sql,ARRAY_A);
			$insert_id = $this->wpcon->insert_id;
			$this->redirect_to_page_js("?page=agweeContracts_plugin&editor=general&contract_id=".$insert_id);
			return;
		}
		$sql = "SELECT * FROM ag_contract_design WHERE 1";
		$contract_list = $this->wpcon->get_results($sql,ARRAY_A);
		?>
		<h2><?php echo __("Contracts Management", 'agwee-contracts-text'); ?></h2>
		<div>
		<form action="" method="POST">
			<h2><?php echo __("Create a new Contract", 'agwee-contracts-text'); ?>:</h2>
			<b><?php echo __("The Contract title", 'agwee-contracts-text'); ?>: </b><input type="text" name="contract_title"/> <input type="submit" name="create_new_contract" value="<?php echo __("Save", 'agwee-contracts-text'); ?>"/>
		</form>
		</div>
		<h2><?php echo __("Contracts list", 'agwee-contracts-text'); ?>:</h2>
		<table border = '0'>
			
				<?php foreach($contract_list as $contract): ?>
					<tr>
						<td>
							<a href="?page=agweeContracts_plugin&editor=general&contract_id=<?php echo $contract['id']; ?>"><?php echo $contract['title']; ?></a>
						</td>
					</tr>
				<?php endforeach; ?>
			
		</table>
		
		
		
		
		<?php
	}
	public function general_editor(){
		
		$time = time();
		$contract_id = $_GET['contract_id'];

		$asset_img_path = WP_CONTENT_DIR."/plugins/agwee/assets/img/uploads";

		$asset_img_httppath = WP_CONTENT_URL."/plugins/agwee/assets/img/uploads";

		if(isset($_GET['delete_contract'])){
			
			$img_sql = "SELECT * FROM ag_contract_design WHERE 1 AND id = '".$contract_id."'";
			$img_data_arr = $this->wpcon->get_results($img_sql,ARRAY_A);
			$img_data = $img_data_arr[0];
			if($img_data['header_img'] != ""){
				unlink($asset_img_path."/".$img_data['header_img']);
			}
			if($img_data['footer_img'] != ""){
				unlink($asset_img_path."/".$img_data['footer_img']);
			}
			$del_sql = "DELETE FROM ag_contract_design WHERE 1 AND id='".$contract_id."'";
			$del_res = $this->wpcon->get_results($del_sql,ARRAY_A);
			$del_sql_2 = "DELETE FROM ag_contract_fields_settings WHERE contract_id='".$contract_id."'"; 
			$del_res_2 = $this->wpcon->get_results($del_sql_2,ARRAY_A);
			$this->redirect_to_page_js("?page=agweeContracts_plugin&editor=list");
			return;
		}
		
		if(isset($_REQUEST['edit_ag_contract_design'])){
			echo "TODO: take care of images upload and unlinking images for deleted contracts";
			$contract_id = $_REQUEST['edit_ag_contract_design'];
			$update_arr = array("title","identifier","head_px","foot_px");
			$update_set_sql = "";
			$update_set_i = 0;
			foreach($update_arr as $update_key){
				if($update_set_i != 0){
					$update_set_sql.=",";
				}
				$update_set_sql .= $update_key ." = '".$_REQUEST[$update_key]."'";
				$update_set_i++;
			}
			$sql = "UPDATE ag_contract_design SET $update_set_sql WHERE 1 AND id = ".$contract_id."";
			$res = $this->wpcon->get_results($sql,ARRAY_A);
			//check if files being uploaded
			if($_FILES)
			{
				

				if(!is_dir($asset_img_path)){
					$mask=umask(0);
					mkdir($asset_img_path, 0777);
					umask($mask);
				}

				$field_name = array("header_img"=>"head","footer_img"=>"foot");
				foreach($field_name as $temp_name=>$img_name)
				{
					//$temp_name = $field_name[$temp];
					if ( $_FILES[$temp_name]['name'] != "" ){
						$temp_file_name = $_FILES[$temp_name]['name'];
						
						$file_name_arr = explode(".",$temp_file_name);
						$ext_str = $file_name_arr[(count($file_name_arr) - 1)];
						$ext_str = strtolower($ext_str);
						$file_error = false;
						if($ext_str!="png" && $ext_str!="jpg" && $ext_str!="gif"){
							$file_error = __("Unable to upload image(supported extentions: gif,jpg,png)", 'agwee-contracts-text');
						}
						elseif($_FILES[$temp_name]["size"] > 500000){
							$file_error = __("The image you try to upload is too large", 'agwee-contracts-text');
						}
						else{
							
							$sql = "SELECT $temp_name FROM ag_contract_design WHERE id = $contract_id";
							$curent_image_data = $this->wpcon->get_results($sql,ARRAY_A);
							$curent_image = $curent_image_data[0][$temp_name];
							if($curent_image!=""){
								unlink($asset_img_path."/".$curent_image);
							}
							

							$upload_image_name = $img_name."_".$contract_id.".$ext_str";
							$up = move_uploaded_file($_FILES[$temp_name]['tmp_name'],$asset_img_path."/".$upload_image_name);
							if($up){
								$sql = "UPDATE ag_contract_design SET $temp_name = '$upload_image_name' WHERE id='$contract_id'";
								$res = $this->wpcon->get_results($sql,ARRAY_A);
							}
						}
					}
				}
			}
			$this->redirect_to_page_js("?page=agweeContracts_plugin&editor=general&contract_id=".$_REQUEST['contract_id']);
			return;
		}
		$contract = agweeContracts_handler::get_contract_data($_GET['contract_id']);
		if($contract['head_px'] == ""){
			$contract['head_px'] = 45;
		}
		if($contract['foot_px'] == ""){
			$contract['foot_px'] = 40;
		}	
		?>
			<h2><?php echo __("Main settings", 'agwee-contracts-text'); ?></h2>
			<form action="" method="POST" enctype="multipart/form-data">
				<input type="hidden" name="edit_ag_contract_design" value="<?php echo $contract['id']; ?>" /> 
				<div style="padding:10px; border:1px solid blue; margin-top:10px;">
					<b><?php echo __("Title", 'agwee-contracts-text'); ?></b><br/>
					<input type="text" name="title" value="<?php echo $contract['title']; ?>" /><br/>
					<br/>
					<b><?php echo __("Identifier(For system use. can leave this empty)", 'agwee-contracts-text'); ?></b><br/>
					<input type="text" name="identifier" value="<?php echo $contract['identifier']; ?>" /><br/>
					<h3><?php echo __("Text margins", 'agwee-contracts-text'); ?></h3>
					<b><?php echo __("Margin top", 'agwee-contracts-text'); ?></b><br/>
					<input type="text" name="head_px" value="<?php echo $contract['head_px']; ?>" /><br/>
					<b><?php echo __("Margin bottom", 'agwee-contracts-text'); ?></b><br/>
					<input type="text" name="foot_px" value="<?php echo $contract['foot_px']; ?>" /><br/>
				</div>			
				<div style="padding:10px; border:1px solid blue; margin-top:10px;">
					<b><?php echo __("Header image", 'agwee-contracts-text'); ?></b><br/>
					<?php if($contract['header_img'] != ""): ?>
						<div>
							<img style="max-width:100%;" src ="<?php echo $asset_img_httppath."/".$contract['header_img']; ?>?t=<?php echo $time; ?>"/>
							<br/> 
						</div>
					<?php endif; ?>			
					
					<input type="file" name="header_img" /><br/>
					<div style="clear:both;"></div>
				</div>
				<div style="padding:10px; border:1px solid blue; margin-top:10px;">
					<b><?php echo __("Footer image", 'agwee-contracts-text'); ?></b><br/>
					<?php if($contract['footer_img'] != ""): ?>
						<div>
							<img style="max-width:100%;" src ="<?php echo $asset_img_httppath."/".$contract['footer_img']; ?>?t=<?php echo $time; ?>"/>
							<br/>
						</div>
					<?php endif; ?>	
					
					<input type="file" name="footer_img" /><br/>
					<div style="clear:both;"></div>
				</div>

				<?php
				
					$save_button_style = "font-size:30px;font-weight:bold;display:block; height:87px;width:200px;border-radius:50px;margin:auto;margin-top:20px;background:#f9f9f9;color:#8e7d7d;cursor:pointer;";
					echo "<input type='submit' value='".__("Save", 'agwee-contracts-text')."' class='submit_style' style = '".$save_button_style."'>";
				?>
			</form>
		<?php
	}
	public function fields_editor(){
		if(isset($_POST['fields'])){
			$sql = "DELETE FROM ag_contract_fields_settings WHERE contract_id = '".$_REQUEST['contract_id']."'";
			$res = $this->wpcon->get_results($sql,ARRAY_A);
			foreach($_POST['fields'] as $field_setting_id=>$field_value){
				$field_value = str_replace("'","&#39;",$field_value);
				$field_value = str_replace('"',"&quot;",$field_value);
				$sql = "INSERT INTO ag_contract_fields_settings(contract_id,field_key,field_val) VALUES('".$_REQUEST['contract_id']."','".$field_setting_id."','".$field_value."')";
				$res = $this->wpcon->get_results($sql,ARRAY_A);
			}
			$this->redirect_to_page_js("?page=agweeContracts_plugin&editor=fields&contract_id=".$_REQUEST['contract_id']);
		}	
		
		$contract_id = $_GET['contract_id'];
		$contract_data = agweeContracts_handler::get_contract_data($contract_id);
		$form_fields_defult = agweeContracts_handler::get_defult_fields_arr($contract_id);
		$form_fields = agweeContracts_handler::create_fields_arr_from_contract($contract_id);
		?>
		<style type="text/css">
			.ag_contract_fields_editor{font-size:18px;}
			.ag_contract_fields_editor tr{line-height:30px;}
			.ag_contract_fields_editor .fieldsdoor{}
			.ag_contract_fields_editor .add_field_button{font-size: 25px; float: left; color: green;  margin: 10px;}
			.add_user_button{font-size: 25px; float: right; color: green;  margin: 10px;}
		</style>
		<h2><?php echo __("The Contract fields", 'agwee-contracts-text'); ?></h2>
		<form name='edit_content_form' method='post' action=''>
		<input type='hidden' name='contract_id' value='<?php echo $contract_id; ?>' />
		<div class="ag_contract_fields_editor" id='' style= 'text-align:right;'>
		<hr/>
			<table id='' border='1' style= 'border-collapse:collapse;text-align:right;min-width:650px;' cellpadding='5'>

				<tr class='tr_general tr_general_main' style='background:#ffffdf;'>
					<th colspan= '2'>
						<?php echo __("Main details", 'agwee-contracts-text'); ?>:
						<br/>
						<a class='fieldsdoor' style='color:blue;font-weight:normal;' href='javascript://' onclick='return showhide_general(this);' rel='open'><span class='showstr'><?php echo __("Show", 'agwee-contracts-text'); ?></span><span class='hidestr' style='display:none'><?php echo __("Hide", 'agwee-contracts-text'); ?></span> <?php echo __("Main fields", 'agwee-contracts-text'); ?></a>
					</th>
					<th colspan= '20'> <?php echo __("Tag", 'agwee-contracts-text'); ?> <br/><input type='text' name='fields[general_t_title]' value='<?php echo $form_fields['general']['title']; ?>' /></th>
					
				</tr>
				<?php $last_key= 0; ?>
				<tr class='tr_general'>
					<th><?php echo __("Location", 'agwee-contracts-text'); ?></th>
					<th><?php echo __("Field Title", 'agwee-contracts-text'); ?></th>
					<th><?php echo __("Default value", 'agwee-contracts-text'); ?></th>
					<th><?php echo __("Is required", 'agwee-contracts-text'); ?></th>
					<th><?php echo __("Field type", 'agwee-contracts-text'); ?></th>
					<th><?php echo __("Allow edit", 'agwee-contracts-text'); ?></th>
					<th></th>
				</tr>
				
				<?php foreach($form_fields['general']['fields'] as $key=>$fields_group): ?>
					<?php 
					if(is_numeric($key) && $key > $last_key){
						$last_key = $key;
					}	
					if(isset($fields_group['identifier']) && is_numeric($fields_group['identifier']) && $fields_group['identifier'] > $last_key){
						$last_key = $fields_group['identifier'];
					}
					?>
					<tr class='tr_general tr_general_<?php echo $key; ?>'>
						<td>
							<input type='text' name='fields[general_field_t_<?php echo $key; ?>_i_order]' value='<?php echo $fields_group['order']; ?>' style='width:35px;'/>
							<?php
							if(!isset($fields_group['identifier'])){
								$fields_group['identifier'] = $key;
							}
							?>
							<input type='hidden' name='fields[general_field_t_<?php echo $key; ?>_i_identifier]' value='<?php echo $fields_group['identifier']; ?>'/>
						</td>
						<td><input type='text' name='fields[general_field_t_<?php echo $key; ?>_i_title]' value='<?php echo $fields_group['title']; ?>' /></td>
						<td><input type='text' name='fields[general_field_t_<?php echo $key; ?>_i_def]' value='<?php echo $fields_group['def']; ?>' /></td>
						<td><?php echo agweeContracts_handler::create_field_yesno_select("fields[general_field_t_".$key."_i_required]",$fields_group['required']); ?></td>	
						<td><?php echo agweeContracts_handler::create_field_type_select("fields[general_field_t_".$key."_i_type]",$fields_group['type']); ?></td>
						<td><?php echo agweeContracts_handler::create_field_yesno_select("fields[general_field_t_".$key."_i_allowedit]",$fields_group['allowedit']); ?></td>	
						<th style='text-align:center;background:#ffd3d3;'><a href='javascript://' title='<?php echo __("Remove field", 'agwee-contracts-text'); ?>'  onclick='return remove_general_field(<?php echo $key; ?>)' style='color:red;text-decoration:none;display:block;width:20px;'>X</a></th>
					</tr>
					
				<?php endforeach; ?>
				
				<tr class='tr_general tr_general_after'></tr>
				<tr class='tr_general' style='background:#f9f9f9;'>
							<td colspan= '20' style='text-align:center'>
							
							
							<button class="add_field_button" type="button" onclick="return add_general_field(this)" rel="1"  style="">+<?php echo __("Add a Global field", 'agwee-contracts-text'); ?></button>
							</td>
						</tr>		
				
				<tr>
				<th colspan= '2'><?php echo __("Participants", 'agwee-contracts-text'); ?>:</th>
				<th colspan= '20'><?php echo __("Tag", 'agwee-contracts-text'); ?> <br/><input type='text' name='fields[users_fields_t_title]' value='<?php echo $form_fields['users_fields']['title']; ?>' /></th></tr>
				<?php $last_user = 0; ?>
				<?php foreach($form_fields['users_fields']['users'] as $user_key=>$user_group){ ?>
					<?php 
					if($user_key > $last_user){
						$last_user = $user_key;
					}
					
					if(isset($user_group['identifier']) && is_numeric($user_group['identifier']) && $user_group['identifier'] > $last_user){
						$last_user = $user_group['identifier'];
					}
					?>
					<tr class='tr_user_<?php echo $user_key; ?> tr_user_<?php echo $user_key; ?>_main' style='background:#ffffdf;'>
					
					
								<th colspan= '2'>
									<input type='hidden' name='fields[users_t_<?php echo $user_key; ?>_uid_identifier]' value='<?php echo $user_group['identifier']; ?>' />
									<?php echo __("Participant", 'agwee-contracts-text'); ?> <?php echo $user_key; ?>: <br/>
									<a class='fieldsdoor' style='color:blue;font-weight:normal;' href='javascript://' onclick='return showhide_user(this,<?php echo $user_key; ?>);' rel='open'><span class='showstr'><?php echo __("Show", 'agwee-contracts-text'); ?></span><span class='hidestr' style='display:none'><?php echo __("Hide", 'agwee-contracts-text'); ?></span> <?php echo __("שדות", 'agwee-contracts-text'); ?></a>
								</th>
								<th colspan= '2'>
									<?php echo __("Role", 'agwee-contracts-text'); ?> <br/><input type='text' name='fields[users_t_<?php echo $user_key; ?>_uid_role_name]' value='<?php echo $user_group['role_name']; ?>' /> 
									
								</th>
								<th colspan= '2'>
									<?php echo __("Location", 'agwee-contracts-text'); ?> - <input type='text' name='fields[users_t_<?php echo $user_key; ?>_uid_order]' value='<?php echo $user_group['order']; ?>' style='width:35px;'/> 
								 
								</th>	
								<th style='text-align:center;'><a href='javascript://' title='<?php echo __("Remove participant", 'agwee-contracts-text'); ?>' onclick='return remove_user(<?php echo $user_key; ?>)' style='color:red;text-decoration:none;display:block;width:20px;'>X</a></th>
							</tr>				
					
					<?php $last_key = 0; ?>
					<tr class='tr_user_<?php echo $user_key; ?>'>
						<th><?php echo __("Location", 'agwee-contracts-text'); ?></th>
						<th><?php echo __("Field Title", 'agwee-contracts-text'); ?></th>
						<th><?php echo __("Default value", 'agwee-contracts-text'); ?></th>
						<th><?php echo __("Is required", 'agwee-contracts-text'); ?></th>
						<th><?php echo __("Field type", 'agwee-contracts-text'); ?></th>
						<th><?php echo __("Allow edit", 'agwee-contracts-text'); ?></th>
						<th></th>
						
					</tr>
					
					<?php foreach($user_group['fields'] as $key=>$fields_group){ ?>
						<?php
						if(is_numeric($key) && $key > $last_key){
							$last_key = $key;
						}
						if(isset($fields_group['identifier']) && is_numeric($fields_group['identifier']) && $fields_group['identifier'] > $last_key){
							$last_key = $fields_group['identifier'];
						}
						?>
						<tr class='tr_user_<?php echo $user_key; ?> tr_user_<?php echo $user_key; ?>_fiealds_<?php echo $key; ?>'>
							<td>
								<input type='text' name='fields[user_field_t_<?php echo $user_key; ?>_uid_<?php echo $key; ?>_i_order]' value='<?php echo $fields_group['order']; ?>' style='width:35px;'/>
								<?php
								if(!isset($fields_group['identifier'])){
									$fields_group['identifier'] = $key;
								}
								?>
								<input type='hidden' name='fields[user_field_t_<?php echo $user_key; ?>_uid_<?php echo $key; ?>_i_identifier]' value='<?php echo $fields_group['identifier']; ?>'/>
								
							</td>						
							<td><input type='text' name='fields[user_field_t_<?php echo $user_key; ?>_uid_<?php echo $key; ?>_i_title]' value='<?php echo $fields_group['title']; ?>' /></td>
							<td><input type='text' name='fields[user_field_t_<?php echo $user_key; ?>_uid_<?php echo $key; ?>_i_def]' value='<?php echo $fields_group['def']; ?>' /></td>
							<td><?php echo agweeContracts_handler::create_field_yesno_select("fields[user_field_t_".$user_key."_uid_".$key."_i_required]",$fields_group['required']); ?></td>
							<td><?php echo agweeContracts_handler::create_field_type_select("fields[user_field_t_".$user_key."_uid_".$key."_i_type]",$fields_group['type']); ?></td>
							<td><?php echo agweeContracts_handler::create_field_yesno_select("fields[user_field_t_".$user_key."_uid_".$key."_i_allowedit]",$fields_group['allowedit']); ?></td>	
							<th style='text-align:center;background:#ffd3d3;'><a href='javascript://' title='<?php echo __("Remove field", 'agwee-contracts-text'); ?>'  onclick='return remove_user_field(<?php echo $user_key; ?>,<?php echo $key; ?>)' style='color:red;text-decoration:none;display:block;width:20px;'>X</a></th>
						</tr>
						
					<?php } ?>
					<tr class='tr_user_<?php echo $user_key; ?> tr_user_<?php echo $user_key; ?>_after'></tr>
					<tr class='tr_user_<?php echo $user_key; ?>' style='background:#f9f9f9;'>
							<td colspan= '20' style='text-align:center;'>
								<button class='add_field_button' style='' type='button' onclick='return add_user_field(this,<?php echo $user_key; ?>)' rel='<?php echo $last_key; ?>'>
									+ <?php echo __("Add field to Participant", 'agwee-contracts-text'); ?> - <?php echo $user_key; ?>
								</button>
							</td>
						</tr>
					
				<?php } ?>
				<tr class='tr_add_user' style='background:#f9f9f9;'>
							<td colspan= '20' style='text-align:center'>
								<button class="add_user_button" style='' type='button' onclick='return add_user(this)' rel='<?php echo $last_user; ?>'>
									+ <?php echo __("Add participant", 'agwee-contracts-text'); ?>
								</button>
							</td>
						</tr>
				<tr class='tr_save_button' style='background:#f9f9f9;'>
					<td colspan= '20' style='text-align:center'>
						<input type='submit' value='<?php echo __("Click here to save", 'agwee-contracts-text'); ?>' class='submit_style' style = 'font-size: 42px;width:100%;padding: 8px;font-weight: bold;color: #408e40;'>
					</td>
				</tr>		
			</table>
		</div>
		</form>
		<!-- script templates -->
		<table style='display:none;' id='fields_editor_templates'>
			<?php 
			$key = "fieldkey";
			$user_key = "userkey";
			?>
			<!-- general field template -->
			<tr class='tr_general tr_general_fieldkey tr_general_fieald_fieldkey'>
				<td>
					<input type='text' name='fields[general_field_t_<?php echo $key; ?>_i_order]' value='10' style='width:35px;'/>
					<input type='hidden' name='fields[general_field_t_<?php echo $key; ?>_i_identifier]' value='<?php echo $key; ?>'/>
				</td>
				<td><input type='text' name='fields[general_field_t_<?php echo $key; ?>_i_title]' value='' /></td>
				<td><input type='text' name='fields[general_field_t_<?php echo $key; ?>_i_def]' value='' /></td>
				<td><?php echo agweeContracts_handler::create_field_yesno_select("fields[general_field_t_".$key."_i_required]",'1'); ?></td>
				<td><?php echo agweeContracts_handler::create_field_type_select("fields[general_field_t_".$key."_i_type]",''); ?></td>
				<td><?php echo agweeContracts_handler::create_field_yesno_select("fields[general_field_t_".$key."_i_allowedit]",'1'); ?></td>	
				<th style='text-align:center;background:#ffd3d3;'><a href='javascript://' title='<?php echo __("Remove field", 'agwee-contracts-text'); ?>'  onclick='return remove_general_field(<?php echo $key; ?>)' style='color:red;text-decoration:none;display:block;width:20px;'>X</a></th>		
			</tr>
			


			<!-- full user template -->
			<tr class='tr_user_<?php echo $user_key; ?> tr_user_<?php echo $user_key; ?>_main tr_user_temp_<?php echo $user_key; ?>' style='background:#ffffdf;'>
				<th colspan= '2'>
					<input type='hidden' name='fields[users_t_<?php echo $user_key; ?>_uid_identifier]' value='<?php echo $user_key; ?>' />
					<?php echo __("Participant", 'agwee-contracts-text'); ?> <?php echo $user_key; ?>: 
					<br/>
					<a class='fieldsdoor dum_template' style='color:blue;font-weight:normal;' href='javascript://' onclick='return showhide_user(this,<?php echo $user_key; ?>);' rel='open'><span class='showstr' style='display:none'><?php echo __("Show", 'agwee-contracts-text'); ?></span><span class='hidestr'><?php echo __("Hide", 'agwee-contracts-text'); ?></span> <?php echo __("Fields", 'agwee-contracts-text'); ?></a>
				</th>
				<th colspan= '2'>
					<?php echo __("Role", 'agwee-contracts-text'); ?> <br/><input type='text' name='fields[users_t_<?php echo $user_key; ?>_uid_role_name]' value='' /> 
				</th>
				<th colspan= '2'>
					<?php echo __("Location", 'agwee-contracts-text'); ?> - <input type='text' name='fields[users_t_<?php echo $user_key; ?>_uid_order]' value='10'  style='width:35px;'/> 
				 
				</th>
				<th style='text-align:center;'><a href='javascript://' title='<?php echo __("Remove participant", 'agwee-contracts-text'); ?>'  onclick='return remove_user(<?php echo $user_key; ?>)' style='color:red;text-decoration:none;display:block;width:20px;'>X</a></th>
			</tr>
			<?php $last_key = 0; ?>
			<tr class='tr_user_<?php echo $user_key; ?> tr_user_temp_<?php echo $user_key; ?>'>
				<th><?php echo __("Location", 'agwee-contracts-text'); ?></th>
				<th><?php echo __("Field Title", 'agwee-contracts-text'); ?></th>
				<th><?php echo __("Default value", 'agwee-contracts-text'); ?></th>
				<th><?php echo __("Is required", 'agwee-contracts-text'); ?></th>
				<th><?php echo __("Field type", 'agwee-contracts-text'); ?></th>
				<th><?php echo __("Allow edit", 'agwee-contracts-text'); ?></th>
				<th></th>
			</tr>
			 
			<?php foreach($form_fields_defult['users_fields']['users'][1]['fields'] as $key=>$fields_group){ ?>
				<?php
				if(is_numeric($key) && $key > $last_key){
					$last_key = $key;
				}
				if(is_numeric($fields_group['identifier']) && $fields_group['identifier'] > $last_key){
					$last_key = $fields_group['identifier'];
				}
				?>				
				<tr class='tr_user_<?php echo $user_key; ?> tr_user_temp_<?php echo $user_key; ?>  tr_user_<?php echo $user_key; ?>_fiealds_<?php echo $key; ?>'>
					<td>
						<input type='text' name='fields[user_field_t_<?php echo $user_key; ?>_uid_<?php echo $key; ?>_i_order]' value='<?php echo $fields_group['order']; ?>' style='width:35px;'/>
						<?php
						if(!isset($fields_group['identifier'])){
							$fields_group['identifier'] = $key;
						}
						?>
						<input type='hidden' name='fields[user_field_t_<?php echo $user_key; ?>_uid_<?php echo $key; ?>_i_identifier]' value='<?php echo $fields_group['identifier']; ?>'/>
					</td>
					<td><input type='text' name='fields[user_field_t_<?php echo $user_key; ?>_uid_<?php echo $key; ?>_i_title]' value='<?php echo $fields_group['title']; ?>' /></td>
					<td><input type='text' name='fields[user_field_t_<?php echo $user_key; ?>_uid_<?php echo $key; ?>_i_def]' value='<?php echo $fields_group['def']; ?>' /></td>
					<td><?php echo agweeContracts_handler::create_field_yesno_select("fields[user_field_t_".$user_key."_uid_".$key."_i_required]",$fields_group['required']); ?></td>
					<td><?php echo agweeContracts_handler::create_field_type_select("fields[user_field_t_".$user_key."_uid_".$key."_i_type]",''); ?></td>
					<td><?php echo agweeContracts_handler::create_field_yesno_select("fields[user_field_t_".$user_key."_uid_".$key."_i_allowedit]",$fields_group['allowedit']); ?></td>
					<th style='text-align:center;background:#ffd3d3;'><a href='javascript://' title='הסר שדה'  onclick='return remove_user_field(<?php echo $user_key; ?>,<?php echo $key; ?>)' style='color:red;text-decoration:none;display:block;width:20px;'>X</a></th>
				</tr>
						
				
			<?php } ?>
			<tr class='tr_user_<?php echo $user_key; ?> tr_user_temp_<?php echo $user_key; ?> tr_user_<?php echo $user_key; ?>_after'></tr>
			<tr class='tr_user_<?php echo $user_key; ?> tr_user_temp_<?php echo $user_key; ?> ' style='background:#f9f9f9;'>
						<td colspan= '20' style='text-align:center'>
							<button class='add_field_button' style='' type='button' onclick='return add_user_field(this,<?php echo $user_key; ?>)' rel='$last_key'>
								+ <?php echo __("Add field to participant", 'agwee-contracts-text'); ?> - <?php echo $user_key; ?>
							</button>
						</td>
					</tr>
			
			<!-- user input template -->
			<?php $key = "fieldkey"; ?>
				<tr class='tr_user_<?php echo $user_key; ?> tr_user_userkey_fiealds_fieldkey tr_user_userkey_fieald_fieldkey'>
					<td>
						<input type='text' name='fields[user_field_t_<?php echo $user_key; ?>_uid_<?php echo $key; ?>_i_order]' value='10' style='width:35px;'/>
						<input type='hidden' name='fields[user_field_t_<?php echo $user_key; ?>_uid_<?php echo $key; ?>_i_identifier]' value='<?php echo $key; ?>'/>
					</td>
					<td><input type='text' name='fields[user_field_t_<?php echo $user_key; ?>_uid_<?php echo $key; ?>_i_title]' value='' /></td>
					<td><input type='text' name='fields[user_field_t_<?php echo $user_key; ?>_uid_<?php echo $key; ?>_i_def]' value='' /></td>
					<td><?php echo agweeContracts_handler::create_field_yesno_select("fields[user_field_t_".$user_key."_uid_".$key."_i_required]",'1'); ?></td>
					<td><?php echo agweeContracts_handler::create_field_type_select("fields[user_field_t_".$user_key."_uid_".$key."_i_type]",''); ?></td>
					<td><?php echo agweeContracts_handler::create_field_yesno_select("fields[user_field_t_".$user_key."_uid_".$key."_i_allowedit]",'1'); ?></td>		
					<th style='text-align:center;background:#ffd3d3;'><a href='javascript://' title='<?php echo __("Remove field", 'agwee-contracts-text'); ?>'  onclick='return remove_user_field(<?php echo $user_key; ?>,<?php echo $key; ?>)' style='color:red;text-decoration:none;display:block;width:20px;'>X</a></th>
				</tr>	
			
		</table>	
	
		<script type="text/javascript">
			jQuery(document).ready(
				function($){
					$(".fieldsdoor").each(function(){
						if(!$(this).hasClass("dum_template")){
							$(this).click();
						}
					});
				}
			);
				
			function add_user_field(el_id,user_id){
				jQuery(function($){
					var ftr = $('<div>').append($(".tr_user_userkey_fieald_fieldkey").first().clone()).html(); 
					var find = "userkey";
					var replace = user_id;
					ftr = ftr.replace(new RegExp(find, 'g'), replace);
		
					var lastkey = $(el_id).attr("rel");
					var lastkeyint = parseInt(lastkey) + 1;
					
					$(el_id).attr("rel",lastkeyint);
					find = "fieldkey";
					replace = lastkeyint;
					ftr = ftr.replace(new RegExp(find, 'g'), replace);				
					$(ftr).insertBefore(".tr_user_"+user_id+"_after");
				});
			}
			function add_general_field(el_id){
				jQuery(function($){
					var ftr = $('<div>').append($(".tr_general_fieald_fieldkey").first().clone()).html(); 
					var lastkey = $(el_id).attr("rel");
					
					var lastkeyint = parseInt(lastkey) + 1;
					
					$(el_id).attr("rel",lastkeyint);
					find = "fieldkey";
					replace = lastkeyint;
					ftr = ftr.replace(new RegExp(find, 'g'), replace);				
					$(ftr).insertBefore(".tr_general_after");
				});
			}		
			function add_user(el_id){
				jQuery(function($){
					var lastkey = $(el_id).attr("rel");
					var lastkeyint = parseInt(lastkey) + 1;
					$(".tr_user_temp_userkey").each(function(){
						var ftr = $('<div>').append($(this).clone()).html(); 
						$(el_id).attr("rel",lastkeyint);
						var find = "userkey";
						var replace = lastkeyint;		
						ftr = ftr.replace(new RegExp(find, 'g'), replace);	
						$(ftr).insertBefore(".tr_add_user");
						$(".tr_user_"+lastkeyint).find(".fieldsdoor").removeClass("dum_template");
						$(".tr_user_"+lastkeyint).find(".fieldsdoor").click();
					});			
				});
			}
			function showhide_user(el_id,uid){
				jQuery(function($){
					if($(el_id).attr("rel") == 'open'){
						$(".tr_user_"+uid).hide();
						$(".tr_user_"+uid+"_main").show();
						$(el_id).attr("rel","closed");
						$(el_id).find(".hidestr").hide();	
						$(el_id).find(".showstr").show();
						$(el_id).css("color","blue");
					}
					else{
						$(".tr_user_"+uid).show();
						$(el_id).attr("rel","open");
						$(el_id).find(".hidestr").show();	
						$(el_id).find(".showstr").hide();
						$(el_id).css("color","red");
					}
				});
			}
			function showhide_general(el_id){
				jQuery(function($){
					if($(el_id).attr("rel") == 'open'){
						$(".tr_general").hide();
						$(".tr_general_main").show();
						$(el_id).attr("rel","closed");
						$(el_id).find(".hidestr").hide();	
						$(el_id).find(".showstr").show();	
						$(el_id).css("color","blue");
					}
					else{
						$(".tr_general").show();
						$(el_id).attr("rel","open");
						$(el_id).find(".hidestr").show();	
						$(el_id).find(".showstr").hide();
						$(el_id).css("color","red");					
					}
				});
			}	
			function remove_user(uid){
				if(!confirm("<?php echo __("האם למחוק את המשתתף?", 'agwee-contracts-text'); ?>")){
					return;
				}
				jQuery(function($){
					$(".tr_user_"+uid).each(function(){$(this).remove()});
				});
			}	
			function remove_user_field(uid,fid){
				if(!confirm("<?php echo __("Remove the field?", 'agwee-contracts-text'); ?>")){
					return;
				}
				jQuery(function($){
					$(".tr_user_"+uid+"_fiealds_"+fid).each(function(){$(this).remove()});
				});
			}	
			function remove_general_field(fid){
				if(!confirm("<?php echo __("Remove the field?", 'agwee-contracts-text'); ?>")){
					return;
				}
				jQuery(function($){
					$(".tr_general_"+fid).each(function(){$(this).remove()});
				});
			}		
		</script>
		<?php		
	}

	public function content_editor(){
		
		
		// add font type & font size selection option in the WYSIWYG editor
		if ( ! function_exists( 'wdm_add_mce_fontoptions' ) ) {
				   function wdm_add_mce_fontoptions( $buttons ) {
						 array_unshift( $buttons, 'fontselect' );
						 array_unshift( $buttons, 'fontsizeselect' );
						 return $buttons;
				   }
		}
		$editor_id = "contract_content_editor";
		$settings =   array(
			'wpautop' => true, // enable auto paragraph?
			'media_buttons' => true, // show media buttons?
			'textarea_name' => 'contract_content', // id of the target textarea
			'textarea_rows' => get_option('default_post_edit_rows', 10), // This is equivalent to rows="" in HTML
			'tabindex' => '',
			'editor_css' => '', //  additional styles for Visual and Text editor,
			'editor_class' => '', // sdditional classes to be added to the editor
			'teeny' => false, // show minimal editor
			'dfw' => true, // replace the default fullscreen with DFW
			'tinymce' => array(
				// Items for the Visual Tab
				'toolbar1'=> 'bold,italic,underline,bullist,numlist,link,unlink,forecolor,undo,redo,fullscreen',
			),
			'quicktags' => array(
				// Items for the Text Tab
				'buttons' => 'strong,em,underline,ul,ol,li,link,code'
			)
		);
		add_filter( 'mce_buttons_3', 'wdm_add_mce_fontoptions' );
		//test wp_editor function
		
		
		//$settings = array();
		
		//error_reporting(E_ALL);
		//ini_set('display_errors', 1);

		if(isset($_REQUEST['edit_contract_content'])){
			
			$edit_contract_id = $_REQUEST['edit_contract_content'];
			$_POST['contract_content'] = str_replace("'","''",$_POST['contract_content']);
			$sql = "UPDATE ag_contract_design SET content='".stripcslashes($_POST['contract_content'])."' WHERE 1 AND id='".$edit_contract_id."'";
			$res = $this->wpcon->get_results($sql,ARRAY_A);
			$this->redirect_to_page_js("?page=agweeContracts_plugin&editor=content&contract_id=".$edit_contract_id);
			return;
		}
		$contract_id = $_GET['contract_id'];
		$form_fields = agweeContracts_handler::create_fields_arr_from_contract($contract_id);
		$contract_data = agweeContracts_handler::get_contract_data($contract_id,false);
		$contract_content = $contract_data['content'];
		?>
		<h2><?php echo __("The contract content", 'agwee-contracts-text'); ?></h2>
		<table cellpadding='10'>
			<tr>
				<td style='vertical-align:top;'>
					<div style="float:right; color:black; width:210px; font-size:17px;" id="contract_fields_wrap">
						<b style="display:block;padding:5px;"><?php echo __("The fields to add", 'agwee-contracts-text'); ?></b>
						<div style="background:#f3f5f6;margin-top:20px; padding:5px; color:black;height:340px; overflow-y:auto;" id="contract_fields">
							
							<b style="display:block;padding:0px 10px;"><?php echo $form_fields['general']['title']; ?></b>
							<?php foreach($form_fields['general']['fields'] as $field_group): ?>
								<a class='contract_field_a' style="display:block;padding:3px 10px;" href="javascript://" onclick="contract_field_select(this)" rel="{{<?php echo $field_group['title']; ?>}}"><?php echo $field_group['title']; ?></a>
							<?php endforeach; ?>
							<?php foreach($form_fields['users_fields']['users'] as $user_group): ?>
								<b style="display:block;padding:0px 10px;"><?php echo $user_group['role_name']; ?></b>
								<?php foreach($user_group['fields'] as $user_field): ?>
									<a class='contract_field_a' style="display:block;padding:3px 10px;" href="javascript://" onclick="contract_field_select(this)" rel="{{<?php echo $user_field['title']; ?>(<?php echo $user_group['role_name']; ?>)}}"><?php echo $user_field['title']; ?></a>
								<?php endforeach; ?>	
								<a class='contract_field_a' style="display:block;padding:3px 10px;" href="javascript://" onclick="contract_field_select(this)" rel="{{חתימה(<?php echo $user_group['role_name']; ?>)}}">חתימה</a>
							<?php endforeach; ?>
							<a class='contract_field_a' style="display:block;padding:3px 10px;" href="javascript://" onclick="contract_field_select(this)" rel="{{<?php echo __("נספח", 'agwee-contracts-text'); ?>}}"><?php echo __("הוסף נספח לחוזה", 'agwee-contracts-text'); ?></a>
						</div>
						<style type='text/css'>
							.contract_field_a:hover{background:#ddd;}
							.contract_field_a.selected{background:#9d9;}
						</style>
						<button type="button" onclick="add_field_to_contract();" style="margin:10px; padding:10px;"><?php echo __("Click here to add the selected field to the content", 'agwee-contracts-text'); ?></button>
					</div>
				</td>
				<td>
					<form action="" method="POST">
						<input type="hidden" name="edit_contract_content" value="<?php echo $contract_data['id']; ?>" />
						<div style="float:right;">
							<h2><?php echo __("The contract content here!", 'agwee-contracts-text'); ?>:</h2>
							<br/>
							<?php wp_editor($contract_content,$editor_id,$settings); ?>
							<?php
								echo "<input type='submit' value='".__("Save", 'agwee-contracts-text')."' class='submit_style' style = 'font-size: 42px;width:100%;padding: 8px;font-weight: bold;color: #408e40;margin:10px 0px;box-sizing: border-box;'>";
							?>
						</div>
					</form>
				</td>
			</tr>
		</table>
		
		
		
		<script type="text/javascript">
			//var editor_instance = tinyMCE.get("<?php echo $editor_id; ?>");
			jQuery(function($){
				$(".contract_field_a").dblclick(function(){add_field_to_contract();});
						
			});
			function add_text_to_contract(txt){
				//editor_instance.insertHtml(txt);
				tinyMCE.get("<?php echo $editor_id; ?>").execCommand('mceInsertContent', false, txt);
			}
			function contract_field_select(a_el){
				jQuery(function($){
					$(".contract_field_a").each(function(){$(this).removeClass("selected");});
					$(a_el).addClass("selected");
					
				});
			}
			function add_field_to_contract(){
				jQuery(function($){
					$(".contract_field_a").each(function(){
						if($(this).hasClass("selected")){
							add_text_to_contract($(this).attr("rel"));
						}
					});
					
					
				});			
			}
		</script>
		<?php

	}
}