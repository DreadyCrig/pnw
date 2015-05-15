<div class="cstats">
	<strong>Campaign Label</strong>: <?=$campaign->label?>

	<div class="dates">
		<div class="create left">
			<label>Date Created</label>
			<?php if ($date_created):?>
				<?=$this->campaigns_helper->formatDate('%d-%M-%Y - %g:%i', $date_created);?> (API)
			<?php else:?>
				<?=$this->campaigns_helper->formatDate('%d-%M-%Y - %g:%i', $campaign->created_at);?> (DB)
			<?php endif;?>
		</div>
		<div class="scheduled right">
			<label>Date Scheduled</label>
			<?php if ($date_scheduled):?>
				<?=$this->campaigns_helper->formatDate('%d-%M-%Y - %g:%i', $date_scheduled);?> (API)
			<?php else:?>
				<?=$this->campaigns_helper->formatDate('%d-%M-%Y - %g:%i', $campaign->scheduled_on);?> (DB)
			<?php endif;?>
		</div>
		<br clear="all">
	</div>

	<ul class="stats">
		<?php foreach ($stats as $key => $val):?>
		<li>
			<label><?=lang('c:'.$key)?></label>
			<strong><?=$val?></strong>
		</li>
		<?php endforeach;?>
	</ul>
</div>