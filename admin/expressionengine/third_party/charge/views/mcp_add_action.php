
<div id="charge_container" class="mor">

	<form method="post" action="<?=$form_post_url?>">
	<input type="hidden" name="XID" value="<?=XID_SECURE_HASH?>"/>
	<input type="hidden" name="edit_action" value="yes"/>
	<input type="hidden" name="id" value="<?=$action['action_id']?>"/>

	<div class="tg">
		<h2>Add Action</h2>

		<div class="alert ">
			<?php if($subscriptions_enabled == true) : ?>
				<p>Actions are triggered after successful purchases. They can update entries, send emails, and add members to subscriptions.</p>

			<?php else : ?>
				<p>Actions are triggered after successful purchases. They can update entries, and send emails.</p>
			<?php endif; ?>

			</div>

	<table  class="data">
		<tbody>
			<tr>
				<td width="30%">
					<label for="name">Action Name</label>
					<br/><em>eg. 'Email customer after payment'</em></td>
				<td>
					<input class="js-shortname" data-shortname="shortname" type="text" name="name" id="name" value="<?=charge_get_value($action, 'name')?>"/>
					<?=charge_get_value($errors, 'name', '<em class="error">* ','</em>')?>
				</td>
			</tr>

			<tr>
				<td>
					<label for="shortname">Action Shortname</label>
					<br/><em>eg. 'notify_all'. Supply this string on the 'on_success' parameter on your {exp:charge:form} tag</em></td>
				<td>
					<input type="text" name="shortname" data-raw="<?=charge_get_value($action, 'shortname')?>" id="shortname" value="<?=charge_get_value($action, 'shortname')?>"/>
					<?=charge_get_value($errors, 'shortname', '<em class="error">* ','</em>')?>
				</td>
			</tr>
		</tbody>
	</table>
	</div>

	<div class="tg">
		<h3>Emails</h3>
		<div class="alert">
			<p>Note : Emails can also be triggered directly from Stripe's dashboard</p>
		</div>
		<table class="data">
			<tbody>

			<tr>
				<td width="30%">Email Customer<br/>
				<em>Sends an email to the customer email as recorded during payment. <br/>All variables from <strong>{exp:charge:info}</strong> are available here automatically</em></td>
				<td class="sub-table">
					<table>
						<tr>
							<td colspan="2">
								<label for="email_customer_enabled">
									<input class="js-enabler" rel="js-group-customer-email" name="email_customer_enabled" id="email_customer_enabled" type="checkbox" value="yes" <?=charge_check_yes($action['settings'], 'email_customer_enabled', 'checked="checked"')?>/> Enabled
								</label>
							</td>
						</tr>
						<tr class="js-group-customer-email <?=charge_check_yes($action['settings'], 'email_customer_enabled', '', 'inactive')?>">
							<td width="40%">
								<label for="email_customer_template">Email Template</label>
							</td>
							<td>
								<select name="email_customer_template" id="email_customer_template">
									<option></option>
									<?php foreach($templates as $key => $val) : ?>
										<option value="<?=$val?>" <?=charge_check_value($action['settings'], 'email_customer_template', $val, 'selected="selected"')?>><?=$key?></option>
									<?php endforeach ?>
								</select>

								<?=charge_get_value($errors, 'email_customer_template', '<em class="error">* ','</em>')?>
							</td>
						</tr>
						<tr class="js-group-customer-email <?=charge_check_yes($action['settings'], 'email_customer_enabled', '', 'inactive')?>">
							<td>
								<label for="email_customer_subject">Subject</label>
							</td>
							<td>
								<input name="email_customer_subject" id="email_customer_subject" type="text" placeholder="Thanks for you Payment of {amount_currency_formatted}" value="<?=charge_get_value($action['settings'], 'email_customer_subject')?>"/>

								<?=charge_get_value($errors, 'email_customer_subject', '<em class="error">* ','</em>')?>
							</td>
						</tr>
					</table>
				</td>

			</tr>

			<tr>
				<td width="30%">Email Admin <br/>
				<em>Sends a notification to specific email addresses after payment<br/>All variables from <strong>{exp:charge:info}</strong> are available here automatically</em></em></td>
				<td class="sub-table">
					<table>
						<tr>
							<td colspan="2">
								<label for="email_admin_enabled">
									<input class="js-enabler" rel="js-group-admin-email" type="checkbox" name="email_admin_enabled" id="email_admin_enabled" value="yes" <?=charge_check_yes($action['settings'], 'email_admin_enabled', 'checked="checked"')?>/> Enabled
								</label>
							</td>
						</tr>
						<tr class="js-group-admin-email <?=charge_check_yes($action['settings'], 'email_admin_enabled', '', 'inactive')?>">
							<td width="40%">
								<label for="email_admin_addresses">Email Addresses</label>
								<br/><em>Seperate multiple emails with a comma</em>
							</td>
							<td>
								<input name="email_admin_addresses" id="email_admin_addresses" type="text" placeholder="admin@site.com, accounts@site.com" value="<?=charge_get_value($action['settings'], 'email_admin_addresses')?>"/>

								<?=charge_get_value($errors, 'email_admin_addresses', '<em class="error">* ','</em>')?>
							</td>
						</tr>
						<tr class="js-group-admin-email <?=charge_check_yes($action['settings'], 'email_admin_enabled', '', 'inactive')?>">
							<td>
								<label for="email_admin_template">Email Template</label>
							</td>
							<td>
								<select name="email_admin_template" id="email_admin_template">
									<option></option>

									<?php foreach($templates as $key => $val) : ?>
										<option value="<?=$val?>" <?=charge_check_value($action['settings'], 'email_admin_template', $val, 'selected="selected"')?>><?=$key?></option>
									<?php endforeach ?>
								</select>

								<?=charge_get_value($errors, 'email_admin_template', '<em class="error">* ','</em>')?>
							</td>
						</tr>
						<tr class="js-group-admin-email <?=charge_check_yes($action['settings'], 'email_admin_enabled', '', 'inactive')?>">
							<td>
								<label for="email_admin_subject">Subject</label>
							</td>
							<td><input id="email_admin_subject" name="email_admin_subject" type="text" placeholder="Payment made by {customer_name} for {plan_amount_formatted}" value="<?=charge_get_value($action['settings'], 'email_admin_subject')?>"/>

								<?=charge_get_value($errors, 'email_admin_subject', '<em class="error">* ','</em>')?>
							</td>
						</tr>
					</table>
				</td>

			</tr>
		</tbody>
	</table>
	</div>

	<div class="tg">
		<h2>Entry Actions</h2>
		<table class="data">
			<tbody>


			<tr>
				<td width="30%">Create Entry<br/>
				<em>Create an entry on success</em><br/>
				<em>Pass values for the entry as params like 'on_success:my_field_name="value"'. All dynamic charge variables are also available - {plan_amount}, {hash} etc.)</em></td>
				<td class="sub-table">
					<table>
						<tr>
							<td colspan="2">
								<label for="entry_create_enabled">
									<input class="js-enabler" rel="js-group-entry-create" name="entry_create_enabled" id="entry_create_enabled" type="checkbox" value="yes" <?=charge_check_yes($action['settings'], 'entry_create_enabled', 'checked="checked"')?>/> Enabled
								</label>
							</td>
						</tr>
						<tr class="js-group-entry-create <?=charge_check_yes($action['settings'], 'entry_create_enabled', '', 'inactive')?>">
							<td width="40%">
								<label for="entry_create_channel">Channel</label>
							</td>
							<td>
								<select id="entry_create_channel" name="entry_create_channel">
									<option></option>
									<?php foreach($channels as $key => $val) : ?>
										<option value="<?=$key?>" <?=charge_check_value($action['settings'], 'entry_create_channel', $key, 'selected="selected"')?>><?=$val?></option>
									<?php endforeach ?>
								</select>
								<?=charge_get_value($errors, 'entry_create_channel', '<em class="error">* ','</em>')?>
							</td>
						</tr>
						<tr class="js-group-entry-create <?=charge_check_yes($action['settings'], 'entry_create_enabled', '', 'inactive')?>">
							<td width="40%">
								<label for="entry_create_title">Entry Title</label>
							</td>
							<td>
								<input type="text" id="entry_create_title" name="entry_create_title" placeholder="eg. Order Reciept #{id} for {customer_name}" value="<?=charge_get_value($action['settings'], 'entry_create_title')?>"/>
								<?=charge_get_value($errors, 'entry_create_title', '<em class="error">* ','</em>')?>
							</td>
						</tr>

						<tr class="js-group-entry-create <?=charge_check_yes($action['settings'], 'entry_create_enabled', '', 'inactive')?>">
							<td width="40%">
								<label for="entry_create_status">Entry Status</label>
							</td>
							<td>
								<select id="entry_create_status" name="entry_create_status">
									<option></option>
									<?php foreach($statuses as $key => $val) : ?>
										<option value="<?=$key?>" <?=charge_check_value($action['settings'], 'entry_create_status', $key, 'selected="selected"')?>><?=$val?></option>
									<?php endforeach ?>
								</select>
								<?=charge_get_value($errors, 'entry_create_status', '<em class="error">* ','</em>')?>
							</td>
						</tr>
					</table>
				</td>
			</tr>

			<tr>
				<td width="30%">Update Entry<br/>
				<em>Can be used to update any values on an entry, as long as the current user has access to edit that entry.</em><br/>
				<em>Pass the 'entry_id' or 'url_title' as on_success:entry_id / on_success:url_title params</em></td>
				<td class="sub-table">
					<table>
						<tr>
							<td colspan="2">
								<label for="entry_update_enabled">
									<input class="js-enabler" rel="js-group-entry-update" name="entry_update_enabled" id="entry_update_enabled" type="checkbox" value="yes" <?=charge_check_yes($action['settings'], 'entry_update_enabled', 'checked="checked"')?>/> Enabled
								</label>
							</td>
						</tr>
						<tr class="js-group-entry-update <?=charge_check_yes($action['settings'], 'entry_update_enabled', '', 'inactive')?>">
							<td width="40%">
								<label for="entry_update_status">Entry Status</label>
							</td>
							<td>
								<select id="entry_update_status" name="entry_update_status">
									<option></option>
									<?php foreach($statuses as $key => $val) : ?>
										<option value="<?=$key?>" <?=charge_check_value($action['settings'], 'entry_update_status', $key, 'selected="selected"')?>><?=$val?></option>
									<?php endforeach ?>
								</select>
								<?=charge_get_value($errors, 'entry_update_status', '<em class="error">* ','</em>')?>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</tbody>
	</table>
	</div>


	<?php if($subscriptions_enabled == true) : ?>
	<div class="tg">
		<h2>Member Actions</h2>
		<table class="data">
			<tbody>

			<tr>
				<td width="30%">Add Member to Subscription<br/>
				<em>Don't see the subscription you need? <br/> <a href="<?=$add_subscription_uri?>">Create a new subscription</a></em></td>
				<td class="sub-table">
					<table>

						<?php if(count($subscriptions) > 0 ) : ?>

						<tr>
							<td colspan="2">
								<label for="member_subscription_enabled">
									<input class="js-enabler" rel="js-group-subscription" name="member_subscription_enabled" id="member_subscription_enabled" type="checkbox" value="yes" <?=charge_check_yes($action['settings'], 'member_subscription_enabled', 'checked="checked"')?>/> Enabled
								</label>
							</td>
						</tr>
						<tr class="js-group-subscription <?=charge_check_yes($action['settings'], 'member_subscription_enabled', '', 'inactive')?>">
							<td width="40%">
								<label for="member_subscription_id">Add Member to Subscription</label>
							</td>
							<td>
								<select name="member_subscription_id" id="member_subscription_id">
									<option></option>

									<?php foreach($subscriptions as $id => $val) : ?>
										<option value="<?=$val['subscription_id']?>" <?=charge_check_value($action['settings'], 'member_subscription_id', $val['subscription_id'], 'selected="selected"')?>><?=$val['name']?></option>
									<?php endforeach ?>
								</select>
							</td>
						</tr>

						<?php else : ?>

						<tr>
							<td colspan="2">
								<label for="member_subscription_enabled">
									<input name="member_subscription_enabled" id="member_subscription_enabled" type="checkbox" value="yes" disabled="disabled"/> Enabled<br/>
									<em>No subscriptions exist. <a href="<?=$add_subscription_uri?>">Create new member subscription</a></em>
								</label>
							</td>
						</tr>

						<?php endif; ?>
					</table>
				</td>
			</tr>
		</tbody>
	</table>
	</div>
	<?php endif; ?>
	<input type="submit" class="submit" name="submit" value="Save" />
	</form>
	</div>
</div>

