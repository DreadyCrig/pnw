<div id="charge_container" class="mor">


	<?php if(!$encrypt_key_set) : ?>
		<p class="alert error"><strong>No Encryption Key Set!</strong> You need to set a value for <code>encryption_key</code> in your site's config</p>
	<?php endif; ?>


	<form method="post" action="<?=$form_post_url?>">

	<input type="hidden" name="XID" value="<?=XID_SECURE_HASH?>"/>


	<div class="tg">
		<h2>Charge Settings</h2>

	<table id="preferences" class="data">
		<thead>
			<tr style="background-color:transparent">
				<th style="width:40%; text-align:left;">Stripe Configuration</th>
				<th style="width:60%; text-align:left;"></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td>Account Mode</td>
				<td>
				  <?php $mode = $charge_stripe_account_mode ?>
					<label for="charge_stripe_account_mode_live">Live
					<input type="radio" name="charge_stripe_account_mode" id="charge_stripe_account_mode_live" value="live"<?php if ($mode == 'live') : ?> checked<?php endif?>/></label>
					<label for="charge_stripe_account_mode_test">Test
					<input type="radio" name="charge_stripe_account_mode" id="charge_stripe_account_mode_test" value="test"<?php if ($mode == '' OR $mode == 'test') : ?> checked<?php endif?>/>
					</label>
				</td>
			</tr>
			<tr>
				<td>Test Secret Key</td>
				<td>
					<input type="text" name="charge_stripe_test_credentials_sk" id="charge_stripe_test_credentials_sk" placeholder="Test Secret Key" value="<?=$charge_stripe_test_credentials_sk?>"/>
				</td>
			</tr>
			<tr>
				<td>Test Publishable Key</td>
				<td>
					<input type="text" name="charge_stripe_test_credentials_pk" id="charge_stripe_test_credentials_pk" placeholder="Test Publishable Key" value="<?=$charge_stripe_test_credentials_pk?>"/>
				</td>
			</tr>
			<tr>
				<td>Live Secret Key</td>
				<td>
					<input type="text" name="charge_stripe_live_credentials_sk" id="charge_stripe_live_credentials_sk" placeholder="Live Secret Key" value="<?=$charge_stripe_live_credentials_sk?>"/>
				</td>
			</tr>
			<tr>
				<td>Live Publishable Key</td>
				<td>
					<input type="text" name="charge_stripe_live_credentials_pk" id="charge_stripe_live_credentials_pk" placeholder="Live Publishable Key" value="<?=$charge_stripe_live_credentials_pk?>"/>
				</td>
			</tr>
			<tr>
				<td>Default Currency<br/>
				<em>Dependant on currencies available to this Stripe account. Not all currencies are available to all accounts</em></td>
				<td>
				  <?php $currency = $charge_stripe_currency?>
					<select id="charge_stripe_currency" name="charge_stripe_currency">
						<?php foreach($currencies as $currency_key => $currency_data) : ?>
						<option value="<?=$currency_key?>"<?php if ($currency == $currency_key) : ?> selected<?php endif?>><?=$currency_data['cleaned_name']?></option>
						<?php endforeach ?>
					</select>
				</td>
			</tr>
		</tbody>
	</table>


	<table class="data">
		<thead>
			<tr style="background-color:transparent">
				<th style="width:40%; text-align:left;">Extra Options</th>
				<th style="width:60%; text-align:left;"></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td>Force SSL<br/>
				<em>On some servers we may not naturally be able to detect https. If needed, you can force the charge:form post url and all Charge assets to be served over ssl.</em></td>
				<td>
					<label for="charge_force_ssl_on">HTTPS Always
					<input type="radio" name="charge_force_ssl" id="charge_force_ssl_on" value="yes"<?php if ($charge_force_ssl == 'yes') : ?> checked<?php endif?>/></label>
					<label for="charge_force_ssl_off">Inherit from URL
					<input type="radio" name="charge_force_ssl" id="charge_force_ssl_off" value="no"<?php if ($charge_force_ssl == '' OR $charge_force_ssl == 'no') : ?> checked<?php endif?>/>
					</label>
				</td>
			</tr>


			<tr>
				<td>Pass Metadata to Stripe<br/>
				<em>Optionally you can stop passing any extra 'meta:' fields to Stripe. All the metadata will still be recorded locally on the site</em><td>
					<label for="charge_force_ssl_on">Pass MetaData
					<input type="radio" name="charge_metadata_pass" id="charge_metadata_pass_on" value="yes"<?php if ($charge_metadata_pass == 'yes' OR $charge_metadata_pass == '' ) : ?> checked<?php endif?>/></label>
					<label for="charge_force_ssl_off">Don't send to Stripe
					<input type="radio" name="charge_metadata_pass" id="charge_metadata_pass_off" value="no"<?php if ($charge_metadata_pass == 'no') : ?> checked<?php endif?>/>
					</label>
				</td>
			</tr>




			<!--<tr>
				<td>Audit Logging</td>
			  <?php $logging = $charge_logging_level?>
				<td>
					<select id="charge_logging_level" name="charge_logging_level">
						<option value="0"<?php if ($logging == '0') : ?> selected<?php endif?>>None -  audit logging is off</option>
						<option value="1"<?php if ($logging == '1') : ?> selected<?php endif?>>Info - outputs exceptions</option>
						<option value="2"<?php if ($logging == '2') : ?> selected<?php endif?>>All - logs everything</option>
					</select>
				</td>
			</tr> -->

		</tbody>
	</table>
	</div>

	<?php if($subscriptions_enabled == true) : ?>

	<div class="tg">
		<h2>Stripe Webhooks</h2>
		<div class="alert">
			<p>To receive data back from Stripe about recurring payments after the first payment, you'll need to add the following webhook url to your Stripe account. The same webhook works for both test and live enviroments.<br/>
			<!--<a href="<?=$webhook_log_uri?>">View Callback Log &rarr;</a>-->
			</p>
		</div>
		<table class="data">
			<tbody>
				<tr>
					<td>Webhook URL</td>
					<td><input type="text" value="<?=$callback_url?>"/></td>
				</tr>
			</tbody>
		</table>
	</div>
	<?php endif; ?>

	<input type="submit" class="submit" name="submit" value="Save" />
	</form>

</div>
