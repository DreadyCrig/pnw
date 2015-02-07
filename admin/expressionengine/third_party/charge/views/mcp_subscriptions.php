<div id="charge_container" class="mor">



	<div class="tg">
		<h2>Subscriptions</h2>
		<div class="alert">
			<p>Subscriptions allow you to control a member's group based on their payments. Both one-off and recurring payments. Add a member to a subscription by triggering it in an <a href="<?=$actions_uri?>">Action</a>.</p>
		</div>

		<table class="data">
			<tbody>
				<?php foreach($subscriptions as $sub) : ?>
					<tr>
						<td><?=$sub['name']?></td>
						<td><?=$sub['auto_description']?></td>
						<td><a href="<?=$view_sub_members_uri.$sub['subscription_id']?>">View Members</a></td>
						<td><a href="<?=$view_single_subscription_uri.$sub['subscription_id']?>">View / Edit</a></td>
						<td><a class="js-delete" data-type="Subscription" href="<?=$delete_sub_uri.$sub['subscription_id']?>">Delete</a></td>

					</tr>
				<?php endforeach ?>

			</tbody>
		</table>
		<div class="alert">
			<p><a href="<?=$add_subscription_uri?>" class="btn">Add a Subscription</a></p>
		</div>
	</div>
</div>