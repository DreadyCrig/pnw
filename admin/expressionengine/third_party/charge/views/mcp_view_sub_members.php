<div id="charge_container" class="mor">



	<div class="tg">
		<h2>Subscription Members</h2>
		<div class="alert">
			<p>Members are only added to subscriptions after a successful transaction, based on the <em>on_success</em> action. <br/><a href="http://squarebit.co.uk/software/expressionengine/charge/docs/subscriptions">View the Documentation on how Subscriptions work</a>.</p>
			<p>When their subscription ends, their status will change to <em>inactive</em> and their user will be moved to the <em>subscription failure group</em>.</p>

		</div>

		<table class="data">
			<thead>
				<tr style="background:transparent">
					<th>Member</th>
					<th>Status</th>
					<th>Stripe Customer Id</th>
					<th>Subscription Start Date</th>
					<th>Last Stripe Contact</th>
				</tr>
			</thead>
			<tbody>

				<?php if(count($members) == '0') : ?>
					<tr>
						<td colspan="5">
							No members currently in this subscription
						</td>
					</tr>
				<?php endif; ?>

				<?php foreach($members as $member) : ?>
					<tr>
						<td><?=$member['member_id']?></td>
						<td><?=$member['status']?></td>
						<td><?=$member['customer_id']?></td>
						<td><?=date('H:i', $member['timestamp']). ' on '.date('l jS F', $member['timestamp'])?></td>
						<td>
							<?php if($member['last_contact'] == '0') : ?>
								Never
							<?php else : ?>
								<?=date('H:i', $member['last_contact']). ' on '.date('l jS F', $member['last_contact'])?>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach ?>

			</tbody>
		</table>
	</div>
</div>