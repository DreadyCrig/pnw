
<div id="charge_container" class="mor">

	<form method="post" action="<?=$form_post_url?>">
	<input type="hidden" name="XID" value="<?=XID_SECURE_HASH?>"/>
	<input type="hidden" name="edit_subscription" value="yes"/>
	<input type="hidden" name="id" value="<?=$action['subscription_id']?>"/>

	<div class="tg">
		<h2>Subscription</h2>

		<div class="alert ">
			<p>Subscriptions allow you to control a member's group based on their payments. Both one-off and recurring payments. Add a member to a subscription by triggering it in an <a href="<?=$actions_uri?>">Action</a>.</p>
		</div>

	<table  class="data">
		<tbody>
			<tr>
				<td width="30%">Subscription Name <br/><em>eg. 'Premium Site Members'</em></td>
				<td>
					<input type="text" name="name" id="action_name" value="<?=charge_get_value($action, 'name')?>"/>

					<?=charge_get_value($errors, 'name', '<em class="error">* ','</em>')?>
				</td>
			</tr>
		</tbody>
	</table>
	</div>

	<div class="tg">
		<h2>Member Groups</h2>
		<table class="data">
			<tr>
				<td width="30%">
					<label for="member_group_valid">Success Member Group</label><br/>
				<em>While the member's subscription is valid, keep them in this member group</em>
				</td>
				<td>
					<select name="member_group_valid" id="member_group_valid">
						<option></option>
						<?php foreach($member_groups as $group_id => $group_name) : ?>
							<option value="<?=$group_id?>" <?=charge_check_value($action, 'member_group_valid', $group_id, 'selected="selected"')?>><?=$group_name?></option>
						<?php endforeach?>
					</select>

					<?=charge_get_value($errors, 'member_group_valid', '<em class="error">* ','</em>')?>
				</td>
			</tr>

			<tr>
				<td>
					<label for="member_group_invalid">Failure Member Group</label>
					<br/>
					<em>When payment fails, move the member to this member group</em>
				</td>
				<td>
					<select name="member_group_invalid" id="member_group_invalid">
						<option></option>
						<?php foreach($member_groups as $group_id => $group_name) : ?>
							<option value="<?=$group_id?>" <?=charge_check_value($action, 'member_group_invalid', $group_id, 'selected="selected"')?>><?=$group_name?></option>
						<?php endforeach?>
					</select>

					<?=charge_get_value($errors, 'member_group_invalid', '<em class="error">* ','</em>')?>
				</td>
			</tr>
		</table>
	</div>

	<div class="tg">
		<h3>Emails</h3>
		<table class="data">
			<tbody>

			<tr>
				<td width="30%">Welcome Email<br/>
				<em>Sends an email to the member welcoming them. This is in addititon to any emails triggered from Stripe or the Action</em></td>
				<td class="sub-table">
					<table>
						<tr>
							<td colspan="2">
								<label for="email_member_welcome_enabled">
									<input class="js-enabler" rel="js-group-welcome" name="email_member_welcome_enabled" id="email_member_welcome_enabled" type="checkbox" value="yes" <?=charge_check_yes($action['settings'], 'email_member_welcome_enabled', 'checked="checked"')?>/> Enabled
								</label>
							</td>
						</tr>
						<tr class="js-group-welcome <?=charge_check_yes($action['settings'], 'email_member_welcome_enabled', '', 'inactive')?>">
							<td width="40%">
								<label for="email_member_welcome_template">Email Template</label>
							</td>
							<td>
								<select name="email_member_welcome_template" id="email_member_welcome_template">
									<option></option>
									<?php foreach($templates as $key => $val) : ?>
										<option value="<?=$key?>" <?=charge_check_value($action['settings'], 'email_member_welcome_template', $key, 'selected="selected"')?>><?=$val?></option>
									<?php endforeach ?>
								</select>

								<?=charge_get_value($errors, 'email_member_welcome_template', '<em class="error">* ','</em>')?>
							</td>
						</tr>
						<tr class="js-group-welcome <?=charge_check_yes($action['settings'], 'email_member_welcome_enabled', '', 'inactive')?>">
							<td>
								<label for="email_member_welcome_subject">Subject</label>
							</td>
							<td>
								<input name="email_member_welcome_subject" id="email_member_welcome_subject" type="text" placeholder="Welcome to the Premium Member group" value="<?=charge_get_value($action['settings'], 'email_member_welcome_subject')?>"/>

								<?=charge_get_value($errors, 'email_member_welcome_subject', '<em class="error">* ','</em>')?>
							</td>

						</tr>
						<tr class="js-group-welcome <?=charge_check_yes($action['settings'], 'email_member_welcome_enabled', '', 'inactive')?>">
							<td>
								<label for="email_member_welcome_bcc">Bcc<br/>
									<em>Seperate multiple addresses with a comma</label>
							</td>
							<td>
								<input name="email_member_welcome_bcc" id="email_member_welcome_bcc" type="text" placeholder="admin@site.com, accounts@site.com" value="<?=charge_get_value($action['settings'], 'email_member_welcome_bcc')?>"/>

								<?=charge_get_value($errors, 'email_member_welcome_bcc', '<em class="error">* ','</em>')?>
							</td>
						</tr>
					</table>
				</td>

			</tr>

			<tr>
				<td width="30%">Recurring Payment<br/>
				<em>Sends an email to the after a successful recurring payment. This is in addititon to any emails triggered from Stripe</em></td>
				<td class="sub-table">
					<table>
						<tr>
							<td colspan="2">
								<label for="email_member_recurring_enabled">
									<input class="js-enabler" rel="js-group-recurring" name="email_member_recurring_enabled" id="email_member_recurring_enabled" type="checkbox" value="yes" <?=charge_check_yes($action['settings'], 'email_member_recurring_enabled', 'checked="checked"')?>/> Enabled
								</label>
							</td>
						</tr>
						<tr class="js-group-recurring <?=charge_check_yes($action['settings'], 'email_member_recurring_enabled', '', 'inactive')?>">
							<td width="40%">
								<label for="email_member_recurring_template">Email Template</label>
							</td>
							<td>
								<select name="email_member_recurring_template" id="email_member_recurring_template">
									<option></option>
									<?php foreach($templates as $key => $val) : ?>
										<option value="<?=$key?>" <?=charge_check_value($action['settings'], 'email_member_recurring_template', $key, 'selected="selected"')?>><?=$val?></option>
									<?php endforeach ?>
								</select>

								<?=charge_get_value($errors, 'email_member_recurring_template', '<em class="error">* ','</em>')?>
							</td>
						</tr>
						<tr class="js-group-recurring <?=charge_check_yes($action['settings'], 'email_member_recurring_enabled', '', 'inactive')?>">
							<td>
								<label for="email_member_recurring_subject">Subject</label>
							</td>
							<td>
								<input name="email_member_recurring_subject" id="email_member_recurring_subject" type="text" placeholder="Thanks for being a premium member" value="<?=charge_get_value($action['settings'], 'email_member_recurring_subject')?>"/>

								<?=charge_get_value($errors, 'email_member_recurring_subject', '<em class="error">* ','</em>')?>
							</td>
						</tr>
						<tr class="js-group-recurring <?=charge_check_yes($action['settings'], 'email_member_recurring_enabled', '', 'inactive')?>">
							<td>
								<label for="email_member_recurring_bcc">Bcc<br/>
									<em>Seperate multiple addresses with a comma</label>
							</td>
							<td>
								<input name="email_member_recurring_bcc" id="email_member_recurring_bcc" type="text" placeholder="admin@site.com, accounts@site.com" value="<?=charge_get_value($action['settings'], 'email_member_recurring_bcc')?>"/>

								<?=charge_get_value($errors, 'email_member_recurring_bcc', '<em class="error">* ','</em>')?>
							</td>
						</tr>

					</table>
				</td>
			</tr>

			<tr>
				<td width="30%">Subscription End<br/>
				<em>Sends an email (if) the customer leaves the recurring subscription, or their payment fails</em></td>
				<td class="sub-table">
					<table>
						<tr>
							<td colspan="2">
								<label for="email_member_failure_enabled">
									<input class="js-enabler" rel="js-group-failure" name="email_member_failure_enabled" id="email_member_failure_enabled" type="checkbox" value="yes" <?=charge_check_yes($action['settings'], 'email_member_failure_enabled', 'checked="checked"')?>/> Enabled
								</label>
							</td>
						</tr>
						<tr class="js-group-failure <?=charge_check_yes($action['settings'], 'email_member_failure_enabled', '', 'inactive')?>">
							<td width="40%">
								<label for="email_member_failure_template">Email Template</label>
							</td>
							<td>
								<select name="email_member_failure_template" id="email_member_failure_template">
									<option></option>
									<?php foreach($templates as $key => $val) : ?>
										<option value="<?=$key?>" <?=charge_check_value($action['settings'], 'email_member_failure_template', $key, 'selected="selected"')?>><?=$val?></option>
									<?php endforeach ?>
								</select>

								<?=charge_get_value($errors, 'email_member_failure_template', '<em class="error">* ','</em>')?>
							</td>
						</tr>
						<tr class="js-group-failure <?=charge_check_yes($action['settings'], 'email_member_failure_enabled', '', 'inactive')?>">
							<td>
								<label for="email_member_failure_subject">Subject</label>
							</td>
							<td>
								<input name="email_member_failure_subject" id="email_member_failure_subject" type="text" placeholder="Your Subscription has Expired" value="<?=charge_get_value($action['settings'], 'email_member_failure_subject')?>"/>

								<?=charge_get_value($errors, 'email_member_failure_subject', '<em class="error">* ','</em>')?>
							</td>
						</tr>
						<tr class="js-group-failure <?=charge_check_yes($action['settings'], 'email_member_failure_enabled', '', 'inactive')?>">
							<td>
								<label for="email_member_failure_bcc">Bcc<br/>
									<em>Seperate multiple addresses with a comma</label>
							</td>
							<td>
								<input name="email_member_failure_bcc" id="email_member_failure_bcc" type="text" placeholder="admin@site.com, accounts@site.com" value="<?=charge_get_value($action['settings'], 'email_member_failure_bcc')?>"/>

								<?=charge_get_value($errors, 'email_member_failure_bcc', '<em class="error">* ','</em>')?>
							</td>
						</tr>
					</table>
				</td>
			</tr>


		</tbody>
	</table>
	</div>


	<input type="submit" class="submit" name="submit" value="Save" />
	</form>
	</div>
</div>

