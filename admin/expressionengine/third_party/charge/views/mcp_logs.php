<div id="charge_container" class="mor">

	<?php if(!$encrypt_key_set) : ?>
		<p class="alert error"><strong>No Encryption Key Set!</strong> You need to set a value for <code>encryption_key</code> in your site's config</p>
	<?php endif; ?>

	<div class="tg">
		<h2>Charge Logs</h2>


		<?php if(count($logs) == 0) : ?>
			<div class="alert">
				Nothing in the logs just yet. Need a hand getting started? <a href="http://squarebit.co.uk/addons/charge">Full Documentation is here</a> or <a href="mailto:support@squarebit.co.uk">Email support and we'll help you out</a>.
			</div>
		<?php else: ?>


		<table class="data">
			<thead>
				<tr style="background-color:transparent">
					<th width="5%">ID</th>
					<th width="5%">Site ID</th>
					<th>Time</th>
					<th>Event</th>
					<th width="5%">Member ID</th>
					<th>Info</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach($logs as $row) : ?>
					<tr>
						<td><?=$row['log_id']?></td>
						<td><?=$row['site_id']?></td>
						<td><?=$row['time_wordy']?></td>
						<td><?=$row['log_icon']?> <?=ee()->lang->line($row['type'])?></td>
						<td>
							<?php if($row['member_id'] == '0') : ?>
								Guest
							<?php else : ?>
								<?php if(isset($members[$row['member_id']])) : ?>
									<a href="<?=$members_cp_uri.$row['member_id']?>"><?=$members[$row['member_id']]['screen_name']?></a>
								<?php else : ?>
									<a href="<?=$members_cp_uri.$row['member_id']?>"><?=$row['member_id']?></a>
								<?php endif; ?>
							<?php endif; ?>
						</td>

						<td class="extended_data">
							<pre><?=print_R($row['extended'],1)?></pre>
						</td>
					</tr>
				<?php endforeach; ?>

			</tbody>
		</table>
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


		<?php endif; ?>

	</div>

</div>