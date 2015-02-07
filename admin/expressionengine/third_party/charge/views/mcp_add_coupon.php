
<div id="charge_container" class="mor">

	<form method="post" action="<?=$form_post_url?>">
	<input type="hidden" name="XID" value="<?=XID_SECURE_HASH?>"/>
	<input type="hidden" name="edit_coupon" value="yes"/>
	<input type="hidden" name="id" value="<?=$coupon['coupon_id']?>"/>
	<input type="hidden" name="stripe_id" value="<?=$coupon['stripe_id']?>"/>

	<div class="tg">
		<h2>Add Coupon</h2>
		<?=charge_get_value($errors, 'general', '<p class="alert error" style="margin-bottom :0">','</p>')?>


	<table  class="data">
		<tbody>

			<tr>
				<td width="30%">
					<label for="name">Coupon Name</label>
					<br/><em>eg. 'Summer Offer $10 off'</em></td>
				<td>
					<input class="js-code" data-code="code" type="text" name="name" id="name" value="<?=charge_get_value($coupon, 'name')?>"/>
					<?=charge_get_value($errors, 'name', '<em class="error">* ','</em>')?>
				</td>
			</tr>

			<tr>
				<td>
					<label for="code">Coupon Code</label>
					<br/><em>eg. 'OFF10'</em></td>
				<td>
					<input type="text" name="code" data-raw="<?=charge_get_value($coupon, 'code')?>" id="code" value="<?=charge_get_value($coupon, 'code')?>"/>
					<?=charge_get_value($errors, 'code', '<em class="error">* ','</em>')?>
				</td>
			</tr>
		</tbody>
	</table>
	</div>

	<div class="tg">
		<h3>Coupon Type</h3>
		<table class="data">
			<tbody>
				<tr>
					<td width="30%">Coupon Type<br/>
					<em>Percentage off or fixed amount</em></td>

					<td>
						<select name="type" id="type" class="js-enabler js-hide js-select" rel="js-coupon-type">
								<option value="fixed" <?=charge_check_value($coupon, 'type', 'fixed', 'selected="selected"')?>>Fixed Amount</option>
								<option value="percentage"  <?=charge_check_value($coupon, 'type', 'percentage', 'selected="selected"')?>>Percentage Off</option>
						</select>
						<?=charge_get_value($errors, 'type', '<em class="error">* ','</em>')?>
					</td>
				</tr>

				<tr class="js-coupon-type <?=charge_check_value($coupon, 'type', 'fixed', '', 'hidden')?>" rel="fixed">
					<td width="30%">Amount Off<br/>
					<em>Amount for the discount. ie. 3.50</em></td>
					<td>
						<label for="amount_off">
							<input style="width:30%" name="amount_off" id="amount_off" type="text" value="<?=charge_get_value($coupon, 'amount_off')?>"/>
						</label>
						<?=charge_get_value($errors, 'amount_off', '<em class="error">* ','</em>')?>
					</td>
				</tr>
				<tr class="js-coupon-type <?=charge_check_value($coupon, 'type', 'fixed', '', 'hidden')?>" rel="fixed">
					<td width="30%">Currency<br/>
					<em>Currency for the discount off</em></td>
					<td>
						<label for="currency">
							<select name="currency" id="currency" style="width:30%">
								<?php foreach($supported_currencies as $key => $currency) : ?>
									<option value="<?=$key?>" <?=charge_check_value($coupon, 'currency', $key, 'selected="selected"')?>><?=$currency['cleaned_name']?></option>
								<?php endforeach ?>

							</select>
						</label>
						<?=charge_get_value($errors, 'currency', '<em class="error">* ','</em>')?>
					</td>
				</tr>

				<tr class="js-coupon-type <?=charge_check_value_not($coupon, 'type', 'percentage', 'hidden')?>" rel="percentage">
					<td width="30%">Percentage Off<br/>
					<em>Integer value : 1 - 100</em></td>
					<td>
						<label for="percentage_off">
							<input style="width:30%" name="percentage_off" id="percentage_off" type="text" value="<?=charge_get_value($coupon, 'percentage_off')?>"/>
						</label>
						<?=charge_get_value($errors, 'percentage_off', '<em class="error">* ','</em>')?>
					</td>
				</tr>
			</tbody>
		</table>
	</div>


	<div class="tg">
		<h3>Payment Type</h3>
		<table class="data">
			<tbody>
			<tr>
				<td width="30%">Payment Type<br/>
				<em>What types of payment can this coupon is valid for</em></td>
				<td>
					<select name="payment_type" id="payment_type" class="js-enabler js-hide js-select" rel="js-payment-type">
							<option value="one-off" <?=charge_check_value($coupon, 'payment_type', 'one-off', 'selected="selected"')?>>One-off</option>
							<option value="recurring"  <?=charge_check_value($coupon, 'payment_type', 'recurring', 'selected="selected"')?>>Recurring</option>
					</select>
					<?=charge_get_value($errors, 'payment_type', '<em class="error">* ','</em>')?>
				</td>
			</tr>


			<tr class="js-payment-type <?=charge_check_value($coupon, 'payment_type', 'recurring', '', 'hidden')?>" rel="recurring">
				<td width="30%">Max Usages<br/>
				<em>How many times can this be used? Leave blank or zero for no limit</em></td>
				<td>
					<label for="max_redemptions">
						<input style="width:30%" name="max_redemptions" id="max_redemptions" type="text" value="<?=charge_get_value($coupon, 'max_redemptions')?>"/>
					</label>
					<?=charge_get_value($errors, 'max_redemptions', '<em class="error">* ','</em>')?>
				</td>
			</tr>

			<tr class="js-payment-type <?=charge_check_value($coupon, 'payment_type', 'recurring', '', 'hidden')?>" rel="recurring">
				<td width="30%">Duration<br/>
				<em>How long does the applied discount stay in effect for?</em></td>
				<td>
					<select name="duration" id="duration" class="js-enabler js-hide js-select" rel="js-duration">
						<option value="once" <?=charge_check_value($coupon, 'duration', 'once', 'selected="selected"')?>>Once</option>
						<option value="forever"  <?=charge_check_value($coupon, 'duration', 'forever', 'selected="selected"')?>>Forever</option>
						<option value="repeating"  <?=charge_check_value($coupon, 'duration', 'repeating', 'selected="selected"')?>>Repeating</option>
					</select>
					<?=charge_get_value($errors, 'duration', '<em class="error">* ','</em>')?>
				</td>
			</tr>
			<tr class="js-duration <?=charge_check_value($coupon, 'duration', 'repeating', '', 'hidden')?>" rel="repeating">
				<td width="30%">Duration in Months<br/>
				<em>How many months does the coupon repeat for?</em></td>
				<td>
					<label for="duration_in_months">
						<input style="width:30%" name="duration_in_months" id="duration_in_months" type="text" value="<?=charge_get_value($coupon, 'duration_in_months')?>"/>
					</label>
					<?=charge_get_value($errors, 'duration_in_months', '<em class="error">* ','</em>')?>

				</td>
			</tr>
		</tbody>
	</table>
	</div>


	<input type="submit" class="submit" name="submit" value="Save" />
	</form>
	</div>
</div>

