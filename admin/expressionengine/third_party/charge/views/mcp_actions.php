<div id="charge_container" class="mor">

	<div class="tg">
		<h2>Actions</h2>
		<div class="alert">
			<p>Actions are triggered after successful purchases. They can update entries, send emails, and add members to subscriptions.</p>
		</div>

		<table class="data">
			<tbody>
				<?php foreach($actions as $act) : ?>
					<tr>
						<td><?=$act['name']?></td>
						<td><?=$act['shortname']?></td>
						<td><a href="<?=$view_single_action_uri.$act['action_id']?>">View / Edit</a></td>
						<td><a class="js-delete" data-type="action" href="<?=$delete_action_uri.$act['action_id']?>">Delete</a></td>
					</tr>
				<?php endforeach ?>

			</tbody>
		</table>
		<div class="alert">
			<p><a href="<?=$add_action_uri?>" class="btn">Add an Action</a></p>
		</div>
	</div>

</div>

