<div class="pagehead">
	<h2><?=lang('c:lists')?></h2>
</div>

<section class="pagebody">


	<?php foreach ($services as $key => $row): ?>
	<div class="list">
	<table cellpadding="0" cellspacing="0">
	<thead>
		<tr>
			<th>
				<h3><?=$row->label?></h3>
				<small><?=$row->name?></small>
			</th>
			<th><?=lang('c:using')?>: <strong><?=lang('c:'.$row->provider)?></strong></th>
			<th style="text-align:right">
				<?php if ($csettings['show_service_logo'] == 'yes'):?><img src="<?=$theme_url?>img/service_<?=$row->provider?>.png"><?php endif;?>
			</th>
		</tr>
	</thead>
	<tbody>
		<?php if (empty($row->lists) === true):?>
		<tr><td colspan="9" class="empty">
			<h3><?=lang('c:lists:none')?></h3>
			<p><?=lang('c:lists:none:exp')?></p>
		</td></tr>
		<?php endif;?>

		<?php foreach ($row->lists as $list):?>
		<tr>
			<td class="name"><label><?=$list['label']?></label></td>
			<td class="stats">
				<ul>
					<?php foreach ($list['stats'] as $key => $val):?>
					<li><?=lang('c:'.$key)?>: <strong><?=$val?></strong></li>
					<?php endforeach;?>
				</ul>
			</td>
			<td></td>
		</tr>
		<?php endforeach;?>
	</tbody>

	</table>
	</div>
	<?php endforeach ?>


	<?php if (empty($services)):?>
	<div class="empty">
		<h3><?=lang('c:services:none')?></h3>
		<p><?=lang('c:services:exp')?></p>
	</div>
	<?php endif;?>

</section>