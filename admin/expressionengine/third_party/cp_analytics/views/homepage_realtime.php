<?php $this->EE =& get_instance(); ?>
<div id="cp_analytics_realtime_inner" class="group">
	<div class="cp-analytics-rt-col cp-analytics-rt-users">
		<div class="cp-analytics-active-users">
			<strong><?=$active_users;?></strong>
			<?=($active_users == 1) ? $this->EE->lang->line('cp_analytics_active_user') : $this->EE->lang->line('cp_analytics_active_users');?>
		</div>
		<?php if(!empty($devices)) : ?>
		<div class="cp-analytics-rt-devices cp-analytics-group">
		<?php foreach($devices as $row) : ?>
			<span class="<?=strtolower($row['device']);?>" style="width: <?=$row['percentage_precise'];?>;" title="<?=$row['users'];?> <?=strtolower($row['device']);?> <?=($row['users'] == 1) ? strtolower($this->EE->lang->line('cp_analytics_user')) : strtolower($this->EE->lang->line('cp_analytics_users')); ?>"><?= ($row['percentage_numeric'] >= 10) ? $row['percentage'] : NBS; ?></span>
		<?php endforeach; ?>	
		</div>
		<ul class="cp-analytics-rt-device-legend">
		<?php foreach($devices as $row) : ?>	
			<li><span class="<?=strtolower($row['device']);?>"></span> <?=$row['device'];?></li>
		<?php endforeach; ?>
		</ul>
		<?php endif; ?>
	</div>
	<div class="cp-analytics-rt-col cp-analytics-rt-content">
		<table class="cp-analytics-rt-table" cellspacing="0">
			<tr>
				<th class="cp-analytics-rt-label-col"><?=$this->EE->lang->line('cp_analytics_rt_pages'); ?></th>
				<th class="cp-analytics-rt-visitors-col"><?=$this->EE->lang->line('cp_analytics_users'); ?></th>
			</tr>
		<?php if(empty($content)) : ?>
			<tr>
				<td colspan="2" class="cp-analytics-no-data">No data.</td>
			</tr>
		<?php else : $class="odd"; foreach($content as $row) : ?>
			<tr class="<?=$class;?>">
				<td class="cp-analytics-rt-label-col" title="<?=$row['page_title'];?>: <?=$row['page_path'];?>"><?=$row['page_title'];?></td>
				<td class="cp-analytics-rt-visitors-col"><strong><?=$row['percentage'];?></strong> (<?=$row['users'];?>)</td>
			</tr>
			<?php $class = ($class == 'odd') ? 'even' : 'odd'; ?>
		<?php endforeach; endif; ?>	
		</table>
	</div>
	<div class="cp-analytics-rt-col cp-analytics-rt-referrers">
		<table class="cp-analytics-rt-table" cellspacing="0">
			<tr>
				<th class="cp-analytics-rt-label-col"><?=$this->EE->lang->line('cp_analytics_rt_referrers'); ?></th>
				<th class="cp-analytics-rt-visitors-col"><?=$this->EE->lang->line('cp_analytics_users'); ?></th>
			</tr>
		<?php if(empty($sources)) : ?>
			<tr>
				<td colspan="2" class="cp-analytics-no-data">No data.</td>
			</tr>
		<?php else : $class="odd"; foreach($sources as $row) : ?>
			<tr class="<?=$class;?>">
				<td class="cp-analytics-rt-label-col"><?=$row['source'];?></td>
				<td class="cp-analytics-rt-visitors-col"><strong><?=$row['percentage'];?></strong> (<?=$row['users'];?>)</td>
			</tr>
			<?php $class = ($class == 'odd') ? 'even' : 'odd'; ?>
		<?php endforeach; endif; ?>	
		</table>
	</div>
	<div class="cp-analytics-rt-col cp-analytics-rt-countries">
		<table class="cp-analytics-rt-table" cellspacing="0">
			<tr>
				<th class="cp-analytics-rt-label-col"><?=$this->EE->lang->line('cp_analytics_rt_countries'); ?></th>
				<th class="cp-analytics-rt-visitors-col"><?=$this->EE->lang->line('cp_analytics_users'); ?></th>
			</tr>
		<?php if(empty($countries)) : ?>
			<tr>
				<td colspan="2" class="cp-analytics-no-data">No data.</td>
			</tr>
		<?php else : $class="odd"; foreach($countries as $row) : ?>
			<tr class="<?=$class;?>">
				<td class="cp-analytics-rt-label-col"><?=$row['country'];?></td>
				<td class="cp-analytics-rt-visitors-col"><strong><?=$row['percentage'];?></strong> (<?=$row['users'];?>)</td>
			</tr>
			<?php $class = ($class == 'odd') ? 'even' : 'odd'; ?>
		<?php endforeach; endif; ?>
		</table>
	</div>
</div>