<div id="charge_container" class="mor">

	<?php if(!$encrypt_key_set) : ?>
		<p class="alert error"><strong>No Encryption Key Set!</strong> You need to set a value for <code>encryption_key</code> in your site's config</p>
	<?php endif; ?>

	<?php if($have_overview) : ?>
	<div class="tg">
		<h2>Overview</h2>

		<?php if($have_payment_overview) : ?>
		<div class="alert success" style="font-size:1.4em">
			<strong>17</strong> payments worth <strong>$40.00</strong> in the last 7 days. <br/>
			<small><strong>49</strong> payments worth <strong>$422.00</strong> overall</small>
		</div>
		<?php endif; ?>

		<?php if($have_recurring_overview) : ?>
		<div class="alert info" style="font-size:1.4em">
			<strong>12</strong> new subscriptions worth <strong>$32.00</strong> started in the last 7 days<br/>
			<small><strong>42</strong> subscriptions worth <strong>$500.00</strong> monthly overall</small>
		</div>
		<?php endif; ?>
		<div class="alert">
			<a href="#" class="btn">Full Details avaialable at Stripe.com &rarr;</a>
		</div>
	</div>

	<?php endif; ?>

	<div class="tg">
		<h2>Charge Transactions <a class="btn pull_right export_button" href="<?=$export_uri?>">Export</a></span></h2>

		<?php if(count($charges) == 0) : ?>
			<div class="alert">
				No charges recorded just yet. Need a hand getting started? <a href="http://squarebit.co.uk/software/expressionengine/charge">Full Documentation is here</a> or <a href="mailto:support@squarebit.co.uk">Email support and we'll help you out</a>.
			</div>
		<?php endif; ?>

		<?php foreach($charges as $charge) : ?>

		<div class="tg">
			<table>
				<tbody>
				<?php if($charge['mode'] == 'test') : ?>
				<tr class="test_mode">
					<td colspan="5"><strong>Test Mode Transaction</strong>, no real payments made</td>
				</tr>
				<?php endif; ?>
				<tr>
					<td colspan="5"><?=$charge['time_wordy']?><?php if($charge['state'] == 'cancelled') : ?>, <em class="cancelled_payment">cancelled on <?=$charge['ended_on_wordy']?></em><?php endif?></td>
				</tr>
				<tr>
					<td class="plan_value" width="10%">
						<span class="plan_amount"><?=$charge['amount_currency_formatted']?></span><br/>
							<?php if($charge['type'] == 'charge') : echo('Payment'); else : ?>
								<?=$charge['plan_wordy']?>
						<?php endif; ?>
					</td>
					<td class="card_details" width="10%">
							<div class="stripe_card type_<?=$charge['card_type']?>">
								<?=$charge['card_number_dotted']?>
							</div>

							<div class="stripe_card_owner">
								<?php if($charge['card_name'] != '') : ?><strong><?=$charge['card_name']?></strong><br/><?php endif; ?>
								<?php if($charge['card_address_line1'] != '') : ?><?=$charge['card_address_line1']?><br/><?php endif; ?>
								<?php if($charge['card_address_line2'] != '') : ?><?=$charge['card_address_line2']?><br/><?php endif; ?>
								<?php if($charge['card_address_city'] != '') : ?><?=$charge['card_address_city']?><br/><?php endif; ?>
								<?php if($charge['card_address_state'] != '') : ?><?=$charge['card_address_state']?><br/><?php endif; ?>
								<?php if($charge['card_address_zip'] != '') : ?><?=$charge['card_address_zip']?><br/><?php endif; ?>
								<?php if($charge['card_address_country'] != '') : ?><?=$charge['card_address_country']?><br/><?php endif; ?>
							</div>
					</td>
					<td class="customer_details" width="30%">
						<strong>Name : </strong> <?=$charge['customer_name']?><br/>
						<strong>Email : </strong> <?=$charge['customer_email']?><br/>

						<?php if(count($charge['meta']) > 0) :?>
							<?php foreach($charge['meta'] as $meta_key => $meta_val) : ?>
								<strong><?=$meta_key?> :</strong> <?=$meta_val?><br/>
							<?php endforeach; ?>
						<?php endif; ?>
						<!--
						<?php if($charge['member_id'] != '0') : ?><br/>
							<a href="#">View Member &rarr;</a>
						<?php endif; ?>-->
					</td>
					<td class="transaction_details" width="30%">
						Id : <strong><?=$charge['id']?></strong><br/>
						Our Hash : <strong><?=$charge['hash']?></strong><br/>
						Source Url : <strong><?=$charge['source_url']?></strong><br/>

					</td>

                    <td class="transaction_details" width="20%">
                        Stripe Customer : <a href="https://dashboard.stripe.com/<?php if($charge['mode'] == 'test') : ?>test/<?php endif; ?>customers/<?=$charge['customer_id']?>"><?=$charge['customer_id']?></a><br/>
                        <?php if($charge['payment_id'] != '') : ?>Stripe Payment : <a href="https://dashboard.stripe.com/<?php if($charge['mode'] == 'test') : ?>test/<?php endif; ?>payment/<?=$charge['payment_id']?>"><?=$charge['payment_id']?></a><br/><?php endif; ?>

                    </td>
				</tr>
				</tbody>
			</table>
			</div>

		<?php endforeach; ?>

		<?php if($has_pagination) : ?>
		<ul class="pagination">

			<li>Page <?=$current_page?> of <?=$total_pages?></li>
			<li>
				<?php if( $prev_link ) : ?>
					<a href="<?=$prev_link?>" title="Previous page">Previous</a>
				<?php else : ?>
					<b>Previous</b>
				<?php endif; ?>
			</li>
			<li>
				<?php if( $next_link ) : ?>
					<a href="<?=$next_link?>" title="Next page">Next</a>
				<?php else : ?>
					<b>Next</b>
				<?php endif; ?>
			</li>
		</ul>
		<?php endif; ?>

	</div>



</div>